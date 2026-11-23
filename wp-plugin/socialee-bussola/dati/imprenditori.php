<?php
/**
 * «Che tipo di imprenditore sei?» (23/11/2026, richiesta della titolare): il quiz è quello della Bussola
 * (dati/questionario.php, 77 affermazioni). Qui ci sono solo i nomi dei cinque tipi (uno per spinta: il tipo è la
 * spinta col punteggio più alto), i settori del modulo e le province.
 */

defined( 'ABSPATH' ) || exit;

return array(
	'version'   => '1.0',
	// I cinque tipi: uno per spinta. Il tipo è la spinta col punteggio più alto, la seconda dà la «sfumatura».
	'types'     => array(
		'dp' => array(
			'nome'     => 'L’Artigiano',
			'motto'    => 'Se lo faccio, lo faccio bene.',
			'colore'   => '#9B8CF2',
			'ritratto' => 'Per te la qualità è una firma. I clienti si fidano di te perché sanno che niente esce dalla tua impresa senza essere passato sotto i tuoi occhi.',
			'forza'    => 'Cura, precisione, reputazione solida: il passaparola lavora per te.',
			'trappola' => 'Il «non è ancora pronto» può diventare un freno: lanci tardi, pubblichi poco, deleghi a fatica.',
			'social'   => 'Sui social rischi di sparire perché nessun contenuto ti sembra all’altezza. Ti aiutano standard chiari decisi prima («fatto bene» vuol dire questo) e un calendario che ti obbliga a uscire.',
			'consiglio'=> 'Questa settimana pubblica una cosa «buona» invece di aspettare quella perfetta, e guarda cosa succede.',
		),
		'ds' => array(
			'nome'     => 'La Roccia',
			'motto'    => 'Ci penso io.',
			'colore'   => '#4F9DE8',
			'ritratto' => 'Sei il punto fermo della tua impresa: nelle tempeste tieni la barra dritta e gli altri si appoggiano a te.',
			'forza'    => 'Autonomia, tenuta nei momenti difficili, affidabilità.',
			'trappola' => 'Fare tutto da solo limita la crescita a quanto riesci a reggere tu, e a volte chiedi aiuto troppo tardi.',
			'social'   => 'Ti fidi dei fatti più che delle promesse: scegli pochi numeri da guardare ogni mese e partner a cui puoi chiedere senza sentirti in debito.',
			'consiglio'=> 'Scegli un compito che fai da solo da troppo tempo e affidalo, anche solo in parte, a qualcun altro.',
		),
		'dt' => array(
			'nome'     => 'Il Maratoneta',
			'motto'    => 'Non mi fermo finché non ci arrivo.',
			'colore'   => '#EA9D53',
			'ritratto' => 'Hai un’energia che non si spegne: dove gli altri mollano, tu continui. La tua impresa è cresciuta a forza di impegno.',
			'forza'    => 'Determinazione, resistenza, capacità di superare gli ostacoli.',
			'trappola' => 'La fatica può diventare la misura del valore: apri troppi fronti, complichi ciò che potrebbe essere semplice, rischi di bruciarti.',
			'social'   => 'Non serve essere ovunque: pochi canali fatti bene battono dieci aperti a metà. Scegline due e dai loro un ritmo sostenibile.',
			'consiglio'=> 'Per il prossimo progetto chiediti: qual è il modo più semplice per ottenere lo stesso risultato?',
		),
		'dh' => array(
			'nome'     => 'Lo Sprinter',
			'motto'    => 'Facciamolo adesso.',
			'colore'   => '#34B99B',
			'ritratto' => 'Vedi le occasioni prima degli altri e ti muovi subito. La velocità è il tuo vantaggio competitivo.',
			'forza'    => 'Prontezza, decisione, capacità di cogliere le opportunità.',
			'trappola' => 'L’impazienza fa cambiare rotta prima che le cose diano frutti, e mette fretta a chi lavora con te.',
			'social'   => 'Ti servono numeri rapidi ma anche il tempo di leggerli: obiettivi a 30, 60 e 90 giorni e un cruscotto semplice ti tengono in rotta senza cambiare strategia ogni settimana.',
			'consiglio'=> 'Prima di cambiare una campagna o un piano, aspetta di avere almeno quattro settimane di dati.',
		),
		'do' => array(
			'nome'     => 'Il Padrone di casa',
			'motto'    => 'Il cliente prima di tutto.',
			'colore'   => '#E87AA8',
			'ritratto' => 'Sai far sentire le persone accolte: clienti e collaboratori tornano da te per come li tratti.',
			'forza'    => 'Relazioni, fedeltà dei clienti, passaparola, clima positivo.',
			'trappola' => 'Il desiderio di accontentare tutti porta a sconti, troppi sì e prezzi che non rispettano il tuo valore.',
			'social'   => 'Il tuo superpotere è la relazione: community, recensioni e storie dei clienti funzionano benissimo per te. Lavora sul posizionamento per non dover competere sul prezzo.',
			'consiglio'=> 'Alla prossima richiesta fuori dagli accordi, prova a rispondere con un’alternativa invece che con un sì.',
		),
	),
	// Le guide regalate: «Calendario marketing 2027» di Influencee Trends (a cura di Giorgia Palazzo), le stesse di
	// influencee.it/guide-e-risorse/, copiate in guide/ (PDF e copertina). Si aggiornano rimettendo i file nuovi lì.
	'guide'     => array(
		'food'            => array( 'nome' => 'Food', 'titolo' => 'Calendario marketing food 2027', 'testo' => 'Quando promuovere panettone, colomba, gelato e gli altri temi del cibo.', 'pagine' => 16 ),
		'viaggi'          => array( 'nome' => 'Viaggi', 'titolo' => 'Calendario marketing viaggi 2027', 'testo' => 'Sci, mare, Ferragosto, mercatini e Capodanno: quando cercano gli italiani.', 'pagine' => 14 ),
		'casa'            => array( 'nome' => 'Casa', 'titolo' => 'Calendario marketing casa 2027', 'testo' => 'Condizionatori, giardino, fotovoltaico, Halloween e Natale.', 'pagine' => 12 ),
		'famiglia'        => array( 'nome' => 'Famiglia', 'titolo' => 'Calendario marketing famiglia 2027', 'testo' => 'Befana, Carnevale, maturità, estate e Babbo Natale.', 'pagine' => 14 ),
		'sport-benessere' => array( 'nome' => 'Sport e benessere', 'titolo' => 'Calendario marketing sport e benessere 2027', 'testo' => 'Tennis, Giro d’Italia, corsa, piscina, allergie e sauna.', 'pagine' => 12 ),
		'moda-beauty'     => array( 'nome' => 'Moda e beauty', 'titolo' => 'Calendario marketing moda e beauty 2027', 'testo' => 'Costumi, abbronzatura, Black Friday e le tendenze che crescono.', 'pagine' => 12 ),
		'tech-mobilita'   => array( 'nome' => 'Tech e mobilità', 'titolo' => 'Calendario marketing tech e mobilità 2027', 'testo' => 'Black Friday, Cyber Monday, gomme invernali e bollo.', 'pagine' => 14 ),
	),
	// Settori: i 12 di Influencee Trends + «Altro». 'valore' va in Brevo (attributo SETTORE); 'guida' è la guida
	// regalata (vuota = nessuna guida del settore: si manda la pagina con tutte le guide, da scegliere).
	'sectors'   => array(
		array( 'slug' => 'alimentari-bevande', 'etichetta' => 'Alimentari e bevande (ristorazione, bar, food)', 'valore' => 'Alimentari e bevande', 'guida' => 'food' ),
		array( 'slug' => 'abbigliamento-accessori', 'etichetta' => 'Abbigliamento e accessori', 'valore' => 'Abbigliamento e accessori', 'guida' => 'moda-beauty' ),
		array( 'slug' => 'cura-persona', 'etichetta' => 'Cura della persona (beauty, parrucchieri, benessere)', 'valore' => 'Cura della persona', 'guida' => 'moda-beauty' ),
		array( 'slug' => 'salute', 'etichetta' => 'Farmaceutici e salute', 'valore' => 'Farmaceutici e salute', 'guida' => 'sport-benessere' ),
		array( 'slug' => 'tempo-libero-sport', 'etichetta' => 'Tempo libero e sport', 'valore' => 'Tempo libero e sport', 'guida' => 'sport-benessere' ),
		array( 'slug' => 'turismo-viaggi', 'etichetta' => 'Turismo e viaggi', 'valore' => 'Turismo e viaggi', 'guida' => 'viaggi' ),
		array( 'slug' => 'casa', 'etichetta' => 'Abitazione e casa', 'valore' => 'Abitazione e casa', 'guida' => 'casa' ),
		array( 'slug' => 'auto-veicoli', 'etichetta' => 'Automobili e veicoli', 'valore' => 'Automobili e veicoli', 'guida' => 'tech-mobilita' ),
		array( 'slug' => 'tecnologia', 'etichetta' => 'Informatica e telecomunicazioni', 'valore' => 'Informatica e telecomunicazioni', 'guida' => 'tech-mobilita' ),
		array( 'slug' => 'finanza-assicurazioni', 'etichetta' => 'Finanza e assicurazioni', 'valore' => 'Finanza e assicurazioni', 'guida' => '' ),
		array( 'slug' => 'media-editoria', 'etichetta' => 'Media ed editoria', 'valore' => 'Media ed editoria', 'guida' => '' ),
		array( 'slug' => 'infanzia', 'etichetta' => 'Giochi e infanzia', 'valore' => 'Giochi e infanzia', 'guida' => 'famiglia' ),
		array( 'slug' => 'pmi', 'etichetta' => 'Altro settore', 'valore' => 'Altro', 'guida' => '' ),
	),
	// Province e città metropolitane (110, con il riordino della Sardegna del 2025) + chi è all'estero.
	'province'  => array(
		'Agrigento', 'Alessandria', 'Ancona', 'Aosta', 'Arezzo', 'Ascoli Piceno', 'Asti', 'Avellino', 'Bari', 'Barletta-Andria-Trani',
		'Belluno', 'Benevento', 'Bergamo', 'Biella', 'Bologna', 'Bolzano', 'Brescia', 'Brindisi', 'Cagliari', 'Caltanissetta',
		'Campobasso', 'Caserta', 'Catania', 'Catanzaro', 'Chieti', 'Como', 'Cosenza', 'Cremona', 'Crotone', 'Cuneo',
		'Enna', 'Fermo', 'Ferrara', 'Firenze', 'Foggia', 'Forlì-Cesena', 'Frosinone', 'Gallura Nord-Est Sardegna', 'Genova', 'Gorizia',
		'Grosseto', 'Imperia', 'Isernia', 'L’Aquila', 'La Spezia', 'Latina', 'Lecce', 'Lecco', 'Livorno', 'Lodi',
		'Lucca', 'Macerata', 'Mantova', 'Massa-Carrara', 'Matera', 'Medio Campidano', 'Messina', 'Milano', 'Modena', 'Monza e Brianza',
		'Napoli', 'Novara', 'Nuoro', 'Ogliastra', 'Oristano', 'Padova', 'Palermo', 'Parma', 'Pavia', 'Perugia',
		'Pesaro e Urbino', 'Pescara', 'Piacenza', 'Pisa', 'Pistoia', 'Pordenone', 'Potenza', 'Prato', 'Ragusa', 'Ravenna',
		'Reggio Calabria', 'Reggio Emilia', 'Rieti', 'Rimini', 'Roma', 'Rovigo', 'Salerno', 'Sassari', 'Savona', 'Siena',
		'Siracusa', 'Sondrio', 'Sulcis Iglesiente', 'Taranto', 'Teramo', 'Terni', 'Torino', 'Trapani', 'Trento', 'Treviso',
		'Trieste', 'Udine', 'Varese', 'Venezia', 'Verbano-Cusio-Ossola', 'Vercelli', 'Verona', 'Vibo Valentia', 'Vicenza', 'Viterbo',
		'Fuori dall’Italia',
	),
);
