<?php
/** Account senza il permesso per questa pagina. */
defined( 'ABSPATH' ) || exit;

$scb_dash = 'dashboard' === scb_quale_pagina();
scb_apri( 'Accesso non abilitato' );
?>
<section class="card" aria-labelledby="titolo-negato">
	<div class="eyebrow">Accesso non abilitato</div>
	<h1 id="titolo-negato"><?php echo $scb_dash ? 'Solo per <em>amministratori</em>' : 'Non ancora <em>abilitato</em>'; // phpcs:ignore WordPress.Security.EscapeOutput ?></h1>
	<?php if ( $scb_dash ) : ?>
		<p class="lead">La dashboard degli aggregati anonimi è riservata agli amministratori del sito.</p>
		<?php if ( scb_puo_compilare() ) : ?>
			<div class="actions"><a class="btn" href="<?php echo esc_url( home_url( SCB_BASE ) ); ?>">Vai al quiz</a></div>
		<?php endif; ?>
	<?php else : ?>
		<p class="lead">Il tuo account non è ancora abilitato alla Bussola Interna del team. Se fai parte del team Socialee, chiedi all'amministratore del sito di abilitarlo.</p>
	<?php endif; ?>
	<div class="actions"><a class="btn ghost" href="<?php echo esc_url( wp_logout_url( home_url( SCB_BASE ) ) ); ?>">Esci e accedi con un altro account</a></div>
</section>
<?php
scb_chiudi();
