<?php
/**
 * Modulo contatti → Brevo, dal server.
 * Prima la pagina /contatti/ chiamava api.brevo.com dal browser con la chiave API dentro il JavaScript (visibile a
 * chiunque). Ora il modulo manda i dati qui e la chiave resta sul server: costante SOCIALEE_BREVO_KEY in wp-config.php
 * oppure Socialee Sito → Impostazioni. Senza chiave la richiesta arriva per email (nessun contatto perso).
 *
 * POST /wp-json/socialee-sito/v1/contatto  {email, nome, azienda, telefono, pacchetto, messaggio, sito_web (trappola)}
 */

defined( 'ABSPATH' ) || exit;

function scl_brevo_key() {
	if ( defined( 'SOCIALEE_BREVO_KEY' ) && SOCIALEE_BREVO_KEY ) {
		return (string) SOCIALEE_BREVO_KEY;
	}
	return (string) get_option( 'scl_brevo_key', '' );
}

function scl_contatto( WP_REST_Request $req ) {
	nocache_headers();
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	$lock = 'scl_cont_' . md5( $ip );
	if ( (int) get_transient( $lock ) >= 5 ) {
		return new WP_REST_Response( array( 'ok' => false, 'errore' => 'Troppe richieste: riprova tra qualche minuto.' ), 429 );
	}
	set_transient( $lock, (int) get_transient( $lock ) + 1, 10 * MINUTE_IN_SECONDS );

	$f = function ( $k, $max = 200 ) use ( $req ) {
		return mb_substr( sanitize_text_field( (string) $req->get_param( $k ) ), 0, $max );
	};
	if ( '' !== $f( 'sito_web' ) ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 ); // trappola per i bot: risposta finta
	}
	$email = sanitize_email( (string) $req->get_param( 'email' ) );
	if ( ! is_email( $email ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'errore' => 'Email non valida.' ), 400 );
	}
	$tel = preg_replace( '/[\s\-\(\)]/', '', $f( 'telefono', 30 ) );
	if ( '' !== $tel && '+39' !== $tel && 0 !== strpos( $tel, '+' ) ) {
		$tel = '+39' . $tel;
	}
	$attr = array(
		'FIRSTNAME'    => $f( 'nome' ),
		'NOME_AZIENDA' => $f( 'azienda' ),
		'PACCHETTO'    => $f( 'pacchetto' ),
		'MESSAGE_FORM' => mb_substr( sanitize_textarea_field( (string) $req->get_param( 'messaggio' ) ), 0, 3000 ),
	);
	if ( preg_match( '/^\+\d{8,15}$/', $tel ) ) {
		$attr['SMS'] = $tel;
	}
	$key = scl_brevo_key();
	if ( $key ) {
		$res  = wp_remote_post(
			'https://api.brevo.com/v3/contacts',
			array(
				'timeout' => 20,
				'headers' => array( 'accept' => 'application/json', 'content-type' => 'application/json', 'api-key' => $key ),
				'body'    => wp_json_encode( array( 'email' => $email, 'attributes' => $attr, 'listIds' => array( (int) apply_filters( 'scl_brevo_lista', 14 ) ), 'updateEnabled' => true ) ),
			)
		);
		$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
		if ( $code >= 200 && $code < 300 ) {
			return new WP_REST_Response( array( 'ok' => true ), 200 );
		}
		scl_log( 'contatto: Brevo ha risposto ' . $code, is_wp_error( $res ) ? $res->get_error_message() : wp_remote_retrieve_body( $res ) );
	}
	// senza chiave (o Brevo non risponde): la richiesta arriva per email
	$testo = "Nuova richiesta dal modulo contatti di socialee.it\n\nEmail: $email\nTelefono: $tel\n";
	foreach ( $attr as $k => $v ) {
		$testo .= $k . ': ' . $v . "\n";
	}
	$to   = (string) apply_filters( 'scl_contatto_email', 'info@socialee.it' );
	$sent = wp_mail( $to, 'Richiesta dal sito: ' . ( $attr['FIRSTNAME'] ? $attr['FIRSTNAME'] : $email ), $testo, array( 'Reply-To: ' . $email ) );
	return $sent ? new WP_REST_Response( array( 'ok' => true ), 200 ) : new WP_REST_Response( array( 'ok' => false, 'errore' => 'Invio non riuscito.' ), 500 );
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route( SCL_NS, '/contatto', array( 'methods' => 'POST', 'permission_callback' => '__return_true', 'callback' => 'scl_contatto' ) );
	}
);
