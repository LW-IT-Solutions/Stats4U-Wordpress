<?php
/**
 * Beim Loeschen des Plugins entfernt es alles, was es selbst angelegt hat:
 * seine Optionen (Zaehler, Shop-Meldungen, Fehler- und Uebersprungliste),
 * geplante Wiederholungen, die je Nutzer gemerkte Ansicht der
 * Einstellungsseite und den ausgeblendeten Shop-Hinweis sowie die
 * Bestellmetadaten der Shop-Meldungen (_stats4u_order_reference,
 * _stats4u_retry_count und die Schluessel _s4u_* aus 1.7.0/1.7.1).
 *
 * Ein Zaehler auf stats4u.net wird dadurch NICHT geloescht - er gehoert dem
 * Betreiber der Seite und nicht diesem Plugin, und seine Statistik soll nicht
 * verschwinden, weil jemand das Plugin ausprobiert und wieder entfernt hat.
 */
if (!defined('WP_UNINSTALL_PLUGIN')) { exit; }

/** Alles, was je Blog steht. */
function stats4u_deinstallieren_blog() {
    global $wpdb;

    delete_option('stats4u_einstellungen');
    delete_option('stats4u_shop');
    delete_option('stats4u_shop_error');
    delete_option('stats4u_shop_skipped');
    wp_unschedule_hook('stats4u_shop_retry');

    $schluessel = array('_stats4u_order_reference', '_stats4u_retry_count', '_s4u_order_reference', '_s4u_retry_count');

    // Bestellungen im klassischen Speicher (und die Kopie bei HPOS-Abgleich).
    foreach ($schluessel as $k) { delete_post_meta_by_key($k); }

    // Bestellungen im HPOS-Speicher. Direkt in der Tabelle, weil WooCommerce
    // beim Loeschen dieses Plugins schon abgeschaltet sein kann; ohne die
    // Tabelle (kein HPOS) passiert nichts.
    $tabelle = $wpdb->prefix . 'wc_orders_meta';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- einmalige Abfrage beim Loeschen des Plugins, kein Cache noetig.
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($tabelle))) === $tabelle) {
        foreach ($schluessel as $k) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_query_meta_key -- WooCommerce bietet fuer seine HPOS-Tabelle kein delete_metadata(); einmalig beim Loeschen.
            $wpdb->delete($tabelle, array('meta_key' => $k), array('%s'));
        }
    }
}

stats4u_deinstallieren_blog();

// Nutzerdaten gibt es im Netzwerk nur einmal: die gemerkte Ansicht und der
// ausgeblendete Shop-Hinweis.
delete_metadata('user', 0, 'stats4u_ansicht', '', true);
delete_metadata('user', 0, 'stats4u_shop_notice', '', true);

// Bei einem Netzwerk steht das Uebrige je Blog.
if (is_multisite()) {
    foreach (get_sites(array('fields' => 'ids', 'number' => 0)) as $stats4u_blog) {
        switch_to_blog($stats4u_blog);
        stats4u_deinstallieren_blog();
        restore_current_blog();
    }
}
