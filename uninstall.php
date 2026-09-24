<?php
/**
 * Beim Loeschen des Plugins bleibt nichts zurueck.
 *
 * Das Plugin legt genau eine Option an. Ein Zaehler auf stats4u.net wird
 * dadurch NICHT geloescht - er gehoert dem Betreiber der Seite und nicht
 * diesem Plugin, und seine Statistik soll nicht verschwinden, weil jemand
 * das Plugin ausprobiert und wieder entfernt hat.
 */
if (!defined('WP_UNINSTALL_PLUGIN')) { exit; }

delete_option('stats4u_einstellungen');

// Bei einem Netzwerk steht sie je Blog.
if (is_multisite()) {
    foreach (get_sites(array('fields' => 'ids')) as $blog) {
        switch_to_blog($blog);
        delete_option('stats4u_einstellungen');
        restore_current_blog();
    }
}
