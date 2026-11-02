<?php
/**
 * Accesso di Claude al sito, senza plugin esterni e senza password per le applicazioni.
 *
 * - MCP (JSON-RPC 2.0 su HTTP):  POST /wp-json/socialee-sito/v1/mcp   (GET = elenco strumenti)
 * - Zip di un plugin Socialee:   POST /wp-json/socialee-sito/v1/plugin?attiva=1  (corpo = zip, intestazione X-Sha256)
 * - Chiave: intestazione X-Socialee-Key, creata in Socialee Sito → Impostazioni (mostrata una volta, si salva solo
 *   l'impronta). 20 chiavi sbagliate dallo stesso indirizzo = 10 minuti di blocco. Le azioni girano come
 *   l'amministratore che ha creato la chiave e finiscono nel registro.
 */

defined( 'ABSPATH' ) || exit;

const SCL_HEADER = 'X-Socialee-Key';
const SCL_NS     = 'socialee-sito/v1';

function scl_key_hash( $key ) {
	return hash_hmac( 'sha256', (string) $key, wp_salt( 'auth' ) );
}

function scl_key_ok( WP_REST_Request $req ) {
	$key  = (string) $req->get_header( 'x_socialee_key' );
	$hash = (string) get_option( 'scl_key_hash', '' );
	if ( '' === $key || '' === $hash ) {
		return false;
	}
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	$lock = 'scl_key_' . md5( $ip );
	if ( (int) get_transient( $lock ) >= 20 ) {
		return false;
	}
	if ( ! hash_equals( $hash, scl_key_hash( $key ) ) ) {
		set_transient( $lock, (int) get_transient( $lock ) + 1, 10 * MINUTE_IN_SECONDS );
		return false;
	}
	$user = (int) get_option( 'scl_key_user', 0 );
	if ( ! $user || ! user_can( $user, 'manage_options' ) ) {
		return false;
	}
	wp_set_current_user( $user );
	return true;
}

function scl_permesso( WP_REST_Request $req ) {
	if ( scl_key_ok( $req ) || current_user_can( 'manage_options' ) ) {
		return true;
	}
	return new WP_Error( 'scl_chiave', 'Serve la chiave di Socialee (intestazione ' . SCL_HEADER . ') o un amministratore.', array( 'status' => 401 ) );
}

function scl_nocache() {
	nocache_headers();
	do_action( 'litespeed_control_set_nocache', 'socialee mcp' );
}

/* ─── Strumenti ─── */

function scl_plugin_famiglia() {
	return array( 'socialee-core', 'socialee-pagine', 'socialee-aggiornamenti' );
}

function scl_stato() {
	global $wp_version;
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$fam = array();
	foreach ( get_plugins() as $file => $p ) {
		$slug = dirname( $file );
		if ( in_array( $slug, scl_plugin_famiglia(), true ) || preg_match( '/elementor|rank-math|autoptimize|optimize|iubenda|tandem|brevo|mailin/i', $file ) ) {
			$fam[ $file ] = $p['Version'] . ( is_plugin_active( $file ) ? '' : ' (spento)' );
		}
	}
	$pages = scl_pages();
	return array(
		'wordpress'    => $wp_version,
		'php'          => PHP_VERSION,
		'tema'         => get_stylesheet(),
		'core'         => SCL_VERSION,
		'plugin'       => $fam,
		'impostazioni' => scl_settings(),
		'pagine'       => count( $pages ),
		'shell_su_tutto_il_sito' => scl_shell_attiva(),
		'elementor_pro' => defined( 'ELEMENTOR_PRO_VERSION' ),
	);
}

function scl_pagine_elenco() {
	$s   = scl_settings();
	$out = array();
	foreach ( scl_pages() as $path => $p ) {
		$wp    = get_page_by_path( trim( $path, '/' ) );
		$out[] = array( 'path' => $path, 'attiva' => ! empty( $s['pagine'] ) && ! in_array( $path, (array) $s['off'], true ), 'pagina_wp' => $wp ? $wp->post_status : ( '/' === $path ? 'home' : 'manca' ) );
	}
	return $out;
}

function scl_imposta( $in ) {
	if ( empty( $in['conferma'] ) ) {
		return new WP_Error( 'scl_conferma', 'Serve conferma: true.' );
	}
	$s = scl_settings();
	$v = isset( $in['valori'] ) && is_array( $in['valori'] ) ? $in['valori'] : array();
	if ( array_key_exists( 'pagine', $v ) ) {
		$s['pagine'] = $v['pagine'] ? 1 : 0;
	}
	if ( array_key_exists( 'off', $v ) ) {
		$known    = array_keys( scl_pages() );
		$s['off'] = array_values( array_intersect( (array) $v['off'], $known ) );
	}
	if ( array_key_exists( 'shell', $v ) ) {
		if ( ! in_array( $v['shell'], array( 'auto', 'on', 'off' ), true ) ) {
			return new WP_Error( 'scl_valore', 'shell accetta solo auto, on, off.' );
		}
		$s['shell'] = $v['shell'];
	}
	update_option( 'scl_settings', $s );
	return array( 'impostazioni' => $s, 'cache' => scl_svuota_cache() );
}

function scl_plugin_elenco() {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$out = array();
	foreach ( get_plugins() as $file => $p ) {
		$out[] = array( 'file' => $file, 'nome' => $p['Name'], 'versione' => $p['Version'], 'attivo' => is_plugin_active( $file ) );
	}
	return array( 'plugin' => $out, 'tema' => get_stylesheet() );
}

function scl_plugin_attiva( $in ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$file = isset( $in['file'] ) ? (string) $in['file'] : '';
	if ( empty( $in['conferma'] ) ) {
		return new WP_Error( 'scl_conferma', 'Serve conferma: true.' );
	}
	if ( ! isset( get_plugins()[ $file ] ) ) {
		return new WP_Error( 'scl_plugin', 'Plugin non trovato: ' . $file . ' (usa socialee__plugin-elenco).' );
	}
	if ( empty( $in['attiva'] ) && 'socialee-core/socialee-core.php' === $file ) {
		return new WP_Error( 'scl_plugin', 'Il Core non si spegne da qui: spegnerebbe anche questo accesso.' );
	}
	if ( empty( $in['attiva'] ) ) {
		deactivate_plugins( $file );
	} else {
		$r = activate_plugin( $file );
		if ( is_wp_error( $r ) ) {
			return $r;
		}
	}
	return array( 'file' => $file, 'attivo' => is_plugin_active( $file ), 'cache' => scl_svuota_cache() );
}

/** Scarica una pagina del sito dal server stesso e ne riassume lo stato. */
function scl_controlla_pagina( $in ) {
	$path = '/' . trim( isset( $in['path'] ) ? (string) $in['path'] : '/', '/' ) . '/';
	$path = '//' === $path ? '/' : $path;
	$t    = microtime( true );
	$res  = wp_remote_get( add_query_arg( 'scl_check', time(), home_url( $path ) ), array( 'timeout' => 40, 'redirection' => 0 ) );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$h = (string) wp_remote_retrieve_body( $res );
	preg_match( '#<title>(.*?)</title>#is', $h, $tt );
	preg_match( '#<meta name="description" content="([^"]*)"#i', $h, $dd );
	return array(
		'path'        => $path,
		'codice'      => (int) wp_remote_retrieve_response_code( $res ),
		'secondi'     => round( microtime( true ) - $t, 2 ),
		'title'       => isset( $tt[1] ) ? html_entity_decode( trim( $tt[1] ) ) : '',
		'description' => isset( $dd[1] ) ? html_entity_decode( $dd[1] ) : '',
		'h1'          => preg_match_all( '#<h1\b#i', $h ),
		'socialee'    => false !== strpos( $h, 'socialee-main' ),
		'elementor'   => preg_match_all( '#elementor#i', $h ),
		'kb'          => round( strlen( $h ) / 1024 ),
	);
}

function scl_strumenti() {
	$vuoto = array( 'type' => 'object', 'properties' => new stdClass() );
	return array(
		'socialee/stato'           => array( 'description' => 'Versioni (WordPress, PHP, plugin Socialee, Elementor, Rank Math, cache), impostazioni, numero di pagine.', 'inputSchema' => $vuoto, 'run' => 'scl_stato' ),
		'socialee/pagine'          => array( 'description' => 'Pagine servite dal plugin: percorso, accesa, stato della pagina WordPress.', 'inputSchema' => $vuoto, 'run' => 'scl_pagine_elenco' ),
		'socialee/imposta'         => array(
			'description' => 'Cambia le impostazioni: pagine (bool, tutte le pagine del plugin), off (elenco di percorsi lasciati a WordPress), shell (auto|on|off: intestazione e piè di pagina Socialee sul resto del sito). Svuota la cache. Richiede conferma.',
			'inputSchema' => array( 'type' => 'object', 'properties' => array( 'valori' => array( 'type' => 'object' ), 'conferma' => array( 'type' => 'boolean' ) ), 'required' => array( 'valori', 'conferma' ) ),
			'run'         => 'scl_imposta',
		),
		'socialee/svuota-cache'    => array( 'description' => 'Svuota Autoptimize, WP-Optimize, LiteSpeed e la cache di WordPress.', 'inputSchema' => $vuoto, 'run' => function () { return array( 'cache' => scl_svuota_cache() ); } ),
		'socialee/controlla-pagina' => array(
			'description' => 'Scarica una pagina del sito dal server: codice, secondi, title, description, numero di H1, se è servita da Socialee, quante volte compare «elementor».',
			'inputSchema' => array( 'type' => 'object', 'properties' => array( 'path' => array( 'type' => 'string' ) ), 'required' => array( 'path' ) ),
			'run'         => 'scl_controlla_pagina',
		),
		'socialee/plugin-elenco'   => array( 'description' => 'Plugin installati (file, nome, versione, attivo) e tema.', 'inputSchema' => $vuoto, 'run' => 'scl_plugin_elenco' ),
		'socialee/plugin-attiva'   => array(
			'description' => 'Attiva (attiva=true) o disattiva (attiva=false) un plugin dal suo file. Non elimina mai; il Core non si spegne da qui. Richiede conferma.',
			'inputSchema' => array( 'type' => 'object', 'properties' => array( 'file' => array( 'type' => 'string' ), 'attiva' => array( 'type' => 'boolean' ), 'conferma' => array( 'type' => 'boolean' ) ), 'required' => array( 'file', 'attiva', 'conferma' ) ),
			'run'         => 'scl_plugin_attiva',
		),
		'socialee/registro'        => array( 'description' => 'Ultime 100 azioni registrate dal Core.', 'inputSchema' => $vuoto, 'run' => function () { return array_reverse( (array) get_option( 'scl_log', array() ) ); } ),
	);
}

function scl_rpc( $msg ) {
	$id     = isset( $msg['id'] ) ? $msg['id'] : null;
	$method = isset( $msg['method'] ) ? (string) $msg['method'] : '';
	$params = isset( $msg['params'] ) && is_array( $msg['params'] ) ? $msg['params'] : array();
	$ok     = function ( $r ) use ( $id ) {
		return array( 'jsonrpc' => '2.0', 'id' => $id, 'result' => $r );
	};
	$err    = function ( $c, $t ) use ( $id ) {
		return array( 'jsonrpc' => '2.0', 'id' => $id, 'error' => array( 'code' => $c, 'message' => $t ) );
	};
	switch ( $method ) {
		case 'initialize':
			return $ok( array( 'protocolVersion' => isset( $params['protocolVersion'] ) ? (string) $params['protocolVersion'] : '2025-03-26', 'capabilities' => array( 'tools' => array( 'listChanged' => false ) ), 'serverInfo' => array( 'name' => 'socialee', 'version' => SCL_VERSION ), 'instructions' => 'Gestione di socialee.it (plugin Socialee). Una richiesta alla volta. Gli zip dei plugin si caricano con POST /wp-json/socialee-sito/v1/plugin.' ) );
		case 'ping':
			return $ok( new stdClass() );
		case 'tools/list':
			$l = array();
			foreach ( scl_strumenti() as $n => $t ) {
				$l[] = array( 'name' => str_replace( '/', '__', $n ), 'description' => $t['description'], 'inputSchema' => $t['inputSchema'] );
			}
			return $ok( array( 'tools' => $l ) );
		case 'tools/call':
			$name = str_replace( '__', '/', isset( $params['name'] ) ? (string) $params['name'] : '' );
			$args = isset( $params['arguments'] ) && is_array( $params['arguments'] ) ? $params['arguments'] : array();
			$all  = scl_strumenti();
			if ( ! isset( $all[ $name ] ) ) {
				return $err( -32602, 'Strumento sconosciuto: ' . $name );
			}
			$res    = call_user_func( $all[ $name ]['run'], $args );
			$is_err = is_wp_error( $res );
			if ( 'socialee/registro' !== $name ) {
				scl_log( $name, $is_err ? 'errore: ' . $res->get_error_message() : wp_json_encode( $args ) );
			}
			$text = $is_err ? $res->get_error_message() : wp_json_encode( $res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
			return $ok( array( 'content' => array( array( 'type' => 'text', 'text' => (string) $text ) ), 'isError' => $is_err ) );
		default:
			return $err( -32601, 'Metodo non supportato: ' . $method );
	}
}

/** Installa uno zip della famiglia Socialee (cartella e nome del plugin controllati), riattiva se era attivo. */
function scl_installa_zip( $zip, $sha, $attiva ) {
	if ( '' === $zip || ! hash_equals( strtolower( (string) $sha ), hash( 'sha256', $zip ) ) ) {
		return new WP_Error( 'scl_zip', 'Zip mancante o impronta X-Sha256 diversa.' );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	$tmp = wp_tempnam( 'socialee.zip' );
	file_put_contents( $tmp, $zip ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$za   = new ZipArchive();
	$slug = '';
	if ( true === $za->open( $tmp ) && $za->numFiles > 0 ) {
		$slug = strtok( $za->getNameIndex( 0 ), '/' );
		$za->close();
	}
	if ( ! in_array( $slug, scl_plugin_famiglia(), true ) ) {
		wp_delete_file( $tmp );
		return new WP_Error( 'scl_zip', 'Si caricano solo i plugin Socialee (' . implode( ', ', scl_plugin_famiglia() ) . ').' );
	}
	if ( ! WP_Filesystem() ) {
		wp_delete_file( $tmp );
		return new WP_Error( 'scl_fs', 'WordPress non può scrivere i file direttamente.' );
	}
	$file    = $slug . '/' . $slug . '.php';
	$era     = is_plugin_active( $file );
	$skin    = new WP_Ajax_Upgrader_Skin();
	$up      = new Plugin_Upgrader( $skin );
	$res     = $up->install( $tmp, array( 'overwrite_package' => true ) );
	wp_delete_file( $tmp );
	if ( is_wp_error( $res ) || ! $res ) {
		$e = is_wp_error( $res ) ? $res : $skin->get_errors();
		return new WP_Error( 'scl_install', is_wp_error( $e ) && $e->has_errors() ? $e->get_error_message() : 'Installazione non riuscita.' );
	}
	wp_clean_plugins_cache();
	if ( ( $era || $attiva ) && ! is_plugin_active( $file ) ) {
		$a = activate_plugin( $file );
		if ( is_wp_error( $a ) ) {
			return $a;
		}
	}
	$v = get_plugins()[ $file ]['Version'] ?? '';
	return array( 'plugin' => $file, 'versione' => $v, 'attivo' => is_plugin_active( $file ), 'cache' => scl_svuota_cache() );
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			SCL_NS,
			'/mcp',
			array(
				array(
					'methods'             => 'POST',
					'permission_callback' => 'scl_permesso',
					'callback'            => function ( WP_REST_Request $req ) {
						scl_nocache();
						$body = json_decode( (string) $req->get_body(), true );
						if ( ! is_array( $body ) ) {
							return new WP_REST_Response( array( 'jsonrpc' => '2.0', 'id' => null, 'error' => array( 'code' => -32700, 'message' => 'JSON non valido' ) ), 400 );
						}
						$batch = isset( $body[0] );
						$out   = array();
						foreach ( $batch ? $body : array( $body ) as $msg ) {
							if ( isset( $msg['id'] ) ) {
								$out[] = scl_rpc( $msg );
							}
						}
						if ( ! $out ) {
							return new WP_REST_Response( null, 202 );
						}
						return new WP_REST_Response( $batch ? $out : $out[0], 200 );
					},
				),
				array(
					'methods'             => 'GET',
					'permission_callback' => 'scl_permesso',
					'callback'            => function () {
						scl_nocache();
						return new WP_REST_Response( array( 'server' => 'socialee', 'versione' => SCL_VERSION, 'strumenti' => array_map( function ( $n ) { return str_replace( '/', '__', $n ); }, array_keys( scl_strumenti() ) ) ), 200 );
					},
				),
			)
		);
		register_rest_route(
			SCL_NS,
			'/plugin',
			array(
				'methods'             => 'POST',
				'permission_callback' => 'scl_permesso',
				'callback'            => function ( WP_REST_Request $req ) {
					scl_nocache();
					$res = scl_installa_zip( (string) $req->get_body(), (string) $req->get_header( 'x_sha256' ), '1' === (string) $req->get_param( 'attiva' ) );
					scl_log( 'plugin (upload)', is_wp_error( $res ) ? 'errore: ' . $res->get_error_message() : wp_json_encode( $res ) );
					return is_wp_error( $res ) ? new WP_REST_Response( array( 'errore' => $res->get_error_message() ), 400 ) : new WP_REST_Response( $res, 200 );
				},
			)
		);
	}
);
