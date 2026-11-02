<?php
/**
 * Plugin Name:       Socialee Aggiornamenti
 * Description:       Aggiorna da solo i plugin Socialee dal repository GitHub del sito: ogni ora controlla le versioni pubblicate, scarica gli zip, ne verifica l'impronta, installa, svuota la cache e controlla che il sito risponda; se non risponde rimette la versione precedente. Nessuna chiave da gestire.
 * Version:           1.0.0
 * Author:            Influencee SRL (Socialee)
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Text Domain:       socialee-aggiornamenti
 */

defined( 'ABSPATH' ) || exit;

define( 'SOCU_VERSION', '1.0.0' );

/**
 * Da dove arrivano gli aggiornamenti: cartella consegne/plugin-wordpress del repository pubblico del sito.
 * versioni.json elenca per ogni plugin versione, impronta sha256 e se aggiornarlo da solo («auto»).
 */
function socu_sorgente() {
	$base = defined( 'SOCIALEE_AGGIORNAMENTI_URL' ) ? SOCIALEE_AGGIORNAMENTI_URL : 'https://raw.githubusercontent.com/influencee-dev/socialee-sito/main/consegne/plugin-wordpress/';
	return trailingslashit( (string) apply_filters( 'socu_sorgente', $base ) );
}

/** Solo questi plugin, con questo nome esatto nell'intestazione. */
function socu_famiglia() {
	return array(
		'socialee-core'          => 'Socialee Core',
		'socialee-pagine'        => 'Socialee Pagine',
		'socialee-aggiornamenti' => 'Socialee Aggiornamenti',
	);
}

function socu_log( $msg ) {
	$log   = (array) get_option( 'socu_log', array() );
	$log[] = array( 'quando' => current_time( 'mysql' ), 'cosa' => (string) $msg );
	update_option( 'socu_log', array_slice( $log, -60 ), false );
}

function socu_versione_installata( $slug ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$file = WP_PLUGIN_DIR . '/' . $slug . '/' . $slug . '.php';
	if ( ! file_exists( $file ) ) {
		return '';
	}
	$d = get_plugin_data( $file, false, false );
	return isset( $d['Version'] ) ? (string) $d['Version'] : '';
}

function socu_manifest() {
	$res = wp_remote_get( socu_sorgente() . 'versioni.json?t=' . time(), array( 'timeout' => 20 ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return new WP_Error( 'socu_manifest', 'Elenco delle versioni non raggiungibile: ' . ( is_wp_error( $res ) ? $res->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $res ) ) );
	}
	$m = json_decode( (string) wp_remote_retrieve_body( $res ), true );
	return ( is_array( $m ) && isset( $m['plugin'] ) && is_array( $m['plugin'] ) ) ? $m : new WP_Error( 'socu_manifest', 'Elenco delle versioni non valido.' );
}

/** Il sito risponde ancora? (home con codice 200 e pagina completa) */
function socu_sito_ok() {
	$res = wp_remote_get( add_query_arg( 'socu_check', time(), home_url( '/' ) ), array( 'timeout' => 30, 'redirection' => 2, 'headers' => array( 'Cache-Control' => 'no-cache' ) ) );
	return ! is_wp_error( $res ) && 200 === (int) wp_remote_retrieve_response_code( $res ) && false !== stripos( (string) wp_remote_retrieve_body( $res ), '</html>' );
}

function socu_svuota_cache() {
	do_action( 'litespeed_purge_all' );
	wp_cache_flush();
}

/** Copia della cartella del plugin in uploads/socialee-backup (per tornare indietro). */
function socu_backup( $slug ) {
	$dir = WP_PLUGIN_DIR . '/' . $slug;
	if ( ! is_dir( $dir ) || ! class_exists( 'ZipArchive' ) ) {
		return '';
	}
	$up   = wp_upload_dir();
	$dest = trailingslashit( $up['basedir'] ) . 'socialee-backup';
	wp_mkdir_p( $dest );
	if ( ! file_exists( $dest . '/index.html' ) ) {
		file_put_contents( $dest . '/index.html', '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		file_put_contents( $dest . '/.htaccess', "Require all denied\nDeny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	$zip  = $dest . '/' . $slug . '-' . socu_versione_installata( $slug ) . '-' . gmdate( 'Ymd-His' ) . '.zip';
	$za   = new ZipArchive();
	if ( true !== $za->open( $zip, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		return '';
	}
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $f ) {
		$za->addFile( $f->getPathname(), $slug . '/' . ltrim( substr( $f->getPathname(), strlen( $dir ) ), '/\\' ) );
	}
	$za->close();
	// tiene le ultime 3 copie per plugin
	$old = glob( $dest . '/' . $slug . '-*.zip' );
	if ( $old && count( $old ) > 3 ) {
		sort( $old );
		foreach ( array_slice( $old, 0, count( $old ) - 3 ) as $o ) {
			wp_delete_file( $o );
		}
	}
	return $zip;
}

/** Installa uno zip della famiglia (controllo struttura e nome), riattiva se era attivo. */
function socu_installa_zip( $tmp, $slug ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	$fam = socu_famiglia();
	$za  = new ZipArchive();
	if ( ! isset( $fam[ $slug ] ) || true !== $za->open( $tmp ) ) {
		return new WP_Error( 'socu_zip', 'Zip non leggibile.' );
	}
	$ok = $za->numFiles > 0;
	for ( $i = 0; $ok && $i < $za->numFiles; $i++ ) {
		$n  = (string) $za->getNameIndex( $i );
		$ok = 0 === strpos( $n, $slug . '/' ) && false === strpos( $n, '..' );
	}
	$main = $ok ? (string) $za->getFromName( $slug . '/' . $slug . '.php' ) : '';
	$za->close();
	if ( ! $ok || ! preg_match( '/Plugin Name:\s*' . preg_quote( $fam[ $slug ], '/' ) . '\s*$/mi', $main ) ) {
		return new WP_Error( 'socu_contenuto', 'Lo zip non è il plugin ' . $fam[ $slug ] . '.' );
	}
	if ( ! WP_Filesystem() ) {
		return new WP_Error( 'socu_fs', 'WordPress non può scrivere i file dei plugin.' );
	}
	$file   = $slug . '/' . $slug . '.php';
	$was_on = is_plugin_active( $file );
	$up     = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
	$res    = $up->install( $tmp, array( 'overwrite_package' => true ) );
	if ( is_wp_error( $res ) || ! $res ) {
		return is_wp_error( $res ) ? $res : new WP_Error( 'socu_install', 'Installazione non riuscita.' );
	}
	if ( $was_on ) {
		$act = activate_plugin( $file, '', false, true );
		if ( is_wp_error( $act ) ) {
			return $act;
		}
	}
	return true;
}

/**
 * Plugin nuovo della famiglia («installa» nell'elenco delle versioni): installato e attivato. Se l'attivazione
 * fallisce o il sito non risponde più, viene disattivato e tolto.
 */
function socu_aggiungi( $tmp, $slug, $versione, $check ) {
	$r    = socu_installa_zip( $tmp, $slug );
	$file = $slug . '/' . $slug . '.php';
	$act  = is_wp_error( $r ) ? $r : activate_plugin( $file, '', false, false );
	socu_svuota_cache();
	if ( is_wp_error( $act ) || ( $check && ! socu_sito_ok() ) ) {
		$perche = is_wp_error( $act ) ? $act->get_error_message() : 'il sito non risponde dopo l’attivazione';
		deactivate_plugins( $file, true );
		if ( file_exists( WP_PLUGIN_DIR . '/' . $file ) ) {
			delete_plugins( array( $file ) );
		}
		socu_svuota_cache();
		socu_log( $slug . ' ' . $versione . ': installazione NON riuscita (' . $perche . '), tolto.' );
		return 'non installato';
	}
	socu_log( $slug . ' ' . $versione . ': installato e attivato.' );
	return 'installato ' . $versione;
}

/** Controllo e aggiornamento: un plugin alla volta, con controllo del sito e ritorno indietro. */
function socu_controlla( $forza = false ) {
	if ( get_transient( 'socu_in_corso' ) && ! $forza ) {
		return array( 'stato' => 'già in corso' );
	}
	set_transient( 'socu_in_corso', 1, 15 * MINUTE_IN_SECONDS );
	require_once ABSPATH . 'wp-admin/includes/file.php'; // wp_tempnam e WP_Filesystem: nel controllo orario non sono caricati
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	if ( ! wp_is_file_mod_allowed( 'socu_update' ) || ! class_exists( 'ZipArchive' ) ) {
		delete_transient( 'socu_in_corso' );
		socu_log( 'Bloccato: il sito non permette di modificare i plugin o manca ZipArchive.' );
		return array( 'stato' => 'bloccato' );
	}
	$m = socu_manifest();
	if ( is_wp_error( $m ) ) {
		delete_transient( 'socu_in_corso' );
		socu_log( $m->get_error_message() );
		return array( 'stato' => $m->get_error_message() );
	}
	update_option( 'socu_ultimo_controllo', current_time( 'mysql' ), false );
	$fatti = array();
	$check = null; // il controllo del sito si fa solo se funziona già prima (alcuni hosting bloccano le richieste a sé stessi)
	foreach ( $m['plugin'] as $slug => $p ) {
		if ( ! isset( socu_famiglia()[ $slug ] ) || empty( $p['auto'] ) || empty( $p['versione'] ) || empty( $p['sha256'] ) ) {
			continue;
		}
		$ora   = socu_versione_installata( $slug );
		$nuovo = '' === $ora;
		if ( ( $nuovo && empty( $p['installa'] ) ) || ( ! $nuovo && version_compare( $p['versione'], $ora, '<=' ) ) ) {
			continue; // non installato qui e non da aggiungere, o già aggiornato
		}
		$res = wp_remote_get( socu_sorgente() . rawurlencode( isset( $p['file'] ) ? $p['file'] : $slug . '.zip' ) . '?v=' . rawurlencode( $p['versione'] ), array( 'timeout' => 90 ) );
		$zip = is_wp_error( $res ) ? '' : (string) wp_remote_retrieve_body( $res );
		if ( '' === $zip || ! hash_equals( strtolower( $p['sha256'] ), hash( 'sha256', $zip ) ) ) {
			socu_log( $slug . ' ' . $p['versione'] . ': zip non scaricato o impronta diversa, saltato.' );
			continue;
		}
		if ( null === $check ) {
			$check = socu_sito_ok();
			if ( ! $check ) {
				socu_log( 'Controllo del sito non disponibile da qui: aggiorno senza ritorno automatico dopo il controllo.' );
			}
		}
		$tmp = wp_tempnam( $slug . '.zip' );
		file_put_contents( $tmp, $zip ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( $nuovo ) {
			$fatti[ $slug ] = socu_aggiungi( $tmp, $slug, $p['versione'], $check );
			wp_delete_file( $tmp );
			continue;
		}
		$bak = socu_backup( $slug );
		$r   = socu_installa_zip( $tmp, $slug );
		wp_delete_file( $tmp );
		socu_svuota_cache();
		if ( is_wp_error( $r ) || ( $check && ! socu_sito_ok() ) ) {
			$perche = is_wp_error( $r ) ? $r->get_error_message() : 'il sito non risponde dopo l’aggiornamento';
			if ( $bak ) {
				socu_installa_zip( $bak, $slug );
				socu_svuota_cache();
			}
			socu_log( $slug . ' ' . $ora . ' → ' . $p['versione'] . ': NON riuscito (' . $perche . ')' . ( $bak ? ', rimessa la ' . $ora : '' ) . '.' );
			$fatti[ $slug ] = 'ritornato a ' . $ora;
			continue;
		}
		socu_log( $slug . ' ' . $ora . ' → ' . $p['versione'] . ': aggiornato.' );
		$fatti[ $slug ] = $ora . ' → ' . $p['versione'];
	}
	delete_transient( 'socu_in_corso' );
	return array( 'stato' => 'ok', 'aggiornati' => $fatti );
}

/* ─── Pianificazione: ogni ora, e un minuto dopo l'attivazione ─── */

add_action( 'socu_controllo', 'socu_controlla' );

register_activation_hook(
	__FILE__,
	function () {
		if ( ! wp_next_scheduled( 'socu_controllo' ) ) {
			wp_schedule_event( time() + 60, 'hourly', 'socu_controllo' );
		}
		socu_log( 'Attivato: controllo ogni ora da ' . socu_sorgente() );
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		wp_clear_scheduled_hook( 'socu_controllo' );
	}
);

add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'socu_controllo' ) ) {
			wp_schedule_event( time() + 300, 'hourly', 'socu_controllo' );
		}
	}
);

/* ─── Stato pubblico (sola lettura, nessun dato riservato): per verificare da fuori cosa è installato ─── */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'socialee-aggiornamenti/v1',
			'/stato',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function () {
					nocache_headers();
					do_action( 'litespeed_control_set_nocache', 'socialee aggiornamenti' );
					$v = array();
					foreach ( array_keys( socu_famiglia() ) as $slug ) {
						$v[ $slug ] = socu_versione_installata( $slug );
					}
					return array( 'versioni' => $v, 'ultimo_controllo' => get_option( 'socu_ultimo_controllo', '' ), 'prossimo' => wp_next_scheduled( 'socu_controllo' ) ? gmdate( 'c', wp_next_scheduled( 'socu_controllo' ) ) : '', 'registro' => array_slice( (array) get_option( 'socu_log', array() ), -5 ) );
				},
			)
		);
	}
);

/* ─── Pagina in Strumenti: stato, registro e «Controlla adesso» ─── */

add_action(
	'admin_menu',
	function () {
		add_management_page(
			'Socialee Aggiornamenti',
			'Socialee Aggiornamenti',
			'manage_options',
			'socialee-aggiornamenti',
			function () {
				$esito = null;
				if ( isset( $_POST['socu_ora'] ) && check_admin_referer( 'socu_ora' ) ) {
					$esito = socu_controlla( true );
				}
				$m = socu_manifest();
				echo '<div class="wrap"><h1>Socialee Aggiornamenti</h1><p>Ogni ora il sito controlla <code>' . esc_html( socu_sorgente() ) . 'versioni.json</code> e installa da solo le versioni nuove dei plugin Influencee segnati come automatici, con copia di sicurezza e ritorno indietro se il sito non risponde.</p>';
				if ( $esito ) {
					echo '<div class="notice notice-info"><p>' . esc_html( wp_json_encode( $esito, JSON_UNESCAPED_UNICODE ) ) . '</p></div>';
				}
				echo '<table class="widefat striped" style="max-width:760px"><thead><tr><th>Plugin</th><th>Installata</th><th>Pubblicata</th><th>Automatico</th></tr></thead><tbody>';
				foreach ( socu_famiglia() as $slug => $name ) {
					$p = ! is_wp_error( $m ) && isset( $m['plugin'][ $slug ] ) ? $m['plugin'][ $slug ] : array();
					echo '<tr><td>' . esc_html( $name ) . '</td><td>' . esc_html( socu_versione_installata( $slug ) ?: '—' ) . '</td><td>' . esc_html( isset( $p['versione'] ) ? $p['versione'] : '—' ) . '</td><td>' . ( ! empty( $p['auto'] ) ? 'sì' : 'no' ) . '</td></tr>';
				}
				echo '</tbody></table>';
				if ( is_wp_error( $m ) ) {
					echo '<p style="color:#b42318">' . esc_html( $m->get_error_message() ) . '</p>';
				}
				echo '<form method="post" style="margin:16px 0">';
				wp_nonce_field( 'socu_ora' );
				echo '<button class="button button-primary" name="socu_ora" value="1">Controlla adesso</button> <span class="description">Ultimo controllo: ' . esc_html( get_option( 'socu_ultimo_controllo', 'mai' ) ) . '</span></form>';
				echo '<h2>Registro</h2><ul>';
				foreach ( array_reverse( (array) get_option( 'socu_log', array() ) ) as $l ) {
					echo '<li><code>' . esc_html( $l['quando'] ) . '</code> ' . esc_html( $l['cosa'] ) . '</li>';
				}
				echo '</ul></div>';
			}
		);
	}
);
