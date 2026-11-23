<?php
/** Chi non ha fatto l'accesso: modulo di login di WordPress (stesso controllo di wp-login.php, plugin di sicurezza compresi). */
defined( 'ABSPATH' ) || exit;

$scb_qui = home_url( scb_percorso_richiesto() );
scb_apri(
	'Area riservata al team',
	'.accedi{max-width:560px}.accedi form p{margin:0 0 14px}.accedi label{display:block;font-weight:600;margin-bottom:6px}.accedi input[type=text],.accedi input[type=password]{width:100%;border:2px solid #cfcfcf;border-radius:12px;padding:12px 14px;min-height:48px}.accedi input[type=text]:focus,.accedi input[type=password]:focus{border-color:#E6007E;outline:none;box-shadow:0 0 0 3px rgba(230,0,126,.25)}.accedi .login-remember label{display:flex;gap:10px;align-items:center;font-weight:500}.accedi .login-remember input{width:20px;height:20px;accent-color:#D60074}.accedi input[type=submit]{background:#D60074;border:2px solid #D60074;border-radius:999px;padding:12px 26px;min-height:48px;color:#fff;font-weight:700;cursor:pointer}.accedi input[type=submit]:hover{background:#B8005F}',
	false
);
?>
<section class="card accedi" aria-labelledby="titolo-accedi">
	<div class="eyebrow">Solo per il team Socialee</div>
	<h1 id="titolo-accedi">Area <em>riservata</em></h1>
	<p class="lead">La dashboard degli aggregati è solo per gli amministratori di socialee.it. Il quiz invece è libero: <a href="<?php echo esc_url( home_url( SCB_BASE ) ); ?>">vai al quiz</a>.</p>
	<?php
	wp_login_form(
		array(
			'redirect'       => $scb_qui,
			'label_username' => 'Email o nome utente',
			'label_password' => 'Password',
			'label_remember' => 'Ricordami su questo dispositivo',
			'label_log_in'   => 'Accedi',
			'remember'       => true,
		)
	);
	?>
	<p class="privacy"><a href="<?php echo esc_url( wp_lostpassword_url( $scb_qui ) ); ?>">Password dimenticata?</a> · Non hai un account? Chiedilo all'amministratore del sito.</p>
</section>
<?php
scb_chiudi();
