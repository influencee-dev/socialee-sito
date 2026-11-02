<?php
/**
 * Intestazione e piè di pagina di Socialee (shell/header.html e shell/footer.html del plugin Socialee Pagine).
 * - Sulle pagine del plugin: sempre.
 * - Sul resto del sito (articoli, academy, archivi, 404): quando Elementor Pro non c'è più (impostazione «auto»),
 *   oppure sempre («on»). Il tema Hello non stampa più la sua intestazione.
 */

defined( 'ABSPATH' ) || exit;

function scl_shell_attiva() {
	if ( is_admin() ) {
		return false;
	}
	$m = scl_settings()['shell'];
	if ( 'off' === $m ) {
		return false;
	}
	return 'on' === $m || ! defined( 'ELEMENTOR_PRO_VERSION' );
}

function scl_shell_part( $name ) {
	$src  = scl_source();
	$file = $src ? $src['dir'] . 'shell/' . $name . '.html' : '';
	return ( $file && is_readable( $file ) ) ? (string) file_get_contents( $file ) : '';
}

function scl_shell_qui() {
	return scl_current_page() || scl_shell_attiva();
}

add_filter(
	'hello_elementor_header_footer',
	function ( $show ) {
		return scl_shell_qui() ? false : $show;
	}
);

add_action(
	'wp_body_open',
	function () {
		static $fatto = false;
		if ( $fatto || ! scl_shell_qui() ) {
			return;
		}
		$fatto = true;
		echo scl_shell_part( 'header' ); // phpcs:ignore WordPress.Security.EscapeOutput
	},
	1
);

add_action(
	'wp_footer',
	function () {
		static $fatto = false;
		if ( $fatto || ! scl_shell_qui() ) {
			return;
		}
		$fatto = true;
		echo scl_shell_part( 'footer' ); // phpcs:ignore WordPress.Security.EscapeOutput
	},
	0
);

/** Caratteri del sito (gli stessi di oggi). */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( scl_shell_qui() ) {
			wp_enqueue_style( 'socialee-font', 'https://fonts.googleapis.com/css2?family=Archivo+Black&family=Inter:wght@400;500;600;700&display=swap', array(), null );
		}
	},
	5
);
