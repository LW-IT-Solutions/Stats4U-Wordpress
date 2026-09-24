<?php
/**
 * Plugin Name:       Stats4U
 * Plugin URI:        https://www.stats4u.net/
 * Description:       Puts a Stats4U counter on your site - as the shortcode [stats4u] or automatically in the footer. One image, no script, no cookie.
 * Version:           1.3.1
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
 * Es setzt ein <img> auf die Seite. Mehr nicht.
 *
 * Kein Skript, kein Cookie, kein eigener Endpunkt, keine eigene Tabelle, kein
 * Aufruf nach Hause aus dem Adminbereich. Das ausgelieferte Bild IST die
 * Zaehlung - deshalb braucht es hier nichts weiter, und deshalb zaehlt es auch
 * bei Lesern, die Skripte abschalten.
 *
 * Bewusst NICHT die Skriptfassung von stats4u.net: die kann den Zaehler von
 * selbst nachladen, kostet dafuer aber drei Abrufe statt einem. Was das in
 * Byte ausmacht, steht gemessen auf https://www.stats4u.net/weight - ein
 * WordPress-Blog braucht das Nachladen nicht.
 *
 * WARUM DIE ZEICHENKETTEN ENGLISCH SIND
 *
 * Bis 1.0.0 standen sie auf Deutsch, und WordPress zeigte sie deshalb auf
 * JEDEM Blog auf Deutsch - auch auf einem englischen oder polnischen. Das ist
 * beim Einbau in ein echtes WordPress aufgefallen, nicht beim Lesen des Codes:
 * WordPress uebersetzt nur weg VON der Quellsprache, und die ist per
 * Uebereinkunft Englisch. Deutsch und Polnisch liegen jetzt in languages/ -
 * Polnisch, weil dort die meisten Nutzer dieses Dienstes sitzen.
 */

if (!defined('ABSPATH')) { exit; }

const STATS4U_OPTION = 'stats4u_einstellungen';

/**
 * Die Vorgaben. 950 ist der einstellbare Entwurf; wer einen anderen will,
 * traegt dessen Nummer ein.
 */
function stats4u_vorgaben() {
    return array(
        'id'    => '',
        'style' => '950',
        'form'  => 'pill',
        'pal'   => 'mint',
        'dark'  => 'auto',
        'fuss'  => 0,
        // Den Zaehler mit seiner oeffentlichen Statistikseite verlinken.
        // Ab Werk AUS (1.2.0): wordpress.org, Richtlinie 10 - ein Plugin darf
        // ohne ausdrueckliche Zustimmung keine Verweise auf die oeffentliche
        // Seite setzen. Bis 1.1.0 war der Verweis fest eingebaut.
        'link'  => 0,
    );
}

function stats4u_einstellungen() {
    $e = get_option(STATS4U_OPTION);
    return wp_parse_args(is_array($e) ? $e : array(), stats4u_vorgaben());
}

/**
 * Alles, was aus dem Formular kommt, wird hier beschnitten - nicht beim
 * Ausgeben. So steht in der Datenbank nie etwas, das erst beim Anzeigen
 * entschaerft werden muesste.
 *
 * Beschnitten heisst wirklich beschnitten: aus 950" onerror="alert(1) wird
 * 950onerroralert1. Das ist harmlos, aber es ist auch kein gueltiger Entwurf -
 * deshalb faellt ein leeres Feld auf die Vorgabe zurueck, ein verdrehtes aber
 * nicht. Wer sich vertippt, soll das im Vorschaubild sehen.
 */
function stats4u_saeubern($ein) {
    $v = stats4u_vorgaben();
    $ein = is_array($ein) ? $ein : array();

    $aus = array();
    $aus['id']    = preg_replace('/[^0-9]/', '', (string) ($ein['id'] ?? ''));
    $aus['style'] = preg_replace('/[^0-9a-z_]/', '', strtolower((string) ($ein['style'] ?? $v['style'])));
    $aus['form']  = preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($ein['form'] ?? $v['form'])));
    $aus['pal']   = preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($ein['pal'] ?? $v['pal'])));

    $dunkel = (string) ($ein['dark'] ?? $v['dark']);
    $aus['dark'] = in_array($dunkel, array('auto', '1', '0'), true) ? $dunkel : 'auto';

    $aus['fuss'] = empty($ein['fuss']) ? 0 : 1;
    $aus['link'] = empty($ein['link']) ? 0 : 1;

    if ($aus['style'] === '') { $aus['style'] = $v['style']; }
    if ($aus['form']  === '') { $aus['form']  = $v['form']; }
    if ($aus['pal']   === '') { $aus['pal']   = $v['pal']; }
    return $aus;
}

/**
 * Die Bildadresse. Die kurze Form mit Endung, damit sie auch dort durchgeht,
 * wo eine Adresse ohne Endung nicht als Bild angenommen wird.
 */
function stats4u_bildadresse($e, $nurAnzeigen = false) {
    $url = 'https://www.stats4u.net/c/' . rawurlencode($e['id']) . '-' . rawurlencode($e['style']) . '.png';

    $teile = array();
    // form und pal gelten nur fuer den einstellbaren Entwurf; bei einem festen
    // waeren sie wirkungslos und stuenden nur in der Adresse herum.
    if ($e['style'] === '950') {
        if ($e['form'] !== '') { $teile['form'] = $e['form']; }
        if ($e['pal']  !== '') { $teile['pal']  = $e['pal']; }
    }
    if ($e['dark'] !== '0') { $teile['dark'] = $e['dark']; }
    // rl=1: anzeigen, nicht zaehlen. Fuer das Vorschaubild auf der
    // Einstellungsseite - bis 1.1.0 zaehlte jeder Blick darauf als Besuch.
    if ($nurAnzeigen) { $teile['rl'] = '1'; }

    return $teile ? $url . '?' . http_build_query($teile) : $url;
}

/**
 * Das fertige Stueck HTML.
 *
 * Kein loading="lazy": ein Zaehler im Fussbereich wuerde damit erst zaehlen,
 * wenn jemand bis nach unten scrollt.
 */
function stats4u_html() {
    $e = stats4u_einstellungen();
    if ($e['id'] === '') { return ''; }

    $bild = sprintf(
        '<img src="%s" alt="%s" decoding="async">',
        esc_url(stats4u_bildadresse($e)),
        esc_attr__('Visitor counter', 'stats4u')
    );

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
 * Was stats4u_html() ausgeben darf - fuer wp_kses() bei jeder Ausgabe.
 * Das HTML ist oben schon mit esc_url()/esc_attr() gebaut; die Liste macht
 * das fuer jeden Leser des Codes (und fuer Plugin Check) nachpruefbar.
 */
function stats4u_erlaubt() {
    return array(
        'a'    => array('class' => true, 'href' => true, 'rel' => true),
        'span' => array('class' => true),
        'img'  => array('src' => true, 'alt' => true, 'decoding' => true),
    );
}

/**
 * Ein Satz mit einem Verweis darin. $satz kommt schon durch esc_html__(), der
 * Verweis wird hier mit esc_url()/esc_html() gebaut; ausgegeben wird das
 * Ganze trotzdem erst durch wp_kses() mit stats4u_erlaubt_link() - so sieht
 * auch Plugin Check an der echo-Stelle, dass escaped wird.
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
 * wp-content/languages/plugins/ liegt - wp_get_installed_translations('plugins')
 * kennt ein Plugin mit eigener languages/ nicht. Ohne diese Zeile blieb die
 * Oberflaeche auf einem deutschen und einem polnischen Blog englisch, obwohl
 * beide .mo-Dateien danebenlagen und sich von Hand einwandfrei laden liessen.
 *
 * Auf 'init' und nicht frueher: seit WordPress 6.7 meldet ein zu frueher
 * Aufruf eine Warnung.
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

// --- Kurzcode --------------------------------------------------------------
add_shortcode('stats4u', 'stats4u_kurzcode');
function stats4u_kurzcode() {
    return wp_kses(stats4u_html(), stats4u_erlaubt());
}

// --- Automatisch unter dem Fussbereich des Themes ---------------------------
//
// BIS 1.3.0 hing der Zaehler an wp_footer, also direkt vor </body>. Das geht
// nur gut, solange <body> ein gewoehnlicher Block ist. Auf lukaswojcik.com
// (eigenes Theme, body { display: flex; align-items: center }) wurde er zur
// zweiten Spalte NEBEN der ganzen Seite, auf halber Hoehe - gemessen am
// 24.09.2026 bei x = 1124, y = 3456 von 6937 px. Im HTML stand er richtig;
// sehen konnte man es erst auf einem Foto der Seite.
//
// Seit 1.3.1: die Ausgabe ab dem Ende von wp_head puffern (das ruft jedes
// Theme; wp_body_open fehlt z. B. auf lukaswojcik.com) und den Zaehler bei
// wp_footer direkt HINTER das </footer> des Seitenfusses setzen. Dort steht er
// im selben Behaelter wie der Fussbereich und folgt dessen Fluss. Findet sich
// kein Seitenfuss oder liegt inzwischen ein fremder Puffer obenauf, bleibt es
// beim alten Platz vor </body> - schlechter als bis 1.3.0 wird es nie.

function stats4u_fuss_aktiv() {
    static $aktiv = null;
    if ($aktiv === null) {
        $e = stats4u_einstellungen();
        $aktiv = !empty($e['fuss']) && $e['id'] !== '' && !is_admin() && !is_feed()
              && !is_embed() && !wp_doing_ajax() && !(defined('REST_REQUEST') && REST_REQUEST);
    }
    return $aktiv;
}

// Vorrang PHP_INT_MAX: als letzter in wp_head, damit Puffer, die andere
// Plugins dort oeffnen, UNTER dem eigenen liegen.
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
    if (!empty($stats4u_puffer) || did_action('wp_footer') || !stats4u_fuss_aktiv()) { return; }
    ob_start();
    $stats4u_puffer = array('stufe' => ob_get_level(), 'fuss' => null);
}

/**
 * Weist sich ein <footer ...>-Tag als Seitenfuss aus? Namen als ganze Woerter:
 * "entry-footer" ist nicht "footer".
 */
function stats4u_ist_seitenfuss($tag) {
    if (preg_match('/(?<![\w-])role\s*=\s*["\']?contentinfo\b/i', $tag)) { return true; }
    $namen = array('colophon', 'footer', 'site-footer', 'main-footer', 'page-footer', 'global-footer',
                   'wp-block-template-part', 'elementor-location-footer');
    foreach (array('id', 'class') as $attr) {
        if (preg_match('/(?<![\w-])' . $attr . '\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $tag, $m)) {
            $wert = strtolower(($m[1] ?? '') . ($m[2] ?? '') . ($m[3] ?? ''));
            if (array_intersect(preg_split('/\s+/', trim($wert)), $namen)) { return true; }
        }
    }
    return false;
}

/**
 * Die Stelle direkt hinter dem </footer> des Seitenfusses, oder null.
 *
 * Erste Wahl ist das letzte <footer>, das sich als Seitenfuss ausweist
 * (stats4u_ist_seitenfuss). Sonst das erste <footer> ab get_footer() - aber
 * NUR dort: davor stehen Beitraege mit <footer class="entry-footer">.
 */
function stats4u_hinter_fuss($html, $fuss_ab) {
    if (!preg_match_all('/<footer(?=[\s>\/])[^>]*>/i', $html, $auf, PREG_OFFSET_CAPTURE)) { return null; }
    $start = null;
    foreach ($auf[0] as $tag) {
        if (stats4u_ist_seitenfuss($tag[0])) { $start = $tag[1]; }
    }
    if ($start === null && $fuss_ab !== null) {
        foreach ($auf[0] as $tag) {
            if ($tag[1] >= $fuss_ab) { $start = $tag[1]; break; }
        }
    }
    if ($start === null) { return null; }
    // Das passende </footer> suchen, verschachtelte mitgezaehlt.
    if (!preg_match_all('/<(\/?)footer(?=[\s>\/])[^>]*>/i', $html, $tags, PREG_OFFSET_CAPTURE | PREG_SET_ORDER, $start)) { return null; }
    $tiefe = 0;
    foreach ($tags as $t) {
        $tiefe += ($t[1][0] === '') ? 1 : -1;
        if ($tiefe === 0) { return $t[0][1] + strlen($t[0][0]); }
    }
    return null;
}

// Vorrang PHP_INT_MIN: den eigenen Puffer schliessen, bevor irgendein anderer
// wp_footer-Handler etwas ausgibt oder einen Puffer anfasst.
add_action('wp_footer', 'stats4u_fuss', PHP_INT_MIN);
function stats4u_fuss() {
    global $stats4u_puffer;
    static $fertig = false;
    if ($fertig || !stats4u_fuss_aktiv()) { return; }
    $fertig = true;

    // clear: hinter gefloateten Fussbereichen; grid-column: in einem Raster ueber
    // die volle Breite statt in die naechste freie Zelle.
    $zaehler = '<div class="stats4u-fuss" style="clear:both;grid-column:1/-1;text-align:center;margin:1em 0">'
             . wp_kses(stats4u_html(), stats4u_erlaubt()) . '</div>';

    if (!empty($stats4u_puffer) && $stats4u_puffer['stufe'] > 0
        && ob_get_level() === $stats4u_puffer['stufe']) {
        $fuss_ab = $stats4u_puffer['fuss'];
        $stats4u_puffer = array('stufe' => -1, 'fuss' => null);
        $html = (string) ob_get_clean();
        $stelle = stats4u_hinter_fuss($html, $fuss_ab);
        if ($stelle !== null) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the theme's own, already rendered footer HTML, handed back unchanged; $zaehler is built with wp_kses() above
            echo substr($html, 0, $stelle) . $zaehler . substr($html, $stelle);
            return;
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the theme's own, already rendered footer HTML, handed back unchanged
        echo $html;
    }
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with wp_kses() above
    echo $zaehler;
}

// --- Einstellungsseite -----------------------------------------------------
add_action('admin_menu', 'stats4u_menue');
function stats4u_menue() {
    add_options_page(
        __('Stats4U', 'stats4u'),
        __('Stats4U', 'stats4u'),
        'manage_options',
        'stats4u',
        'stats4u_seite'
    );
}

add_action('admin_init', 'stats4u_registrieren');
function stats4u_registrieren() {
    register_setting('stats4u_gruppe', STATS4U_OPTION, array(
        'type'              => 'array',
        'sanitize_callback' => 'stats4u_saeubern',
        'default'           => stats4u_vorgaben(),
    ));
}

function stats4u_seite() {
    // Doppelt gemoppelt und trotzdem richtig: add_options_page prueft die
    // Berechtigung schon, aber die Funktion ist oeffentlich erreichbar.
    if (!current_user_can('manage_options')) { return; }

    $e = stats4u_einstellungen();
    $n = STATS4U_OPTION;
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Stats4U Visitor Counter', 'stats4u'); ?></h1>

        <?php if ($e['id'] === '') : ?>
        <div class="notice notice-info"><p><?php
            echo wp_kses(stats4u_satz_mit_link(
                /* translators: %s: link to the Stats4U home page */
                esc_html__('You need a counter number first. Get one at %s - no sign-up.', 'stats4u'),
                'https://www.stats4u.net/', 'stats4u.net'
            ), stats4u_erlaubt_link());
        ?></p></div>
        <?php endif; ?>

        <form action="options.php" method="post">
            <?php settings_fields('stats4u_gruppe'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="s4u_id"><?php echo esc_html__('Counter number', 'stats4u'); ?></label></th>
                    <td><input name="<?php echo esc_attr($n); ?>[id]" id="s4u_id"
                               type="text" inputmode="numeric" class="regular-text"
                               value="<?php echo esc_attr($e['id']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="s4u_style"><?php echo esc_html__('Design', 'stats4u'); ?></label></th>
                    <td><input name="<?php echo esc_attr($n); ?>[style]" id="s4u_style"
                               type="text" class="small-text"
                               value="<?php echo esc_attr($e['style']); ?>">
                        <p class="description"><?php echo esc_html__('The number from the gallery. 950 is the adjustable counter.', 'stats4u'); ?></p></td>
                </tr>
                <?php
                // Form und Palette standen bis 1.0.0 zwar in den Einstellungen,
                // hatten aber kein Feld - der Entwurf 950 blieb damit auf jedem
                // WordPress bei pill und mint, egal was der Nutzer wollte.
                ?>
                <tr>
                    <th scope="row"><label for="s4u_form"><?php echo esc_html__('Shape', 'stats4u'); ?></label></th>
                    <td><input name="<?php echo esc_attr($n); ?>[form]" id="s4u_form"
                               type="text" class="regular-text"
                               value="<?php echo esc_attr($e['form']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="s4u_pal"><?php echo esc_html__('Palette', 'stats4u'); ?></label></th>
                    <td><input name="<?php echo esc_attr($n); ?>[pal]" id="s4u_pal"
                               type="text" class="regular-text"
                               value="<?php echo esc_attr($e['pal']); ?>">
                        <p class="description"><?php
                            echo wp_kses(stats4u_satz_mit_link(
                                /* translators: %s: link to the counter creator */
                                esc_html__('Shape and palette only apply to design 950. Pick them in the creator at %s and copy the names out of the address - there are far too many to list here, and a list here would go stale.', 'stats4u'),
                                'https://www.stats4u.net/', 'stats4u.net'
                            ), stats4u_erlaubt_link());
                        ?></p></td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Dark mode', 'stats4u'); ?></th>
                    <td>
                        <?php
                        $modi = array(
                            'auto' => __('follow the device', 'stats4u'),
                            '0'    => __('always light', 'stats4u'),
                            '1'    => __('always dark', 'stats4u'),
                        );
                        foreach ($modi as $wert => $beschriftung) : ?>
                        <label style="margin-right:1em">
                            <input type="radio" name="<?php echo esc_attr($n); ?>[dark]"
                                   value="<?php echo esc_attr($wert); ?>"
                                   <?php checked($e['dark'], $wert); ?>>
                            <?php echo esc_html($beschriftung); ?>
                        </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Where to show it', 'stats4u'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr($n); ?>[fuss]"
                                   value="1" <?php checked($e['fuss'], 1); ?>>
                            <?php echo esc_html__('show automatically in the footer', 'stats4u'); ?>
                        </label>
                        <p class="description"><?php
                            echo esc_html__('Otherwise put the shortcode [stats4u] wherever you like.', 'stats4u');
                        ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Link', 'stats4u'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr($n); ?>[link]"
                                   value="1" <?php checked($e['link'], 1); ?>>
                            <?php echo esc_html__('link the counter to its public statistics page on stats4u.net', 'stats4u'); ?>
                        </label>
                        <p class="description"><?php
                            echo esc_html__('Off by default. Visitors who click the counter then see your statistics.', 'stats4u');
                        ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <?php if ($e['id'] !== '') : ?>
        <h2><?php echo esc_html__('Preview', 'stats4u'); ?></h2>
        <p><img src="<?php echo esc_url(stats4u_bildadresse($e, true)); ?>" alt=""></p>
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
