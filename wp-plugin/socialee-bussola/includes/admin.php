<?php
/** Pannello «Utenti → Bussola del team»: i due indirizzi e la privacy. Nessuna risposta passa da qui. */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_users_page( 'La Bussola Interna · Team', 'Bussola del team', 'promote_users', 'socialee-bussola', 'scb_pannello' );
	}
);

add_filter(
	'plugin_action_links_socialee-bussola/socialee-bussola.php',
	function ( $l ) {
		array_unshift( $l, '<a href="' . esc_url( admin_url( 'users.php?page=socialee-bussola' ) ) . '">Accessi</a>' );
		return $l;
	}
);

function scb_utenti_con_accesso() {
	$out = array();
	foreach ( get_users( array( 'fields' => 'all', 'number' => 500 ) ) as $u ) {
		if ( $u->has_cap( SCB_CAP ) ) {
			$out[] = $u;
		}
	}
	return $out;
}

function scb_pannello() {
	if ( ! current_user_can( 'promote_users' ) ) {
		return;
	}
	$quiz  = home_url( SCB_BASE );
	$dash  = home_url( SCB_BASE . 'dashboard/' );
	echo '<div class="wrap"><h1>La Bussola Interna · Team</h1>';
	echo '<p>Quiz: <a href="' . esc_url( $quiz ) . '" target="_blank" rel="noopener"><code>' . esc_html( $quiz ) . '</code></a> — libero per chiunque abbia il link: non serve un account (non è nel menu e non è su Google). Dashboard degli aggregati anonimi (solo amministratori): <a href="' . esc_url( $dash ) . '" target="_blank" rel="noopener"><code>' . esc_html( $dash ) . '</code></a>.</p>';
	echo '<p><strong>Privacy:</strong> le risposte e i punteggi restano nel browser di chi compila; il sito non li riceve e da qui non si vedono. Sulle pagine della Bussola non ci sono statistiche né pixel. Lo strumento non è un test validato e non va usato per valutare le persone.</p>';
	echo '</div>';
}
