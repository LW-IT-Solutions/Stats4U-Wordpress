<?php
/**
 * Plugin Name:       Stats4U
 * Plugin URI:        https://www.stats4u.net/
 * Description:       Puts a Stats4U visitor counter on your site. Paste the code from stats4u.net, choose where it appears and, if you use a consent banner, let it decide when the counter loads.
 * Version:           1.4.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            LW IT Solutions Company
 * Author URI:        https://www.stats4u.net/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       stats4u
 * Domain Path:       /languages
 *
 * WAS DIESES PLUGIN TUT UND WAS NICHT
 *
 * Es setzt ein <img> auf die Seite. Kein Cookie, kein eigener Endpunkt, keine
 * eigene Tabelle, kein Aufruf nach Hause vom Server aus. Das ausgelieferte
 * Bild IST die Zaehlung - deshalb zaehlt es auch bei Lesern, die Skripte
 * abschalten.
 *
 * Ein Skript gibt es genau dann, wenn der Betreiber ein Consent-Werkzeug
 * waehlt, das Bilder nicht selbst freischalten kann: dann steht das Bild ohne
 * Adresse im HTML, und ein Einzeiler setzt sie, sobald das Werkzeug ihn nach
 * der Einwilligung ausfuehrt. Werkzeuge, die ein <img> selbst freischalten
 * (consented.eu, Cookiebot ...), bekommen gar kein Skript. Ohne
 * Consent-Werkzeug bleibt es beim reinen Bild.
 *
 * Bewusst NICHT die Skriptfassung von stats4u.net: die kann den Zaehler von
 * selbst nachladen, kostet dafuer aber drei Abrufe statt einem. Was das in
 * Byte ausmacht, steht gemessen auf https://www.stats4u.net/weight.
 *
 * WARUM DIE ZEICHENKETTEN ENGLISCH SIND
 *
 * Bis 1.0.0 standen sie auf Deutsch, und WordPress zeigte sie deshalb auf
 * JEDEM Blog auf Deutsch. WordPress uebersetzt nur weg VON der Quellsprache,
 * und die ist per Uebereinkunft Englisch. Die 18 Uebersetzungen liegen in
 * languages/.
 */

if (!defined('ABSPATH')) { exit; }

const STATS4U_OPTION  = 'stats4u_einstellungen';
const STATS4U_VERSION = '1.4.0';

/** Die 19 Sprachen von stats4u.net - dieselben wie die Oberflaeche dort. */
function stats4u_sprachen() {
    return array('pl', 'en', 'de', 'es', 'fr', 'it', 'pt', 'ru', 'uk', 'zh', 'ja',
                 'nl', 'ko', 'bg', 'ro', 'id', 'ar', 'tr', 'cs');
}

/**
 * Die Vorgaben.
 *
 * platz 'unter' ist neu ab 1.4.0 die Vorgabe: wer einen Zaehler einfuegt,
 * will ihn sehen. Bestehende Installationen behalten ihre Wahl (siehe
 * stats4u_einstellungen()).
 */
function stats4u_vorgaben() {
    return array(
        'id'            => '',
        'style'         => '950',
        // Was der Assistent auf stats4u.net sonst noch in die Bildadresse
        // schreibt (Form, Palette, Farben ...) - aus dem eingefuegten Code.
        'params'        => array('form' => 'pill', 'pal' => 'mint'),
        'gr'            => 100,
        'sprache'       => '',
        'dark'          => 'auto',
        'metrik'        => '',
        // Den Zaehler mit seiner oeffentlichen Statistikseite verlinken. Ab
        // Werk AUS (1.2.0): wordpress.org, Richtlinie 10.
        'link'          => 0,
        'platz'         => 'unter',
        'ausrichtung'   => 'center',
        'wo'            => 'alle',
        'consent'       => '',
        'consent_wert'  => '',
        'consent_eigen' => '',
    );
}

function stats4u_einstellungen() {
    $e = get_option(STATS4U_OPTION);
    $e = is_array($e) ? $e : array();

    // Bis 1.3.1 standen form und pal einzeln da und der Fuss war ein Haken.
    if (!isset($e['params'])) {
        $e['params'] = array();
        if (!isset($e['style']) || $e['style'] === '950') {
            foreach (array('form', 'pal') as $k) {
                if (!empty($e[$k])) { $e['params'][$k] = (string) $e[$k]; }
            }
        }
    }
    if (!isset($e['platz']) && isset($e['fuss'])) {
        $e['platz'] = empty($e['fuss']) ? 'aus' : 'unter';
    }
    unset($e['form'], $e['pal'], $e['fuss'], $e['code']);
    if (!is_array($e['params'])) { $e['params'] = array(); }

    return wp_parse_args($e, stats4u_vorgaben());
}

// --- Den Code von stats4u.net lesen -------------------------------------------
//
// Auf stats4u.net bekommt man HTML, BBCode, Markdown, eine Bildadresse oder den
// Skript-Einbau. Bis 1.3.x wollte das Plugin trotzdem nur die Nummer - und dazu
// Form und Palette von Hand abgetippt. Jetzt wird eingefuegt, was man kopiert
// hat, und alles, was der Assistent eingestellt hat, kommt mit.

/** Welche Parameter aus dem Code uebernommen werden (js/start.js, wizParams). */
function stats4u_param_namen() {
    return array(
        // 950 als SVG
        'form', 'pal', 'c1', 'c2', 'spark', 'el', 't',
        // 950 als klassisches Bild
        'format', 'size', 'bgmode', 'bgcolor1', 'bgcolor2', 'bgvorlage', 'custom_title',
        'rahmenaus', 'rahmencol', 'textcolor1', 'textcolor2', 'textcolor3', 'textfont', 'nocount',
        // Flaggenzaehler
        'layout', 'ausrichtung', 'bgcolor', 'border', 'bordercolor', 'countrycode', 'flag',
        'groesse', 'header', 'txtcolor',
        // Kartenzaehler als Bild
        'kopf', 'orte', 'skala', 'tage', 'zahlen', 'bg',
    );
}

function stats4u_params_saeubern($p) {
    $aus = array();
    foreach ((array) $p as $k => $v) {
        $k = strtolower((string) $k);
        if (is_array($v) || !in_array($k, stats4u_param_namen(), true)) { continue; }
        $v = (string) $v;
        if ($k === 't' || $k === 'custom_title') {
            $v = mb_substr(sanitize_text_field($v), 0, 60);
        } else {
            $v = substr(preg_replace('/[^A-Za-z0-9_,.#-]/', '', $v), 0, 40);
        }
        if ($v !== '') { $aus[$k] = $v; }
    }
    return $aus;
}

function stats4u_groesse($g) {
    $g = (int) $g;
    return ($g >= 25 && $g <= 400) ? $g : 100;
}

/**
 * Liest einen eingefuegten Code. Rueckgabe: id, style, params, fehler - und
 * dark/gr/metrik/sprache nur dann, wenn der Code sie festlegt.
 *
 * fehler: '' | 'skript' (Globus/Karte als Skript) | 'alias' (/live/<name>
 * ohne Nummer) | 'unbekannt'.
 */
function stats4u_code_lesen($roh) {
    $t = trim(html_entity_decode((string) $roh, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $aus = array('id' => '', 'style' => '', 'params' => array(), 'fehler' => '');
    $query = null;

    if (preg_match('~(?:globe|karte)\.js~i', $t)) {
        $aus['fehler'] = 'skript';
        return $aus;
    }
    if (preg_match('~/c/([0-9]{1,12})-([0-9a-z_]{1,24})\.[a-z]{2,4}(\?[^\s"\'<>\[\]()]*)?~i', $t, $m)) {
        // Bildadresse - in <img>, BBCode, Markdown oder allein.
        $aus['id']    = $m[1];
        $aus['style'] = strtolower($m[2]);
        $query        = isset($m[3]) ? substr($m[3], 1) : '';
    } elseif (preg_match('~[?&]s4uid=([0-9]{1,12})(?![0-9])~', $t, $m)) {
        // Die alte Langform ?action=pic&s4uid=...
        $aus['id'] = $m[1];
        if (preg_match('~[?&]s4ustyleid=([0-9a-z_]{1,24})~i', $t, $s)) { $aus['style'] = strtolower($s[1]); }
        $query = preg_match('~\?([^\s"\'<>\[\]()]*)~', $t, $q) ? $q[1] : '';
    } elseif (preg_match('~s4u\.js~i', $t) && preg_match('~data-id\s*=\s*["\']?([0-9]{1,12})~i', $t, $m)) {
        // Der Skript-Einbau: <script src=".../s4u.js" data-id data-style data-params ...>
        $aus['id'] = $m[1];
        if (preg_match('~data-style\s*=\s*["\']?([0-9a-z_]{1,24})~i', $t, $s)) { $aus['style'] = strtolower($s[1]); }
        $query = '';
        if (preg_match('~data-params\s*=\s*(?:"([^"]*)"|\'([^\']*)\')~i', $t, $q)) {
            $query = $q[1] !== '' ? $q[1] : ($q[2] ?? '');
        }
        if (preg_match('~data-metric\s*=\s*["\']?unique~i', $t)) { $query .= ($query === '' ? '' : '&') . 'm=uniq'; }
    } elseif (preg_match('~/live/([0-9]{1,12})(?![0-9a-z_-])~i', $t, $m)) {
        // Nur die Statistikseite - Nummer ja, Entwurf nein.
        $aus['id'] = $m[1];
    } elseif (preg_match('~^#?\s*([0-9]{1,12})$~', $t, $m)) {
        $aus['id'] = $m[1];
    } else {
        $aus['fehler'] = preg_match('~/live/[a-z]~i', $t) ? 'alias' : 'unbekannt';
        return $aus;
    }

    if ($query !== null && $query !== '') {
        parse_str($query, $roh_p);
        $aus['params'] = stats4u_params_saeubern($roh_p);
        if (isset($roh_p['gr']) && !is_array($roh_p['gr'])) { $aus['gr'] = stats4u_groesse($roh_p['gr']); }
        if (($roh_p['m'] ?? '') === 'uniq') { $aus['metrik'] = 'uniq'; }
        if (isset($roh_p['cl']) && in_array($roh_p['cl'], stats4u_sprachen(), true)) { $aus['sprache'] = $roh_p['cl']; }
        if (isset($roh_p['dark']) && in_array($roh_p['dark'], array('auto', '1', '0'), true)) { $aus['dark'] = $roh_p['dark']; }
    }
    // Der Assistent laesst dark weg, wenn "hell" gewaehlt ist.
    if (!isset($aus['dark']) && $aus['style'] === '950' && $query !== null) { $aus['dark'] = '0'; }
    if (!isset($aus['metrik']) && $query !== null) { $aus['metrik'] = ''; }
    return $aus;
}

function stats4u_fehlertext($fehler) {
    if ($fehler === 'skript') {
        return __('This is a globe or map widget. The plugin shows image counters - pick an image design on stats4u.net.', 'stats4u');
    }
    if ($fehler === 'alias') {
        return __('This is a link to a statistics page with a custom name. Paste the counter code instead - or just the counter number.', 'stats4u');
    }
    return __('No Stats4U counter found in what you pasted. Paste the code from stats4u.net - or just the counter number.', 'stats4u');
}

// --- Consent -----------------------------------------------------------------
//
// Vier Wege, je nachdem, was das Werkzeug kann - immer der sicherste, den es
// dokumentiert:
//
// 'img':    Das Werkzeug schaltet ein <img> selbst frei (eigenes Attribut fuer
//           die Adresse). Dann gibt es kein einziges Skript vom Plugin.
// 'skript': Das Werkzeug schaltet nur Skripte frei (type="text/plain" o. ae.
//           plus sein Attribut). Das Bild steht ohne Adresse da, ein
//           Einzeiler setzt sie - und den fuehrt das Werkzeug erst nach der
//           Einwilligung aus.
// 'extern': Wie 'skript', aber das Werkzeug sperrt nur Skripte mit eigener
//           Adresse (CookieYes, Osano): dann ist der Einzeiler freigabe.js.
// 'api':    Das Werkzeug sperrt nichts, sagt aber, ob eingewilligt ist. Ein
//           kleines Skript fragt nach und horcht auf die Aenderung - und laedt
//           NUR bei ausdruecklicher Zustimmung. Im Zweifel bleibt es aus.
//
// {wert} ist Kategorie, Zweck oder Dienst im Werkzeug, {src} die Adresse.
// Grundlage: Recherche in den Dokumentationen und im ausgelieferten Code der
// Werkzeuge vom 24.09.2026. Nicht aufgenommen: Beautiful Cookie Banner (kennt
// ab Werk keine Statistik-Kategorie), TrustArc (braucht das Integration
// Studio), InMobi/Sourcepoint/TCF (Stats4U ist kein Anbieter der Liste),
// Google Consent Mode (von aussen nicht lesbar).
//
// 'art' der Erklaerung fuer den Betreiber: kategorie | dienst | pflicht
// (pflicht = das Werkzeug vergibt die Kennung je Seite, ohne sie kein Laden).

function stats4u_cmps() {
    return array(
        // --- schalten ein <img> selbst frei --------------------------------
        'consented' => array('name' => 'consented.eu', 'weg' => 'img', 'wert' => 'stats4u', 'art' => 'dienst',
            'attr' => array('data-consented' => '{wert}', 'data-consented-src' => '{src}')),
        'cookiebot' => array('name' => 'Cookiebot', 'weg' => 'img', 'wert' => 'statistics', 'art' => 'kategorie',
            'attr' => array('data-cookieblock-src' => '{src}', 'data-cookieconsent' => '{wert}'),
            'plugins' => array('cookiebot/cookiebot.php')),
        'klaro' => array('name' => 'Klaro!', 'weg' => 'img', 'wert' => 'stats4u', 'art' => 'dienst',
            'attr' => array('data-name' => '{wert}', 'data-src' => '{src}')),
        'termly' => array('name' => 'Termly', 'weg' => 'img', 'wert' => 'analytics', 'art' => 'kategorie',
            'attr' => array('data-src' => '{src}', 'data-categories' => '{wert}'),
            'plugins' => array('uk-cookie-consent/uk-cookie-consent.php')),
        'iubenda' => array('name' => 'iubenda', 'weg' => 'img', 'wert' => '4', 'art' => 'kategorie',
            'attr' => array('src' => '', 'class' => '_iub_cs_activate', 'data-iub-purposes' => '{wert}', 'data-suppressedsrc' => '{src}'),
            'plugins' => array('iubenda-cookie-law-solution/iubenda_cookie_solution.php')),
        'cookiescript' => array('name' => 'Cookie-Script', 'weg' => 'img', 'wert' => 'performance', 'art' => 'kategorie',
            'attr' => array('data-src' => '{src}', 'data-cookiescript' => 'accepted', 'data-cookiecategory' => '{wert}'),
            'plugins' => array('cookie-script-com/index.php')),
        'cookieinformation' => array('name' => 'Cookie Information', 'weg' => 'img', 'wert' => 'cookie_cat_statistic', 'art' => 'kategorie',
            'attr' => array('src' => '', 'data-consent-src' => '{src}', 'data-category-consent' => '{wert}'),
            'plugins' => array('cookie-information-consent-solution/plugin.php')),
        'civic' => array('name' => 'Civic Cookie Control (9.5+)', 'weg' => 'img', 'wert' => 'analytics', 'art' => 'dienst',
            'attr' => array('data-src' => '{src}', 'data-cc-category' => '{wert}'),
            'plugins' => array('civic-cookie-control-8/cookiecontrol-settings.php')),
        // --- schalten ein gesperrtes Inline-Skript frei ---------------------
        'complianz' => array('name' => 'Complianz', 'weg' => 'skript', 'wert' => 'statistics', 'art' => 'kategorie',
            'attr' => array('type' => 'text/plain', 'data-category' => '{wert}'),
            'plugins' => array('complianz-gdpr/complianz-gpdr.php', 'complianz-gdpr-premium/complianz-gpdr-premium.php')),
        'usercentrics' => array('name' => 'Usercentrics', 'weg' => 'skript', 'wert' => 'Stats4U', 'art' => 'dienst',
            'attr' => array('type' => 'text/plain', 'data-usercentrics' => '{wert}')),
        'onetrust' => array('name' => 'OneTrust / CookiePro', 'weg' => 'skript', 'wert' => 'C0002', 'art' => 'kategorie',
            'attr' => array('type' => 'text/plain', 'class' => 'optanon-category-{wert}'),
            'plugins' => array('cookiepro/class-cookiepro.php')),
        'cookieyes_alt' => array('name' => 'CookieYes (legacy mode)', 'weg' => 'skript', 'wert' => 'analytics', 'art' => 'kategorie',
            'attr' => array('type' => 'text/plain', 'data-cli-class' => 'cli-blocker-script', 'data-cli-script-type' => '{wert}',
                            'data-cli-block' => 'true', 'data-cli-element-position' => 'body')),
        'cookieadmin' => array('name' => 'CookieAdmin', 'weg' => 'skript', 'wert' => 'analytics', 'art' => 'kategorie',
            'attr' => array('type' => 'text/plain', 'data-cookieadmin-category' => '{wert}'),
            'plugins' => array('cookieadmin/cookieadmin.php')),
        'cookiefirst' => array('name' => 'CookieFirst', 'weg' => 'skript', 'wert' => 'performance', 'art' => 'kategorie',
            'attr' => array('type' => 'text/plain', 'data-cookiefirst-category' => '{wert}'),
            'plugins' => array('cookiefirst-gdpr-cookie-consent-banner/cookiefirst-plugin.php')),
        'cookiehub' => array('name' => 'CookieHub', 'weg' => 'skript', 'wert' => 'analytics', 'art' => 'kategorie',
            'attr' => array('type' => 'text/plain', 'data-consent' => '{wert}'),
            'plugins' => array('cookiehub/cookiehub.php')),
        'cookieconsent' => array('name' => 'CookieConsent 3 (orestbida)', 'weg' => 'skript', 'wert' => 'analytics', 'art' => 'kategorie',
            'attr' => array('type' => 'text/plain', 'data-category' => '{wert}')),
        'consentmanager' => array('name' => 'consentmanager', 'weg' => 'skript', 'wert' => '', 'art' => 'pflicht',
            'attr' => array('type' => 'text/plain', 'class' => 'cmplazyload', 'data-cmp-purpose' => '{wert}'),
            'plugins' => array('consent-manager/consentmanager.php')),
        'ccm19' => array('name' => 'CCM19', 'weg' => 'skript', 'wert' => '', 'art' => 'pflicht',
            'attr' => array('type' => 'text/x-ccm-loader', 'data-ccm-loader-group' => '{wert}'),
            'plugins' => array('ccm19-integration/ccm19-integration.php')),
        'didomi' => array('name' => 'Didomi', 'weg' => 'skript', 'wert' => '', 'art' => 'pflicht',
            'attr' => array('type' => 'didomi/javascript', 'data-purposes' => '{wert}')),
        // --- sperren nur Skripte mit eigener Adresse --------------------------
        'cookieyes' => array('name' => 'CookieYes', 'weg' => 'extern', 'wert' => 'analytics', 'art' => 'kategorie',
            'attr' => array('data-cookieyes' => 'cookieyes-{wert}'),
            'plugins' => array('cookie-law-info/cookie-law-info.php')),
        'osano' => array('name' => 'Osano', 'weg' => 'extern', 'wert' => 'ANALYTICS', 'art' => 'kategorie',
            'attr' => array('data-osano' => '{wert}')),
        // --- sagen nur, ob eingewilligt ist ------------------------------------
        'wpconsent' => array('name' => 'WP Consent API', 'weg' => 'api', 'wert' => 'statistics', 'art' => 'kategorie',
            'plugins' => array('wp-consent-api/wp-consent-api.php')),
        'borlabs' => array('name' => 'Borlabs Cookie 3', 'weg' => 'api', 'wert' => 'statistics', 'art' => 'kategorie',
            'plugins' => array('borlabs-cookie/borlabs-cookie.php')),
        'realcookiebanner' => array('name' => 'Real Cookie Banner', 'weg' => 'api', 'wert' => 'stats4u', 'art' => 'dienst',
            'plugins' => array('real-cookie-banner/index.php', 'real-cookie-banner-pro/index.php')),
        'moove' => array('name' => 'GDPR Cookie Compliance (Moove)', 'weg' => 'api', 'wert' => 'thirdparty', 'art' => 'kategorie',
            'plugins' => array('gdpr-cookie-compliance/moove-gdpr.php')),
        'humanity' => array('name' => 'Cookie Compliance (Hu-manity.co)', 'weg' => 'api', 'wert' => '3', 'art' => 'kategorie',
            'plugins' => array('cookie-notice/cookie-notice.php')),
        'axeptio' => array('name' => 'Axeptio', 'weg' => 'api', 'wert' => 'stats4u', 'art' => 'dienst',
            'plugins' => array('axeptio-sdk-integration/axeptio-wordpress-plugin.php')),
    );
}

/**
 * Das Nachfrage-Skript der Werkzeuge mit Weg 'api'. k = Kategorie/Dienst,
 * an() = die Adressen setzen. Jede Fassung laedt nur bei ausdruecklicher
 * Zustimmung; wo die Schnittstelle fehlt, passiert nichts (Zaehler bleibt aus).
 */
function stats4u_api_js($cmp_id) {
    switch ($cmp_id) {
        case 'wpconsent':
            // Ohne gesetzten Consent-Typ antwortet wp_has_consent() mit true -
            // auch fuer Besucher, die abgelehnt haben (CookieYes, Hu-manity im
            // Banner-Modus, CookieAdmin). Deshalb erst den Typ pruefen.
            return "function ja(){var t=window.wp_consent_type||window.wp_fallback_consent_type;return !!t&&typeof window.wp_has_consent==='function'&&window.wp_has_consent(k);}"
                 . "if(ja()){an();return;}"
                 . "document.addEventListener('wp_listen_for_consent_change',function(e){if(e.detail&&e.detail[k]==='allow'&&ja()){an();}});"
                 . "document.addEventListener('wp_consent_type_defined',function(){if(ja()){an();}});";
        case 'borlabs':
            // Borlabs ist ein ES-Modul und existiert beim Lesen der Seite noch
            // nicht; handle-unblock kommt beim Start, consent-saved bei Aenderung.
            return "function ja(){var b=window.BorlabsCookie;return !!(b&&b.Consents&&b.Consents.hasConsentForServiceGroup(k));}"
                 . "function pr(){if(ja()){an();}}"
                 . "window.addEventListener('borlabs-cookie-handle-unblock',pr);"
                 . "window.addEventListener('borlabs-cookie-consent-saved',function(){setTimeout(pr,0);});pr();";
        case 'realcookiebanner':
            // consent() loest SOFORT auf, wenn es den Dienst nicht gibt - deshalb
            // danach consentSync(k).cookie !== null pruefen.
            return "var a=window.consentApi;if(!a||typeof a.consent!=='function'){return;}"
                 . "a.consent(k).then(function(){var r=a.consentSync(k);if(r&&r.cookie!==null&&r.cookieOptIn===true){an();}})['catch'](function(){});";
        case 'moove':
            // Kein Ereignis bei Zustimmung: das Cookie lesen, zwei Minuten lang
            // alle zwei Sekunden. Werte sind '1'/'0'.
            // document.cookie kann werfen (abgeschottete Rahmen) - dann: keine Zustimmung.
            return "function ja(){var v='',d=null;try{v=(document.cookie.split('; ').filter(function(c){return c.indexOf('moove_gdpr_popup=')===0;})[0]||'').slice(17);"
                 . "d=v?JSON.parse(decodeURIComponent(v)):null;}catch(e){}return !!d&&String(d[k])==='1';}"
                 . "var n=0;(function pr(){if(ja()){an();return;}if(++n<60){setTimeout(pr,2000);}})();";
        case 'humanity':
            // hu-consent: {consent, categories}; set-consent.hu bei jeder
            // Entscheidung, mit derselben Form im detail.
            return "var r='',c=null;try{r=(document.cookie.split('; ').filter(function(c){return c.indexOf('hu-consent=')===0;})[0]||'').slice(11);}catch(e){}"
                 . "try{c=JSON.parse(r);}catch(e){try{c=JSON.parse(decodeURIComponent(r));}catch(e2){}}"
                 . "if(c&&c.consent===true&&c.categories&&c.categories[k]===true){an();return;}"
                 . "document.addEventListener('set-consent.hu',function(e){if(e.detail&&e.detail.categories&&e.detail.categories[k]===true){an();}});";
        case 'axeptio':
            // cookies:complete wird nachgereicht, auch wenn das SDK schon lief.
            return "window._axcb=window._axcb||[];window._axcb.push(function(sdk){sdk.on('cookies:complete',function(c){if(c&&c[k]===true){an();}});});";
    }
    return '';
}

function stats4u_cmp($e) {
    $k = (string) $e['consent'];
    if ($k === '') { return null; }
    if ($k === 'eigen') {
        return array('name' => 'custom', 'weg' => 'skript', 'wert' => '', 'art' => 'kategorie',
                     'attr' => stats4u_eigene_attribute($e['consent_eigen']));
    }
    $alle = stats4u_cmps();
    return isset($alle[$k]) ? $alle[$k] : null;
}

function stats4u_consent_wert($e, $cmp) {
    $w = trim((string) $e['consent_wert']);
    return $w !== '' ? $w : (string) ($cmp['wert'] ?? '');
}

/**
 * Eigene Attribute fuer ein Werkzeug, das nicht in der Liste steht, z. B.
 * data-cookieconsent="statistics". Erlaubt sind data-*, class und type -
 * mehr braucht keine Skript-Sperre, und ein onload= hat hier nichts verloren.
 */
function stats4u_eigene_attribute($roh) {
    $aus = array('type' => 'text/plain');
    if (preg_match_all('/([a-zA-Z][a-zA-Z0-9_.:-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+))/', (string) $roh, $m, PREG_SET_ORDER)) {
        foreach ($m as $a) {
            $name = strtolower($a[1]);
            if (!preg_match('/^(data-[a-z0-9_.:-]+|class|type)$/', $name)) { continue; }
            $wert = $a[2] !== '' ? $a[2] : ((isset($a[3]) && $a[3] !== '') ? $a[3] : ($a[4] ?? ''));
            $aus[$name] = substr(preg_replace('/[^A-Za-z0-9 _.:\/,#-]/', '', $wert), 0, 80);
        }
    }
    return $aus;
}

function stats4u_eigene_attribute_text($roh) {
    $teile = array();
    foreach (stats4u_eigene_attribute($roh) as $k => $v) { $teile[] = $k . '="' . $v . '"'; }
    return implode(' ', $teile);
}

/** Merkt sich, ob auf der Seite ein Zaehler auf den Einzeiler wartet. */
function stats4u_wartet($setzen = null) {
    static $wartet = false;
    if ($setzen !== null) { $wartet = (bool) $setzen; }
    return $wartet;
}

// --- Das Bild ------------------------------------------------------------------

/** Die Sprache der Beschriftung; '' = wie auf stats4u.net eingestellt. */
function stats4u_cl($e) {
    $s = (string) $e['sprache'];
    if ($s === 'site') {
        // Mehrsprachige Seiten (Polylang, WPML) setzen das Gebietsschema je
        // Seite - der Zaehler spricht dann die Sprache der gerade gelesenen.
        $s = strtolower(substr(determine_locale(), 0, 2));
    }
    return in_array($s, stats4u_sprachen(), true) ? $s : '';
}

/**
 * Die Bildadresse. Die kurze Form mit Endung, damit sie auch dort durchgeht,
 * wo eine Adresse ohne Endung nicht als Bild angenommen wird.
 */
function stats4u_bildadresse($e, $nurAnzeigen = false) {
    $url = 'https://www.stats4u.net/c/' . rawurlencode($e['id']) . '-' . rawurlencode($e['style']) . '.png';

    $teile = is_array($e['params']) ? $e['params'] : array();
    if ($e['dark'] !== '0') { $teile['dark'] = $e['dark']; }
    if (stats4u_groesse($e['gr']) !== 100) { $teile['gr'] = stats4u_groesse($e['gr']); }
    if ($e['metrik'] === 'uniq') { $teile['m'] = 'uniq'; }
    $cl = stats4u_cl($e);
    if ($cl !== '') { $teile['cl'] = $cl; }
    // rl=1: anzeigen, nicht zaehlen. Fuer jede Vorschau - bis 1.1.0 zaehlte
    // jeder Blick auf die Einstellungsseite als Besuch.
    if ($nurAnzeigen) { $teile['rl'] = '1'; }

    return $teile ? $url . '?' . http_build_query($teile, '', '&', PHP_QUERY_RFC3986) : $url;
}

/**
 * Das fertige Stueck HTML.
 *
 * Kein loading="lazy": ein Zaehler im Fussbereich wuerde damit erst zaehlen,
 * wenn jemand bis nach unten scrollt. Aus demselben Grund die Marken, mit
 * denen sich die verbreiteten Lazy-Load-Plugins ein Bild ausreden lassen
 * (skip-lazy, no-lazyload, data-no-lazy).
 */
function stats4u_html($vorschau = false) {
    $e = stats4u_einstellungen();
    if ($e['id'] === '') { return ''; }

    $src  = stats4u_bildadresse($e, $vorschau);
    $cmp  = $vorschau ? null : stats4u_cmp($e);
    $attr = array();
    if ($cmp === null) {
        $attr['src'] = $src;
    } elseif ($cmp['weg'] === 'img') {
        foreach ($cmp['attr'] as $k => $v) {
            $attr[$k] = ($v === '{src}') ? $src : str_replace('{wert}', stats4u_consent_wert($e, $cmp), $v);
        }
    } else {
        $attr['data-stats4u-src'] = $src;
        stats4u_wartet(true);
    }
    // Eine Klasse des Werkzeugs (iubenda) bleibt vorn, die Lazy-Load-Marken kommen dazu.
    $attr['class'] = trim(($attr['class'] ?? '') . ' skip-lazy no-lazyload');
    $attr += array(
        'alt'          => __('Visitor counter', 'stats4u'),
        'data-no-lazy' => '1',
        'decoding'     => 'async',
    );

    $bild = '<img';
    foreach ($attr as $k => $v) {
        $url  = ($k === 'src' || substr($k, -4) === '-src' || $k === 'data-suppressedsrc');
        $bild .= ' ' . $k . '="' . ($url ? esc_url($v) : esc_attr($v)) . '"';
    }
    $bild .= '>';

    if (empty($e['link'])) {
        return '<span class="stats4u-zaehler">' . $bild . '</span>';
    }
    return sprintf(
        '<a class="stats4u-zaehler" href="%s" rel="noopener">%s</a>',
        esc_url('https://www.stats4u.net/live/' . $e['id']),
        $bild
    );
}

/**
 * Was stats4u_html() ausgeben darf - fuer wp_kses() bei jeder Ausgabe. Das
 * HTML ist oben schon mit esc_url()/esc_attr() gebaut; die Liste macht das
 * fuer jeden Leser des Codes (und fuer Plugin Check) nachpruefbar.
 */
function stats4u_erlaubt() {
    $img = array('src' => true, 'alt' => true, 'class' => true, 'decoding' => true,
                 'data-no-lazy' => true, 'data-stats4u-src' => true);
    $cmp = stats4u_cmp(stats4u_einstellungen());
    if ($cmp && $cmp['weg'] === 'img') {
        foreach (array_keys($cmp['attr']) as $k) { $img[$k] = true; }
    }
    return array(
        'a'    => array('class' => true, 'href' => true, 'rel' => true),
        'span' => array('class' => true),
        'img'  => $img,
    );
}

/** Der Zaehler in seinem Kasten, fuer die automatischen Plaetze. */
function stats4u_kasten($klasse, $stil) {
    $html = stats4u_html();
    if ($html === '') { return ''; }
    return '<div class="' . esc_attr($klasse) . '" style="' . esc_attr($stil) . '">'
         . wp_kses($html, stats4u_erlaubt()) . '</div>';
}

function stats4u_ausrichtung($e) {
    return in_array($e['ausrichtung'], array('left', 'center', 'right'), true) ? $e['ausrichtung'] : 'center';
}

/**
 * Der Einzeiler fuer Werkzeuge, die nur Skripte freischalten, und fuer die
 * WP Consent API. Einmal je Seite, am Ende von wp_footer - nach allen
 * Zaehlern, auch denen aus Kurzcode und Block.
 */
add_action('wp_footer', 'stats4u_aktivierer', 100);
function stats4u_aktivierer() {
    if (!stats4u_wartet()) { return; }
    $e   = stats4u_einstellungen();
    $cmp = stats4u_cmp($e);
    if (!$cmp) { return; }

    $wert = stats4u_consent_wert($e, $cmp);
    // Ohne die Kennung, die das Werkzeug je Seite vergibt, bleibt das Bild
    // gesperrt - lieber kein Zaehler als einer ohne Einwilligung.
    if ($wert === '' && $cmp['art'] === 'pflicht') { return; }

    $an = "function an(){var l=document.querySelectorAll('img[data-stats4u-src]');for(var i=0;i<l.length;i++){l[i].src=l[i].getAttribute('data-stats4u-src');l[i].removeAttribute('data-stats4u-src');}}";
    $los = "if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',los);}else{los();}";

    if ($cmp['weg'] === 'api') {
        $js = '(function(k){' . $an . 'function los(){' . stats4u_api_js($e['consent']) . '}' . $los . '})('
            . wp_json_encode($wert) . ');';
        wp_print_inline_script_tag($js, array('id' => 'stats4u-consent'));
        return;
    }
    $attr = array();
    foreach ($cmp['attr'] as $k => $v) { $attr[$k] = str_replace('{wert}', $wert, $v); }
    $attr['id'] = 'stats4u-consent';
    if ($cmp['weg'] === 'extern') {
        $attr['src'] = plugins_url('freigabe.js', __FILE__) . '?ver=' . STATS4U_VERSION;
        wp_print_script_tag($attr);
        return;
    }
    if ($cmp['weg'] === 'skript') {
        wp_print_inline_script_tag('(function(){' . $an . 'function los(){an();}' . $los . '})();', $attr);
    }
}

// Die WP Consent API will wissen, welche Plugins sie befragen.
add_filter('wp_consent_api_registered_' . plugin_basename(__FILE__), '__return_true');

/**
 * Ein Satz mit einem Verweis darin. $satz kommt schon durch esc_html__(), der
 * Verweis wird hier mit esc_url()/esc_html() gebaut; ausgegeben wird das
 * Ganze erst durch wp_kses() mit stats4u_erlaubt_link().
 */
function stats4u_satz_mit_link($satz, $url, $text) {
    $link = sprintf('<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url($url), esc_html($text));
    return sprintf($satz, $link);
}

function stats4u_erlaubt_link() {
    return array('a' => array('href' => true, 'target' => true, 'rel' => true));
}

/**
 * Die mitgelieferte Uebersetzung laden.
 *
 * NACHGEMESSEN, NICHT ANGENOMMEN: WordPress 7.0 findet von selbst nur, was in
 * wp-content/languages/plugins/ liegt. Ohne diese Zeile blieb die Oberflaeche
 * auf einem deutschen und einem polnischen Blog englisch, obwohl die
 * .mo-Dateien danebenlagen. Auf 'init': seit WordPress 6.7 meldet ein zu
 * frueher Aufruf eine Warnung.
 */
add_action('init', 'stats4u_sprache');
function stats4u_sprache() {
    // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- bundled translations in /languages, see above; a language pack from translate.wordpress.org still wins
    load_plugin_textdomain('stats4u', false, dirname(plugin_basename(__FILE__)) . '/languages');
    if (is_textdomain_loaded('stats4u')) { return; }

    // Kein genauer Treffer: dieselbe Sprache aus einem anderen Land nehmen
    // (de_AT, de_CH -> de_DE, es_MX -> es_ES, pt_BR -> pt_PT, fr_CA -> fr_FR).
    // Nicht fuer zh: zh_TW und zh_HK schreiben Langzeichen, zh_CN Kurzzeichen.
    $sprache = (string) strtok(determine_locale(), '_');
    if ($sprache === '' || $sprache === 'zh') { return; }
    foreach (glob(__DIR__ . '/languages/stats4u-*.mo') ?: array() as $datei) {
        if (strtok(substr(basename($datei, '.mo'), 8), '_') === $sprache) {
            load_textdomain('stats4u', $datei);
            return;
        }
    }
}

// --- Kurzcode und Block ---------------------------------------------------------
add_shortcode('stats4u', 'stats4u_kurzcode');
function stats4u_kurzcode() {
    return wp_kses(stats4u_html(), stats4u_erlaubt());
}

/**
 * Der Block "Stats4U counter". Ohne Build-Schritt: editor.js ist einfaches
 * JavaScript mit wp.element.createElement. Im Editor zeigt er das
 * Vorschaubild (rl=1, zaehlt nicht), auf der Seite rendert ihn PHP - mit
 * derselben Consent-Behandlung wie ueberall.
 */
add_action('init', 'stats4u_block');
function stats4u_block() {
    if (!function_exists('register_block_type')) { return; }
    wp_register_script('stats4u-editor', plugins_url('editor.js', __FILE__),
                       array('wp-blocks', 'wp-element', 'wp-block-editor'), STATS4U_VERSION, true);
    $e = stats4u_einstellungen();
    wp_add_inline_script('stats4u-editor', 'window.stats4uEditor=' . wp_json_encode(array(
        'titel'        => __('Stats4U counter', 'stats4u'),
        'beschreibung' => __('Your visitor counter from stats4u.net.', 'stats4u'),
        'vorschau'     => $e['id'] !== '' ? stats4u_bildadresse($e, true) : '',
        'leer'         => __('Set up your counter first under Settings > Stats4U.', 'stats4u'),
    )) . ';', 'before');
    register_block_type('stats4u/counter', array(
        'api_version'     => 2,
        'editor_script'   => 'stats4u-editor',
        'render_callback' => 'stats4u_block_ausgabe',
        'attributes'      => array('ausrichtung' => array('type' => 'string', 'default' => 'center')),
        'supports'        => array('html' => false),
    ));
}

function stats4u_block_ausgabe($attr) {
    $a = (isset($attr['ausrichtung']) && in_array($attr['ausrichtung'], array('left', 'center', 'right'), true))
       ? $attr['ausrichtung'] : 'center';
    return stats4u_kasten('wp-block-stats4u-counter', 'text-align:' . $a);
}

// --- Automatische Plaetze --------------------------------------------------------
//
// unter  - direkt hinter dem Fussbereich des Themes (Vorgabe)
// im     - im Fussbereich, an seinem Ende
// ende   - vor </body>, wie bis 1.3.0
// inhalt - unter dem Inhalt von Beitraegen und Seiten
// ecke_r / ecke_l - fest unten in einer Ecke
// aus    - nur Block und Kurzcode
//
// BIS 1.3.0 hing der Zaehler an wp_footer, also direkt vor </body>. Auf
// lukaswojcik.com (body { display: flex; align-items: center }) wurde er zur
// Spalte NEBEN der Seite, auf halber Hoehe: x = 1124, y = 3456 von 6937 px.

function stats4u_auto_aktiv() {
    static $aktiv = null;
    if ($aktiv === null) {
        $e = stats4u_einstellungen();
        $aktiv = $e['id'] !== '' && $e['platz'] !== 'aus'
              && !is_admin() && !is_feed() && !is_embed() && !wp_doing_ajax()
              && !(defined('REST_REQUEST') && REST_REQUEST);
        if ($aktiv && $e['wo'] === 'start')    { $aktiv = is_front_page(); }
        if ($aktiv && $e['wo'] === 'einzeln')  { $aktiv = is_singular(); }
    }
    return $aktiv;
}

function stats4u_platz() {
    $e = stats4u_einstellungen();
    return stats4u_auto_aktiv() ? $e['platz'] : 'aus';
}

// Unter dem Inhalt: einmal, nur im Hauptinhalt eines einzelnen Beitrags.
add_filter('the_content', 'stats4u_nach_inhalt', 20);
function stats4u_nach_inhalt($inhalt) {
    static $fertig = false;
    if ($fertig || stats4u_platz() !== 'inhalt' || !is_singular() || !in_the_loop() || !is_main_query()) {
        return $inhalt;
    }
    $fertig = true;
    return $inhalt . stats4u_kasten('stats4u-fuss', 'clear:both;text-align:' . stats4u_ausrichtung(stats4u_einstellungen()) . ';margin:1em 0');
}

// Puffer fuer 'unter' und 'im': ab dem Ende von wp_head (das ruft jedes
// Theme; wp_body_open fehlt z. B. auf lukaswojcik.com). Vorrang PHP_INT_MAX:
// als letzter in wp_head, damit Puffer anderer Plugins UNTER dem eigenen liegen.
add_action('wp_head', 'stats4u_puffer_start', PHP_INT_MAX);

// Vorrang 0: Elementor Pro gibt seinen Fussbereich IN einem get_footer-Handler
// aus und ruft dort auch wp_footer() - so liegt das schon im Puffer.
add_action('get_footer', 'stats4u_puffer_fuss', 0);
function stats4u_puffer_fuss() {
    global $stats4u_puffer;
    stats4u_puffer_start();
    // Merken, wo im Puffer der Fussbereich des Themes beginnt.
    if (!empty($stats4u_puffer) && $stats4u_puffer['fuss'] === null
        && ob_get_level() === $stats4u_puffer['stufe']) {
        $stats4u_puffer['fuss'] = (int) ob_get_length();
    }
}

function stats4u_puffer_start() {
    global $stats4u_puffer;
    if (!empty($stats4u_puffer) || did_action('wp_footer') || !in_array(stats4u_platz(), array('unter', 'im'), true)) { return; }
    ob_start();
    $stats4u_puffer = array('stufe' => ob_get_level(), 'fuss' => null);
}

/**
 * Weist sich ein oeffnendes Tag als Seitenfuss aus? Namen als ganze Woerter:
 * "entry-footer" ist nicht "footer". wp-block-template-part nur an <footer> -
 * an einem <div> ist es irgendein Vorlagenteil.
 */
function stats4u_ist_seitenfuss($name, $tag) {
    if (preg_match('/(?<![\w-])role\s*=\s*["\']?contentinfo\b/i', $tag)) { return true; }
    if (preg_match('/(?<![\w-])itemtype\s*=\s*["\']?[^"\'>]*WPFooter/i', $tag)) { return true; }
    $namen = array('colophon', 'footer', 'site-footer', 'main-footer', 'page-footer', 'global-footer',
                   'elementor-location-footer');
    if ($name === 'footer') { $namen[] = 'wp-block-template-part'; }
    foreach (array('id', 'class') as $attr) {
        if (preg_match('/(?<![\w-])' . $attr . '\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $tag, $m)) {
            $wert = strtolower(($m[1] ?? '') . ($m[2] ?? '') . ($m[3] ?? ''));
            if (array_intersect(preg_split('/\s+/', trim($wert)), $namen)) { return true; }
        }
    }
    return false;
}

/**
 * Zum oeffnenden Tag bei $start das passende schliessende suchen - Skripte,
 * Stile und Kommentare uebersprungen, gleichnamige verschachtelte
 * mitgezaehlt. Rueckgabe array(Beginn des schliessenden Tags, Ende) oder null.
 */
function stats4u_schliesst($html, $start, $name) {
    $re = '~<!--.*?-->|<(script|style)\b[^>]*>.*?</\1\s*>|<(/?)' . $name . '(?=[\s>/])[^>]*>~is';
    if (!preg_match_all($re, $html, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE, $start)) { return null; }
    $tiefe = 0;
    foreach ($m as $t) {
        if (!isset($t[2]) || $t[2][1] < 0) { continue; }
        $tiefe += ($t[2][0] === '') ? 1 : -1;
        if ($tiefe === 0) { return array($t[0][1], $t[0][1] + strlen($t[0][0])); }
    }
    return null;
}

/**
 * Der Seitenfuss im Puffer: array(Beginn, Beginn des schliessenden Tags,
 * Ende) oder null.
 *
 * Erste Wahl: der letzte AEUSSERSTE Kandidat (footer, div, section, aside),
 * der sich als Seitenfuss ausweist - bei GeneratePress steckt das <footer>
 * in einem <div class="site-footer">, und der Zaehler gehoert unter den
 * ganzen Balken. Sonst das erste <footer> ab get_footer(), aber NUR dort:
 * davor stehen Beitraege mit <footer class="entry-footer">.
 */
function stats4u_fuss_spanne($html, $fuss_ab) {
    $re = '~<!--.*?-->|<(script|style)\b[^>]*>.*?</\1\s*>|<(footer|div|section|aside)(?=[\s>/])[^>]*>~is';
    if (!preg_match_all($re, $html, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) { return null; }
    $kandidaten = array();
    $erstes_ab_fuss = null;
    foreach ($m as $t) {
        if (!isset($t[2]) || $t[2][1] < 0) { continue; }
        $name = strtolower($t[2][0]);
        $beginn = $t[0][1];
        if (stats4u_ist_seitenfuss($name, $t[0][0])) {
            $zu = stats4u_schliesst($html, $beginn, $name);
            if ($zu) { $kandidaten[] = array($beginn, $zu[0], $zu[1]); }
        } elseif ($erstes_ab_fuss === null && $name === 'footer' && $fuss_ab !== null && $beginn >= $fuss_ab) {
            $zu = stats4u_schliesst($html, $beginn, 'footer');
            if ($zu) { $erstes_ab_fuss = array($beginn, $zu[0], $zu[1]); }
        }
    }
    $aussen = null;
    foreach ($kandidaten as $k) {
        foreach ($kandidaten as $o) {
            if ($o !== $k && $o[0] <= $k[0] && $o[2] >= $k[2]) { continue 2; }
        }
        $aussen = $k; // der letzte aeusserste gewinnt
    }
    return $aussen ?: $erstes_ab_fuss;
}

// Vorrang PHP_INT_MIN: den eigenen Puffer schliessen, bevor irgendein anderer
// wp_footer-Handler etwas ausgibt oder einen Puffer anfasst.
add_action('wp_footer', 'stats4u_fuss', PHP_INT_MIN);
function stats4u_fuss() {
    global $stats4u_puffer;
    static $fertig = false;
    $platz = stats4u_platz();
    if ($fertig || !in_array($platz, array('unter', 'im', 'ende'), true)) { return; }
    $fertig = true;

    // clear: hinter gefloateten Fussbereichen; grid-column: in einem Raster
    // ueber die volle Breite statt in die naechste freie Zelle.
    $zaehler = stats4u_kasten('stats4u-fuss', 'clear:both;grid-column:1/-1;text-align:'
             . stats4u_ausrichtung(stats4u_einstellungen()) . ';margin:1em 0');

    if (!empty($stats4u_puffer) && $stats4u_puffer['stufe'] > 0
        && ob_get_level() === $stats4u_puffer['stufe']) {
        $fuss_ab = $stats4u_puffer['fuss'];
        $stats4u_puffer = array('stufe' => -1, 'fuss' => null);
        $html = (string) ob_get_clean();
        $spanne = stats4u_fuss_spanne($html, $fuss_ab);
        if ($spanne !== null) {
            $stelle = ($platz === 'im') ? $spanne[1] : $spanne[2];
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the theme's own, already rendered HTML, handed back unchanged; $zaehler is built with wp_kses() in stats4u_kasten()
            echo substr($html, 0, $stelle) . $zaehler . substr($html, $stelle);
            return;
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the theme's own, already rendered HTML, handed back unchanged
        echo $html;
    }
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with wp_kses() in stats4u_kasten()
    echo $zaehler;
}

// Fest in einer Ecke: ueber allem, unabhaengig vom Theme.
add_action('wp_footer', 'stats4u_ecke', 10);
function stats4u_ecke() {
    $platz = stats4u_platz();
    if ($platz !== 'ecke_r' && $platz !== 'ecke_l') { return; }
    $seite = ($platz === 'ecke_l') ? 'left' : 'right';
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with wp_kses() in stats4u_kasten()
    echo stats4u_kasten('stats4u-ecke', 'position:fixed;bottom:12px;' . $seite . ':12px;z-index:99990;line-height:0');
}

// --- Einstellungsseite -------------------------------------------------------------
add_action('admin_menu', 'stats4u_menue');
function stats4u_menue() {
    add_options_page(__('Stats4U', 'stats4u'), __('Stats4U', 'stats4u'), 'manage_options', 'stats4u', 'stats4u_seite');
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'stats4u_aktionslinks');
function stats4u_aktionslinks($links) {
    array_unshift($links, '<a href="' . esc_url(admin_url('options-general.php?page=stats4u')) . '">'
                        . esc_html__('Settings', 'stats4u') . '</a>');
    return $links;
}

add_action('admin_init', 'stats4u_registrieren');
function stats4u_registrieren() {
    register_setting('stats4u_gruppe', STATS4U_OPTION, array(
        'type'              => 'array',
        'sanitize_callback' => 'stats4u_saeubern',
        'default'           => stats4u_vorgaben(),
    ));
}

/**
 * Alles, was aus dem Formular kommt, wird hier beschnitten - nicht beim
 * Ausgeben.
 *
 * Achtung, WordPress ruft diese Funktion beim ALLERERSTEN Speichern zweimal:
 * einmal mit dem Formular, einmal mit dem eigenen Ergebnis (update_option ->
 * add_option). Deshalb versteht sie beide Formen.
 */
function stats4u_saeubern($ein) {
    $ein = is_array($ein) ? $ein : array();
    $alt = stats4u_einstellungen();
    $v   = stats4u_vorgaben();
    $aus = $alt;

    // Zweiter Durchlauf: schon gesaeuberte Werte - Zaehler direkt uebernehmen.
    if (isset($ein['id']) && !isset($ein['code'])) {
        $aus['id']     = preg_replace('/[^0-9]/', '', (string) $ein['id']);
        $aus['style']  = preg_replace('/[^0-9a-z_]/', '', strtolower((string) ($ein['style'] ?? $v['style']))) ?: $v['style'];
        $aus['params'] = stats4u_params_saeubern($ein['params'] ?? array());
    }

    // Aussehen, Platz, Consent aus dem Formular.
    $wahl = function ($k, $erlaubt) use ($ein, $alt) {
        $w = isset($ein[$k]) ? (string) $ein[$k] : (string) $alt[$k];
        return in_array($w, $erlaubt, true) ? $w : $erlaubt[0];
    };
    $aus['gr']          = stats4u_groesse($ein['gr'] ?? $alt['gr']);
    $aus['sprache']     = $wahl('sprache', array_merge(array('', 'site'), stats4u_sprachen()));
    $aus['dark']        = $wahl('dark', array('auto', '1', '0'));
    $aus['metrik']      = $wahl('metrik', array('', 'uniq'));
    $aus['link']        = empty($ein['link']) ? 0 : 1;
    $aus['platz']       = $wahl('platz', array('unter', 'im', 'ende', 'inhalt', 'ecke_r', 'ecke_l', 'aus'));
    $aus['ausrichtung'] = $wahl('ausrichtung', array('center', 'left', 'right'));
    $aus['wo']          = $wahl('wo', array('alle', 'start', 'einzeln'));
    $aus['consent']     = $wahl('consent', array_merge(array(''), array_keys(stats4u_cmps()), array('eigen')));
    $aus['consent_wert']  = substr(preg_replace('/[^A-Za-z0-9 _.:-]/', '', (string) ($ein['consent_wert'] ?? $alt['consent_wert'])), 0, 60);
    $aus['consent_eigen'] = stats4u_eigene_attribute_text($ein['consent_eigen'] ?? $alt['consent_eigen']);
    if ($aus['consent_eigen'] === 'type="text/plain"') { $aus['consent_eigen'] = ''; }

    // Der eingefuegte Code zuletzt: was er festlegt, gewinnt gegen die
    // Auswahlfelder - er ist das, was der Nutzer gerade bewusst geaendert hat.
    $code = isset($ein['code']) ? trim((string) $ein['code']) : '';
    if ($code !== '') {
        $l = stats4u_code_lesen($code);
        if ($l['fehler'] !== '') {
            add_settings_error(STATS4U_OPTION, 'stats4u_code', stats4u_fehlertext($l['fehler']), 'error');
        } else {
            $aus['id'] = $l['id'];
            // Nur eine Nummer eingefuegt: Entwurf und Parameter bleiben.
            if ($l['style'] !== '') {
                $aus['style']  = $l['style'];
                $aus['params'] = $l['params'];
            }
            foreach (array('dark', 'gr', 'metrik', 'sprache') as $k) {
                if (isset($l[$k])) { $aus[$k] = $l[$k]; }
            }
        }
    }

    unset($aus['code']);
    return $aus;
}

/** Wo stats4u.net nach dem Assistenten hin zurueckfuehren soll. */
function stats4u_erstellen_url() {
    $sprache = strtolower(substr(get_user_locale(), 0, 2));
    $pfad = (in_array($sprache, stats4u_sprachen(), true) && $sprache !== 'en') ? '/' . $sprache . '/' : '/';
    return add_query_arg('wpback', rawurlencode(admin_url('options-general.php?page=stats4u')),
                         'https://www.stats4u.net' . $pfad);
}

/** Was der Betreiber im Werkzeug einstellen muss - ein Satz je Art. */
function stats4u_cmp_hinweis($c) {
    if ($c['art'] === 'dienst') {
        return sprintf(
            /* translators: %s: name of the service to create in the consent tool */
            __('Your consent tool decides per service: add a service named "%s" there, in its statistics category - or enter the name you already use.', 'stats4u'),
            $c['wert']
        );
    }
    if ($c['art'] === 'pflicht') {
        return __('Enter the ID your consent tool uses for statistics - its settings show it. Until then the counter does not load.', 'stats4u');
    }
    return sprintf(
        /* translators: %s: name of a consent category, e.g. statistics */
        __('Uses the category "%s" of your consent tool. Change it only if yours is called differently.', 'stats4u'),
        $c['wert']
    );
}

/** Welche Consent-Werkzeuge sind hier als Plugin aktiv? */
function stats4u_cmps_erkannt() {
    if (!function_exists('is_plugin_active')) { require_once ABSPATH . 'wp-admin/includes/plugin.php'; }
    $da = array();
    foreach (stats4u_cmps() as $k => $c) {
        foreach ((array) ($c['plugins'] ?? array()) as $p) {
            if (is_plugin_active($p)) { $da[] = $k; break; }
        }
    }
    return $da;
}

// Ein paar Zeilen Skript fuer die Einstellungsseite: blendet ein, was zur Wahl
// passt. Ohne Skript funktioniert die Seite genauso, nur zeigt sie alles.
add_action('admin_enqueue_scripts', 'stats4u_admin_skript');
function stats4u_admin_skript($seite) {
    if ($seite !== 'settings_page_stats4u') { return; }
    wp_register_script('stats4u-admin', false, array(), STATS4U_VERSION, true);
    wp_enqueue_script('stats4u-admin');
    $daten = array();
    foreach (stats4u_cmps() as $k => $c) { $daten[$k] = array('wert' => $c['wert'], 'hinweis' => stats4u_cmp_hinweis($c)); }
    wp_add_inline_script('stats4u-admin', 'window.stats4uCmp=' . wp_json_encode($daten) . ';'
        . "(function(){var d=document,c=d.getElementById('s4u_consent');if(!c){return;}"
        . "var w=d.getElementById('s4u_consent_wert'),h=d.getElementById('s4u_wert_hinweis'),wz=d.getElementById('s4u_wert_zeile'),ez=d.getElementById('s4u_eigen_zeile');"
        . "function neu(){var k=c.value,x=window.stats4uCmp[k];if(w){w.placeholder=x?x.wert:'';}if(h&&x){h.textContent=x.hinweis;}"
        . "if(wz){wz.hidden=!x;}if(ez){ez.hidden=(k!=='eigen');}}"
        . "c.addEventListener('change',neu);neu();"
        . "var p=d.querySelectorAll('input[name$=\"[platz]\"]'),az=d.getElementById('s4u_ausrichtung_zeile');"
        . "function platz(){var x=d.querySelector('input[name$=\"[platz]\"]:checked');if(az&&x){az.hidden=/^(ecke_r|ecke_l|aus)$/.test(x.value);}}"
        . "for(var i=0;i<p.length;i++){p[i].addEventListener('change',platz);}platz();})();");
}

function stats4u_seite() {
    // Doppelt gemoppelt und trotzdem richtig: add_options_page prueft die
    // Berechtigung schon, aber die Funktion ist oeffentlich erreichbar.
    if (!current_user_can('manage_options')) { return; }

    $e = stats4u_einstellungen();
    $n = STATS4U_OPTION;

    // Code, den stats4u.net nach dem Assistenten mitschickt. Er fuellt nur das
    // Feld vor - gespeichert wird erst mit dem Knopf, also mit Nonce.
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only prefill of a form field; nothing is saved without the nonce-protected form below
    $zurueck = isset($_GET['stats4u_code']) ? sanitize_textarea_field(wp_unslash($_GET['stats4u_code'])) : '';
    $erkannt = ($zurueck !== '') ? stats4u_code_lesen($zurueck) : null;

    $groessen = array(50, 75, 100, 125, 150, 200, 300);
    if (!in_array((int) $e['gr'], $groessen, true)) { $groessen[] = (int) $e['gr']; sort($groessen); }
    $sprachnamen = array(
        'pl' => 'Polski', 'en' => 'English', 'de' => 'Deutsch', 'es' => 'Español', 'fr' => 'Français',
        'it' => 'Italiano', 'pt' => 'Português', 'ru' => 'Русский', 'uk' => 'Українська', 'zh' => '中文',
        'ja' => '日本語', 'nl' => 'Nederlands', 'ko' => '한국어', 'bg' => 'Български', 'ro' => 'Română',
        'id' => 'Bahasa Indonesia', 'ar' => 'العربية', 'tr' => 'Türkçe', 'cs' => 'Čeština',
    );
    $erkannte_cmps = stats4u_cmps_erkannt();
    $alle_cmps = stats4u_cmps();
    uasort($alle_cmps, function ($a, $b) { return strcasecmp($a['name'], $b['name']); });
    // Noch nie gespeichert und genau ein Consent-Werkzeug aktiv: vorschlagen.
    // Wer "sofort laden" will, waehlt das bewusst und speichert.
    if (get_option(STATS4U_OPTION) === false && count($erkannte_cmps) === 1) {
        $e['consent'] = $erkannte_cmps[0];
    }
    $cmp_jetzt = stats4u_cmp($e);
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Stats4U Visitor Counter', 'stats4u'); ?></h1>

        <?php if ($cmp_jetzt && $cmp_jetzt['art'] === 'pflicht' && stats4u_consent_wert($e, $cmp_jetzt) === '') : ?>
        <div class="notice notice-warning"><p><?php
            echo esc_html(sprintf(
                /* translators: %s: name of a consent tool */
                __('%s needs the ID it uses for statistics (see "Category or service" below). Until you enter it, the counter does not load.', 'stats4u'),
                $cmp_jetzt['name']
            ));
        ?></p></div>
        <?php endif; ?>

        <?php if ($erkannt && $erkannt['fehler'] === '') : ?>
        <div class="notice notice-success"><p><?php
            echo esc_html(sprintf(
                /* translators: 1: counter number, 2: design number */
                __('Received from stats4u.net: counter %1$s, design %2$s. Click "Save Changes" to use it.', 'stats4u'),
                $erkannt['id'], $erkannt['style'] !== '' ? $erkannt['style'] : $e['style']
            ));
        ?></p></div>
        <?php elseif ($erkannt) : ?>
        <div class="notice notice-warning"><p><?php echo esc_html(stats4u_fehlertext($erkannt['fehler'])); ?></p></div>
        <?php elseif ($e['id'] === '') : ?>
        <div class="notice notice-info"><p><?php
            echo esc_html__('Paste the code you got on stats4u.net below - or create a free counter there first. No sign-up.', 'stats4u');
        ?></p></div>
        <?php endif; ?>

        <form action="options.php" method="post">
            <?php settings_fields('stats4u_gruppe'); ?>

            <h2><?php echo esc_html__('Your counter', 'stats4u'); ?></h2>
            <table class="form-table" role="presentation">
                <?php if ($e['id'] !== '') : ?>
                <tr>
                    <th scope="row"><?php echo esc_html__('Counter', 'stats4u'); ?></th>
                    <td>
                        <p><strong><?php
                            echo esc_html(sprintf(
                                /* translators: 1: counter number, 2: design number */
                                __('Counter %1$s, design %2$s', 'stats4u'), $e['id'], $e['style']
                            ));
                        ?></strong></p>
                        <p><img src="<?php echo esc_url(stats4u_bildadresse($e, true)); ?>" alt=""></p>
                        <p class="description"><?php echo esc_html__('Preview - not counted.', 'stats4u'); ?></p>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th scope="row"><label for="s4u_code"><?php echo esc_html__('Code from stats4u.net', 'stats4u'); ?></label></th>
                    <td>
                        <?php $offen = ($e['id'] === '' || $zurueck !== ''); ?>
                        <details <?php echo $offen ? 'open' : ''; ?>>
                            <summary><?php echo $e['id'] === ''
                                ? esc_html__('Paste your code', 'stats4u')
                                : esc_html__('Use a different counter or code', 'stats4u'); ?></summary>
                            <p><textarea name="<?php echo esc_attr($n); ?>[code]" id="s4u_code" rows="4" class="large-text code"
                                placeholder="&lt;img src=&quot;https://www.stats4u.net/c/12345-950.png&quot; ...&gt;"><?php
                                echo esc_textarea($zurueck); ?></textarea></p>
                            <p class="description"><?php
                                echo esc_html__('Paste the HTML, BBCode or image address from stats4u.net - or just the counter number. Design, colors, size and everything else you set there come along.', 'stats4u');
                            ?></p>
                        </details>
                        <p><a class="button" href="<?php echo esc_url(stats4u_erstellen_url()); ?>"><?php
                            echo esc_html__('Create a free counter on stats4u.net', 'stats4u'); ?></a></p>
                        <p class="description"><?php
                            echo esc_html__('At the end, a button brings the code back here.', 'stats4u');
                        ?></p>
                    </td>
                </tr>
            </table>

            <h2><?php echo esc_html__('Appearance', 'stats4u'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="s4u_gr"><?php echo esc_html__('Size', 'stats4u'); ?></label></th>
                    <td><select name="<?php echo esc_attr($n); ?>[gr]" id="s4u_gr">
                        <?php foreach ($groessen as $g) : ?>
                        <option value="<?php echo esc_attr($g); ?>" <?php selected((int) $e['gr'], $g); ?>><?php echo esc_html($g . ' %'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php echo esc_html__('Sharp at any size for the modern designs. The classic pixel designs keep their original size.', 'stats4u'); ?></p></td>
                </tr>
                <tr>
                    <th scope="row"><label for="s4u_sprache"><?php echo esc_html__('Language of the counter', 'stats4u'); ?></label></th>
                    <td><select name="<?php echo esc_attr($n); ?>[sprache]" id="s4u_sprache">
                        <option value="" <?php selected($e['sprache'], ''); ?>><?php echo esc_html__('as set on stats4u.net', 'stats4u'); ?></option>
                        <option value="site" <?php selected($e['sprache'], 'site'); ?>><?php echo esc_html__('the language of each page', 'stats4u'); ?></option>
                        <?php foreach ($sprachnamen as $code => $name) : ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php selected($e['sprache'], $code); ?>><?php echo esc_html($name); ?></option>
                        <?php endforeach; ?>
                    </select></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Dark mode', 'stats4u'); ?></th>
                    <td><?php
                        $modi = array(
                            'auto' => __('follow the device', 'stats4u'),
                            '0'    => __('always light', 'stats4u'),
                            '1'    => __('always dark', 'stats4u'),
                        );
                        foreach ($modi as $wert => $beschriftung) : ?>
                        <label style="margin-right:1em"><input type="radio" name="<?php echo esc_attr($n); ?>[dark]"
                               value="<?php echo esc_attr($wert); ?>" <?php checked($e['dark'], $wert); ?>>
                            <?php echo esc_html($beschriftung); ?></label>
                        <?php endforeach; ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Count', 'stats4u'); ?></th>
                    <td>
                        <label style="margin-right:1em"><input type="radio" name="<?php echo esc_attr($n); ?>[metrik]" value="" <?php checked($e['metrik'], ''); ?>>
                            <?php echo esc_html__('every page view', 'stats4u'); ?></label>
                        <label><input type="radio" name="<?php echo esc_attr($n); ?>[metrik]" value="uniq" <?php checked($e['metrik'], 'uniq'); ?>>
                            <?php echo esc_html__('unique visitors', 'stats4u'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Link', 'stats4u'); ?></th>
                    <td>
                        <label><input type="checkbox" name="<?php echo esc_attr($n); ?>[link]" value="1" <?php checked($e['link'], 1); ?>>
                            <?php echo esc_html__('link the counter to its public statistics page on stats4u.net', 'stats4u'); ?></label>
                        <p class="description"><?php echo esc_html__('Off by default. Visitors who click the counter then see your statistics.', 'stats4u'); ?></p>
                    </td>
                </tr>
            </table>

            <h2><?php echo esc_html__('Placement', 'stats4u'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php echo esc_html__('Where to show it', 'stats4u'); ?></th>
                    <td><fieldset><?php
                        $plaetze = array(
                            'unter'  => __('below your theme\'s footer (recommended)', 'stats4u'),
                            'im'     => __('inside your theme\'s footer, at its end', 'stats4u'),
                            'ende'   => __('at the very end of the page', 'stats4u'),
                            'inhalt' => __('below the content of posts and pages', 'stats4u'),
                            'ecke_r' => __('fixed in the bottom right corner', 'stats4u'),
                            'ecke_l' => __('fixed in the bottom left corner', 'stats4u'),
                            'aus'    => __('nowhere automatically - I place it myself', 'stats4u'),
                        );
                        foreach ($plaetze as $wert => $beschriftung) : ?>
                        <label style="display:block;margin:.3em 0"><input type="radio" name="<?php echo esc_attr($n); ?>[platz]"
                               value="<?php echo esc_attr($wert); ?>" <?php checked($e['platz'], $wert); ?>>
                            <?php echo esc_html($beschriftung); ?></label>
                        <?php endforeach; ?></fieldset>
                        <p class="description"><?php
                            echo esc_html__('To place it yourself, use the block "Stats4U counter" or the shortcode [stats4u] - for example in a footer widget.', 'stats4u');
                        ?></p></td>
                </tr>
                <tr id="s4u_ausrichtung_zeile" <?php echo in_array($e['platz'], array('ecke_r', 'ecke_l', 'aus'), true) ? 'hidden' : ''; ?>>
                    <th scope="row"><?php echo esc_html__('Alignment', 'stats4u'); ?></th>
                    <td><?php
                        $seiten = array('left' => __('left', 'stats4u'), 'center' => __('center', 'stats4u'), 'right' => __('right', 'stats4u'));
                        foreach ($seiten as $wert => $beschriftung) : ?>
                        <label style="margin-right:1em"><input type="radio" name="<?php echo esc_attr($n); ?>[ausrichtung]"
                               value="<?php echo esc_attr($wert); ?>" <?php checked($e['ausrichtung'], $wert); ?>>
                            <?php echo esc_html($beschriftung); ?></label>
                        <?php endforeach; ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('On which pages', 'stats4u'); ?></th>
                    <td><?php
                        $wo = array('alle' => __('on all pages', 'stats4u'), 'start' => __('only on the front page', 'stats4u'),
                                    'einzeln' => __('only on single posts and pages', 'stats4u'));
                        foreach ($wo as $wert => $beschriftung) : ?>
                        <label style="margin-right:1em"><input type="radio" name="<?php echo esc_attr($n); ?>[wo]"
                               value="<?php echo esc_attr($wert); ?>" <?php checked($e['wo'], $wert); ?>>
                            <?php echo esc_html($beschriftung); ?></label>
                        <?php endforeach; ?></td>
                </tr>
            </table>

            <h2><?php echo esc_html__('Consent', 'stats4u'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="s4u_consent"><?php echo esc_html__('Load the counter', 'stats4u'); ?></label></th>
                    <td><select name="<?php echo esc_attr($n); ?>[consent]" id="s4u_consent">
                        <option value="" <?php selected($e['consent'], ''); ?>><?php echo esc_html__('right away - I use no consent tool', 'stats4u'); ?></option>
                        <?php if ($erkannte_cmps) : ?>
                        <optgroup label="<?php echo esc_attr__('Found on this site', 'stats4u'); ?>">
                            <?php foreach ($erkannte_cmps as $k) : ?>
                            <option value="<?php echo esc_attr($k); ?>" <?php selected($e['consent'], $k); ?>><?php
                                echo esc_html(sprintf(
                                    /* translators: %s: name of a consent tool */
                                    __('after consent in %s', 'stats4u'),
                                    $alle_cmps[$k]['name']
                                )); ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                        <optgroup label="<?php echo esc_attr__('Consent tools', 'stats4u'); ?>">
                            <?php foreach ($alle_cmps as $k => $c) : if (in_array($k, $erkannte_cmps, true)) { continue; } ?>
                            <option value="<?php echo esc_attr($k); ?>" <?php selected($e['consent'], $k); ?>><?php
                                echo esc_html(sprintf(
                                    /* translators: %s: name of a consent tool */
                                    __('after consent in %s', 'stats4u'),
                                    $c['name']
                                )); ?></option>
                            <?php endforeach; ?>
                            <option value="eigen" <?php selected($e['consent'], 'eigen'); ?>><?php echo esc_html__('after consent in another tool - enter its attributes', 'stats4u'); ?></option>
                        </optgroup>
                    </select>
                    <p class="description"><?php
                        echo esc_html__('With a consent tool, visitors who decline are not counted - your numbers will be lower.', 'stats4u');
                    ?></p></td>
                </tr>
                <tr id="s4u_wert_zeile" <?php echo ($e['consent'] === '' || $e['consent'] === 'eigen') ? 'hidden' : ''; ?>>
                    <th scope="row"><label for="s4u_consent_wert"><?php echo esc_html__('Category or service', 'stats4u'); ?></label></th>
                    <td><input name="<?php echo esc_attr($n); ?>[consent_wert]" id="s4u_consent_wert" type="text" class="regular-text"
                               value="<?php echo esc_attr($e['consent_wert']); ?>"
                               placeholder="<?php echo esc_attr($cmp_jetzt ? $cmp_jetzt['wert'] : ''); ?>">
                        <p class="description" id="s4u_wert_hinweis"><?php
                            echo esc_html($cmp_jetzt && $e['consent'] !== 'eigen'
                                ? stats4u_cmp_hinweis($cmp_jetzt)
                                : __('Leave empty for the usual one of your tool. If your tool works with named services, enter the name you gave Stats4U there.', 'stats4u'));
                        ?></p></td>
                </tr>
                <tr id="s4u_eigen_zeile" <?php echo $e['consent'] !== 'eigen' ? 'hidden' : ''; ?>>
                    <th scope="row"><label for="s4u_consent_eigen"><?php echo esc_html__('Attributes', 'stats4u'); ?></label></th>
                    <td><input name="<?php echo esc_attr($n); ?>[consent_eigen]" id="s4u_consent_eigen" type="text" class="large-text code"
                               value="<?php echo esc_attr($e['consent_eigen']); ?>" placeholder='type="text/plain" data-category="statistics"'>
                        <p class="description"><?php
                            echo esc_html__('What your consent tool wants on a script it should only run after consent. Allowed: type, class and data-* attributes.', 'stats4u');
                        ?></p></td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>

        <?php if ($e['id'] !== '') : ?>
        <p class="description"><?php
            echo wp_kses(stats4u_satz_mit_link(
                /* translators: %s: link to the ready-made privacy paragraph */
                esc_html__('A paragraph for your privacy policy is at %s.', 'stats4u'),
                'https://www.stats4u.net/privacy-embed?s4uid=' . rawurlencode($e['id']), 'stats4u.net/privacy-embed'
            ), stats4u_erlaubt_link());
        ?></p>
        <?php endif; ?>
    </div>
    <?php
}
