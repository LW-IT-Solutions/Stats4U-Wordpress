<?php
/**
 * Optional WooCommerce reporting (Settings > Stats4U shop).
 *
 * Off by default. Only when the site owner switches it on, the WordPress
 * server sends confirmed payments and refunds to stats4u.net: paid and
 * refunded amount in minor currency units, currency, payment time and a
 * keyed (pseudonymous) order reference, signed with the shop secret, to
 * https://www.stats4u.net/index.php?action=insights_sale&s4uid=<counter number>.
 * No customer fields and no product details are sent.
 *
 * Order meta used: _stats4u_order_reference and _stats4u_retry_count.
 * Versions 1.7.0 and 1.7.1 wrote _s4u_order_reference and _s4u_retry_count;
 * those are still read as a fallback and are moved to the new keys the next
 * time an order is reported. uninstall.php removes both.
 *
 * @package Stats4U
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Currencies Stats4U accepts for shop reports. */
const STATS4U_SHOP_WAEHRUNGEN = array( 'EUR', 'PLN', 'USD', 'GBP', 'CHF', 'CZK', 'SEK', 'HUF', 'RON' );

/** Where shop reports go (server to server, only with reporting switched on). */
const STATS4U_SHOP_ZIEL = 'https://www.stats4u.net/index.php?action=insights_sale';

/** Order meta keys (current) and the keys of 1.7.0/1.7.1 (read as fallback). */
const STATS4U_SHOP_META_REFERENZ     = '_stats4u_order_reference';
const STATS4U_SHOP_META_VERSUCHE     = '_stats4u_retry_count';
const STATS4U_SHOP_META_REFERENZ_ALT = '_s4u_order_reference';
const STATS4U_SHOP_META_VERSUCHE_ALT = '_s4u_retry_count';

/**
 * Default shop settings.
 *
 * @return array
 */
function stats4u_shop_defaults() {
	return array(
		'enabled' => false,
		'id'      => '',
		'secret'  => '',
	);
}

/**
 * Stored shop settings merged with the defaults.
 *
 * @return array
 */
function stats4u_shop_settings() {
	return wp_parse_args( get_option( 'stats4u_shop', array() ), stats4u_shop_defaults() );
}

add_action( 'admin_menu', 'stats4u_shop_menue' );
/**
 * Adds Settings > Stats4U shop.
 */
function stats4u_shop_menue() {
	add_options_page(
		esc_html__( 'Stats4U shop', 'stats4u' ),
		esc_html__( 'Stats4U shop', 'stats4u' ),
		'manage_options',
		'stats4u-shop',
		'stats4u_shop_page'
	);
}

/**
 * Address of the shop settings page.
 *
 * @return string
 */
function stats4u_shop_seite_url() {
	return admin_url( 'options-general.php?page=stats4u-shop' );
}

/**
 * Stops unless the current user may manage options.
 */
function stats4u_shop_nur_admin() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to manage options for this site.', 'stats4u' ), '', array( 'response' => 403 ) );
	}
}

add_action( 'admin_post_stats4u_shop_save', 'stats4u_shop_speichern' );
/**
 * Saves the shop settings (form on the shop settings page).
 */
function stats4u_shop_speichern() {
	stats4u_shop_nur_admin();
	check_admin_referer( 'stats4u-shop' );

	$cfg     = stats4u_shop_settings();
	$id      = isset( $_POST['stats4u_id'] ) ? sanitize_text_field( wp_unslash( $_POST['stats4u_id'] ) ) : '';
	$secret  = isset( $_POST['stats4u_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['stats4u_secret'] ) ) : '';
	$enabled = isset( $_POST['stats4u_enabled'] ) && '1' === sanitize_key( wp_unslash( $_POST['stats4u_enabled'] ) );

	if ( ! preg_match( '/^[1-9][0-9]{0,17}$/D', $id ) || ( '' !== $secret && ! preg_match( '/^[a-f0-9]{64}$/D', $secret ) ) ) {
		// Shown once on the next view of the page.
		set_transient( 'stats4u_shop_invalid_' . get_current_user_id(), 1, MINUTE_IN_SECONDS );
	} else {
		if ( '' === $secret ) {
			$secret = $cfg['secret'];
		}
		update_option(
			'stats4u_shop',
			array(
				'enabled' => $enabled && '' !== $secret,
				'id'      => $id,
				'secret'  => $secret,
			),
			false
		);
	}
	wp_safe_redirect( stats4u_shop_seite_url() );
	exit;
}

add_action( 'admin_post_stats4u_shop_retry', 'stats4u_shop_wiederholen' );
/**
 * "Retry failed reports": sends every failed order once more, right now.
 */
function stats4u_shop_wiederholen() {
	stats4u_shop_nur_admin();
	check_admin_referer( 'stats4u-shop' );

	foreach ( array_keys( (array) get_option( 'stats4u_shop_error', array() ) ) as $oid ) {
		$oid   = absint( $oid );
		$order = ( $oid && function_exists( 'wc_get_order' ) ) ? wc_get_order( $oid ) : false;
		if ( $order ) {
			$order->delete_meta_data( STATS4U_SHOP_META_VERSUCHE );
			$order->delete_meta_data( STATS4U_SHOP_META_VERSUCHE_ALT );
			$order->save_meta_data();
			stats4u_shop_report( $oid );
		}
	}
	wp_safe_redirect( stats4u_shop_seite_url() );
	exit;
}

add_action( 'admin_post_stats4u_shop_clear_skipped', 'stats4u_shop_liste_leeren' );
/**
 * Clears the list of orders that cannot be sent (unsupported currency or amount).
 */
function stats4u_shop_liste_leeren() {
	stats4u_shop_nur_admin();
	check_admin_referer( 'stats4u-shop' );
	delete_option( 'stats4u_shop_skipped' );
	wp_safe_redirect( stats4u_shop_seite_url() );
	exit;
}

/**
 * Prints the start of a form that posts to admin-post.php.
 *
 * @param string $aktion admin-post action.
 */
function stats4u_shop_formular_start( $aktion ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="' . esc_attr( $aktion ) . '">';
	wp_nonce_field( 'stats4u-shop' );
}

/**
 * The shop settings page.
 */
function stats4u_shop_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$cfg       = stats4u_shop_settings();
	$ungueltig = (bool) get_transient( 'stats4u_shop_invalid_' . get_current_user_id() );
	if ( $ungueltig ) {
		delete_transient( 'stats4u_shop_invalid_' . get_current_user_id() );
	}
	$fehler      = array_filter( (array) get_option( 'stats4u_shop_error', array() ) );
	$uebersprung = (array) get_option( 'stats4u_shop_skipped', array() );
	unset( $fehler[0], $uebersprung[0] );
	$wc     = class_exists( 'WooCommerce' );
	$secret = '' !== (string) $cfg['secret'];

	if ( $cfg['enabled'] && $wc ) {
		$pill = array( $fehler ? 'warn' : 'ok', $fehler ? 'warning' : 'yes-alt', $fehler ? __( 'Reporting on, needs attention', 'stats4u' ) : __( 'Reporting on', 'stats4u' ) );
	} elseif ( $cfg['enabled'] ) {
		$pill = array( 'warn', 'warning', __( 'Waiting for WooCommerce', 'stats4u' ) );
	} else {
		$pill = array( 'aus', 'controls-pause', __( 'Reporting off', 'stats4u' ) );
	}

	echo '<div class="wrap s4u-shop"><div class="s4u-shop-kopf"><h1>' . esc_html__( 'Stats4U shop', 'stats4u' ) . '</h1>';
	echo '<span class="s4u-pille s4u-pille-' . esc_attr( $pill[0] ) . '"><span class="dashicons dashicons-' . esc_attr( $pill[1] ) . '" aria-hidden="true"></span>' . esc_html( $pill[2] ) . '</span></div>';
	echo '<hr class="wp-header-end">';
	if ( $ungueltig ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Check the counter number and shop secret.', 'stats4u' ) . '</p></div>';
	}
	if ( ! $wc ) {
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'WooCommerce is required for shop reporting.', 'stats4u' ) . '</p></div>';
	}
	if ( $fehler ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'A shop report failed. Stats4U retries automatically; check your settings and the server connection.', 'stats4u' ) . '</p></div>';
	}

	// Kennzahlen: nur aus den gespeicherten Optionen, keine Anfrage.
	$kacheln = array(
		array( $cfg['enabled'] ? 'ok' : 'aus', 'chart-line', __( 'Reporting', 'stats4u' ), $cfg['enabled'] ? __( 'On', 'stats4u' ) : __( 'Off', 'stats4u' ) ),
		array( $secret ? 'ok' : 'warn', 'lock', __( 'Shop secret', 'stats4u' ), $secret ? __( 'Saved', 'stats4u' ) : __( 'Missing', 'stats4u' ) ),
		array( $fehler ? 'fehler' : 'ok', 'update', __( 'Failed reports', 'stats4u' ), number_format_i18n( count( $fehler ) ) ),
		array( $uebersprung ? 'warn' : 'ok', 'hidden', __( 'Not sent', 'stats4u' ), number_format_i18n( count( $uebersprung ) ) ),
	);
	echo '<div class="s4u-kacheln">';
	foreach ( $kacheln as $k ) {
		echo '<div class="s4u-kachel s4u-kachel-' . esc_attr( $k[0] ) . '"><span class="dashicons dashicons-' . esc_attr( $k[1] ) . '" aria-hidden="true"></span><span class="s4u-kachel-name">' . esc_html( $k[2] ) . '</span><strong>' . esc_html( $k[3] ) . '</strong></div>';
	}
	echo '</div>';

	echo '<div class="s4u-shop-raster"><div class="s4u-shop-spalte">';

	echo '<section class="s4u-karte"><h2>' . esc_html__( 'Settings', 'stats4u' ) . '</h2><div class="s4u-karte-innen">';
	echo '<p>' . esc_html__( 'Enable shop reporting in your Stats4U owner link, create a shop secret and enter it here. When reporting is on, your server sends confirmed payments and refunds to stats4u.net under your counter number: paid and refunded amount, currency, payment time and a keyed (pseudonymous) order reference. No customer details and no product details are sent.', 'stats4u' ) . '</p>';
	stats4u_shop_formular_start( 'stats4u_shop_save' );
	echo '<table class="form-table" role="presentation"><tbody>';
	echo '<tr><th scope="row">' . esc_html__( 'Shop reporting', 'stats4u' ) . '</th><td><label class="s4u-schalter" for="stats4u_enabled"><input type="checkbox" id="stats4u_enabled" name="stats4u_enabled" value="1" ' . checked( $cfg['enabled'], true, false ) . '><span class="s4u-schalter-bahn" aria-hidden="true"></span><span>' . esc_html__( 'Enable shop reporting', 'stats4u' ) . '</span></label></td></tr>';
	echo '<tr><th scope="row"><label for="stats4u_shop_id">' . esc_html__( 'Counter number', 'stats4u' ) . '</label></th><td><input type="text" id="stats4u_shop_id" class="regular-text" name="stats4u_id" inputmode="numeric" pattern="[1-9][0-9]{0,17}" value="' . esc_attr( $cfg['id'] ) . '" required></td></tr>';
	echo '<tr><th scope="row"><label for="stats4u_shop_secret">' . esc_html__( 'Shop secret', 'stats4u' ) . '</label></th><td><input type="password" id="stats4u_shop_secret" class="regular-text code" name="stats4u_secret" value="" autocomplete="new-password" maxlength="64" aria-describedby="stats4u_shop_secret_hint"' . ( $secret ? ' placeholder="&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;&#8226;"' : '' ) . '>';
	if ( $secret ) {
		echo ' <span class="s4u-pille s4u-pille-ok s4u-pille-klein"><span class="dashicons dashicons-lock" aria-hidden="true"></span>' . esc_html__( 'Saved', 'stats4u' ) . '</span>';
	}
	echo '<p class="description" id="stats4u_shop_secret_hint">' . esc_html__( 'Leave the secret blank to keep the existing one.', 'stats4u' ) . '</p></td></tr>';
	echo '</tbody></table>';
	submit_button();
	echo '</form></div></section>';

	if ( $fehler ) {
		echo '<section class="s4u-karte"><h2>' . esc_html__( 'Failed reports', 'stats4u' ) . '</h2><div class="s4u-karte-innen">';
		stats4u_shop_tabelle( array_fill_keys( array_keys( $fehler ), 'failed' ) );
		stats4u_shop_formular_start( 'stats4u_shop_retry' );
		echo '<p><button class="button" type="submit"><span class="dashicons dashicons-update" aria-hidden="true"></span>' . esc_html__( 'Retry failed reports', 'stats4u' ) . '</button></p></form></div></section>';
	}

	if ( $uebersprung ) {
		echo '<section class="s4u-karte"><h2>' . esc_html__( 'Not sent', 'stats4u' ) . '</h2><div class="s4u-karte-innen"><p>';
		echo esc_html(
			sprintf(
				/* translators: %s: list of currency codes */
				__( 'These orders were not sent and are not retried: their currency is not supported or their amount could not be read. Stats4U accepts %s.', 'stats4u' ),
				implode( ', ', STATS4U_SHOP_WAEHRUNGEN )
			)
		);
		echo '</p>';
		stats4u_shop_tabelle( $uebersprung );
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-s4u-bestaetigen="' . esc_attr__( 'Clear the list of orders that were not sent? The orders themselves stay in WooCommerce; they are not sent to Stats4U.', 'stats4u' ) . '" data-s4u-knopf="' . esc_attr__( 'Clear this list', 'stats4u' ) . '">';
		echo '<input type="hidden" name="action" value="stats4u_shop_clear_skipped">';
		wp_nonce_field( 'stats4u-shop' );
		echo '<p><button class="button" type="submit"><span class="dashicons dashicons-trash" aria-hidden="true"></span>' . esc_html__( 'Clear this list', 'stats4u' ) . '</button></p></form></div></section>';
	}

	echo '</div><div class="s4u-shop-spalte">';
	stats4u_shop_fluss();
	echo '<p class="s4u-shop-link"><a href="https://www.stats4u.net/guide/wordpress"><span class="dashicons dashicons-book" aria-hidden="true"></span>' . esc_html__( 'Integration guide', 'stats4u' ) . '</a></p>';
	echo '</div></div></div>';
}

/**
 * Orders as a table: number (linked to the order with WooCommerce) and reason.
 *
 * @param array $liste Order ID => 'failed', 'currency' or 'amount'.
 */
function stats4u_shop_tabelle( $liste ) {
	$gruende = array(
		'failed'   => array( 'fehler', __( 'report failed, retried automatically', 'stats4u' ) ),
		'currency' => array( 'warn', __( 'currency not supported', 'stats4u' ) ),
		'amount'   => array( 'warn', __( 'amount could not be read', 'stats4u' ) ),
	);
	echo '<table class="widefat striped s4u-shop-tabelle"><thead><tr><th scope="col">' . esc_html__( 'Order', 'stats4u' ) . '</th><th scope="col">' . esc_html__( 'Reason', 'stats4u' ) . '</th></tr></thead><tbody>';
	foreach ( $liste as $oid => $grund ) {
		$oid   = absint( $oid );
		$g     = isset( $gruende[ $grund ] ) ? $gruende[ $grund ] : $gruende['amount'];
		$order = ( $oid && function_exists( 'wc_get_order' ) ) ? wc_get_order( $oid ) : false;
		$nr    = '#' . ( $order ? $order->get_order_number() : $oid );
		echo '<tr><td>' . ( $order ? '<a href="' . esc_url( $order->get_edit_order_url() ) . '">' . esc_html( $nr ) . '</a>' : esc_html( $nr ) ) . '</td>';
		echo '<td><span class="s4u-pille s4u-pille-klein s4u-pille-' . esc_attr( $g[0] ) . '">' . esc_html( $g[1] ) . '</span></td></tr>';
	}
	echo '</tbody></table>';
}

/**
 * What a shop report contains, as a small diagram - the same fields as in
 * stats4u_shop_report() and the readme. Fixed markup, nothing from outside.
 */
function stats4u_shop_fluss() {
	$felder = array(
		__( 'Paid amount', 'stats4u' ),
		__( 'Refunded amount', 'stats4u' ),
		__( 'Currency', 'stats4u' ),
		__( 'Payment time', 'stats4u' ),
		__( 'Keyed order reference (pseudonymous)', 'stats4u' ),
	);
	echo '<section class="s4u-karte s4u-fluss"><h2>' . esc_html__( 'What a report contains', 'stats4u' ) . '</h2><div class="s4u-karte-innen">';
	echo '<ol class="s4u-fluss-schritte">';
	echo '<li><span class="dashicons dashicons-cart" aria-hidden="true"></span><strong>' . esc_html__( 'Confirmed payment or refund', 'stats4u' ) . '</strong><span>' . esc_html__( 'in WooCommerce, only with reporting switched on', 'stats4u' ) . '</span></li>';
	echo '<li><span class="dashicons dashicons-shield" aria-hidden="true"></span><strong>' . esc_html__( 'Your server signs the report', 'stats4u' ) . '</strong><span>' . esc_html__( 'with the shop secret', 'stats4u' ) . '</span>';
	echo '<ul class="s4u-fluss-felder">';
	foreach ( $felder as $f ) {
		echo '<li><span class="dashicons dashicons-yes" aria-hidden="true"></span>' . esc_html( $f ) . '</li>';
	}
	echo '</ul></li>';
	echo '<li><span class="dashicons dashicons-chart-bar" aria-hidden="true"></span><strong>stats4u.net</strong><span>' . esc_html__( 'under your counter number', 'stats4u' ) . '</span></li>';
	echo '</ol>';
	echo '<p class="s4u-fluss-nie"><span class="dashicons dashicons-dismiss" aria-hidden="true"></span>' . esc_html__( 'Never sent: customer details and product details.', 'stats4u' ) . '</p>';
	echo '</div></section>';
}

add_action( 'admin_enqueue_scripts', 'stats4u_shop_assets' );
/**
 * Styles of the shop page and the script for its confirmation dialog.
 *
 * @param string $seite Current admin page.
 */
function stats4u_shop_assets( $seite ) {
	if ( 'settings_page_stats4u-shop' !== $seite ) {
		return;
	}
	wp_enqueue_style( 'stats4u-shop', plugins_url( 'shop.css', __FILE__ ), array( 'dashicons' ), STATS4U_VERSION );
	wp_enqueue_script( 'stats4u-admin', plugins_url( 'admin.js', __FILE__ ), array(), STATS4U_VERSION, true );
	wp_add_inline_script( 'stats4u-admin', 'window.stats4uDialog=' . wp_json_encode( array( 'abbrechen' => __( 'Cancel', 'stats4u' ) ), JSON_HEX_TAG | JSON_HEX_AMP ) . ';', 'before' );
}

/**
 * An amount as minor currency units (cents), or null if it cannot be read.
 *
 * @param mixed $amount Amount from WooCommerce.
 * @return int|null
 */
function stats4u_shop_cents( $amount ) {
	$s = wc_format_decimal( $amount, 2 );
	if ( ! preg_match( '/^([0-9]{1,10})\.([0-9]{2})$/D', $s, $m ) ) {
		return null;
	}
	$n = (int) $m[1] * 100 + (int) $m[2];
	return $n <= 100000000000 ? $n : null;
}

/**
 * Marks an order as failed (retryable) or removes the mark.
 *
 * @param int  $id     Order ID.
 * @param bool $failed True to mark, false to remove.
 */
function stats4u_shop_error( $id, $failed ) {
	$a = (array) get_option( 'stats4u_shop_error', array() );
	unset( $a[0] );
	if ( $failed ) {
		$a[ (int) $id ] = true;
	} else {
		unset( $a[ (int) $id ] );
	}
	if ( count( $a ) > 100 ) {
		$a = array_slice( $a, -100, null, true );
	}
	if ( $a ) {
		update_option( 'stats4u_shop_error', $a, false );
	} else {
		delete_option( 'stats4u_shop_error' );
	}
}

/**
 * Records an order that can never be sent (currency or amount): listed on the
 * shop settings page, not retried and not counted as a failed report.
 *
 * @param int    $id    Order ID.
 * @param string $grund 'currency' or 'amount'.
 */
function stats4u_shop_skip( $id, $grund ) {
	stats4u_shop_error( $id, false );
	wp_clear_scheduled_hook( 'stats4u_shop_retry', array( (int) $id ) );
	$a = (array) get_option( 'stats4u_shop_skipped', array() );
	unset( $a[0] );
	$a[ (int) $id ] = ( 'currency' === $grund ) ? 'currency' : 'amount';
	if ( count( $a ) > 100 ) {
		$a = array_slice( $a, -100, null, true );
	}
	update_option( 'stats4u_shop_skipped', $a, false );
}

/**
 * Sends one order to Stats4U (only with shop reporting switched on).
 *
 * Called by the WooCommerce hooks below, by WP-Cron retries and by
 * "Retry failed reports".
 *
 * @param int $order_id Order ID.
 * @param int $attempt  Retry number (from WP-Cron).
 */
function stats4u_shop_report( $order_id, $attempt = 0 ) {
	$order_id = absint( $order_id );
	$cfg      = stats4u_shop_settings();
	if ( ! $cfg['enabled'] || ! $order_id || ! function_exists( 'wc_get_order' ) ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order || ! $order->get_date_paid() ) {
		return;
	}
	$paid_at = $order->get_date_paid()->getTimestamp();
	if ( $paid_at < time() - 400 * DAY_IN_SECONDS ) {
		return;
	}
	$currency = $order->get_currency();
	if ( ! in_array( $currency, STATS4U_SHOP_WAEHRUNGEN, true ) ) {
		stats4u_shop_skip( $order_id, 'currency' );
		return;
	}
	$paid     = stats4u_shop_cents( $order->get_total() );
	$refunded = stats4u_shop_cents( $order->get_total_refunded() );
	if ( null === $paid || null === $refunded || $paid <= 0 || $refunded > $paid ) {
		stats4u_shop_skip( $order_id, 'amount' );
		return;
	}

	$reference = (string) $order->get_meta( STATS4U_SHOP_META_REFERENZ );
	if ( ! preg_match( '/^[a-f0-9]{64}$/D', $reference ) ) {
		$reference = (string) $order->get_meta( STATS4U_SHOP_META_REFERENZ_ALT );
		if ( ! preg_match( '/^[a-f0-9]{64}$/D', $reference ) ) {
			// Deterministic reference survives secret rotation, concurrent hooks and retries.
			$reference = hash_hmac( 'sha256', home_url( '/' ) . '|' . $order->get_id(), wp_salt( 'auth' ) );
		}
		$order->update_meta_data( STATS4U_SHOP_META_REFERENZ, $reference );
		$order->delete_meta_data( STATS4U_SHOP_META_REFERENZ_ALT );
		$order->save_meta_data();
	}

	$body      = wp_json_encode(
		array(
			'order'    => $reference,
			'paid'     => $paid,
			'refunded' => $refunded,
			'currency' => $currency,
			'paid_at'  => $paid_at,
		)
	);
	$stamp     = time();
	$signature = hash_hmac( 'sha256', $stamp . '.' . $body, $cfg['secret'] );
	$response  = wp_remote_post(
		STATS4U_SHOP_ZIEL . '&s4uid=' . rawurlencode( $cfg['id'] ),
		array(
			'timeout'     => 10,
			'redirection' => 0,
			'headers'     => array(
				'Content-Type'    => 'application/json',
				'X-S4U-Timestamp' => (string) $stamp,
				'X-S4U-Signature' => $signature,
			),
			'body'        => $body,
		)
	);

	$alte_versuche = (int) $order->get_meta( STATS4U_SHOP_META_VERSUCHE_ALT );
	if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
		stats4u_shop_error( $order_id, false );
		wp_clear_scheduled_hook( 'stats4u_shop_retry', array( $order_id ) );
		$order->delete_meta_data( STATS4U_SHOP_META_VERSUCHE );
		$order->delete_meta_data( STATS4U_SHOP_META_VERSUCHE_ALT );
		$order->save_meta_data();
		return;
	}

	stats4u_shop_error( $order_id, true );
	$count = max( (int) $attempt, (int) $order->get_meta( STATS4U_SHOP_META_VERSUCHE ), $alte_versuche ) + 1;
	$order->update_meta_data( STATS4U_SHOP_META_VERSUCHE, $count );
	$order->delete_meta_data( STATS4U_SHOP_META_VERSUCHE_ALT );
	$order->save_meta_data();
	if ( $count <= 8 && ! wp_next_scheduled( 'stats4u_shop_retry', array( $order_id ) ) ) {
		wp_schedule_single_event( time() + min( 21600, 60 * ( 2 ** $count ) ), 'stats4u_shop_retry', array( $order_id ) );
	}
}
add_action( 'woocommerce_payment_complete', 'stats4u_shop_report', 20, 1 );
add_action( 'woocommerce_order_status_processing', 'stats4u_shop_report', 20, 1 );
add_action( 'woocommerce_order_status_completed', 'stats4u_shop_report', 20, 1 );
add_action( 'woocommerce_order_refunded', 'stats4u_shop_report', 20, 1 );
add_action( 'stats4u_shop_retry', 'stats4u_shop_report', 10, 1 );

/**
 * Fingerprint of the current failures - a hidden notice comes back only
 * when another order fails.
 *
 * @return string Empty if nothing failed.
 */
function stats4u_shop_fehler_stand() {
	$a = array_keys( (array) get_option( 'stats4u_shop_error', array() ) );
	if ( ! $a ) {
		return '';
	}
	sort( $a );
	return md5( implode( ',', $a ) );
}

add_action( 'admin_notices', 'stats4u_shop_hinweis' );
/**
 * Failed reports: a notice for administrators that can be hidden, shown only
 * on the Dashboard, the plugin list, the Stats4U settings page and the
 * WooCommerce order screens. The shop settings page has its own message.
 */
function stats4u_shop_hinweis() {
	if ( ! current_user_can( 'manage_options' ) || ! function_exists( 'get_current_screen' ) ) {
		return;
	}
	$screen = get_current_screen();
	$seiten = array( 'dashboard', 'plugins', 'settings_page_stats4u', 'edit-shop_order', 'shop_order', 'woocommerce_page_wc-orders' );
	if ( ! $screen || ! in_array( $screen->id, $seiten, true ) ) {
		return;
	}
	$stand = stats4u_shop_fehler_stand();
	if ( '' === $stand || get_user_meta( get_current_user_id(), 'stats4u_shop_notice', true ) === $stand ) {
		return;
	}
	$ausblenden = wp_nonce_url( admin_url( 'admin-post.php?action=stats4u_shop_dismiss' ), 'stats4u_shop_dismiss' );
	echo '<div class="notice notice-error"><p><a href="' . esc_url( stats4u_shop_seite_url() ) . '">' . esc_html__( 'Stats4U: shop reporting needs attention.', 'stats4u' ) . '</a>'
		. ' &middot; <a href="' . esc_url( $ausblenden ) . '">' . esc_html__( 'Hide this notice', 'stats4u' ) . '</a></p></div>';
}

add_action( 'admin_post_stats4u_shop_dismiss', 'stats4u_shop_hinweis_aus' );
/**
 * Hides the notice for the current user until another order fails.
 */
function stats4u_shop_hinweis_aus() {
	stats4u_shop_nur_admin();
	check_admin_referer( 'stats4u_shop_dismiss' );
	update_user_meta( get_current_user_id(), 'stats4u_shop_notice', stats4u_shop_fehler_stand() );
	$zurueck = wp_get_referer();
	wp_safe_redirect( $zurueck ? $zurueck : admin_url() );
	exit;
}
