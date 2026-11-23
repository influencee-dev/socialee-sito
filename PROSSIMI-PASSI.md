# Prossimi passi (02/11/2026)

## Fatto
- Copia pubblica di socialee.it (32 pagine + 10 articoli) in `copia-sito/` (HTML e REST).
- 31 pagine estratte da Elementor in `sorgente/`, servite dal plugin con lo stesso URL e lo stesso aspetto
  (collaudo su WordPress locale contro il sito vero).
- Plugin Socialee Core 1.0.0, Socialee Pagine 1.0.0, Socialee Aggiornamenti 1.0.0 (Core e Pagine si installano da soli).

## Unico passo a mano del titolare
1. Caricare in WordPress **solo** `consegne/plugin-wordpress/socialee-aggiornamenti.zip` (Plugin → Aggiungi nuovo →
   Carica plugin → Attiva). Entro un'ora installa e attiva Socialee Core e Socialee Pagine: le 31 pagine passano al
   template nuovo (Elementor resta acceso, serve ancora ad academy e articoli). Per non aspettare:
   Strumenti → Socialee Aggiornamenti → «Controlla adesso».
2. *Socialee Sito → Impostazioni → Crea la chiave per Claude* e salvarla come segreto di rete (sito `socialee.it`,
   percorso `/wp-json/socialee-sito/`, intestazione `X-Socialee-Key`). Poi una sessione nuova.

## Da fare con l'accesso (Claude)
1. `socialee__stato`, `socialee__controlla-pagina` su tutte le 31 pagine (codice 200, title Rank Math, 1 H1,
   `socialee: true`), screenshot di confronto.
2. Academy (`/academy/`, plugin «sa») e i 10 articoli: controllare che con l'intestazione Socialee (`shell: on`) si
   vedano bene; i 4 articoli con widget Elementor (`text-editor`) vanno ricontrollati senza Elementor.
3. Spegnere Elementor Pro, poi Elementor (la shell si accende da sola), svuotare la cache, ricontrollare tutto.
   Disattivare anche il vecchio bridge `claude/v1` ed Elementor MCP Composer. Eliminare solo dopo 30 giorni e con l'ok.
4. Dopo: CSS ripulito dalle regole non usate (oggi 131 KB, molte correzioni con `!important`), font in locale.

## Bussola del team (23/11/2026)
- Socialee Bussola 1.0.0 e Socialee Aggiornamenti 1.0.1 (riconosce il plugin nuovo). Collaudo su WordPress locale:
  anonimo 401 con modulo di accesso, account senza permesso 403, team 200, dashboard solo amministratori; punteggi uguali
  a un calcolo indipendente (domande invertite comprese), ripresa, cancellazione, esportazioni, stampa/PDF, tastiera,
  iPhone/Android/desktop senza scroll orizzontale, nessuna richiesta esterna oltre a caratteri e logo.
- Titolare: creare gli account dei collaboratori con ruolo «Team Socialee» (Utenti → Aggiungi nuovo) o spuntare
  «Accesso alla Bussola» nel profilo di chi c'è già. Elenco in Utenti → Bussola del team.
- Bussola 1.0.1: nel report «Fatti aiutare a leggerlo» (prompt già compilato coi punteggi, senza nome né risposte;
  «Apri in ChatGPT» con il prompt nell'indirizzo, «Apri in Gemini» lo copia e apre Gemini, che non accetta testo
  nell'indirizzo) e «Confrontati con il team» (scheda con nome scelto e punteggi da mandare ai colleghi, le loro schede
  incollate danno un prompt di team su come comunicare). Tutto nel browser, parte solo al tocco: il sito non vede niente.
  Una bacheca del team salvata sul sito NON c'è: sarebbe raccolta centrale di profili dei lavoratori (serve informativa,
  consenso, tempi di conservazione e un parere legale prima).
