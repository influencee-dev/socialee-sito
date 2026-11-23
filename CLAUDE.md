# socialee.it senza Elementor — istruzioni per Claude

Sito WordPress **socialee.it** (agenzia creativa di Influencee SRL). Stesso schema di influencee.it
(`influencee-dev/Landing-Page-evento-30-giugno`), in versione snella.

## Come funziona
- Ogni pagina Elementor di socialee.it era HTML scritto a mano in 3 widget «HTML» (intestazione, contenuto, piè di
  pagina). `scripts/estrai.py` li prende da `copia-sito/html/` (copia pubblica del 02/11/2026) e li mette in `sorgente/`.
- **Si modifica `sorgente/`** (pagine, `shell/header.html`, `shell/footer.html`, `css/sito.css`, `css/kit.css`), mai
  le copie generate in `wp-plugin/socialee-pagine/{pages,shell,assets}`.
- Build: `python3 -I scripts/build.py` → zip in `dist/` e `consegne/plugin-wordpress/` + `versioni.json`.
- **Pubblicare** = alzare `Version` del plugin cambiato (un plugin nuovo va anche in `socu_famiglia()` di Aggiornamenti e
  in `PLUGINS` di build.py: arriva dopo due giri, prima Aggiornamenti poi il plugin), build, commit e push su `main`. Il plugin
  **Socialee Aggiornamenti** sul sito legge `consegne/plugin-wordpress/versioni.json` (raw GitHub) ogni ora e installa
  da solo (copia di sicurezza, ritorno indietro se la home non risponde). `wp-plugin/aggiornamenti.json` decide quali.

## Plugin (`wp-plugin/`)
- `socialee-core`: serve le pagine di Socialee Pagine con lo stesso URL (template HTML5, niente CSS/JS di Elementor),
  intestazione e piè di pagina Socialee anche sul resto del sito quando Elementor Pro è spento (impostazione `shell`),
  classe `socialee-kit` sul body al posto di `elementor-kit-1665`. **SEO resta di Rank Math** (stessa pagina WordPress):
  title e description si cambiano in Rank Math, non qui. Cookie (iubenda), pixel Meta, tandem-forms, Brevo arrivano
  da `wp_head`/`wp_footer`. `?socialee_originale=1` (da loggati) mostra la pagina com'era.
- `socialee-pagine`: il contenuto (31 pagine).
- `socialee-aggiornamenti`: aggiornamenti automatici dal repository.
- `socialee-bussola` (23/11/2026): «La Bussola Interna — Team Edition» su `/bussola-team/` (quiz, 77 domande) e
  `/bussola-team/dashboard/` (aggregati anonimi, solo amministratori). Sorgente direttamente in `wp-plugin/socialee-bussola/`
  (domande in `dati/questionario.php`, copia di `questionario_sorgente.json` della cartella Drive «SOCIALEE - La Bussola
  Interna - Team»: contenuti, punteggi e cautele non si cambiano senza la titolare). Il quiz è LIBERO per chi ha il link
  (decisione della titolare del 23/11/2026: niente login); la dashboard resta dietro il login di WordPress col permesso
  `socialee_bussola_admin` (amministratori). Nel report: prompt per ChatGPT/Gemini e schede da scambiare tra colleghi. Pagina propria senza `wp_head`/`wp_footer`: niente Rank Math,
  pixel, iubenda o statistiche; noindex, no-store, CSP con nonce. Le risposte restano nel localStorage del browser (chiave
  separata per account se loggati); niente server, niente database. NON va nel menu pubblico.

## Accesso al sito
- MCP: `POST https://socialee.it/wp-json/socialee-sito/v1/mcp` (JSON-RPC 2.0; `GET` = elenco strumenti):
  `socialee__stato`, `socialee__pagine`, `socialee__imposta` (pagine, off, shell; conferma), `socialee__svuota-cache`,
  `socialee__controlla-pagina` (path), `socialee__plugin-elenco`, `socialee__plugin-attiva` (file, attiva, conferma),
  `socialee__registro`.
- Zip: `POST https://socialee.it/wp-json/socialee-sito/v1/plugin?attiva=1`, corpo = zip, intestazione `X-Sha256`.
- Chiave: intestazione `X-Socialee-Key`, creata in *Socialee Sito → Impostazioni → Crea la chiave per Claude*, salvata dal
  titolare come segreto di rete (sito `socialee.it`, percorso `/wp-json/socialee-sito/`). Il proxy la aggiunge da solo.
- Sul sito c'è anche un vecchio bridge `claude/v1` (non nostro): non usarlo, va disattivato.
- Regole: una richiesta alla volta; disattivare prima di eliminare; chiedere al titolare prima di cancellare.

## Verifica
WordPress locale (SQLite) con tema Hello Elementor: confronto stile per stile e screenshot contro il sito vero
(0 differenze su home, pacchetti, chi siamo, contatti; altezze uguali al pixel a 1280 e 390).

## Stato e prossimi passi: `PROSSIMI-PASSI.md`
