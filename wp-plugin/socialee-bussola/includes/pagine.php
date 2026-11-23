<?php
/**
 * Le due pagine della Bussola, servite dal plugin allo stesso indirizzo anche senza una pagina WordPress:
 *   /bussola-team/            quiz, libero per chiunque abbia il link (non è nel menu, non è su Google)
 *   /bussola-team/dashboard/  aggregati anonimi (permesso socialee_bussola_admin)
 * Documento HTML proprio, senza wp_head/wp_footer: su queste pagine non arrivano statistiche, pixel, cookie banner
 * né script di altri plugin. Intestazioni: noindex, niente cache, CSP con nonce (nessuna richiesta verso l'esterno
 * a parte i caratteri del sito su Google Fonts).
 */

defined( 'ABSPATH' ) || exit;

const SCB_BASE = '/bussola-team/';

/** Percorso richiesto, senza la cartella di WordPress se il sito ne ha una. */
function scb_percorso_richiesto() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( $home && '/' !== $home && 0 === strpos( $path, rtrim( $home, '/' ) . '/' ) ) {
		$path = substr( $path, strlen( rtrim( $home, '/' ) ) );
	}
	return '/' . ltrim( $path, '/' );
}

/** 'quiz', 'dashboard', 'barra' (manca la barra finale) o null se la richiesta non è nostra. */
function scb_quale_pagina() {
	$p = strtolower( scb_percorso_richiesto() );
	if ( SCB_BASE === $p ) {
		return 'quiz';
	}
	if ( SCB_BASE . 'dashboard/' === $p ) {
		return 'dashboard';
	}
	if ( SCB_IMP_PATH === $p ) {
		return 'imprenditori';
	}
	if ( rtrim( SCB_BASE, '/' ) === $p || SCB_BASE . 'dashboard' === $p || rtrim( SCB_IMP_PATH, '/' ) === $p ) {
		return 'barra';
	}
	return null;
}

function scb_nonce() {
	static $n = null;
	if ( null === $n ) {
		$n = base64_encode( random_bytes( 18 ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}
	return $n;
}

function scb_intestazioni( $stato, $pubblica = false ) {
	status_header( $stato );
	nocache_headers();
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true ); // WP-Optimize, W3TC, WP Super Cache
	}
	do_action( 'litespeed_control_set_nocache', 'socialee bussola' );
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private' );
	if ( $pubblica ) { // pagina da trovare su Google (quiz per imprenditori)
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	} else {
		header( 'X-Robots-Tag: noindex, nofollow, noarchive, nosnippet' );
		header( 'Referrer-Policy: no-referrer' );
	}
	header( 'X-Frame-Options: DENY' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=()' );
	$n = scb_nonce();
	header(
		"Content-Security-Policy: default-src 'none'; script-src 'nonce-{$n}'; style-src 'nonce-{$n}' https://fonts.googleapis.com; " .
		"font-src https://fonts.gstatic.com; img-src 'self' https://socialee.it data:; connect-src " . ( $pubblica ? "'self'" : "'none'" ) . '; ' .
		"form-action 'self'; base-uri 'none'; frame-ancestors 'none'"
	);
}

add_action(
	'template_redirect',
	function () {
		$quale = scb_quale_pagina();
		if ( null === $quale ) {
			return;
		}
		if ( 'barra' === $quale ) {
			wp_safe_redirect( home_url( scb_percorso_richiesto() . '/' ), 301 );
			exit;
		}
		require_once SCB_DIR . 'vista/guscio.php';
		if ( 'imprenditori' === $quale ) { // quiz per imprenditori: pubblico e indicizzabile
			scb_intestazioni( 200, true );
			require SCB_DIR . 'vista/imprenditori.php';
			exit;
		}
		if ( 'quiz' === $quale ) { // il quiz è libero: chiunque abbia il link (decisione della titolare, 23/11/2026)
			scb_intestazioni( 200 );
			require SCB_DIR . 'vista/quiz.php';
			exit;
		}
		if ( ! is_user_logged_in() ) {
			scb_intestazioni( 401 );
			require SCB_DIR . 'vista/accedi.php';
			exit;
		}
		if ( ! scb_puo_vedere_aggregati() ) {
			scb_intestazioni( 403 );
			require SCB_DIR . 'vista/negato.php';
			exit;
		}
		scb_intestazioni( 200 );
		require SCB_DIR . 'vista/dashboard.php';
		exit;
	},
	0
);

/** Le pagine della Bussola non finiscono nella sitemap di Rank Math anche se un giorno esistesse una pagina con lo stesso slug. */
add_filter(
	'rank_math/sitemap/entry',
	function ( $url ) {
		return ( is_array( $url ) && isset( $url['loc'] ) && false !== strpos( (string) $url['loc'], SCB_BASE ) ) ? false : $url;
	}
);
