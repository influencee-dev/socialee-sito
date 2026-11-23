<?php
/** Pannello «Utenti → Bussola del team»: chi ha l'accesso e come darlo. Nessuna risposta passa da qui. */

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
	$nuovo = admin_url( 'user-new.php' );
	echo '<div class="wrap"><h1>La Bussola Interna · Team</h1>';
	echo '<p>Quiz: <a href="' . esc_url( $quiz ) . '" target="_blank" rel="noopener"><code>' . esc_html( $quiz ) . '</code></a> — solo per chi ha fatto l\'accesso e ha il permesso. Dashboard degli aggregati anonimi (solo amministratori): <a href="' . esc_url( $dash ) . '" target="_blank" rel="noopener"><code>' . esc_html( $dash ) . '</code></a>.</p>';
	echo '<h2>Come dare l\'accesso a un collaboratore</h2><ol>';
	echo '<li><a href="' . esc_url( $nuovo ) . '">Utenti → Aggiungi nuovo</a>, ruolo <strong>Team Socialee</strong> (può solo aprire la Bussola: niente articoli, niente pannello). WordPress gli manda l\'email per scegliere la password.</li>';
	echo '<li>Per un utente che esiste già con un altro ruolo: apri il suo profilo e spunta <strong>Accesso alla Bussola</strong>.</li>';
	echo '<li>Per togliere l\'accesso: cambia ruolo, togli la spunta o elimina l\'utente.</li></ol>';
	echo '<p><strong>Privacy:</strong> le risposte e i punteggi restano nel browser di chi compila; il sito non li riceve e da qui non si vedono. Sulle pagine della Bussola non ci sono statistiche né pixel. Lo strumento non è un test validato e non va usato per valutare le persone.</p>';
	$utenti = scb_utenti_con_accesso();
	echo '<h2>Account con accesso (' . count( $utenti ) . ')</h2>';
	echo '<table class="widefat striped" style="max-width:760px"><thead><tr><th>Utente</th><th>Ruolo</th><th>Dashboard</th></tr></thead><tbody>';
	foreach ( $utenti as $u ) {
		$ruoli = array_map(
			function ( $r ) {
				$n = wp_roles()->get_names();
				return isset( $n[ $r ] ) ? translate_user_role( $n[ $r ] ) : $r;
			},
			(array) $u->roles
		);
		echo '<tr><td><a href="' . esc_url( get_edit_user_link( $u->ID ) ) . '">' . esc_html( $u->display_name ) . '</a></td><td>' . esc_html( implode( ', ', $ruoli ) ) . '</td><td>' . ( $u->has_cap( SCB_CAP_ADMIN ) ? 'sì' : '—' ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}
