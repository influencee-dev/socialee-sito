<?php
/**
 * Il quiz. Testi, domande, punteggi e cautele sono quelli del prototipo «bussola_interna_test_team.html» (Drive):
 * qui cambiano solo lo stile (Socialee), l'accessibilità (gruppo di scelte con tastiera e lettori di schermo) e la
 * chiave del salvataggio nel browser, separata per account. Nessuna risposta lascia il browser.
 */
defined( 'ABSPATH' ) || exit;

$scb_dati = require SCB_DIR . 'dati/questionario.php';
// Stessa pagina, due usi: la Bussola del team e «Che tipo di imprenditore sei?» (stesso quiz, poi il modulo).
$scb_imp  = ! empty( $GLOBALS['scb_imprenditori'] );
$scb_conf = array(
	'storage'   => $scb_imp ? 'socialee_imprenditore_v1' : ( is_user_logged_in() ? 'bussola_team_v1_' . scb_impronta_utente() : 'bussola_team_v1' ),
	'questions' => $scb_dati['questions'],
	'meta'      => $scb_dati['dimensions'],
	'imp'       => $scb_imp,
);
if ( $scb_imp ) {
	$scb_d               = scb_imp_dati();
	$scb_conf['types']   = $scb_d['types'];
	$scb_conf['endpoint'] = rest_url( 'socialee-bussola/v1/imprenditore' );
}
$scb_privacy = 'https://www.iubenda.com/privacy-policy/31252963';

scb_apri(
	$scb_imp ? 'Che tipo di imprenditore sei? Il test gratuito di Socialee' : 'La Bussola Interna — Test per il team',
	<<<'CSS'
.chip{padding:7px 12px;background:var(--fucsia-tenue);border-radius:999px;color:var(--fucsia-testo);font-size:12px;font-weight:700;display:inline-block;margin-bottom:18px}
.progress-top{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:12px;font-weight:700;font-size:13px}
.progress-bg{height:10px;background:#ececec;border-radius:999px;overflow:hidden;margin-bottom:30px}
.progress{height:100%;background:linear-gradient(90deg,#E6007E,#ff4fa8);transition:width .18s}
.question{font-family:var(--corpo);text-transform:none;font-size:clamp(22px,3.4vw,30px);font-weight:800;line-height:1.28;letter-spacing:-.02em;margin:0 0 28px}
.question:focus{outline:none}
.options{display:grid;gap:10px}
.opt{background:#fff;border:2px solid #dcdcdc;border-radius:14px;padding:14px 18px;min-height:56px;display:flex;justify-content:space-between;align-items:center;gap:15px;text-align:left;color:var(--ink);font-weight:600;width:100%}
@media(hover:hover){.opt:hover{border-color:#E6007E;background:#fff7fb}}
.opt[aria-checked=true]{border-color:#D60074;background:#D60074;color:#fff}
.opt[aria-checked=true]:focus-visible{outline-color:#000}
.num{display:grid;place-items:center;width:30px;height:30px;border:2px solid #d2d2d2;color:#444;border-radius:9px;flex:none;font-size:13px;font-weight:700}
.opt[aria-checked=true] .num{background:#fff;color:#B8005F;border-color:#fff}
.foot{margin-top:24px;display:flex;justify-content:space-between;align-items:center;gap:12px}
.hint{font-size:13px;color:var(--sub)}
.section{margin-bottom:26px}
.scores{display:grid;gap:17px}
.bar-line{display:flex;justify-content:space-between;gap:10px;font-size:15px;margin-bottom:6px}
.bar-line strong{white-space:nowrap}
.bar-track{height:12px;border-radius:999px;background:#ececec;overflow:hidden}
.bar-fill{height:100%;border-radius:999px}
.metric-group{border:2px solid var(--linea);border-radius:18px;padding:21px}
.insight{background:var(--off);border-radius:14px;padding:17px;margin:0}
.insight p{font-size:15px;margin:5px 0}
.insight h3{margin:4px 0 6px}
.tag{font-size:11px;letter-spacing:.08em;text-transform:uppercase;font-weight:800;color:var(--fucsia-testo)}
.report-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.report-top{padding:clamp(22px,4vw,34px);background:#000;color:#fff;border-radius:18px;margin-bottom:26px;border-bottom:6px solid #E6007E}
.report-top p{color:#e8e8e8}
.report-top .eyebrow{color:#ff7ab8}
.report-top h1{margin-top:9px}
.report-top .nota{font-size:13px;color:#d0d0d0}
.divider{height:2px;background:var(--linea);margin:26px 0}
details{margin-top:16px}
summary{cursor:pointer;font-weight:700;min-height:32px}
.sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
.salvato{margin-top:22px}
.area{max-width:none;min-height:120px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;line-height:1.5;resize:vertical}
.etichetta{display:block;font-weight:700;margin:12px 0 6px}
.anteprima{background:#000;color:#fff;border-radius:18px;padding:clamp(22px,4vw,34px);margin-bottom:24px;border-bottom:6px solid #E6007E}
.anteprima .eyebrow,.tipo-box .eyebrow{color:#ff7ab8}
.anteprima h2{font-size:clamp(30px,6vw,54px);margin:8px 0 10px}
.anteprima p{color:#e6e6e6;margin:0}
.velo{margin:18px 0 0;padding:16px;border-radius:14px;background:#141414;color:#8a8a8a;font-size:14px;filter:blur(2.5px);user-select:none}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 16px}
.form-grid .intera{grid-column:1/-1}
.campo label{display:block;font-weight:700;margin-bottom:6px;font-size:15px}
.campo .field{max-width:none}
select.field{appearance:auto}
.form-errore{color:#a3002f;font-size:14px;font-weight:600;margin-top:12px}
.field[aria-invalid=true]{border-color:#a3002f}
.consensi .check{margin-top:12px;font-size:14px}
.trappola{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}
.tipo-box{background:#000;color:#fff;border-radius:18px;padding:clamp(22px,4vw,34px);margin-bottom:22px}
.tipo-box h2{font-size:clamp(28px,5vw,46px);margin:8px 0 6px}
.tipo-box .motto{font-size:19px;font-weight:700;color:#ff7ab8;margin-bottom:10px}
.tipo-box p{color:#e8e8e8}
.tipo-box .report-grid{margin-top:16px}
.tipo-box .insight{background:#161616;color:#f0f0f0}
.tipo-box .tag{color:#ff7ab8}
.guida{display:flex;gap:18px;align-items:center;justify-content:space-between;flex-wrap:wrap;background:var(--fucsia-tenue);border-left:4px solid #E6007E;border-radius:14px;padding:18px 20px;margin:0 0 26px}
.guida p{margin:0;max-width:560px}
@media(max-width:650px){.form-grid{grid-template-columns:1fr}.guida .btn{width:100%}.report-grid{grid-template-columns:1fr}.opt{padding:12px 14px}.foot{flex-wrap:wrap}.foot .btn{flex:1}.foot .hint{order:3;width:100%}.actions .btn{flex:1 1 100%}}
@media print{.print-hide,.actions,#intro,#quiz,#esito{display:none!important}.report-top,.bar-fill{print-color-adjust:exact;-webkit-print-color-adjust:exact}.metric-group,.insight{break-inside:avoid}.report-grid{grid-template-columns:1fr 1fr}}
CSS
	,
	true,
	$scb_imp ? array(
		'etichetta'   => 'Che tipo di imprenditore sei?',
		'sotto'       => 'Il test gratuito di Socialee',
		'robots'      => SCB_IMP_ATTIVO ? 'index,follow' : 'noindex,nofollow',
		'referrer'    => 'strict-origin-when-cross-origin',
		'descrizione' => 'Scopri le cinque spinte che guidano le tue scelte da imprenditore e come ti relazioni con clienti e collaboratori. Profilo completo e, in regalo, il Calendario marketing 2027 del tuo settore.',
		'canonical'   => home_url( SCB_IMP_PATH ),
		'piede'       => 'Socialee · Influencee SRL · Un test di autoriflessione, non un test psicologico validato.',
	) : array()
);
?>
<section id="intro" class="card" aria-labelledby="titolo-intro">
<?php if ( $scb_imp ) : ?>
	<span class="chip">Test gratuito per imprenditori</span>
	<div class="eyebrow">Per titolari, fondatori e liberi professionisti</div>
	<h1 id="titolo-intro">Che tipo di <em>imprenditore sei?</em></h1>
	<p class="lead">Esplora le cinque spinte interiori che guidano le tue scelte da imprenditore e le modalità con cui ti relazioni con clienti, collaboratori e soci. Non ci sono risposte giuste o sbagliate: conta la tua esperienza reale. Alla fine ricevi il profilo completo e, in regalo, la guida «Calendario marketing 2027» del tuo settore.</p>
<?php else : ?>
	<span class="chip">Riflessione personale · v1.1</span>
	<div class="eyebrow">Un test per capirti meglio</div>
	<h1 id="titolo-intro">Che cosa ti guida <em>quando lavori con gli altri?</em></h1>
	<p class="lead">Esplora le tue cinque spinte interiori e le modalità con cui tendi a relazionarti. Non ci sono risposte giuste o sbagliate: conta la tua esperienza reale.</p>
<?php endif; ?>
	<div class="grid"><div class="stat"><strong>77</strong><span>affermazioni originali</span></div><div class="stat"><strong>~15–20'</strong><span>durata indicativa</span></div><div class="stat"><strong>11</strong><span>dimensioni esplorate</span></div></div>
	<div class="notice"><strong>Un punto importante.</strong> Questo è uno strumento esplorativo ispirato all'Analisi Transazionale, non un questionario psicometrico validato, una diagnosi o un test per la selezione/valutazione del personale. I risultati non dicono chi sei «davvero» e possono cambiare con il contesto.</div>
	<div class="<?php echo $scb_imp ? 'hidden' : ''; ?>">
	<label for="personname"><strong>Come vuoi essere chiamato nel tuo report?</strong> <span class="muted">(facoltativo)</span></label>
	<div class="spazio"></div>
	<input id="personname" class="field" placeholder="Nome o pseudonimo" maxlength="80" autocomplete="off">
	</div>
	<label class="check"><input type="checkbox" id="agree"><span>Ho capito che è un esercizio di autoriflessione e che le risposte restano nel browser di questo dispositivo finché non scelgo di esportarle. La partecipazione e l'eventuale condivisione sono volontarie.</span></label>
	<div class="actions"><button type="button" class="btn" id="start" disabled>Inizia il percorso →</button><button type="button" class="btn ghost hidden" id="resume">Riprendi il test salvato</button></div>
<?php if ( $scb_imp ) : ?>
	<p class="privacy salvato">Le risposte alle singole affermazioni restano nel tuo browser. Alla fine, con il modulo, ci mandi solo i tuoi dati di contatto e i punteggi finali, per ricevere il profilo e la guida. <a href="<?php echo esc_url( $scb_privacy ); ?>" target="_blank" rel="noopener">Informativa privacy</a>.</p>
<?php else : ?>
	<p class="privacy salvato">Nessuna risposta è trasmessa automaticamente a un server o a Google Drive. Puoi esportare i tuoi dati, condividere un risultato anonimo con il tuo team oppure, dal report, mandare la tua scheda ai colleghi e aprire un prompt in ChatGPT o Gemini: sempre e solo se lo scegli tu.</p>
	<details>
		<summary>Privacy di questa pagina</summary>
		<p class="privacy">La pagina è servita da socialee.it (Influencee SRL) a chi ha il link: non serve un account e non è indicizzata dai motori di ricerca. Il sito non riceve né salva le risposte, i punteggi o il nome del report: il calcolo avviene nel tuo browser e i dati restano nella memoria locale di questo dispositivo. Su questa pagina non ci sono statistiche, pixel pubblicitari o cookie di profilazione: nessun cookie. I bottoni «Apri in ChatGPT/Gemini» mandano il prompt (punteggi, e per il prompt di team i nomi scelti dai colleghi) al servizio scelto, solo quando li tocchi. La partecipazione è volontaria e serve solo all'autoriflessione: i risultati non si usano per valutazioni, performance review o decisioni sul personale. Su un computer condiviso, prima di uscire usa «Elimina i miei dati dal browser».</p>
	</details>
<?php endif; ?>
</section>

<section id="quiz" class="card hidden" aria-labelledby="question">
	<div class="progress-top"><span id="phase"></span><span id="count" aria-live="polite">1 di 77</span></div>
	<div class="progress-bg" aria-hidden="true"><div class="progress" id="bar"></div></div>
	<div class="eyebrow" id="contesto">Pensando a come ti comporti di solito al lavoro…</div>
	<div class="spazio"></div>
	<h2 class="question" id="question" tabindex="-1"></h2>
	<div class="options" id="options" role="radiogroup" aria-labelledby="contesto question"></div>
	<div class="foot"><button type="button" class="btn ghost" id="prev">← Indietro</button><span class="hint">Scegli quanto l'affermazione ti descrive (anche con i tasti 1–5).</span><button type="button" class="btn" id="next" disabled>Avanti →</button></div>
	<div class="actions"><button type="button" class="btn ghost" id="saveprogress">Salva avanzamento</button><button type="button" class="btn danger" id="reset">Cancella risposte</button></div>
</section>

<?php if ( $scb_imp ) : ?>
<section id="esito" class="card hidden" aria-labelledby="tipo-anteprima">
	<div class="anteprima">
		<div class="eyebrow">Il tuo profilo è pronto</div>
		<h2 id="tipo-anteprima" tabindex="-1"></h2>
		<p id="motto-anteprima"></p>
		<div class="velo" aria-hidden="true">Le cinque spinte interiori · gli stati dell'Io · la tua lettura · un esperimento per la prossima settimana · il prompt per leggerlo con l'AI</div>
	</div>
	<h2>Ricevi il profilo completo e la guida del tuo settore</h2>
	<p class="muted">Ti mostriamo subito il profilo completo e ti mandiamo per email la guida «Calendario marketing 2027» del tuo settore: quando sale l'interesse dei clienti, le stagioni da segnare e la checklist mese per mese. Gratis.</p>
	<form id="modulo" novalidate>
		<div class="form-grid">
			<div class="campo"><label for="f-nome">Nome</label><input class="field" id="f-nome" name="nome" autocomplete="given-name" maxlength="60" required></div>
			<div class="campo"><label for="f-cognome">Cognome</label><input class="field" id="f-cognome" name="cognome" autocomplete="family-name" maxlength="60" required></div>
			<div class="campo"><label for="f-email">Email</label><input class="field" id="f-email" name="email" type="email" autocomplete="email" inputmode="email" maxlength="120" required></div>
			<div class="campo"><label for="f-telefono">Telefono</label><input class="field" id="f-telefono" name="telefono" type="tel" autocomplete="tel" inputmode="tel" maxlength="20" placeholder="es. 333 123 4567" required></div>
			<div class="campo"><label for="f-provincia">Provincia</label><select class="field" id="f-provincia" name="provincia" required><option value="">Scegli la provincia</option><?php foreach ( $scb_d['province'] as $scb_p ) : ?><option><?php echo esc_html( $scb_p ); ?></option><?php endforeach; ?></select></div>
			<div class="campo"><label for="f-azienda">Nome dell'azienda</label><input class="field" id="f-azienda" name="azienda" autocomplete="organization" maxlength="120" required></div>
			<div class="campo intera"><label for="f-settore">Settore</label><select class="field" id="f-settore" name="settore" required><option value="">Scegli il settore</option><?php foreach ( $scb_d['sectors'] as $scb_s ) : ?><option value="<?php echo esc_attr( $scb_s['slug'] ); ?>"><?php echo esc_html( $scb_s['etichetta'] ); ?></option><?php endforeach; ?></select></div>
		</div>
		<div class="trappola" aria-hidden="true"><label for="f-sito">Non compilare</label><input id="f-sito" name="sito_web" tabindex="-1" autocomplete="off"></div>
		<div class="consensi">
			<label class="check"><input type="checkbox" id="f-privacy" name="privacy" required><span>Ho letto l'<a href="<?php echo esc_url( $scb_privacy ); ?>" target="_blank" rel="noopener">informativa privacy</a> e voglio ricevere il profilo e la guida per email. <span class="muted">(obbligatorio)</span></span></label>
			<label class="check"><input type="checkbox" id="f-marketing" name="marketing"><span>Sì, voglio ricevere da Socialee altri regali e consigli per imprenditori, come l'accesso all'Academy. Posso disiscrivermi quando voglio. <span class="muted">(facoltativo)</span></span></label>
		</div>
		<p class="form-errore" id="errore-form" role="alert" aria-live="assertive"></p>
		<div class="actions"><button type="submit" class="btn" id="invia">Mostrami il profilo completo →</button></div>
		<p class="privacy">Titolare del trattamento: Influencee SRL (Socialee). Usiamo i tuoi dati per mandarti il profilo e la guida e, solo se lo scegli, per altri contenuti. Le risposte alle singole affermazioni restano nel tuo browser: ci arrivano solo i punteggi finali.</p>
	</form>
</section>
<?php endif; ?>

<section id="report" class="card hidden" aria-labelledby="reportTitle">
	<div class="report-top"><div class="eyebrow">Profilo personale · report orientativo</div><h1 id="reportTitle" tabindex="-1">La tua bussola</h1><p id="reportDate"></p><div class="nota">Questi punteggi non sono percentili o misure cliniche: esprimono soltanto la frequenza delle risposte su scale costruite per questo prototipo.</div></div>
<?php if ( $scb_imp ) : ?>
	<div class="tipo-box" id="tipo-box">
		<div class="eyebrow">Che tipo di imprenditore sei</div>
		<h2 id="tipo-nome"></h2>
		<div class="motto" id="tipo-motto"></div>
		<p id="tipo-ritratto"></p>
		<div class="report-grid">
			<div class="insight"><div class="tag">Il tuo punto di forza</div><p id="tipo-forza"></p></div>
			<div class="insight"><div class="tag">Tu e i social</div><p id="tipo-social"></p></div>
		</div>
	</div>
	<div class="guida print-hide" id="box-guida">
		<p><strong>La tua guida è pronta.</strong> <span id="testo-guida"></span> Te l'abbiamo mandata anche per email.</p>
		<a class="btn" id="scarica-guida" href="#" target="_blank" rel="noopener">Scarica la guida (PDF)</a>
	</div>
<?php endif; ?>
	<div class="section"><h2>1. Le cinque spinte interiori</h2><p class="muted">Come riconosci i tuoi automatismi quando affronti responsabilità e pressione.</p><div class="metric-group"><div class="scores" id="driverScores"></div></div></div>
	<div class="section"><h2>2. Gli stati dell'Io</h2><p class="muted">Modalità di risposta che possono attivarsi in situazioni diverse. La scala «Bambino Ribelle» è separata ed esplorativa, non un sesto fattore dell'ESQ-R-I.</p><div class="metric-group"><div class="scores" id="egoScores"></div></div></div>
	<div class="section"><h2>3. La tua lettura</h2><div class="report-grid" id="insights"></div></div>
	<div class="section"><h2>4. Un esperimento per la prossima settimana</h2><div class="insight" id="experiment"></div></div>
	<div class="section print-hide" id="ai">
		<h2>5. Fatti aiutare a leggerlo</h2>
		<p class="muted">Qui sotto c'è un prompt già compilato con i tuoi punteggi. Aprilo in ChatGPT o in Gemini per avere una lettura su come comunichi e collabori, con consigli per te e per chi lavora con te. Puoi modificarlo prima di inviarlo.</p>
		<div class="notice">Il prompt contiene solo i punteggi delle 11 dimensioni: niente nome, niente risposte alle singole domande. Quando lo apri, il testo va al servizio che scegli (OpenAI o Google), con il tuo account e le sue regole sulla privacy: fallo solo se vuoi. Il sito non riceve niente.</div>
		<label for="promptMio" class="etichetta">Il tuo prompt</label>
		<textarea id="promptMio" class="field area" rows="9" spellcheck="false"></textarea>
		<div class="actions"><button type="button" class="btn" data-ai="chatgpt" data-fonte="promptMio">Apri in ChatGPT ↗</button><button type="button" class="btn secondary" data-ai="gemini" data-fonte="promptMio">Apri in Gemini ↗</button><button type="button" class="btn ghost" data-ai="copia" data-fonte="promptMio">Copia il prompt</button></div>
		<p class="privacy" id="avvisoAi" aria-live="polite"></p>
	</div>
<?php if ( ! $scb_imp ) : ?>
	<div class="section print-hide" id="team">
		<h2>6. Confrontati con il team</h2>
		<p class="muted">La Bussola serve soprattutto a capire come comunicare meglio tra voi. Scambiatevi le schede con i colleghi che vogliono farlo: incollando le loro qui sotto ottieni un prompt per leggere insieme come collaborare, chi ha bisogno di cosa e quali accordi di comunicazione prendere.</p>
		<h3>La tua scheda da condividere</h3>
		<label for="nomeTeam" class="etichetta">Il nome con cui ti presenti ai colleghi</label>
		<input id="nomeTeam" class="field" maxlength="40" autocomplete="off" placeholder="Nome">
		<div class="spazio"></div>
		<textarea id="schedaMia" class="field area" rows="6" readonly aria-label="La tua scheda da condividere"></textarea>
		<div class="actions"><button type="button" class="btn" id="condividiScheda">Condividi la scheda</button><button type="button" class="btn ghost" id="copiaScheda">Copia la scheda</button></div>
		<p class="privacy">La scheda contiene il nome che scegli e i punteggi, non le risposte. Mandala solo a chi vuoi, per esempio nel gruppo del team; chi la riceve non la inoltra ad altri. Il sito non la vede.</p>
		<div class="divider"></div>
		<h3>Le schede dei colleghi</h3>
		<label for="schedeColleghi" class="etichetta">Incolla qui le schede che ti hanno mandato (anche tutte insieme)</label>
		<textarea id="schedeColleghi" class="field area" rows="6" spellcheck="false" placeholder="Incolla qui…"></textarea>
		<p class="privacy" id="riconosciute" aria-live="polite">Nessuna scheda incollata.</p>
		<div id="bloccoTeam" class="hidden">
			<label for="promptTeam" class="etichetta">Prompt per il team</label>
			<textarea id="promptTeam" class="field area" rows="9" spellcheck="false"></textarea>
			<div class="actions"><button type="button" class="btn" data-ai="chatgpt" data-fonte="promptTeam">Apri in ChatGPT ↗</button><button type="button" class="btn secondary" data-ai="gemini" data-fonte="promptTeam">Apri in Gemini ↗</button><button type="button" class="btn ghost" data-ai="copia" data-fonte="promptTeam">Copia il prompt</button></div>
			<p class="privacy" id="avvisoTeam" aria-live="polite"></p>
		</div>
		<p class="privacy">Le schede incollate restano solo in questa pagina e spariscono quando la chiudi. Il prompt per il team contiene i nomi e i punteggi di chi ti ha mandato la scheda: usalo solo se loro sono d'accordo.</p>
	</div>
<?php endif; ?>
	<p class="privacy">Interpretazioni indicative, non valutazioni professionali. I punteggi sono trasformati linearmente in scala 0–100 dal valore medio di risposte 1–5 (tenendo conto delle domande invertite). Il contesto e l'autopercezione influenzano i risultati.</p>
	<div class="divider"></div>
	<h3>Esporta solo quello che vuoi condividere</h3>
	<p class="muted"><?php echo $scb_imp ? 'Il report completo e le risposte restano nel tuo browser: salvalo in PDF o copialo per riguardarlo con calma.' : 'Il report completo e le risposte possono rimanere privati. Per un riepilogo collettivo usa soltanto l\'esportazione anonima, senza nome e senza risposte ai singoli item.'; ?></p>
	<div class="actions print-hide"><button type="button" class="btn" id="print">Stampa / salva PDF</button><?php if ( ! $scb_imp ) : ?><button type="button" class="btn secondary" id="anon">Esporta per aggregato team (JSON anonimo)</button><?php endif; ?><button type="button" class="btn ghost" id="personal">Scarica archivio personale (JSON)</button><button type="button" class="btn ghost" id="copy">Copia sintesi</button><button type="button" class="btn danger" id="delete">Elimina i miei dati dal browser</button></div>
	<details class="print-hide"><summary>Come viene calcolato il punteggio?</summary><p class="privacy">Ogni affermazione riceve da 1 a 5 punti; alcuni item sono invertiti. Per ogni dimensione viene calcolata la media: (media−1)×25. In questo prototipo, un punteggio più alto significa soltanto maggiore accordo con le affermazioni relative alla dimensione. Le 11 dimensioni non sono direttamente comparabili come tratti psicometrici validati.</p></details>
</section>
<?php
$scb_js  = 'const CONF=' . wp_json_encode( $scb_conf, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP ) . ";\n";
require_once SCB_DIR . 'vista/quiz-script.php';
$scb_js .= scb_js_quiz();
scb_chiudi( $scb_js );
