<?php
/**
 * Beim Loeschen des Plugins bleibt nichts zurueck.
 *
 * Das Plugin legt eine Option an und merkt sich je Nutzer die Ansicht seiner
 * Einstellungsseite (Assistent oder Einstellungen). Ein Zaehler auf stats4u.net wird
 * dadurch NICHT geloescht - er gehoert dem Betreiber der Seite und nicht
 * diesem Plugin, und seine Statistik soll nicht verschwinden, weil jemand
 * das Plugin ausprobiert und wieder entfernt hat.
 */
if (!defined('WP_UNINSTALL_PLUGIN')) { exit; }

delete_option('stats4u_einstellungen');

// Die gemerkte Ansicht - Nutzerdaten gibt es im Netzwerk nur einmal.
delete_metadata('user', 0, 'stats4u_ansicht', '', true);

// Bei einem Netzwerk steht die Option je Blog.
if (is_multisite()) {
    foreach (get_sites(array('fields' => 'ids')) as $stats4u_blog) {
        switch_to_blog($stats4u_blog);
        delete_option('stats4u_einstellungen');
        restore_current_blog();
    }
}
