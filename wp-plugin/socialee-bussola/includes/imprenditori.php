<?php
/**
 * «Che tipo di imprenditore sei?» su /che-imprenditore-sei/ (il quiz della Bussola, stesse 77 affermazioni) (pagina pubblica, indicizzabile, fuori dal menu finché non si
 * decide altrimenti). Alla fine il modulo manda nome, cognome, email, telefono, provincia, azienda e settore a
 * POST /wp-json/socialee-bussola/v1/imprenditore → contatto Brevo dal server (la chiave non esce mai dal sito):
 *   - lista «Quiz imprenditori» (tutti): parte l'email con il profilo e la guida trend del settore;
 *   - lista «Quiz imprenditori · consenso» (solo con la casella marketing): dopo 2 giorni il regalo dell'Academy.
 * Senza chiave Brevo o se Brevo risponde male, il contatto arriva per email all'amministratore: nessuno si perde.
 * Le risposte alle singole domande NON escono dal browser: al server arrivano solo gli 11 punteggi finali.
 */

defined( 'ABSPATH' ) || exit;

const SCB_IMP_PATH = '/che-imprenditore-sei/';
/** Pagina accesa per tutti? Finché è false la vedono solo gli amministratori (per le prove) e non è indicizzata. */
const SCB_IMP_ATTIVO = true;

function scb_imp_dati() {
	static $d = null;
	if ( null === $d ) {
		$d = require SCB_DIR . 'dati/imprenditori.php';
	}
	return $d;
}

/** Nomi delle liste Brevo del quiz (cartella 1, la stessa di «Academy Imprenditori»). */
const SCB_IMP_NOMI_LISTE = array( 'tutti' => 'Quiz imprenditori', 'consenso' => 'Quiz imprenditori · consenso' );
/** Lista dell'Academy: chi c'è già non riceve il regalo dell'Academy (ha già la sua sequenza). */
const SCB_IMP_LISTA_ACADEMY = 47;

/** Liste Brevo (id): create dal sito alla prima occasione e salvate nell'opzione scb_imp_liste. */
function scb_imp_liste() {
	$o = get_option( 'scb_imp_liste', array() );
	return (array) apply_filters( 'scb_imp_liste', wp_parse_args( is_array( $o ) ? $o : array(), array( 'tutti' => 0, 'consenso' => 0 ) ) );
}

/** Cerca le liste per nome e crea quelle che mancano (una prova all'ora se Brevo non risponde o manca la chiave). */
function scb_imp_prepara_liste() {
	$l = scb_imp_liste();
	if ( ( $l['tutti'] && $l['consenso'] ) || ! scb_imp_brevo_key() || get_transient( 'scb_imp_liste_prova' ) ) {
		return;
	}
	set_transient( 'scb_imp_liste_prova', 1, HOUR_IN_SECONDS );
	$trovate = array();
	for ( $off = 0; $off < 250; $off += 50 ) {
		list( $c, $b ) = scb_imp_brevo( 'GET', '/contacts/lists?limit=50&offset=' . $off );
		$j = json_decode( $b, true );
		if ( 200 !== $c || empty( $j['lists'] ) ) {
			break;
		}
		foreach ( $j['lists'] as $x ) {
			$trovate[ (string) $x['name'] ] = (int) $x['id'];
		}
		if ( count( $j['lists'] ) < 50 ) {
			break;
		}
	}
	foreach ( SCB_IMP_NOMI_LISTE as $k => $nome ) {
		if ( ! empty( $l[ $k ] ) ) {
			continue;
		}
		if ( isset( $trovate[ $nome ] ) ) {
			$l[ $k ] = $trovate[ $nome ];
			continue;
		}
		list( $c, $b ) = scb_imp_brevo( 'POST', '/contacts/lists', array( 'name' => $nome, 'folderId' => 1 ) );
		$j = json_decode( $b, true );
		if ( $c >= 200 && $c < 300 && ! empty( $j['id'] ) ) {
			$l[ $k ] = (int) $j['id'];
		}
	}
	update_option( 'scb_imp_liste', array( 'tutti' => (int) $l['tutti'], 'consenso' => (int) $l['consenso'] ), false );
	scb_imp_attributi();
}
add_action( 'init', 'scb_imp_prepara_liste', 20 );

function scb_imp_brevo_key() {
	if ( function_exists( 'scl_brevo_key' ) ) {
		return (string) scl_brevo_key(); // quella del modulo contatti (Socialee Core → Impostazioni o wp-config)
	}
	return defined( 'SOCIALEE_BREVO_KEY' ) ? (string) SOCIALEE_BREVO_KEY : '';
}

const SCB_GUIDE_PATH = '/guide-e-risorse/';

/** PDF di una guida (chiave di 'guide') o, senza guida, la pagina con tutte le guide da scegliere. */
function scb_imp_guida_url( $guida ) {
	$f = 'guide/calendario-marketing-2027-' . sanitize_key( (string) $guida ) . '.pdf';
	if ( '' === (string) $guida || ! is_readable( SCB_DIR . $f ) ) {
		return home_url( SCB_GUIDE_PATH );
	}
	return plugins_url( $f, SCB_DIR . 'socialee-bussola.php' );
}

function scb_imp_guida_titolo( $guida ) {
	$g = scb_imp_dati()['guide'];
	return isset( $g[ $guida ] ) ? $g[ $guida ]['titolo'] : 'Calendario marketing 2027';
}

function scb_imp_brevo( $metodo, $percorso, $corpo = null ) {
	$args = array(
		'method'  => $metodo,
		'timeout' => 20,
		'headers' => array( 'accept' => 'application/json', 'content-type' => 'application/json', 'api-key' => scb_imp_brevo_key() ),
	);
	if ( null !== $corpo ) {
		$args['body'] = wp_json_encode( $corpo );
	}
	$res = wp_remote_request( 'https://api.brevo.com/v3' . $percorso, $args );
	if ( is_wp_error( $res ) ) {
		return array( 0, $res->get_error_message() );
	}
	return array( (int) wp_remote_retrieve_response_code( $res ), (string) wp_remote_retrieve_body( $res ) );
}

/** Gli attributi nuovi del quiz, creati in Brevo la prima volta (se ci sono già, Brevo risponde 400 e va bene). */
function scb_imp_attributi() {
	if ( '2' === get_option( 'scb_imp_attributi' ) ) {
		return;
	}
	foreach ( array( 'TIPO_IMPRENDITORE', 'GUIDA_URL', 'GUIDA_TITOLO', 'QUIZ_PUNTEGGI' ) as $a ) {
		scb_imp_brevo( 'POST', '/contacts/attributes/normal/' . $a, array( 'type' => 'text' ) );
	}
	update_option( 'scb_imp_attributi', '2', false );
}

function scb_imp_errore( $testo, $stato = 400 ) {
	return new WP_REST_Response( array( 'ok' => false, 'errore' => $testo ), $stato );
}

function scb_imp_ricevi( WP_REST_Request $req ) {
	nocache_headers();
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	$lock = 'scb_imp_' . md5( $ip );
	if ( (int) get_transient( $lock ) >= 6 ) {
		return scb_imp_errore( 'Troppe richieste da questa connessione: riprova tra qualche minuto.', 429 );
	}
	set_transient( $lock, (int) get_transient( $lock ) + 1, 10 * MINUTE_IN_SECONDS );

	$d = scb_imp_dati();
	$f = function ( $k, $max = 120 ) use ( $req ) {
		return trim( mb_substr( sanitize_text_field( (string) $req->get_param( $k ) ), 0, $max ) );
	};
	if ( '' !== $f( 'sito_web' ) || (int) $req->get_param( 'ms' ) < 20000 ) {
		return new WP_REST_Response( array( 'ok' => true, 'guida' => scb_imp_guida_url( '' ) ), 200 ); // bot: risposta finta
	}

	$nome     = $f( 'nome', 60 );
	$cognome  = $f( 'cognome', 60 );
	$azienda  = $f( 'azienda', 120 );
	$email    = sanitize_email( (string) $req->get_param( 'email' ) );
	$tel      = preg_replace( '/[^\d+]/', '', $f( 'telefono', 30 ) );
	$prov     = $f( 'provincia', 60 );
	$settore  = null;
	foreach ( $d['sectors'] as $s ) {
		if ( $s['slug'] === $f( 'settore', 40 ) ) {
			$settore = $s;
		}
	}
	if ( '' === $nome || '' === $cognome || '' === $azienda ) {
		return scb_imp_errore( 'Compila nome, cognome e nome dell’azienda.' );
	}
	if ( ! is_email( $email ) ) {
		return scb_imp_errore( 'L’indirizzo email non sembra valido.' );
	}
	if ( 0 === strpos( $tel, '00' ) ) {
		$tel = '+' . substr( $tel, 2 );
	}
	if ( 0 !== strpos( $tel, '+' ) ) {
		$tel = '+39' . $tel;
	}
	if ( ! preg_match( '/^\+\d{8,15}$/', $tel ) ) {
		return scb_imp_errore( 'Il numero di telefono non sembra valido.' );
	}
	if ( ! in_array( $prov, $d['province'], true ) ) {
		return scb_imp_errore( 'Scegli la provincia dall’elenco.' );
	}
	if ( ! $settore ) {
		return scb_imp_errore( 'Scegli il settore dall’elenco.' );
	}
	if ( ! rest_sanitize_boolean( $req->get_param( 'privacy' ) ) ) {
		return scb_imp_errore( 'Per ricevere il profilo e la guida serve la presa visione dell’informativa privacy.' );
	}
	$marketing = rest_sanitize_boolean( $req->get_param( 'marketing' ) );

	// Gli 11 punteggi della Bussola (0–100). Il tipo è la spinta (driver) più alta, a parità vale l'ordine dei tipi.
	$dim   = ( require SCB_DIR . 'dati/questionario.php' )['dimensions'];
	$punti = (array) $req->get_param( 'punteggi' );
	$sc    = array();
	foreach ( array_keys( $dim ) as $k ) {
		$v = isset( $punti[ $k ] ) && is_numeric( $punti[ $k ] ) ? (int) $punti[ $k ] : -1;
		if ( $v < 0 || $v > 100 ) {
			return scb_imp_errore( 'Il risultato del test non è completo: ricarica la pagina e riprova.' );
		}
		$sc[ $k ] = $v;
	}
	$spinte = array_intersect_key( $sc, $d['types'] );
	$top    = array_keys( $spinte, max( $spinte ), true )[0];
	$tipo   = $d['types'][ $top ]['nome'];
	arsort( $spinte );
	$righe = array();
	foreach ( $spinte as $k => $v ) {
		$righe[] = $dim[ $k ]['label'] . ' ' . $v;
	}
	$io = array_diff_key( $sc, $d['types'] );
	arsort( $io );
	foreach ( $io as $k => $v ) {
		$righe[] = $dim[ $k ]['label'] . ' ' . $v;
	}
	$guida = scb_imp_guida_url( $settore['guida'] );
	$utm   = $f( 'utm', 200 );

	$attr = array(
		'FIRSTNAME'         => $nome,
		'LASTNAME'          => $cognome,
		'NOME_AZIENDA'      => $azienda,
		'TELEFONO'          => $tel,
		'CITTA'             => $prov,
		'SETTORE'           => $settore['valore'],
		'SOURCE'            => 'Quiz Che tipo di imprenditore sei' . ( $utm ? ' · ' . $utm : '' ),
		'TIPO_IMPRENDITORE' => $tipo,
		'GUIDA_URL'         => $guida,
		'GUIDA_TITOLO'      => $settore['guida'] ? scb_imp_guida_titolo( $settore['guida'] ) : 'Calendario marketing 2027: scegli la guida',
		'QUIZ_PUNTEGGI'     => implode( ' · ', $righe ),
	);
	if ( preg_match( '/^\+393\d{8,9}$/', $tel ) ) {
		$attr['SMS'] = $tel; // Brevo accetta in SMS solo numeri di cellulare validi: un fisso farebbe fallire tutto
	}
	if ( $marketing ) {
		$attr['OPT_IN']     = true;
		$attr['OPTIN_TIME'] = gmdate( 'Y-m-d' );
	}
	$esito = 'non inviato a Brevo';
	scb_imp_prepara_liste();
	$liste = scb_imp_liste();
	$ids   = array();
	if ( scb_imp_brevo_key() && $liste['tutti'] ) {
		$ids = array( (int) $liste['tutti'] );
		if ( $marketing && $liste['consenso'] ) {
			// chi è già iscritto all'Academy non riceve il «regalo» dell'Academy: ha già la sua sequenza
			list( $c, $b ) = scb_imp_brevo( 'GET', '/contacts/' . rawurlencode( $email ) );
			$gia           = ( 200 === $c ) ? (array) ( json_decode( $b, true )['listIds'] ?? array() ) : array();
			if ( ! in_array( SCB_IMP_LISTA_ACADEMY, array_map( 'intval', $gia ), true ) ) {
				$ids[] = (int) $liste['consenso'];
			}
		}
	}
	if ( $ids ) {
		scb_imp_attributi();
		list( $codice, $corpo ) = scb_imp_brevo( 'POST', '/contacts', array( 'email' => $email, 'attributes' => $attr, 'listIds' => $ids, 'updateEnabled' => true ) );
		if ( $codice >= 400 && false !== strpos( $corpo, 'SMS' ) ) { // numero rifiutato: riprovo senza
			unset( $attr['SMS'] );
			list( $codice, $corpo ) = scb_imp_brevo( 'POST', '/contacts', array( 'email' => $email, 'attributes' => $attr, 'listIds' => $ids, 'updateEnabled' => true ) );
		}
		$esito = ( $codice >= 200 && $codice < 300 ) ? 'ok' : 'Brevo ' . $codice . ': ' . mb_substr( $corpo, 0, 200 );
	}
	if ( 'ok' !== $esito ) {
		// riserva: il contatto arriva comunque per email
		$righe_mail = array( 'Esito Brevo: ' . $esito, '', 'Nome: ' . $nome . ' ' . $cognome, 'Email: ' . $email, 'Telefono: ' . $tel, 'Provincia: ' . $prov, 'Azienda: ' . $azienda, 'Settore: ' . $settore['valore'], 'Tipo: ' . $tipo . ' (' . implode( ' · ', $righe ) . ')', 'Consenso marketing: ' . ( $marketing ? 'sì' : 'no' ), 'Fonte: ' . $attr['SOURCE'] );
		wp_mail( (string) apply_filters( 'scb_imp_avviso_email', get_option( 'admin_email' ) ), 'Quiz imprenditori: nuovo contatto (' . $nome . ' ' . $cognome . ')', implode( "\n", $righe_mail ) );
		error_log( '[socialee-bussola] quiz imprenditori: ' . $esito ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	}
	$tandem = scb_imp_tandem(
		array(
			array( 'key' => 'nome', 'label' => 'Nome', 'value' => $nome, 'type' => 'text' ),
			array( 'key' => 'cognome', 'label' => 'Cognome', 'value' => $cognome, 'type' => 'text' ),
			array( 'key' => 'email', 'label' => 'Email', 'value' => $email, 'type' => 'email' ),
			array( 'key' => 'telefono', 'label' => 'Telefono', 'value' => $tel, 'type' => 'tel' ),
			array( 'key' => 'azienda', 'label' => 'Nome dell’azienda', 'value' => $azienda, 'type' => 'text' ),
			array( 'key' => 'provincia', 'label' => 'Provincia', 'value' => $prov, 'type' => 'select' ),
			array( 'key' => 'settore', 'label' => 'Settore', 'value' => $settore['valore'], 'type' => 'select' ),
			array( 'key' => 'tipo_imprenditore', 'label' => 'Tipo di imprenditore', 'value' => $tipo, 'type' => 'text' ),
			array( 'key' => 'punteggi', 'label' => 'Punteggi del test', 'value' => implode( ' · ', $righe ), 'type' => 'text' ),
			array( 'key' => 'guida', 'label' => 'Guida regalata', 'value' => $settore['guida'] ? scb_imp_guida_titolo( $settore['guida'] ) : 'Pagina con tutte le guide', 'type' => 'text' ),
			array( 'key' => 'consenso_marketing', 'label' => 'Consenso marketing (altri regali e Academy)', 'value' => $marketing ? 'sì' : 'no', 'type' => 'checkbox' ),
			array( 'key' => 'fonte', 'label' => 'Fonte', 'value' => $utm ? $utm : 'diretta', 'type' => 'text' ),
		)
	);
	$log   = (array) get_option( 'scb_imp_registro', array() );
	$log[] = array( gmdate( 'c' ), substr( md5( strtolower( $email ) ), 0, 8 ), $settore['slug'], $tipo, $marketing ? 1 : 0, 'ok' === $esito ? 'brevo' : $esito, 'tandem: ' . $tandem );
	update_option( 'scb_imp_registro', array_slice( $log, -50 ), false ); // ultime 50, senza dati personali

	return new WP_REST_Response( array( 'ok' => true, 'guida' => $guida, 'settore' => $settore['valore'], 'titolo' => $settore['guida'] ? scb_imp_guida_titolo( $settore['guida'] ) : '' ), 200 );
}

/* ─── Tandem: il quiz è un form di socialee.it come gli altri (Richieste) ─── */

const SCB_IMP_FORM_KEY = 'custom:/che-imprenditore-sei/#modulo';

/** Manda la richiesta a Tandem con il plugin Tandem Forms del sito (dal server; se Tandem non risponde resta in coda). */
function scb_imp_tandem( array $campi ) {
	if ( ! class_exists( 'Tandem_Forms' ) ) {
		return 'plugin Tandem Forms assente';
	}
	$ctx = array(
		'form_key'  => SCB_IMP_FORM_KEY,
		'form_name' => 'Quiz «Che tipo di imprenditore sei?»',
		'page_url'  => home_url( SCB_IMP_PATH ),
		'intent'    => 'SIGNUP',
		'kind'      => 'BRAND',
	);
	try {
		if ( method_exists( 'Tandem_Forms', 'submit' ) ) { // 1.3.2 e successive
			$r = Tandem_Forms::submit( $campi, $ctx );
		} elseif ( method_exists( 'Tandem_Forms', 'build' ) && method_exists( 'Tandem_Forms', 'send' ) ) {
			$ctx['external_id'] = wp_parse_url( home_url( '/' ), PHP_URL_HOST ) . ':' . SCB_IMP_FORM_KEY . ':' . wp_generate_uuid4();
			$r                  = Tandem_Forms::send( Tandem_Forms::build( $campi, $ctx ) );
		} else {
			return 'versione di Tandem Forms senza invio dal server';
		}
	} catch ( \Throwable $e ) {
		return 'errore: ' . $e->getMessage();
	}
	return ! empty( $r['ok'] ) ? 'ok' : 'in coda (' . ( $r['error'] ?? ( 'HTTP ' . ( $r['code'] ?? 0 ) ) ) . ')';
}

/** Il form si dichiara a Tandem (Tandem Forms 1.3.2+): compare tra i form di socialee.it come collegato. */
add_filter(
	'tandem_forms_known_forms',
	function ( $forms ) {
		$forms   = (array) $forms;
		$forms[] = array(
			'key'    => SCB_IMP_FORM_KEY,
			'name'   => 'Quiz «Che tipo di imprenditore sei?»',
			'type'   => 'CUSTOM',
			'pages'  => array( home_url( SCB_IMP_PATH ) ),
			'server' => true,
		);
		return $forms;
	}
);

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'socialee-bussola/v1',
			'/imprenditore',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true', // modulo pubblico: limiti per indirizzo, trappola e tempo minimo
				'callback'            => 'scb_imp_ricevi',
			)
		);
	}
);
