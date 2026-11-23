<?php
/**
 * Domande, dimensioni e testi della Bussola Interna (copia di questionario_sorgente.json, cartella Drive
 * «SOCIALEE - La Bussola Interna - Team»). Sta in un file PHP perché non sia scaricabile da fuori.
 * Contenuti e punteggi NON si cambiano qui senza la titolare.
 */

defined( 'ABSPATH' ) || exit;

return json_decode(
	<<<'JSON'
{
 "version": "1.0",
 "questions": [
  {
   "id": "dp1",
   "key": "dp",
   "section": "driver",
   "text": "Prima di consegnare, controllo anche dettagli che probabilmente nessuno noterà.",
   "reverse": false
  },
  {
   "id": "ds1",
   "key": "ds",
   "section": "driver",
   "text": "Preferisco risolvere i problemi da solo anche quando chiedere aiuto sarebbe utile.",
   "reverse": false
  },
  {
   "id": "dt1",
   "key": "dt",
   "section": "driver",
   "text": "Quando qualcosa mi riesce facilmente, mi chiedo se ho fatto abbastanza.",
   "reverse": false
  },
  {
   "id": "dh1",
   "key": "dh",
   "section": "driver",
   "text": "Sento fretta anche quando non esiste una scadenza ravvicinata.",
   "reverse": false
  },
  {
   "id": "do1",
   "key": "do",
   "section": "driver",
   "text": "Accetto richieste anche quando vorrei rifiutarle.",
   "reverse": false
  },
  {
   "id": "dp2",
   "key": "dp",
   "section": "driver",
   "text": "Un piccolo errore può rovinare la soddisfazione per un buon risultato.",
   "reverse": false
  },
  {
   "id": "ds2",
   "key": "ds",
   "section": "driver",
   "text": "Mi viene spontaneo nascondere la preoccupazione agli altri.",
   "reverse": false
  },
  {
   "id": "dt2",
   "key": "dt",
   "section": "driver",
   "text": "Mi sento particolarmente meritevole quando ho dovuto fare molta fatica.",
   "reverse": false
  },
  {
   "id": "dh2",
   "key": "dh",
   "section": "driver",
   "text": "Mi irrito se una persona impiega più tempo di quanto mi aspettassi.",
   "reverse": false
  },
  {
   "id": "do2",
   "key": "do",
   "section": "driver",
   "text": "Prima di dire ciò che penso, considero molto come reagiranno gli altri.",
   "reverse": false
  },
  {
   "id": "dp3",
   "key": "dp",
   "section": "driver",
   "text": "Fatico a chiudere un’attività quando so che potrei migliorarla ancora.",
   "reverse": false
  },
  {
   "id": "ds3",
   "key": "ds",
   "section": "driver",
   "text": "Quando sono in difficoltà, cerco di non farlo notare.",
   "reverse": false
  },
  {
   "id": "dt3",
   "key": "dt",
   "section": "driver",
   "text": "Anche dopo aver raggiunto l’obiettivo, tendo ad aggiungere altro lavoro.",
   "reverse": false
  },
  {
   "id": "dh3",
   "key": "dh",
   "section": "driver",
   "text": "Faccio contemporaneamente più cose per guadagnare tempo.",
   "reverse": false
  },
  {
   "id": "do3",
   "key": "do",
   "section": "driver",
   "text": "Evito un disaccordo per non creare tensione.",
   "reverse": false
  },
  {
   "id": "dp4",
   "key": "dp",
   "section": "driver",
   "text": "Accetto senza difficoltà qualche imperfezione in un lavoro utile.",
   "reverse": true
  },
  {
   "id": "ds4",
   "key": "ds",
   "section": "driver",
   "text": "Mi riesce naturale dire «qui ho bisogno di supporto».",
   "reverse": true
  },
  {
   "id": "dt4",
   "key": "dt",
   "section": "driver",
   "text": "A volte complico un compito perché la soluzione semplice mi sembra poco seria.",
   "reverse": false
  },
  {
   "id": "dh4",
   "key": "dh",
   "section": "driver",
   "text": "Quando non c’è urgenza, riesco a procedere con calma.",
   "reverse": true
  },
  {
   "id": "do4",
   "key": "do",
   "section": "driver",
   "text": "Riesco a esprimere un’opinione impopolare con serenità.",
   "reverse": true
  },
  {
   "id": "dp5",
   "key": "dp",
   "section": "driver",
   "text": "Mi preparo a lungo per ridurre al minimo il rischio di sbagliare.",
   "reverse": false
  },
  {
   "id": "ds5",
   "key": "ds",
   "section": "driver",
   "text": "Evito di mostrare incertezza quando gli altri contano su di me.",
   "reverse": false
  },
  {
   "id": "dt5",
   "key": "dt",
   "section": "driver",
   "text": "Posso essere soddisfatto di un risultato ottenuto senza grande sforzo.",
   "reverse": true
  },
  {
   "id": "dh5",
   "key": "dh",
   "section": "driver",
   "text": "Mi sorprendo a completare mentalmente le frasi degli altri.",
   "reverse": false
  },
  {
   "id": "do5",
   "key": "do",
   "section": "driver",
   "text": "Mi capita di prendere un impegno per non deludere qualcuno.",
   "reverse": false
  },
  {
   "id": "dp6",
   "key": "dp",
   "section": "driver",
   "text": "Mi accorgo di correggere dettagli anche quando altre priorità attendono.",
   "reverse": false
  },
  {
   "id": "ds6",
   "key": "ds",
   "section": "driver",
   "text": "Posso parlare di una mia fragilità senza sentirmi meno capace.",
   "reverse": true
  },
  {
   "id": "dt6",
   "key": "dt",
   "section": "driver",
   "text": "Faccio fatica a fermarmi se sento che potrei impegnarmi ancora.",
   "reverse": false
  },
  {
   "id": "dh6",
   "key": "dh",
   "section": "driver",
   "text": "Una pausa senza attività mi rende impaziente.",
   "reverse": false
  },
  {
   "id": "do6",
   "key": "do",
   "section": "driver",
   "text": "Cambio il modo di esprimermi per essere più facilmente accettato.",
   "reverse": false
  },
  {
   "id": "dp7",
   "key": "dp",
   "section": "driver",
   "text": "Riesco a fermare le revisioni quando il risultato è sufficientemente buono.",
   "reverse": true
  },
  {
   "id": "ds7",
   "key": "ds",
   "section": "driver",
   "text": "Mi assumo più responsabilità del necessario per non pesare sugli altri.",
   "reverse": false
  },
  {
   "id": "dt7",
   "key": "dt",
   "section": "driver",
   "text": "Valuto la qualità di una giornata anche in base a quanto mi sono sforzato.",
   "reverse": false
  },
  {
   "id": "dh7",
   "key": "dh",
   "section": "driver",
   "text": "Preferisco decidere subito piuttosto che lasciare una questione aperta.",
   "reverse": false
  },
  {
   "id": "do7",
   "key": "do",
   "section": "driver",
   "text": "So porre un limite senza sentirmi automaticamente in colpa.",
   "reverse": true
  },
  {
   "id": "pn1",
   "key": "pn",
   "section": "ego",
   "text": "Mi viene spontaneo ricordare le regole quando vengono trascurate.",
   "reverse": false
  },
  {
   "id": "pa1",
   "key": "pa",
   "section": "ego",
   "text": "Quando qualcuno è in difficoltà, il mio primo impulso è rassicurarlo.",
   "reverse": false
  },
  {
   "id": "ad1",
   "key": "ad",
   "section": "ego",
   "text": "Prima di reagire, cerco di capire quali fatti sono verificati.",
   "reverse": false
  },
  {
   "id": "bl1",
   "key": "bl",
   "section": "ego",
   "text": "Mi entusiasma provare qualcosa di nuovo anche senza essere esperto.",
   "reverse": false
  },
  {
   "id": "ba1",
   "key": "ba",
   "section": "ego",
   "text": "In presenza di figure autorevoli, peso molto le parole.",
   "reverse": false
  },
  {
   "id": "br1",
   "key": "br",
   "section": "ego",
   "text": "Quando una regola mi viene imposta senza spiegazioni, sento il bisogno di contestarla.",
   "reverse": false
  },
  {
   "id": "pn2",
   "key": "pn",
   "section": "ego",
   "text": "Quando qualcuno non rispetta un impegno, sento il bisogno di richiamarlo.",
   "reverse": false
  },
  {
   "id": "pa2",
   "key": "pa",
   "section": "ego",
   "text": "Mi viene naturale incoraggiare chi sta perdendo fiducia.",
   "reverse": false
  },
  {
   "id": "ad2",
   "key": "ad",
   "section": "ego",
   "text": "Se emergono informazioni nuove, sono disposto a cambiare idea.",
   "reverse": false
  },
  {
   "id": "bl2",
   "key": "bl",
   "section": "ego",
   "text": "Esprimo abbastanza facilmente gioia ed entusiasmo.",
   "reverse": false
  },
  {
   "id": "ba2",
   "key": "ba",
   "section": "ego",
   "text": "Mi adeguo facilmente alle aspettative espresse dagli altri.",
   "reverse": false
  },
  {
   "id": "br2",
   "key": "br",
   "section": "ego",
   "text": "Mi irrigidisco quando percepisco che qualcuno vuole controllarmi.",
   "reverse": false
  },
  {
   "id": "pn3",
   "key": "pn",
   "section": "ego",
   "text": "Per me è importante che esistano criteri chiari su ciò che è accettabile.",
   "reverse": false
  },
  {
   "id": "pa3",
   "key": "pa",
   "section": "ego",
   "text": "Cerco di far sentire le persone accolte anche quando sbagliano.",
   "reverse": false
  },
  {
   "id": "ad3",
   "key": "ad",
   "section": "ego",
   "text": "In una discussione chiedo chiarimenti prima di attribuire intenzioni.",
   "reverse": false
  },
  {
   "id": "bl3",
   "key": "bl",
   "section": "ego",
   "text": "Mi diverte inventare modi originali per fare le cose.",
   "reverse": false
  },
  {
   "id": "ba3",
   "key": "ba",
   "section": "ego",
   "text": "Prima di agire, cerco segnali che confermino che sto facendo bene.",
   "reverse": false
  },
  {
   "id": "br3",
   "key": "br",
   "section": "ego",
   "text": "A volte rifiuto una richiesta più per il modo in cui è fatta che per il contenuto.",
   "reverse": false
  },
  {
   "id": "pn4",
   "key": "pn",
   "section": "ego",
   "text": "In un gruppo, noto rapidamente quando un comportamento supera un limite.",
   "reverse": false
  },
  {
   "id": "pa4",
   "key": "pa",
   "section": "ego",
   "text": "Dedico volentieri tempo ad ascoltare un problema personale.",
   "reverse": false
  },
  {
   "id": "ad4",
   "key": "ad",
   "section": "ego",
   "text": "Confronto le possibili conseguenze prima di scegliere.",
   "reverse": false
  },
  {
   "id": "bl4",
   "key": "bl",
   "section": "ego",
   "text": "Riesco a lasciarmi coinvolgere dal gioco e dalla spontaneità.",
   "reverse": false
  },
  {
   "id": "ba4",
   "key": "ba",
   "section": "ego",
   "text": "In un gruppo nuovo, osservo attentamente le regole non dette.",
   "reverse": false
  },
  {
   "id": "br4",
   "key": "br",
   "section": "ego",
   "text": "Mi viene spontaneo mettere alla prova indicazioni che reputo arbitrarie.",
   "reverse": false
  },
  {
   "id": "pn5",
   "key": "pn",
   "section": "ego",
   "text": "Tendo a consigliare agli altri il modo corretto di fare una cosa.",
   "reverse": false
  },
  {
   "id": "pa5",
   "key": "pa",
   "section": "ego",
   "text": "Se noto qualcuno escluso, provo a coinvolgerlo.",
   "reverse": false
  },
  {
   "id": "ad5",
   "key": "ad",
   "section": "ego",
   "text": "Distinguo ciò che so da ciò che sto immaginando.",
   "reverse": false
  },
  {
   "id": "bl5",
   "key": "bl",
   "section": "ego",
   "text": "Quando nasce un’idea creativa, mi piace esplorarla.",
   "reverse": false
  },
  {
   "id": "ba5",
   "key": "ba",
   "section": "ego",
   "text": "A volte trattengo una reazione per non sembrare inopportuno.",
   "reverse": false
  },
  {
   "id": "br5",
   "key": "br",
   "section": "ego",
   "text": "Se qualcuno insiste perché faccia una cosa, posso desiderare di fare l’opposto.",
   "reverse": false
  },
  {
   "id": "pn6",
   "key": "pn",
   "section": "ego",
   "text": "Quando si ripete un errore, sento il bisogno di essere fermo.",
   "reverse": false
  },
  {
   "id": "pa6",
   "key": "pa",
   "section": "ego",
   "text": "Tendo a offrire aiuto prima ancora che venga richiesto.",
   "reverse": false
  },
  {
   "id": "ad6",
   "key": "ad",
   "section": "ego",
   "text": "Cerco soluzioni realistiche tenendo conto dei vincoli disponibili.",
   "reverse": false
  },
  {
   "id": "bl6",
   "key": "bl",
   "section": "ego",
   "text": "Sono capace di ridere di me stesso in modo benevolo.",
   "reverse": false
  },
  {
   "id": "ba6",
   "key": "ba",
   "section": "ego",
   "text": "Quando ricevo un rimprovero, tendo a rivedere subito il mio comportamento.",
   "reverse": false
  },
  {
   "id": "br6",
   "key": "br",
   "section": "ego",
   "text": "Quando sento troppe aspettative su di me, cerco modi per sottrarmi.",
   "reverse": false
  },
  {
   "id": "pn7",
   "key": "pn",
   "section": "ego",
   "text": "Mi è naturale stabilire standard e aspettarmi che siano rispettati.",
   "reverse": false
  },
  {
   "id": "pa7",
   "key": "pa",
   "section": "ego",
   "text": "Mi preoccupo di come una decisione farà sentire le persone coinvolte.",
   "reverse": false
  },
  {
   "id": "ad7",
   "key": "ad",
   "section": "ego",
   "text": "Quando provo un’emozione intensa, posso comunque valutare i fatti.",
   "reverse": false
  },
  {
   "id": "bl7",
   "key": "bl",
   "section": "ego",
   "text": "Trovo naturale mostrare una curiosità sincera.",
   "reverse": false
  },
  {
   "id": "ba7",
   "key": "ba",
   "section": "ego",
   "text": "Mi accorgo di cambiare atteggiamento in base a chi ho davanti.",
   "reverse": false
  },
  {
   "id": "br7",
   "key": "br",
   "section": "ego",
   "text": "In presenza di un ordine che considero ingiusto, il primo impulso è oppormi.",
   "reverse": false
  }
 ],
 "dimensions": {
  "dp": {
   "label": "Sii perfetto",
   "category": "driver",
   "color": "#9B8CF2",
   "strength": "Precisione, cura della qualità, affidabilità.",
   "watch": "Le revisioni possono diventare infinite; l’errore può pesare più del risultato.",
   "practice": "Definisci prima cosa significa «abbastanza buono» e una scadenza per consegnare."
  },
  "ds": {
   "label": "Sii forte",
   "category": "driver",
   "color": "#4F9DE8",
   "strength": "Autonomia, tenuta nelle difficoltà, stabilità.",
   "watch": "Potresti faticare a chiedere aiuto o a condividere un bisogno.",
   "practice": "In una situazione concreta, prova a chiedere un aiuto specifico e limitato."
  },
  "dt": {
   "label": "Sforzati",
   "category": "driver",
   "color": "#EA9D53",
   "strength": "Perseveranza, energia, capacità di affrontare ostacoli.",
   "watch": "La fatica può diventare una misura del valore, anche quando non serve.",
   "practice": "Per un compito, scegli l’approccio più semplice che raggiunge il risultato."
  },
  "dh": {
   "label": "Sbrigati",
   "category": "driver",
   "color": "#34B99B",
   "strength": "Prontezza, ritmo, capacità di agire rapidamente.",
   "watch": "Potresti creare urgenze non necessarie o trasmettere fretta agli altri.",
   "practice": "Prima di accelerare, chiediti: «È davvero urgente o solo scomodo aspettare?»"
  },
  "do": {
   "label": "Compiaci gli altri",
   "category": "driver",
   "color": "#E87AA8",
   "strength": "Sensibilità relazionale, collaborazione, attenzione ai bisogni.",
   "watch": "Il desiderio di armonia può rendere difficile dire di no.",
   "practice": "Esprimi un disaccordo piccolo e rispettoso senza giustificarti troppo."
  },
  "pn": {
   "label": "Genitore Normativo",
   "category": "ego",
   "color": "#8C7CF0",
   "strength": "Confini, criteri, responsabilità e chiarezza nelle regole.",
   "watch": "Le indicazioni possono essere percepite come giudizi o rigidità.",
   "practice": "Trasforma un «dovresti» in una richiesta chiara e negoziabile."
  },
  "pa": {
   "label": "Genitore Affettivo",
   "category": "ego",
   "color": "#D57FAA",
   "strength": "Sostegno, ascolto, incoraggiamento e cura.",
   "watch": "Potresti assumerti problemi altrui o offrire aiuto non richiesto.",
   "practice": "Prima di intervenire chiedi: «Preferisci ascolto, un consiglio o una mano?»"
  },
  "ad": {
   "label": "Adulto",
   "category": "ego",
   "color": "#2DAAAB",
   "strength": "Valutazione dei fatti, problem solving e scelte ponderate.",
   "watch": "Un’eccessiva analisi potrebbe ritardare scelte semplici.",
   "practice": "Quando decidi, separa in due colonne dati verificati e ipotesi."
  },
  "bl": {
   "label": "Bambino Libero",
   "category": "ego",
   "color": "#E6A64B",
   "strength": "Spontaneità, creatività, gioco e curiosità.",
   "watch": "L’impulso del momento può talvolta prevalere sugli impegni.",
   "practice": "Ritagliati un piccolo spazio per sperimentare senza dover essere produttivo."
  },
  "ba": {
   "label": "Bambino Adattato",
   "category": "ego",
   "color": "#698DD4",
   "strength": "Capacità di leggere il contesto e adattarsi alle aspettative.",
   "watch": "Potresti censurare bisogni e opinioni per evitare disapprovazione.",
   "practice": "In una situazione a basso rischio, chiediti cosa vorresti tu prima di adattarti."
  },
  "br": {
   "label": "Bambino Ribelle · esplorativo",
   "category": "facet",
   "color": "#D77D64",
   "strength": "Autonomia, spirito critico e capacità di contestare ciò che non convince.",
   "watch": "Un bisogno legittimo di autonomia può diventare opposizione automatica.",
   "practice": "Quando senti il bisogno di opporsi, prova a nominare prima ciò che non funziona."
  }
 }
}
JSON
	,
	true
);
