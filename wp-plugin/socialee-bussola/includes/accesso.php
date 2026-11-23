<?php
/**
 * Chi entra nella Bussola.
 * - Quiz (/bussola-team/): permesso socialee_bussola. Ce l'hanno il ruolo «Team Socialee» (creato qui, può solo
 *   leggere), gli amministratori e gli utenti a cui l'amministratore spunta «Accesso alla Bussola» nel profilo.
 * - Dashboard (/bussola-team/dashboard/): permesso socialee_bussola_admin, solo amministratori.
 * Il controllo è sul server, a ogni richiesta, con il login di WordPress: niente URL segreti, niente password comuni.
 */

defined( 'ABSPATH' ) || exit;

/** Crea il ruolo e dà i permessi agli amministratori (una volta per versione dei ruoli; anche se l'attivazione è silenziosa). */
function scb_ruoli() {
	if ( ! get_role( SCB_RUOLO ) ) {
		add_role( SCB_RUOLO, 'Team Socialee', array( 'read' => true, SCB_CAP => true ) );
	}
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->add_cap( SCB_CAP );
		$admin->add_cap( SCB_CAP_ADMIN );
	}
	update_option( 'scb_ruoli_v', SCB_RUOLI_V, false );
}

add_action(
	'init',
	function () {
		if ( SCB_RUOLI_V !== get_option( 'scb_ruoli_v' ) ) {
			scb_ruoli();
		}
	}
);

function scb_puo_compilare() {
	return is_user_logged_in() && current_user_can( SCB_CAP );
}

function scb_puo_vedere_aggregati() {
	return is_user_logged_in() && current_user_can( SCB_CAP_ADMIN );
}

/** Impronta stabile e non reversibile dell'utente: tiene separate nel browser le risposte di account diversi. */
function scb_impronta_utente() {
	return substr( hash_hmac( 'sha256', 'bussola|' . get_current_user_id(), wp_salt( 'auth' ) ), 0, 16 );
}

/* ─── Casella «Accesso alla Bussola» nel profilo (solo chi può gestire gli utenti) ─── */

function scb_casella_profilo( $user ) {
	if ( ! current_user_can( 'promote_users' ) ) {
		return;
	}
	$dal_ruolo = in_array( SCB_RUOLO, (array) $user->roles, true ) || in_array( 'administrator', (array) $user->roles, true );
	$diretto   = ! empty( $user->allcaps[ SCB_CAP ] ) && ! $dal_ruolo;
	?>
	<h2>La Bussola Interna · Team</h2>
	<table class="form-table" role="presentation"><tr>
		<th scope="row">Accesso alla Bussola</th>
		<td>
			<?php if ( $dal_ruolo ) : ?>
				<p>Sì, dal ruolo dell'utente.</p>
			<?php else : ?>
				<label><input type="checkbox" name="scb_accesso" value="1" <?php checked( $diretto ); ?>> Può compilare il quiz su <code>/bussola-team/</code></label>
			<?php endif; ?>
			<p class="description">Le risposte restano nel browser di chi compila: l'amministratore non le vede.</p>
		</td>
	</tr></table>
	<?php
}
add_action( 'show_user_profile', 'scb_casella_profilo' );
add_action( 'edit_user_profile', 'scb_casella_profilo' );

function scb_salva_profilo( $user_id ) {
	// WordPress ha già controllato il nonce del modulo del profilo prima di questi hook.
	if ( ! current_user_can( 'promote_users' ) || ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	$user = get_userdata( $user_id );
	if ( ! $user || in_array( SCB_RUOLO, (array) $user->roles, true ) || in_array( 'administrator', (array) $user->roles, true ) ) {
		return;
	}
	if ( ! empty( $_POST['scb_accesso'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$user->add_cap( SCB_CAP );
	} else {
		$user->remove_cap( SCB_CAP );
	}
}
add_action( 'personal_options_update', 'scb_salva_profilo' );
add_action( 'edit_user_profile_update', 'scb_salva_profilo' );

/** Chi ha il ruolo «Team Socialee» non vede la barra di amministrazione. */
add_filter(
	'show_admin_bar',
	function ( $show ) {
		$u = wp_get_current_user();
		return ( $u && array( SCB_RUOLO ) === array_values( (array) $u->roles ) ) ? false : $show;
	}
);
