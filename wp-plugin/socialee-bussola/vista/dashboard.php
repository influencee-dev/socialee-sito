<?php
/**
 * Dashboard degli aggregati (prototipo «dashboard_aggregati_team.html»): solo amministratori. Legge nel browser i
 * JSON anonimi scelti a mano, mostra le medie da 5 file in su, non salva e non invia niente.
 */
defined( 'ABSPATH' ) || exit;

$scb_dati = require SCB_DIR . 'dati/questionario.php';
$scb_meta = array();
foreach ( $scb_dati['dimensions'] as $scb_k => $scb_d ) {
	$scb_meta[ $scb_k ] = array( $scb_d['label'], $scb_d['color'] );
}

scb_apri(
	'La Bussola Interna · Aggregati del team',
	'.upload{display:block;padding:26px;background:var(--fucsia-tenue);border:2px dashed #E6007E;border-radius:16px;text-align:center;font-weight:700;cursor:pointer}.upload:focus-within{outline:3px solid #E6007E;outline-offset:3px}.upload input{position:absolute;opacity:0;width:1px;height:1px}.group{border:2px solid var(--linea);padding:20px;border-radius:16px;margin:20px 0}.metric{margin:12px 0 18px}.row{display:flex;justify-content:space-between;font-size:15px;gap:12px;margin-bottom:7px}.track{height:12px;background:#ececec;border-radius:999px;overflow:hidden}.fill{height:100%;border-radius:999px}#report{display:none}#messages{white-space:pre-line;font-size:13px;padding-top:15px}.nota-fondo{color:#b5b5b5}@media print{#inputPanel,.print-hide{display:none!important}.fill{print-color-adjust:exact;-webkit-print-color-adjust:exact}}'
);
?>
<section class="card" id="inputPanel" aria-labelledby="titolo-dash">
	<div class="eyebrow">Modalità team · solo amministratori</div>
	<h1 id="titolo-dash">Uno sguardo collettivo, <em>senza profilare le persone.</em></h1>
	<p>Importa almeno 5 file «bussola-team-anonimo.json» che i partecipanti hanno scelto volontariamente di condividere. Il riepilogo mostra soltanto punteggi medi di gruppo. Nessun file viene inviato online.</p>
	<div class="notice">Usa soltanto i JSON anonimi: non caricare i JSON «archivio personale». Per gruppi piccoli, anche i dati senza nome possono essere potenzialmente riconducibili alle persone.</div>
	<label class="upload">📂 Seleziona insieme i JSON anonimi dei partecipanti<input type="file" multiple accept=".json,application/json" id="fileinput"></label>
	<div id="messages" class="muted" aria-live="polite"></div>
	<div class="actions"><button type="button" id="clear" class="btn ghost">Svuota tutti i file importati</button></div>
</section>
<section class="card" id="report" aria-labelledby="reportTitle">
	<div class="eyebrow">Sintesi del team · non validata</div>
	<h2 id="reportTitle">Risultati di gruppo</h2>
	<div class="notice">Le medie sono normalizzazioni delle risposte, non punteggi normativi. Non possono essere usate per giudicare o confrontare i collaboratori.</div>
	<div id="scoreGroups"></div>
	<div class="actions print-hide"><button type="button" class="btn" id="csv">Esporta solo medie (CSV)</button><button type="button" class="btn ghost" id="print">Stampa / PDF</button></div>
</section>
<p class="privacy nota-fondo">Nessun nome viene letto, ricercato o mostrato. L'elaborazione avviene soltanto nel tuo browser. Questo cruscotto non salva dati permanentemente.</p>
<?php
$scb_js  = 'const meta=' . wp_json_encode( $scb_meta, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . ";\n";
$scb_js .= <<<'JS'
'use strict';
const groups=[['Driver',['dp','ds','dt','dh','do']],['Stati dell’Io',['pn','pa','ad','bl','ba']],['Modalità aggiuntiva esplorativa',['br']]];
const entries=new Map();let agg=null;const $=x=>document.getElementById(x);
$('fileinput').onchange=async e=>{let accepted=0,rejected=0;for(const file of Array.from(e.target.files)){try{if(file.size>500000)throw Error('dimensioni inattese');const v=JSON.parse(await file.text());if(v.schema!=='bussola-team-anon-v1'||!v.response_id||v.raw_responses_included!==false||!v.scores||Object.keys(meta).some(k=>typeof v.scores[k]!=='number'||v.scores[k]<0||v.scores[k]>100))throw Error('formato incompatibile');if(entries.has(v.response_id))continue;entries.set(v.response_id,v.scores);accepted++}catch(ex){rejected++}}$('fileinput').value='';render(accepted,rejected)};
function render(accepted=0,rejected=0){const n=entries.size;$('messages').textContent=n+' file anonimi distinti caricati.'+(accepted||rejected?' Nuovi: '+accepted+'. Scartati: '+rejected+'.':'')+(n<5?' Servono almeno '+(5-n)+' ulteriori file per le medie.':'');if(n<5){$('report').style.display='none';agg=null;return}agg={};for(const k of Object.keys(meta))agg[k]=Math.round([...entries.values()].reduce((s,v)=>s+v[k],0)/n);$('report').style.display='block';$('reportTitle').textContent='Medie anonime · '+n+' partecipanti';const container=$('scoreGroups');container.replaceChildren();for(const [title,keys] of groups){const section=document.createElement('section');section.className='group';const heading=document.createElement('h3');heading.textContent=title;section.append(heading);for(const k of [...keys].sort((a,b)=>agg[b]-agg[a])){const m=document.createElement('div');m.className='metric';const r=document.createElement('div');r.className='row';const a=document.createElement('span');a.textContent=meta[k][0];const v=document.createElement('strong');v.textContent=agg[k]+'/100';r.append(a,v);const tr=document.createElement('div');tr.className='track';tr.setAttribute('role','img');tr.setAttribute('aria-label',meta[k][0]+': '+agg[k]+' su 100');const f=document.createElement('div');f.className='fill';f.style.width=agg[k]+'%';f.style.background=meta[k][1];tr.append(f);m.append(r,tr);section.append(m)}container.append(section)}}
$('clear').onclick=()=>{entries.clear();render()};$('print').onclick=()=>window.print();$('csv').onclick=()=>{if(!agg)return;const n=entries.size;const rows=[['dimensione','media_su_100','n'],...Object.keys(meta).map(k=>[meta[k][0],String(agg[k]),String(n)])];const csv='﻿'+rows.map(cols=>cols.map(v=>'"'+v.replace(/"/g,'""')+'"').join(';')).join('\r\n');const blob=new Blob([csv],{type:'text/csv;charset=utf-8'});const url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download='bussola_team_medie_anonime.csv';document.body.append(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),2000)};
render();
JS;
scb_chiudi( $scb_js );
