<?php
/**
 * /guide-e-risorse/: le guide gratuite «Calendario marketing 2027» di Influencee Trends (le stesse di
 * influencee.it/guide-e-risorse/), servite da socialee.it. È anche la pagina dove arriva chi ha scelto un settore
 * senza calendario dedicato. Download diretto, senza modulo: il contatto si raccoglie con il quiz.
 */
defined( 'ABSPATH' ) || exit;

$scb_d = scb_imp_dati();
scb_apri(
	'Guide gratuite per imprenditori · Calendario marketing 2027 · Socialee',
	'.guide-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:16px;margin:26px 0 8px}.guida-card{display:flex;flex-direction:column;background:var(--off);border-radius:18px;overflow:hidden}.guida-card img{width:100%;height:auto;aspect-ratio:3/4;object-fit:cover;background:#ddd}.guida-card .dentro{padding:18px;display:flex;flex-direction:column;gap:8px;flex:1}.guida-card h2{font-size:20px;margin:0}.guida-card p{margin:0;font-size:15px}.guida-card .meta{font-size:13px;color:var(--sub)}.guida-card .btn{margin-top:auto}.invito{background:#000;color:#fff;border-radius:18px;padding:clamp(22px,4vw,34px);margin-top:22px;border-bottom:6px solid #E6007E}.invito h2{color:#fff}.invito p{color:#e6e6e6}',
	false,
	array(
		'etichetta'   => 'Guide per imprenditori',
		'sotto'       => 'Gratis, da Socialee',
		'robots'      => SCB_IMP_ATTIVO ? 'index,follow' : 'noindex,nofollow',
		'referrer'    => 'strict-origin-when-cross-origin',
		'descrizione' => 'Il calendario marketing 2027 per settore: quando sale l’interesse dei clienti, le stagioni da segnare e la checklist mese per mese. Sette guide PDF gratuite per negozi, PMI e aziende.',
		'canonical'   => home_url( SCB_GUIDE_PATH ),
		'piede'       => 'Socialee · Guide «Calendario marketing 2027» di Influencee Trends, a cura di Giorgia Palazzo.',
	)
);
?>
<section class="card" aria-labelledby="titolo-guide">
	<div class="eyebrow">Guide gratuite per imprenditori</div>
	<h1 id="titolo-guide">Calendario marketing <em>2027</em></h1>
	<p class="lead">Le date, le stagioni di vendita e le giornate da segnare nel 2027, settore per settore: quando sale l'interesse degli italiani, da quando prepararsi e cosa fare in negozio, online e con i creator. Scegli la tua guida e scaricala gratis.</p>
	<div class="guide-grid">
		<?php foreach ( $scb_d['guide'] as $scb_k => $scb_g ) : ?>
		<article class="guida-card" aria-labelledby="g-<?php echo esc_attr( $scb_k ); ?>">
			<img src="<?php echo esc_url( plugins_url( 'guide/copertina-' . $scb_k . '.webp', SCB_DIR . 'socialee-bussola.php' ) ); ?>" alt="Copertina della guida <?php echo esc_attr( $scb_g['titolo'] ); ?>" width="600" height="800" loading="lazy">
			<div class="dentro">
				<div class="tag"><?php echo esc_html( $scb_g['nome'] ); ?></div>
				<h2 id="g-<?php echo esc_attr( $scb_k ); ?>"><?php echo esc_html( $scb_g['titolo'] ); ?></h2>
				<p><?php echo esc_html( $scb_g['testo'] ); ?></p>
				<p class="meta">PDF · <?php echo (int) $scb_g['pagine']; ?> pagine</p>
				<a class="btn" href="<?php echo esc_url( scb_imp_guida_url( $scb_k ) ); ?>" target="_blank" rel="noopener" download>Scarica gratis</a>
			</div>
		</article>
		<?php endforeach; ?>
	</div>
	<p class="privacy">Guide realizzate da Influencee Trends, a cura di Giorgia Palazzo. Dati di interesse da Wikimedia, aggiornati al 9 novembre 2026.</p>
	<div class="invito">
		<div class="eyebrow">Il test gratuito</div>
		<h2>Che tipo di <em>imprenditore sei?</em></h2>
		<p>Scopri le spinte che guidano le tue scelte e come ti relazioni con clienti e collaboratori. Alla fine ricevi il profilo completo e la guida del tuo settore per email.</p>
		<div class="actions"><a class="btn" href="<?php echo esc_url( home_url( SCB_IMP_PATH ) ); ?>">Fai il test</a></div>
	</div>
</section>
<?php
scb_chiudi();
