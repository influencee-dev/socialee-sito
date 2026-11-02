<?php
/**
 * Plugin Name:       Socialee Pagine
 * Description:       Le pagine di socialee.it in HTML5 senza Elementor (stessi testi e stesso aspetto di oggi), con intestazione, piè di pagina e CSS del sito. Le serve Socialee Core con lo stesso URL.
 * Version:           1.0.0
 * Author:            Influencee SRL (Socialee)
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  socialee-core
 * Text Domain:       socialee-pagine
 */

defined( 'ABSPATH' ) || exit;

define( 'SCLP_VERSION', '1.0.0' );

add_filter(
	'scl_page_sources',
	function ( $sources ) {
		$sources[] = array( 'dir' => plugin_dir_path( __FILE__ ), 'url' => plugin_dir_url( __FILE__ ), 'version' => SCLP_VERSION );
		return $sources;
	}
);

add_action(
	'admin_notices',
	function () {
		if ( ! function_exists( 'scl_source' ) && current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-error"><p><b>Socialee Pagine</b> ha bisogno del plugin <b>Socialee Core</b>.</p></div>';
		}
	}
);
