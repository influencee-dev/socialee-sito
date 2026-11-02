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
