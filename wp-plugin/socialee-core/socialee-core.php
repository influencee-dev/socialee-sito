<?php
/**
 * Plugin Name:       Socialee Core
 * Description:       Il sito socialee.it senza Elementor: serve le pagine del plugin Socialee Pagine con lo stesso URL e un HTML5 pulito, mette intestazione e piè di pagina di Socialee su tutto il sito, dà a Claude un accesso con chiave propria (MCP e caricamento dei plugin Socialee). SEO, cookie banner, pixel e moduli restano dei plugin di oggi (Rank Math, iubenda, tandem-forms).
 * Version:           1.0.1
 * Author:            Influencee SRL (Socialee)
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Text Domain:       socialee-core
 */

defined( 'ABSPATH' ) || exit;

define( 'SCL_VERSION', '1.0.1' );
define( 'SCL_DIR', plugin_dir_path( __FILE__ ) );
define( 'SCL_URL', plugin_dir_url( __FILE__ ) );

/** Impostazioni: pagine del plugin accese, percorsi da lasciare a WordPress, intestazione/piè di pagina su tutto il sito. */
function scl_settings() {
	$s = get_option( 'scl_settings', array() );
	return wp_parse_args( is_array( $s ) ? $s : array(), array( 'pagine' => 1, 'off' => array(), 'shell' => 'auto' ) );
}

function scl_log( $cosa, $dettagli = '' ) {
	$log   = (array) get_option( 'scl_log', array() );
	$log[] = array( 'quando' => current_time( 'mysql' ), 'cosa' => (string) $cosa, 'dettagli' => mb_substr( (string) $dettagli, 0, 300 ) );
	update_option( 'scl_log', array_slice( $log, -100 ), false );
}

/** Svuota le cache presenti sul sito (Autoptimize, WP-Optimize, LiteSpeed, cache di WordPress). */
function scl_svuota_cache() {
	$fatte = array();
	if ( class_exists( 'autoptimizeCache' ) && method_exists( 'autoptimizeCache', 'clearall' ) ) {
		autoptimizeCache::clearall();
		$fatte[] = 'autoptimize';
	}
	if ( function_exists( 'WP_Optimize' ) ) {
		$wpo = WP_Optimize();
		if ( method_exists( $wpo, 'get_page_cache' ) && $wpo->get_page_cache() && method_exists( $wpo->get_page_cache(), 'purge' ) ) {
			$wpo->get_page_cache()->purge();
			$fatte[] = 'wp-optimize';
		}
	}
	if ( has_action( 'litespeed_purge_all' ) ) {
		do_action( 'litespeed_purge_all' );
		$fatte[] = 'litespeed';
	}
	wp_cache_flush();
	$fatte[] = 'wordpress';
	return $fatte;
}

require_once SCL_DIR . 'includes/pagine.php';
require_once SCL_DIR . 'includes/shell.php';
require_once SCL_DIR . 'includes/accesso.php';
require_once SCL_DIR . 'includes/admin.php';
require_once SCL_DIR . 'includes/contatto.php';
