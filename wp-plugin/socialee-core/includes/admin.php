<?php
/** Pagina Socialee → Impostazioni: chiave per Claude, pagine, intestazione su tutto il sito, registro. */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		// slug proprio: «socialee» è già usato da un altro plugin del sito (assets) e i due menu si sovrapponevano
		add_menu_page( 'Socialee Sito', 'Socialee Sito', 'manage_options', 'socialee-sito', 'scl_admin_page', 'dashicons-star-filled', 3 );
		add_submenu_page( 'socialee-sito', 'Socialee Sito', 'Impostazioni', 'manage_options', 'socialee-sito', 'scl_admin_page' );
	}
);

add_action(
	'admin_post_scl_azione',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'scl_azione' ) ) {
			wp_die( 'Non consentito.' );
		}
		$do   = isset( $_POST['do'] ) ? sanitize_key( $_POST['do'] ) : '';
		$back = admin_url( 'admin.php?page=socialee-sito' );
		if ( 'chiave' === $do ) {
			$key = 'scl_' . wp_generate_password( 40, false, false );
			update_option( 'scl_key_hash', scl_key_hash( $key ), false );
			update_option( 'scl_key_user', get_current_user_id(), false );
			update_option( 'scl_key_creata', current_time( 'mysql' ), false );
			set_transient( 'scl_key_show_' . get_current_user_id(), $key, 300 );
			scl_log( 'chiave creata' );
			$back .= '#chiave';
		} elseif ( 'revoca' === $do ) {
			delete_option( 'scl_key_hash' );
			delete_option( 'scl_key_user' );
			scl_log( 'chiave revocata' );
			$back .= '#chiave';
		} elseif ( 'impostazioni' === $do ) {
			$known = array_keys( scl_pages() );
			$off   = array_filter( array_map( 'trim', explode( "\n", isset( $_POST['off'] ) ? wp_unslash( $_POST['off'] ) : '' ) ) );
			$shell = isset( $_POST['shell'] ) ? sanitize_key( $_POST['shell'] ) : 'auto';
			update_option(
				'scl_settings',
				array(
					'pagine' => empty( $_POST['pagine'] ) ? 0 : 1,
					'off'    => array_values( array_intersect( $off, $known ) ),
					'shell'  => in_array( $shell, array( 'auto', 'on', 'off' ), true ) ? $shell : 'auto',
				)
			);
			scl_svuota_cache();
			scl_log( 'impostazioni salvate dal pannello' );
		} elseif ( 'brevo' === $do ) {
			$k = isset( $_POST['brevo_key'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['brevo_key'] ) ) ) : '';
			if ( isset( $_POST['togli'] ) ) {
				delete_option( 'scl_brevo_key' );
			} elseif ( '' !== $k ) {
				update_option( 'scl_brevo_key', $k, false );
			}
			scl_log( 'chiave Brevo del modulo contatti ' . ( isset( $_POST['togli'] ) ? 'tolta' : 'aggiornata' ) );
			$back .= '#brevo';
		} elseif ( 'cache' === $do ) {
			scl_svuota_cache();
		}
		wp_safe_redirect( $back );
		exit;
	}
);

function scl_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s    = scl_settings();
	$uid  = get_current_user_id();
	$key  = get_transient( 'scl_key_show_' . $uid );
	if ( $key ) {
		delete_transient( 'scl_key_show_' . $uid );
	}
	$open = function ( $do ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'scl_azione' );
		echo '<input type="hidden" name="action" value="scl_azione"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
	};
	$src = scl_source();
	?>
	<div class="wrap">
		<h1>Socialee Sito <?php echo esc_html( SCL_VERSION ); ?></h1>
		<p>Le pagine del plugin <b>Socialee Pagine</b> sono servite con lo stesso URL di oggi, in HTML5 e senza Elementor. Title, description e dati strutturati restano di Rank Math; cookie banner, pixel e moduli dei plugin di sempre.</p>
		<?php if ( ! $src ) : ?><div class="notice notice-error"><p>Manca il plugin <b>Socialee Pagine</b>: installalo e attivalo.</p></div><?php endif; ?>

		<h2 id="chiave">Accesso di Claude</h2>
		<p>Indirizzo: <code><?php echo esc_html( rest_url( SCL_NS . '/mcp' ) ); ?></code> · Stato: <?php echo get_option( 'scl_key_hash' ) ? '✅ chiave attiva (creata il ' . esc_html( get_option( 'scl_key_creata' ) ) . ')' : '⚪ nessuna chiave'; ?></p>
		<?php if ( $key ) : ?>
			<div class="notice notice-warning" style="padding:12px"><p><b>Copia la chiave adesso: non verrà più mostrata.</b> In Claude Code: impostazioni dell'ambiente → <b>Segreti di rete</b> → sito <code>socialee.it</code>, percorso <code>/wp-json/socialee-sito/</code>, intestazione <code><?php echo esc_html( SCL_HEADER ); ?></code>, valore = la chiave. Poi apri una sessione nuova. Non incollarla in chat.</p>
			<p><input type="text" readonly value="<?php echo esc_attr( $key ); ?>" style="width:100%;font-family:monospace" onclick="this.select()"></p></div>
		<?php endif; ?>
		<?php $open( 'chiave' ); ?><p><button class="button button-primary"><?php echo get_option( 'scl_key_hash' ) ? 'Rifai la chiave per Claude' : 'Crea la chiave per Claude'; ?></button></p></form>
		<?php if ( get_option( 'scl_key_hash' ) ) { $open( 'revoca' ); echo '<p><button class="button">Revoca la chiave</button></p></form>'; } ?>

		<h2 id="brevo">Modulo contatti → Brevo</h2>
		<p>Il modulo di <code>/contatti/</code> manda i dati al server, che li passa a Brevo (lista 14). La chiave API resta qui e non compare mai nella pagina. Stato: <?php echo defined( 'SOCIALEE_BREVO_KEY' ) ? '✅ chiave in wp-config.php' : ( get_option( 'scl_brevo_key' ) ? '✅ chiave impostata' : '⚠️ nessuna chiave: le richieste arrivano per email a info@socialee.it' ); ?></p>
		<?php $open( 'brevo' ); ?><p><input type="password" name="brevo_key" autocomplete="off" placeholder="xkeysib-… (chiave nuova, creata in Brevo → SMTP e API)" style="width:420px"> <button class="button">Salva la chiave</button> <?php if ( get_option( 'scl_brevo_key' ) ) : ?><button class="button" name="togli" value="1">Togli</button><?php endif; ?></p></form>

		<h2>Pagine</h2>
		<?php $open( 'impostazioni' ); ?>
		<p><label><input type="checkbox" name="pagine" value="1" <?php checked( ! empty( $s['pagine'] ) ); ?>> <b>Servi le pagine Socialee</b> (<?php echo count( scl_pages() ); ?> pagine) al posto di quelle di Elementor</label></p>
		<p><label>Pagine da lasciare a WordPress/Elementor (una per riga):<br><textarea name="off" rows="3" cols="60"><?php echo esc_textarea( implode( "\n", (array) $s['off'] ) ); ?></textarea></label></p>
		<p>Intestazione e piè di pagina Socialee sul resto del sito (articoli, academy, archivi):
			<select name="shell">
				<option value="auto" <?php selected( $s['shell'], 'auto' ); ?>>automatico: quando Elementor Pro è spento</option>
				<option value="on" <?php selected( $s['shell'], 'on' ); ?>>sempre</option>
				<option value="off" <?php selected( $s['shell'], 'off' ); ?>>mai</option>
			</select> · ora: <?php echo scl_shell_attiva() ? '✅ attiva' : '⚪ spenta'; ?></p>
		<p><button class="button button-primary">Salva</button></p></form>
		<?php $open( 'cache' ); ?><p><button class="button">Svuota la cache</button></p></form>
		<details><summary>Elenco delle pagine</summary><ul style="columns:3">
		<?php foreach ( scl_pages() as $path => $p ) : ?>
			<li><a href="<?php echo esc_url( home_url( $path ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $path ); ?></a> · <a href="<?php echo esc_url( add_query_arg( 'socialee_originale', 1, home_url( $path ) ) ); ?>" target="_blank" rel="noopener">com'era</a></li>
		<?php endforeach; ?>
		</ul></details>

		<h2>Registro</h2>
		<table class="widefat striped" style="max-width:900px"><tbody>
		<?php foreach ( array_slice( array_reverse( (array) get_option( 'scl_log', array() ) ), 0, 20 ) as $l ) : ?>
			<tr><td style="width:160px"><?php echo esc_html( $l['quando'] ); ?></td><td><?php echo esc_html( $l['cosa'] ); ?></td><td><code><?php echo esc_html( $l['dettagli'] ); ?></code></td></tr>
		<?php endforeach; ?>
		</tbody></table>
	</div>
	<?php
}

/** Link «Impostazioni» sotto il plugin nell'elenco Plugin. */
add_filter(
	'plugin_action_links_socialee-core/socialee-core.php',
	function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=socialee-sito' ) ) . '">Impostazioni e chiave per Claude</a>' );
		return $links;
	}
);
