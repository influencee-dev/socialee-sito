<?php
/**
 * Plugin Name:       Tandem Forms
 * Plugin URI:        https://tandem-workspace.vercel.app
 * Description:       Porta in Tandem tutte le richieste dei form del sito (Elementor Pro, Contact Form 7, WPForms, Gravity Forms, Fluent Forms, WP Job Manager, Academy Socialee, form Brevo e form personalizzati), ogni settimana comunica quali form ci sono, misura le visite senza cookie per l'Analytics di Tandem e si aggiorna da Tandem.
 * Version:           1.3.2
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Update URI:        https://tandem-workspace.vercel.app/api/plugins/tandem-forms
 * Author:            Influencee
 * License:           GPL-2.0-or-later
 * Text Domain:       tandem-forms
 */

defined('ABSPATH') || exit;

final class Tandem_Forms {
    const VERSION          = '1.3.2';
    const OPT              = 'tandem_forms_settings';
    const QUEUE            = 'tandem_forms_queue';
    const LOG              = 'tandem_forms_log';
    const REPORT           = 'tandem_forms_last_report';
    const KNOWN            = 'tandem_forms_known';
    const TRACK_PATH       = '/api/track';
    const LEAD_TYPES       = ['brand', 'agenzia', 'creator', 'altro'];
    const SLUG             = 'tandem-forms';
    const UPDATE_PATH      = '/api/plugins/tandem-forms';
    const UPDATE_CACHE     = 'tandem_forms_update';
    const UPDATE_LAST      = 'tandem_forms_update_check';
    const CRON_RETRY       = 'tandem_forms_retry';
    const CRON_REPORT      = 'tandem_forms_report';
    const DEFAULT_ENDPOINT = 'https://tandem-workspace.vercel.app/api/intake';
    const MAX_QUEUE        = 1000;

    /** Campi tecnici che non sono dati della persona. */
    const SKIP_KEYS = '/^(g-recaptcha.*|h-captcha.*|cf-turnstile.*|_wp.*|_wpcf7.*|email_address_check|form_id|post_id|queried_id|referer_title|referrer|referer|locale|action|nonce|security|wpforms\[(id|author|post_id|token)\]|gform_.*|is_submit_.*|state_\d+|__fluent_form_embded_post_id|_fluentform_.*|elementor-pro-form-.*)$/i';
    const SKIP_TYPES = ['honeypot', 'recaptcha', 'recaptcha_v3', 'hcaptcha', 'turnstile', 'step', 'html', 'password', 'upload', 'file', 'submit', 'button', 'image', 'reset', 'captcha'];

    public static function boot() {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_post_tandem_forms_test', [__CLASS__, 'action_test']);
        add_action('admin_post_tandem_forms_flush', [__CLASS__, 'action_flush']);
        add_action('admin_post_tandem_forms_connect', [__CLASS__, 'action_connect']);
        add_action('admin_post_tandem_forms_paired', [__CLASS__, 'action_paired']);
        add_action('wp_ajax_tandem_forms_history', [__CLASS__, 'ajax_history']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [__CLASS__, 'links']);
        add_filter('cron_schedules', [__CLASS__, 'schedules']);
        add_action('init', [__CLASS__, 'ensure_cron']);
        add_action(self::CRON_RETRY, [__CLASS__, 'process_queue']);
        add_action(self::CRON_REPORT, [__CLASS__, 'report']);
        add_action('update_option_' . self::OPT, [__CLASS__, 'after_save'], 10, 0);

        // integrazioni lato server
        add_action('elementor_pro/forms/new_record', [__CLASS__, 'on_elementor'], 20, 2);
        add_action('wpcf7_mail_sent', [__CLASS__, 'on_cf7'], 20, 1);
        add_action('wpcf7_mail_failed', [__CLASS__, 'on_cf7'], 20, 1);
        add_action('wpforms_process_complete', [__CLASS__, 'on_wpforms'], 20, 4);
        add_action('gform_after_submission', [__CLASS__, 'on_gravity'], 20, 2);
        add_action('fluentform/submission_inserted', [__CLASS__, 'on_fluent'], 20, 3);
        add_action('fluentform_submission_inserted', [__CLASS__, 'on_fluent'], 20, 3);
        add_action('job_manager_job_submitted', [__CLASS__, 'on_job'], 20, 1);
        add_action('user_register', [__CLASS__, 'on_user_register'], 20, 1);
        add_action('added_post_meta', [__CLASS__, 'on_academy_meta'], 20, 4);
        add_action('updated_post_meta', [__CLASS__, 'on_academy_meta'], 20, 4);

        // form senza plugin (Brevo inline, form personalizzati): cattura dal browser verso questo sito, poi a Tandem
        add_action('rest_api_init', [__CLASS__, 'rest']);
        add_action('wp_footer', [__CLASS__, 'footer_js'], 99);

        // Analytics di Tandem senza cookie: pagine viste, moduli visti e inviati, clic su WhatsApp, telefono ed email
        add_action('wp_head', [__CLASS__, 'head_js'], 30);
        add_action(self::CRON_RETRY, [__CLASS__, 'maybe_refresh_known'], 20);

        // aggiornamenti del plugin pubblicati in Tandem
        add_filter('pre_set_site_transient_update_plugins', [__CLASS__, 'update_check']);
        add_filter('plugins_api', [__CLASS__, 'plugin_info'], 20, 3);
        add_filter('auto_update_plugin', [__CLASS__, 'auto_update'], 20, 2);
    }

    /* ------------------------------------------------------------------ impostazioni */

    public static function defaults() {
        return [
            'endpoint'      => self::DEFAULT_ENDPOINT,
            'key'           => '',
            'capture'       => 1,
            'selectors'     => "form.brief-form\nform[data-tandem]",
            // il form contatti di socialee.it inviava a Brevo dal browser: se c'e', passa dal server
            'relay'         => '#brevo-form',
            'relay_message' => 'Grazie! Abbiamo ricevuto la tua richiesta. Ti risponderemo al più presto.',
            'relay_list'    => 14,
            'brevo_key'     => '',
            'intents'       => '',
            'measure'       => 1,
            'autoupdate'    => 1,
            'installed_at'  => 0,
        ];
    }

    public static function settings() {
        $s = get_option(self::OPT, []);
        return array_merge(self::defaults(), is_array($s) ? $s : []);
    }

    public static function activate() {
        $s = self::settings();
        if (empty($s['installed_at'])) {
            $s['installed_at'] = time();
            update_option(self::OPT, $s, false);
        }
        self::ensure_cron();
    }

    public static function deactivate() {
        wp_clear_scheduled_hook(self::CRON_RETRY);
        wp_clear_scheduled_hook(self::CRON_REPORT);
        delete_transient(self::KNOWN);
        delete_transient(self::UPDATE_CACHE);
    }

    public static function schedules($s) {
        $s['tandem_10min'] = ['interval' => 600, 'display' => 'Ogni 10 minuti (Tandem Forms)'];
        $s['tandem_weekly'] = ['interval' => WEEK_IN_SECONDS, 'display' => 'Ogni settimana (Tandem Forms)'];
        return $s;
    }

    public static function ensure_cron() {
        if (!wp_next_scheduled(self::CRON_RETRY)) wp_schedule_event(time() + 300, 'tandem_10min', self::CRON_RETRY);
        // il segnale settimanale parte la domenica notte, prima del controllo di Tandem del lunedi'
        if (!wp_next_scheduled(self::CRON_REPORT)) wp_schedule_event(strtotime('next sunday 22:00') ?: time() + DAY_IN_SECONDS, 'tandem_weekly', self::CRON_REPORT);
    }

    public static function register_settings() {
        register_setting('tandem_forms', self::OPT, ['type' => 'array', 'sanitize_callback' => [__CLASS__, 'sanitize']]);
    }

    public static function sanitize($in) {
        $old = self::settings();
        $in  = is_array($in) ? $in : [];
        $out = $old;
        // solo https (http ammesso per un Tandem di prova sul proprio computer)
        $endpoint = esc_url_raw(trim((string) ($in['endpoint'] ?? '')), ['https', 'http']);
        if (strpos($endpoint, 'http://') === 0 && !in_array(wp_parse_url($endpoint, PHP_URL_HOST), ['localhost', '127.0.0.1'], true)) $endpoint = '';
        $out['endpoint'] = $endpoint ? $endpoint : self::DEFAULT_ENDPOINT;
        $key = trim((string) ($in['key'] ?? ''));
        if ($key !== '') {
            if (preg_match('/^tdm_[A-Za-z0-9_-]{32,80}$/', $key)) $out['key'] = $key;
            else add_settings_error(self::OPT, 'key', 'La chiave non è valida: copiala di nuovo da Tandem (inizia con tdm_).');
        }
        if (!empty($in['remove_key'])) $out['key'] = '';
        $out['capture'] = empty($in['capture']) ? 0 : 1;
        $out['selectors'] = self::clean_lines($in['selectors'] ?? '', 30);
        $out['relay'] = sanitize_text_field($in['relay'] ?? '');
        $out['relay_message'] = sanitize_text_field($in['relay_message'] ?? '');
        $out['relay_list'] = absint($in['relay_list'] ?? 0);
        $bk = trim((string) ($in['brevo_key'] ?? ''));
        if ($bk !== '') $out['brevo_key'] = sanitize_text_field($bk);
        if (!empty($in['remove_brevo_key'])) $out['brevo_key'] = '';
        $out['intents'] = self::clean_lines($in['intents'] ?? '', 40);
        $out['measure'] = empty($in['measure']) ? 0 : 1;
        $out['autoupdate'] = empty($in['autoupdate']) ? 0 : 1;
        return $out;
    }

    private static function clean_lines($text, $max) {
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $text)));
        $lines = array_map(function ($l) { return preg_replace('/[^\w\s\-.#\[\]="\'*^$:,>|àèéìòù]/u', '', $l); }, $lines);
        return implode("\n", array_slice(array_values(array_filter($lines)), 0, $max));
    }

    public static function after_save() {
        self::report();
    }

    public static function links($links) {
        array_unshift($links, '<a href="' . esc_url(admin_url('options-general.php?page=tandem-forms')) . '">Impostazioni</a>');
        return $links;
    }

    /* ------------------------------------------------------------------ invio a Tandem */

    private static function host() {
        return preg_replace('/^www\./', '', (string) wp_parse_url(home_url(), PHP_URL_HOST));
    }

    /** Invia subito; se Tandem non risponde la richiesta resta in coda e riparte ogni 10 minuti. */
    public static function send(array $payload, $queue_on_fail = true) {
        $s = self::settings();
        if (!$s['key']) {
            if ($queue_on_fail) self::enqueue($payload, 'chiave non impostata');
            return ['ok' => false, 'code' => 0, 'error' => 'Chiave di Tandem non impostata'];
        }
        $r = wp_remote_post($s['endpoint'], [
            'timeout'     => 6,
            'redirection' => 0,
            'headers'     => [
                'Authorization' => 'Bearer ' . $s['key'],
                'Content-Type'  => 'application/json; charset=utf-8',
                'User-Agent'    => 'TandemForms/' . self::VERSION . '; ' . home_url('/'),
            ],
            'body'        => wp_json_encode($payload),
            'data_format' => 'body',
        ]);
        if (is_wp_error($r)) {
            $res = ['ok' => false, 'code' => 0, 'error' => $r->get_error_message()];
        } else {
            $code = (int) wp_remote_retrieve_response_code($r);
            $body = json_decode((string) wp_remote_retrieve_body($r), true);
            $res  = ['ok' => $code >= 200 && $code < 300, 'code' => $code, 'error' => is_array($body) && !empty($body['error']) ? (string) $body['error'] : '', 'body' => is_array($body) ? $body : []];
        }
        if (empty($payload['ping']) && empty($payload['report'])) {
            self::log($payload, $res);
            // 400/413: la richiesta non e' valida e non lo diventera'; tutto il resto si ritenta
            if (!$res['ok'] && $queue_on_fail && !in_array($res['code'], [400, 413], true)) self::enqueue($payload, $res['error'] ?: ('HTTP ' . $res['code']));
        }
        return $res;
    }

    private static function enqueue(array $payload, $why) {
        $q = get_option(self::QUEUE, []);
        if (!is_array($q)) $q = [];
        $q[] = ['p' => $payload, 'tries' => 0, 'at' => time(), 'why' => substr((string) $why, 0, 200)];
        if (count($q) > self::MAX_QUEUE) $q = array_slice($q, -self::MAX_QUEUE);
        update_option(self::QUEUE, $q, false);
    }

    public static function process_queue() {
        $q = get_option(self::QUEUE, []);
        if (!is_array($q) || !$q) return 0;
        if (get_transient('tandem_forms_lock')) return 0;
        set_transient('tandem_forms_lock', 1, 300);
        $keep = [];
        $sent = 0;
        foreach ($q as $i => $item) {
            if ($i >= 40) { $keep[] = $item; continue; }
            $res = self::send($item['p'], false);
            if ($res['ok'] || in_array($res['code'], [400, 413], true)) { $sent++; continue; }
            $item['tries'] = (int) $item['tries'] + 1;
            $item['why'] = $res['error'] ?: ('HTTP ' . $res['code']);
            if ($item['tries'] < 60) $keep[] = $item;
        }
        update_option(self::QUEUE, $keep, false);
        delete_transient('tandem_forms_lock');
        return $sent;
    }

    private static function log(array $p, array $res) {
        $log = get_option(self::LOG, []);
        if (!is_array($log)) $log = [];
        $email = (string) ($p['email'] ?? '');
        $masked = $email && strpos($email, '@') ? substr($email, 0, 1) . '…@' . substr(strrchr($email, '@'), 1) : '';
        array_unshift($log, [
            't'    => time(),
            'form' => substr((string) ($p['form_name'] ?? ''), 0, 80),
            'who'  => $masked,
            'ok'   => (bool) $res['ok'],
            'code' => (int) $res['code'],
            'msg'  => $res['ok'] ? (!empty($res['body']['duplicate']) ? 'già ricevuta' : 'arrivata in Tandem') : substr((string) $res['error'], 0, 120),
        ]);
        update_option(self::LOG, array_slice($log, 0, 50), false);
    }

    /* ------------------------------------------------------------------ dai campi del form alla richiesta */

    private static function norm($s) {
        return strtolower(remove_accents(wp_strip_all_tags((string) $s)));
    }

    /** Intento della richiesta da nome del form e pagina: richiesta commerciale, iscrizione o candidatura. */
    public static function intent_for($form_name, $page_url) {
        $hay = self::norm($form_name . ' ' . $page_url);
        foreach (preg_split('/\n/', (string) self::settings()['intents']) as $line) {
            if (!strpos($line, '=')) continue;
            list($needle, $what) = array_map('trim', explode('=', $line, 2));
            $needle = self::norm($needle);
            if ($needle === '' || strpos($hay, $needle) === false) continue;
            $what = self::norm($what);
            if (strpos($what, 'iscri') === 0) return 'SIGNUP';
            if (strpos($what, 'candid') === 0) return 'APPLICATION';
            return 'REQUEST';
        }
        if (preg_match('/preventiv|consulenz|contatt|richiest|demo|informazion|offerta|brief|promo|bundle|quote|contact/', $hay)) return 'REQUEST';
        if (preg_match('/candidat|lavora[- ]con[- ]noi|ambassador|casting|selezione|curriculum|diventa/', $hay)) return 'APPLICATION';
        if (preg_match('/newsletter|guida|guide|ebook|download|corso|academy|webinar|iscri|risorse|checklist|template|tool/', $hay)) return 'SIGNUP';
        return 'REQUEST';
    }

    /**
     * $fields: [['label'=>, 'value'=>, 'type'=>, 'key'=>], ...]; $ctx: form_key, form_name, page_url, external_id,
     * submitted_at, intent, kind, history. Riconosce email, nome, telefono, azienda, messaggio e consenso marketing.
     */
    public static function build(array $fields, array $ctx) {
        $out = ['email' => '', 'first_name' => '', 'last_name' => '', 'name' => '', 'phone' => '', 'company' => '', 'message' => '', 'consent' => false, 'kind' => (string) ($ctx['kind'] ?? '')];
        $list = [];
        $messages = [];
        foreach ($fields as $f) {
            $key   = (string) ($f['key'] ?? '');
            $type  = strtolower((string) ($f['type'] ?? ''));
            $label = trim(wp_strip_all_tags((string) ($f['label'] ?? '')));
            $value = $f['value'] ?? '';
            if (is_array($value)) $value = implode(', ', array_filter(array_map('strval', $value), 'strlen'));
            $value = trim(wp_strip_all_tags((string) $value));
            if ($value === '' || in_array($type, self::SKIP_TYPES, true) || preg_match(self::SKIP_KEYS, $key)) continue;
            $k = self::norm($key . ' ' . $label);
            // "tipologia azienda", "numero influencer", "budget": descrivono la richiesta, non sono il nome dell'azienda
            $attribute  = preg_match('/tipolog|(^|[^a-z])tipo([^a-z]|$)|categori|settore|dimension|numero|budget|social|quando|ospiti|pacchetto/', $k);
            $companyish = !$attribute && preg_match('/aziend|company|societ|ragione|brand|business|negozio|attivit|organizzaz|impresa|ristorante|locale\b/', $k);
            if (!$out['email'] && ($type === 'email' || (is_email($value) && (strpos($k, 'mail') !== false || $type === '')))) {
                if (is_email($value)) { $out['email'] = sanitize_email($value); }
            } elseif (preg_match('/nome\s*e\s*cognome|nominativo|full.?name|your-name|^name\b/', $k) && !$companyish) {
                if (!$out['name']) $out['name'] = $value;
            } elseif (preg_match('/cognome|last.?name|surname|lastname/', $k)) {
                if (!$out['last_name']) $out['last_name'] = $value;
            } elseif (preg_match('/(^|[^a-z])(nome|name|first.?name|firstname)([^a-z]|$)/', $k) && !$companyish) {
                if (!$out['first_name']) $out['first_name'] = $value;
            } elseif (($type === 'tel' || preg_match('/telefono|(^|[^a-z])tel([^a-z]|$)|phone|cellulare|(^|[^a-z])cell([^a-z]|$)|mobile|whatsapp|(^|[^a-z])sms([^a-z]|$)/', $k)) && preg_match('/^[\d\s+().\/-]{6,}$/', $value)) {
                if (!$out['phone']) $out['phone'] = $value;
            } elseif ($companyish && !$out['company'] && strlen($value) <= 200 && !is_email($value)) {
                $out['company'] = $value;
            } elseif ($type === 'textarea' || (!$attribute && preg_match('/messag|richiesta|note|descri|brief|testo|domanda|comment|progetto/', $k))) {
                $messages[] = $value;
            } elseif (!$out['phone'] && preg_match('/^field[ _]/', $k) && preg_match('/^\+?[\d\s().\/-]{8,20}$/', $value) && strlen(preg_replace('/\D/', '', $value)) >= 8) {
                // campo senza etichetta che contiene un numero di telefono
                $out['phone'] = $value;
            } elseif (preg_match('/^field[ _]/', $k) && mb_strlen($value) >= 60 && substr_count($value, ' ') >= 8) {
                // campo senza etichetta con un testo lungo: e' il messaggio
                $messages[] = $value;
            }
            if (preg_match('/marketing|newsletter|promozion|commercial|opt.?in|optin/', $k) && preg_match('/^(1|on|yes|si|sì|true|accetto|acconsento)|accett|acconsent/i', $value)) $out['consent'] = true;
            if (!$out['kind'] && preg_match('/tipolog|sei un|tipo|profilo|ruolo|categoria|chi sei/', $k)) {
                $v = self::norm($value);
                if (preg_match('/agenzi/', $v)) $out['kind'] = 'AGENCY';
                elseif (preg_match('/influencer|creator|blogger|youtuber|tiktoker/', $v)) $out['kind'] = 'INFLUENCER';
                elseif (preg_match('/brand|aziend|negozio|ristorante|hotel|impresa|attivit|e-?commerce/', $v)) $out['kind'] = 'BRAND';
            }
            // prefisso del telefono e casella del consenso: gia' dentro telefono e consenso
            if (preg_match('/__country_code$|^opt_?in$/i', $key)) continue;
            $list[] = ['label' => $label !== '' ? $label : ucfirst(trim(preg_replace('/\s+/', ' ', str_replace(['_', '-', '[', ']'], ' ', $key)))), 'value' => mb_substr($value, 0, 1000)];
        }
        // "Nome" compilato con nome e cognome: lo divide Tandem
        if ($out['first_name'] && !$out['last_name'] && !$out['name'] && strpos($out['first_name'], ' ') !== false) {
            $out['name'] = $out['first_name'];
            $out['first_name'] = '';
        }
        $out['message'] = mb_substr(implode("\n\n", $messages), 0, 4000);
        $form_name = (string) ($ctx['form_name'] ?? 'Form');
        $page_url  = (string) ($ctx['page_url'] ?? '');
        $intent = !empty($ctx['intent']) ? $ctx['intent'] : self::intent_for($form_name, $page_url);
        if (!$out['kind'] && preg_match('/creator|influencer/', self::norm($form_name . ' ' . $page_url)) && $intent !== 'REQUEST') $out['kind'] = 'INFLUENCER';
        return array_merge([
            'external_id'  => (string) ($ctx['external_id'] ?? (self::host() . ':' . ($ctx['form_key'] ?? 'form') . ':' . wp_generate_uuid4())),
            'form_key'     => mb_substr((string) ($ctx['form_key'] ?? ''), 0, 120),
            'form_name'    => mb_substr($form_name, 0, 200),
            'page_url'     => esc_url_raw($page_url),
            'intent'       => $intent,
            'fields'       => array_slice($list, 0, 40),
            'submitted_at' => (string) ($ctx['submitted_at'] ?? gmdate('c')),
            'label'        => mb_substr(self::norm($form_name), 0, 40),
            'history'      => !empty($ctx['history']),
            'spam'         => !empty($ctx['spam']),
        ], $out);
    }

    private static function current_page() {
        $ref = wp_get_referer();
        if (!$ref && !empty($_POST['referrer'])) $ref = esc_url_raw(wp_unslash($_POST['referrer'])); // phpcs:ignore WordPress.Security.NonceVerification
        return $ref ? (string) $ref : home_url('/');
    }

    /* ------------------------------------------------------------------ integrazioni */

    /** Elementor Pro: chiave "elementor:<id del widget form>", la stessa che il controllo di Tandem trova nella pagina. */
    public static function on_elementor($record, $handler) {
        try {
            $raw = $record->get('fields');
            $fields = [];
            foreach ((array) $raw as $id => $f) {
                $fields[] = ['key' => (string) ($f['id'] ?? $id), 'label' => (string) ($f['title'] ?? ''), 'value' => $f['raw_value'] ?? ($f['value'] ?? ''), 'type' => (string) ($f['type'] ?? '')];
            }
            $meta = (array) $record->get('meta');
            $page = !empty($meta['page_url']['value']) ? $meta['page_url']['value'] : self::current_page();
            $form_id = (string) $record->get_form_settings('id');
            $name = (string) $record->get_form_settings('form_name');
            self::send(self::build($fields, ['form_key' => 'elementor:' . $form_id, 'form_name' => $name ?: 'Form Elementor', 'page_url' => $page]));
        } catch (\Throwable $e) {
            self::log(['form_name' => 'Elementor'], ['ok' => false, 'code' => 0, 'error' => $e->getMessage()]);
        }
    }

    public static function on_cf7($form) {
        if (!class_exists('WPCF7_Submission')) return;
        $sub = WPCF7_Submission::get_instance();
        if (!$sub || !is_object($form)) return;
        $fields = [];
        foreach ((array) $sub->get_posted_data() as $k => $v) $fields[] = ['key' => (string) $k, 'label' => '', 'value' => $v, 'type' => ''];
        foreach ($form->scan_form_tags() as $tag) {
            foreach ($fields as &$f) if ($f['key'] === $tag->name) $f['type'] = $tag->basetype;
            unset($f);
        }
        $page = method_exists($sub, 'get_meta') && $sub->get_meta('url') ? $sub->get_meta('url') : self::current_page();
        self::send(self::build($fields, ['form_key' => 'cf7:' . $form->id(), 'form_name' => $form->title(), 'page_url' => $page]));
    }

    public static function on_wpforms($fields_in, $entry, $form_data, $entry_id) {
        $fields = [];
        foreach ((array) $fields_in as $f) $fields[] = ['key' => 'wpforms_' . ($f['id'] ?? ''), 'label' => (string) ($f['name'] ?? ''), 'value' => $f['value'] ?? '', 'type' => (string) ($f['type'] ?? '')];
        $id = $form_data['id'] ?? '';
        self::send(self::build($fields, ['form_key' => 'wpforms:' . $id, 'form_name' => $form_data['settings']['form_title'] ?? 'WPForms', 'page_url' => self::current_page()]));
    }

    public static function on_gravity($entry, $form) {
        $fields = [];
        foreach ((array) ($form['fields'] ?? []) as $field) {
            $value = is_object($field) && method_exists($field, 'get_value_export') ? $field->get_value_export($entry) : rgar($entry, (string) $field->id);
            $fields[] = ['key' => 'input_' . $field->id, 'label' => (string) $field->label, 'value' => $value, 'type' => (string) $field->type];
        }
        self::send(self::build($fields, ['form_key' => 'gravity:' . ($form['id'] ?? ''), 'form_name' => $form['title'] ?? 'Gravity Forms', 'page_url' => rgar($entry, 'source_url') ?: self::current_page()]));
    }

    public static function on_fluent($entry_id, $data, $form) {
        $fields = [];
        foreach ((array) $data as $k => $v) {
            if (is_array($v) && isset($v['first_name'])) {
                $fields[] = ['key' => 'first_name', 'label' => 'Nome', 'value' => $v['first_name'], 'type' => ''];
                $fields[] = ['key' => 'last_name', 'label' => 'Cognome', 'value' => $v['last_name'] ?? '', 'type' => ''];
                continue;
            }
            $fields[] = ['key' => (string) $k, 'label' => '', 'value' => $v, 'type' => ''];
        }
        $title = is_object($form) && isset($form->title) ? $form->title : 'Fluent Forms';
        $id = is_object($form) && isset($form->id) ? $form->id : '';
        self::send(self::build($fields, ['form_key' => 'fluent:' . $id, 'form_name' => $title, 'page_url' => self::current_page()]));
    }

    /** WP Job Manager: un'azienda che pubblica un'offerta per creator e' un contatto commerciale. */
    public static function on_job($job_id) {
        self::send(self::job_payload((int) $job_id, false));
    }

    private static function job_payload($job_id, $history) {
        $p = get_post($job_id);
        $app = (string) get_post_meta($job_id, '_application', true);
        // email per le candidature che appartiene allo staff del sito: non e' dell'azienda
        if (is_email($app) && ($uid = email_exists($app)) && array_intersect((array) get_userdata($uid)->roles, self::STAFF_ROLES)) $app = '';
        $author = $p ? get_userdata((int) $p->post_author) : false;
        // offerte inserite dallo staff per conto dell'azienda: l'email dell'account e' del team, non dell'azienda
        $staff = $author && array_intersect((array) $author->roles, self::STAFF_ROLES);
        $account = $author && !$staff && is_email($author->user_email) ? $author->user_email : '';
        $fields = [
            ['key' => 'company_name', 'label' => 'Azienda', 'value' => get_post_meta($job_id, '_company_name', true), 'type' => ''],
            ['key' => 'email', 'label' => is_email($app) ? 'Email candidature' : 'Email account', 'value' => is_email($app) ? $app : $account, 'type' => 'email'],
            ['key' => 'account_name', 'label' => 'Pubblicata da', 'value' => $author ? ($staff ? 'staff del sito' : $author->display_name) : '', 'type' => ''],
            ['key' => 'job_status', 'label' => 'Stato offerta', 'value' => $p ? $p->post_status : '', 'type' => ''],
            ['key' => 'job_title', 'label' => 'Offerta', 'value' => $p ? $p->post_title : '', 'type' => ''],
            ['key' => 'job_location', 'label' => 'Luogo', 'value' => get_post_meta($job_id, '_job_location', true), 'type' => ''],
            ['key' => 'company_website', 'label' => 'Sito azienda', 'value' => get_post_meta($job_id, '_company_website', true), 'type' => ''],
            ['key' => 'application_url', 'label' => 'Link candidature', 'value' => is_email($app) ? '' : $app, 'type' => ''],
            ['key' => 'job_description', 'label' => 'Descrizione', 'value' => $p ? wp_trim_words(wp_strip_all_tags($p->post_content), 120) : '', 'type' => 'textarea'],
        ];
        $page = get_option('job_manager_submit_job_form_page_id') ? get_permalink((int) get_option('job_manager_submit_job_form_page_id')) : home_url('/');
        return self::build($fields, [
            'external_id'  => self::host() . ':job:' . $job_id,
            'form_key'     => 'jobmanager:submit',
            'form_name'    => 'Pubblica offerta di lavoro',
            'page_url'     => $page,
            'intent'       => 'REQUEST',
            'kind'         => 'BRAND',
            'history'      => $history,
            'submitted_at' => self::post_date_gmt($p),
        ]);
    }

    /** Data di un contenuto in UTC: le bozze e le offerte in attesa non hanno la data GMT, si usa quella locale o l'ultima modifica. */
    private static function post_date_gmt($p) {
        if (!$p) return gmdate('c');
        foreach ([$p->post_date_gmt, $p->post_date ? get_gmt_from_date($p->post_date) : '', $p->post_modified_gmt] as $d) {
            if ($d && strpos($d, '0000-00-00') !== 0) return str_replace(' ', 'T', $d) . 'Z';
        }
        return '';
    }

    /** Registrazione che vale come contatto: niente staff, niente semplici «iscritti» (quasi sempre bot). */
    private static function is_lead_account($u) {
        $roles = (array) $u->roles;
        if (!$roles || array_intersect($roles, self::STAFF_ROLES)) return false;
        return (bool) array_diff($roles, ['subscriber']);
    }

    /** Ruoli dello staff: le loro registrazioni non sono contatti. */
    const STAFF_ROLES = ['administrator', 'editor', 'author', 'contributor', 'shop_manager', 'wpseo_manager', 'wpseo_editor'];

    public static function on_user_register($user_id) {
        $u = get_userdata((int) $user_id);
        if (!self::is_lead_account($u)) return;
        self::send(self::user_payload($u, false));
    }

    /** Registrazione al sito: azienda (employer di WP Job Manager), creator (candidate) o iscritto. */
    private static function user_payload($u, $history) {
        $roles = (array) $u->roles;
        $kind = in_array('employer', $roles, true) ? 'BRAND' : (in_array('candidate', $roles, true) ? 'INFLUENCER' : '');
        $fields = [
            ['key' => 'first_name', 'label' => 'Nome', 'value' => get_user_meta($u->ID, 'first_name', true), 'type' => ''],
            ['key' => 'last_name', 'label' => 'Cognome', 'value' => get_user_meta($u->ID, 'last_name', true), 'type' => ''],
            ['key' => 'email', 'label' => 'Email', 'value' => $u->user_email, 'type' => 'email'],
            ['key' => 'user_url', 'label' => 'Sito web', 'value' => $u->user_url, 'type' => ''],
            ['key' => 'role', 'label' => 'Tipo di account', 'value' => implode(', ', $roles), 'type' => ''],
        ];
        if (!get_user_meta($u->ID, 'first_name', true) && !get_user_meta($u->ID, 'last_name', true)) {
            $fields[] = ['key' => 'name', 'label' => 'Nome visualizzato', 'value' => $u->display_name !== $u->user_login ? $u->display_name : '', 'type' => ''];
        }
        return self::build($fields, [
            'external_id'  => self::host() . ':user:' . $u->ID,
            'form_key'     => 'wp:register',
            'form_name'    => 'Registrazione al sito',
            'page_url'     => wp_registration_url(),
            'intent'       => 'SIGNUP',
            'kind'         => $kind,
            'history'      => $history,
            'submitted_at' => str_replace(' ', 'T', (string) $u->user_registered) . 'Z',
        ]);
    }

    /** Academy Socialee: l'iscritto e' salvato come sa_lead; l'ultimo dato scritto e' l'esito Brevo. */
    public static function on_academy_meta($meta_id, $post_id, $meta_key, $value) {
        if ($meta_key !== '_sa_brevo' || get_post_type($post_id) !== 'sa_lead') return;
        static $done = [];
        if (isset($done[$post_id])) return;
        $done[$post_id] = 1;
        self::send(self::academy_payload((int) $post_id, false, self::host() . ':academy:' . $post_id . ':' . time()));
    }

    private static function academy_payload($lead_id, $history, $external_id) {
        $p = get_post($lead_id);
        $name = $p ? trim((string) preg_replace('/\s+—\s+.*$/u', '', $p->post_title)) : '';
        $fields = [
            ['key' => 'name', 'label' => 'Nome', 'value' => $name, 'type' => ''],
            ['key' => 'email', 'label' => 'Email', 'value' => get_post_meta($lead_id, '_sa_email', true), 'type' => 'email'],
            ['key' => 'phone', 'label' => 'Telefono', 'value' => get_post_meta($lead_id, '_sa_phone', true), 'type' => 'tel'],
            ['key' => 'company', 'label' => 'Azienda', 'value' => get_post_meta($lead_id, '_sa_company', true), 'type' => ''],
            ['key' => 'sector', 'label' => 'Settore', 'value' => get_post_meta($lead_id, '_sa_sector', true), 'type' => ''],
        ];
        $page = function_exists('sa_opt') ? (string) sa_opt('landing_page', home_url('/academy/')) : home_url('/academy/');
        return self::build($fields, [
            'external_id'  => $external_id,
            'form_key'     => 'academy:signup',
            'form_name'    => 'Iscrizione Academy',
            'page_url'     => is_numeric($page) ? get_permalink((int) $page) : $page,
            'intent'       => 'SIGNUP',
            'kind'         => 'BRAND',
            'history'      => $history,
            'submitted_at' => $p ? get_post_time('c', true, $p) : gmdate('c'),
        ]);
    }

    /* ------------------------------------------------------------------ cattura dal browser */

    public static function rest() {
        register_rest_route('tandem-forms/v1', '/capture', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'rest_capture'],
            'permission_callback' => '__return_true',
        ]);
    }

    private static function same_site($url) {
        $h = preg_replace('/^www\./', '', (string) wp_parse_url((string) $url, PHP_URL_HOST));
        return $h !== '' && $h === self::host();
    }

    private static function client_ip() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        return $ip ?: '0.0.0.0';
    }

    public static function rest_capture(WP_REST_Request $req) {
        $s = self::settings();
        if (empty($s['capture']) || !$s['key']) return new WP_REST_Response(['ok' => false], 403);
        $origin = $req->get_header('origin') ?: $req->get_header('referer');
        if (!self::same_site($origin)) return new WP_REST_Response(['ok' => false], 403);
        $raw = (string) $req->get_body();
        if (strlen($raw) > 60000) return new WP_REST_Response(['ok' => false], 413);
        $b = json_decode($raw, true);
        if (!is_array($b) || empty($b['fields']) || !is_array($b['fields'])) return new WP_REST_Response(['ok' => false], 400);
        // al massimo 10 invii ogni 10 minuti dallo stesso indirizzo
        $rl = 'tandem_rl_' . md5(self::client_ip());
        $n = (int) get_transient($rl);
        if ($n >= 10) return new WP_REST_Response(['ok' => false], 429);
        set_transient($rl, $n + 1, 600);
        $page = esc_url_raw((string) ($b['page_url'] ?? ''));
        if (!self::same_site($page)) $page = home_url('/');
        $form_key = (string) ($b['form_key'] ?? '');
        if (!preg_match('/^(brevo|custom):[A-Za-z0-9_\-\/#.]{1,180}$/', $form_key)) $form_key = 'custom:' . (wp_parse_url($page, PHP_URL_PATH) ?: '/');
        $fields = [];
        foreach (array_slice($b['fields'], 0, 40) as $f) {
            if (!is_array($f)) continue;
            $fields[] = ['key' => substr((string) ($f['name'] ?? ''), 0, 80), 'label' => substr((string) ($f['label'] ?? ''), 0, 80), 'value' => substr((string) ($f['value'] ?? ''), 0, 2000), 'type' => substr((string) ($f['type'] ?? ''), 0, 20)];
        }
        $ext = (string) ($b['id'] ?? '');
        $payload = self::build($fields, [
            'external_id' => self::host() . ':' . $form_key . ':' . (preg_match('/^[a-f0-9-]{20,40}$/i', $ext) ? $ext : wp_generate_uuid4()),
            'form_key'    => $form_key,
            'form_name'   => sanitize_text_field((string) ($b['form_name'] ?? 'Form')),
            'page_url'    => $page,
            'spam'        => !empty($b['hp']),
        ]);
        if (!$payload['email'] && !$payload['phone']) return new WP_REST_Response(['ok' => true, 'skipped' => true], 200);
        $relay = !empty($b['relay']) && $s['relay'] !== '';
        if ($relay && empty($payload['spam'])) self::brevo_upsert($payload, $fields);
        self::send($payload);
        return new WP_REST_Response(['ok' => true, 'message' => $relay ? $s['relay_message'] : ''], 200);
    }

    /**
     * Per gli altri plugin del sito: una richiesta del loro form (inviata dal server) va in Tandem come le altre.
     * $fields come in build(); $ctx: form_key (lo stesso dichiarato con tandem_forms_known_forms), form_name, page_url,
     * intent, kind, external_id. Se Tandem non risponde la richiesta resta in coda e si ritenta.
     */
    public static function submit(array $fields, array $ctx) {
        if (empty($ctx['external_id'])) $ctx['external_id'] = self::host() . ':' . ($ctx['form_key'] ?? 'form') . ':' . wp_generate_uuid4();
        $payload = self::build($fields, $ctx);
        if (!$payload['email'] && !$payload['phone']) return ['ok' => false, 'code' => 0, 'error' => 'Né email né telefono'];
        return self::send($payload);
    }

    /** Form preso in carico dal plugin (era inviato a Brevo dal browser): lo stesso contatto in Brevo, ma dal server. */
    private static function brevo_upsert(array $p, array $fields) {
        $s = self::settings();
        $list = (int) $s['relay_list'];
        $key = $s['brevo_key'];
        if (!$key && function_exists('sa_opt')) $key = trim((string) sa_opt('brevo_api_key', ''));
        if (!$list || !$key || !$p['email']) return;
        $attrs = [];
        $first = $p['first_name'] ?: ($p['name'] ? $p['name'] : '');
        if ($first) $attrs['FIRSTNAME'] = $first;
        if ($p['last_name']) $attrs['LASTNAME'] = $p['last_name'];
        if ($p['company']) $attrs['NOME_AZIENDA'] = $p['company'];
        if ($p['message']) $attrs['MESSAGE_FORM'] = $p['message'];
        foreach ($fields as $f) if (strtolower($f['key']) === 'pacchetto' && $f['value'] !== '') $attrs['PACCHETTO'] = $f['value'];
        $phone = preg_replace('/[^0-9+]/', '', (string) $p['phone']);
        if ($phone && strpos($phone, '+') !== 0) $phone = '+39' . $phone;
        if ($phone) $attrs['SMS'] = $phone;
        $body = ['email' => $p['email'], 'attributes' => $attrs, 'listIds' => [$list], 'updateEnabled' => true];
        $args = ['timeout' => 8, 'headers' => ['api-key' => $key, 'Content-Type' => 'application/json', 'Accept' => 'application/json']];
        $r = wp_remote_post('https://api.brevo.com/v3/contacts', $args + ['body' => wp_json_encode($body)]);
        $code = is_wp_error($r) ? 0 : (int) wp_remote_retrieve_response_code($r);
        if ($code === 400 && isset($attrs['SMS'])) {
            unset($body['attributes']['SMS']);
            wp_remote_post('https://api.brevo.com/v3/contacts', $args + ['body' => wp_json_encode($body)]);
        }
    }

    public static function footer_js() {
        $s = self::settings();
        if (empty($s['capture']) || !$s['key'] || is_admin() || is_feed()) return;
        $cfg = [
            'url'       => esc_url_raw(rest_url('tandem-forms/v1/capture')),
            'selectors' => implode(',', array_filter(preg_split('/\n/', (string) $s['selectors']))),
            'relay'     => (string) $s['relay'],
            'fail'      => 'Non siamo riusciti a inviare la richiesta. Scrivici a info@' . self::host() . '.',
        ];
        ?>
<script id="tandem-forms" class="_iub_cs_skip" data-noptimize="1" data-no-optimize="1" data-no-defer="1" data-cfasync="false">
(function () {
  var C = <?php echo wp_json_encode($cfg); ?>;
  var BREVO = '#sib-form, form[action*="sibforms.com"]';
  var SKIP = '.elementor-form, .wpcf7-form, .wpforms-form, form[id^="gform_"], .frm-fluent-form, #sa-form, #submit-job-form, form[role="search"], .search-form, #commentform, form.cart, .woocommerce form, #loginform';
  function is(f, sel) { try { return !!sel && f.matches(sel); } catch (e) { return false; } }
  function kind(f) {
    if (is(f, SKIP)) return 0;
    if (C.relay && is(f, C.relay)) return 2;
    if (is(f, BREVO) || is(f, C.selectors)) return 1;
    return 0;
  }
  function labelOf(el) {
    var l = el.id && document.querySelector('label[for="' + (window.CSS && CSS.escape ? CSS.escape(el.id) : el.id) + '"]');
    if (!l) l = el.closest('label');
    if (!l) { var box = el.closest('.form-row, .form-group, .sib-input, .entry__field, p, li, div'); l = box && box.querySelector('label'); }
    var t = l ? l.textContent : (el.getAttribute('aria-label') || el.placeholder || '');
    return (t || '').replace(/\s+/g, ' ').replace(/\*/g, '').trim().slice(0, 80);
  }
  function keyOf(f) {
    var a = f.getAttribute('action') || '', m = a.match(/sibforms\.com\/serve\/([A-Za-z0-9_-]+)/);
    if (m) return 'brevo:' + m[1].slice(0, 24);
    var cls = (f.className && typeof f.className === 'string') ? f.className.trim().split(/\s+/)[0] : '';
    var all = Array.prototype.indexOf.call(document.querySelectorAll('form'), f) + 1;
    return 'custom:' + location.pathname + (f.id ? '#' + f.id : cls ? '.' + cls : '#' + all);
  }
  function collect(f) {
    var out = [], hp = false, seen = {};
    for (var i = 0; i < f.elements.length; i++) {
      var el = f.elements[i], t = (el.type || '').toLowerCase(), n = el.name || el.id;
      if (!n || el.disabled || /^(password|file|submit|button|reset|image)$/.test(t)) continue;
      if (/email_address_check|website_hp|hp_|honeypot/i.test(n)) { if (el.value) hp = true; continue; }
      if (t === 'hidden' || /recaptcha|captcha|turnstile|_wpnonce|nonce/i.test(n)) continue;
      if ((t === 'checkbox' || t === 'radio') && !el.checked) continue;
      var v = el.tagName === 'SELECT' && el.multiple ? Array.prototype.filter.call(el.options, function (o) { return o.selected; }).map(function (o) { return o.text; }).join(', ') : (el.tagName === 'SELECT' && el.selectedIndex >= 0 ? el.options[el.selectedIndex].text : el.value);
      v = (v || '').trim();
      if (!v) continue;
      if (seen[n] !== undefined) { out[seen[n]].value += ', ' + v; continue; }
      seen[n] = out.length;
      out.push({ name: n.slice(0, 80), label: labelOf(el), value: v.slice(0, 2000), type: el.tagName === 'TEXTAREA' ? 'textarea' : t });
    }
    if (!out.some(function (x) { return /@/.test(x.value) || x.type === 'tel'; })) return null;
    var btn = f.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');
    var name = f.getAttribute('name') || f.getAttribute('aria-label') || (btn ? (btn.textContent || btn.value || '').trim() : '') || document.title;
    return { id: (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + Math.random().toString(16).slice(2), form_key: keyOf(f), form_name: name.slice(0, 120), page_url: location.href.split('#')[0], fields: out, hp: hp };
  }
  // avvisa l'Analytics di Tandem (head_js): modulo preso in carico o inviato con successo
  function avvisa(nome, f, p) {
    try { document.dispatchEvent(new CustomEvent(nome, { detail: { el: f, form_key: p.form_key, form: p.form_name } })); } catch (e) {}
  }
  function beacon(p) {
    var body = JSON.stringify(p);
    try { if (navigator.sendBeacon && navigator.sendBeacon(C.url, new Blob([body], { type: 'text/plain' }))) return; } catch (e) {}
    try { fetch(C.url, { method: 'POST', body: body, keepalive: true, credentials: 'same-origin', headers: { 'Content-Type': 'text/plain' } }); } catch (e) {}
  }
  function relay(f) {
    var p = collect(f), btn = f.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');
    if (!p) { if (f.reportValidity) f.reportValidity(); return; }
    p.relay = true;
    var txt = btn ? (btn.textContent || btn.value) : '';
    if (btn) { btn.disabled = true; if (btn.tagName === 'BUTTON') btn.textContent = 'Invio in corso...'; }
    fetch(C.url, { method: 'POST', body: JSON.stringify(p), credentials: 'same-origin', headers: { 'Content-Type': 'text/plain' } })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { if (!r.ok) throw j; return j; }); })
      .then(function (j) { avvisa('tandem:lead', f, p); alert(j.message || 'Richiesta inviata.'); f.reset(); })
      .catch(function () { alert(C.fail); })
      .then(function () { if (btn) { btn.disabled = false; if (btn.tagName === 'BUTTON') btn.textContent = txt; } });
  }
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f || f.tagName !== 'FORM') return;
    var k = kind(f);
    if (!k) return;
    if (k === 2) {
      // il form era inviato dal browser con una chiave Brevo visibile: ora passa dal server
      e.preventDefault();
      e.stopImmediatePropagation();
      if (f.checkValidity && !f.checkValidity()) { f.reportValidity(); return; }
      relay(f);
      return;
    }
    var now = Date.now();
    if (f.__tandem && now - f.__tandem < 4000) return;
    f.__tandem = now;
    var p = collect(f);
    if (p) { beacon(p); avvisa('tandem:capture', f, p); }
  }, true);
})();
</script>
        <?php
    }

    /* ------------------------------------------------------------------ Analytics di Tandem (senza cookie) */

    /**
     * Perche' questa pagina non si misura ('' = si misura). Non si misura: dove c'e' Influencee Core (le visite le
     * misura gia' lui, niente doppi conteggi), fuori dalle pagine pubbliche, per lo staff collegato (come GA4), con
     * l'impostazione spenta, senza collegamento a Tandem o se il filtro «tandem_forms_measure» restituisce false.
     */
    public static function measure_off_reason() {
        if (function_exists('inft_analytics_endpoint')) return 'le misura Influencee Core';
        if (is_admin() || wp_doing_ajax() || is_feed() || is_embed()) return 'pagina non pubblica';
        if (is_user_logged_in() && current_user_can('edit_posts')) return 'staff collegato';
        $s = self::settings();
        if (empty($s['measure'])) return 'impostazione spenta';
        if (!$s['key']) return 'sito non collegato a Tandem';
        if (!apply_filters('tandem_forms_measure', true)) return 'spenta dal filtro tandem_forms_measure';
        return '';
    }

    public static function measure_active() {
        return self::measure_off_reason() === '';
    }

    /** Indirizzo che riceve le visite: la base di Tandem + /api/track. */
    private static function track_url() {
        return self::tandem_base() . self::TRACK_PATH;
    }

    /** Percorso della pagina richiesta, senza parametri. */
    private static function request_path() {
        $uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $path = (string) wp_parse_url($uri, PHP_URL_PATH);
        return $path !== '' ? $path : '/';
    }

    /**
     * Tipo di lead per l'Analytics di Tandem: brand, agenzia, creator o altro. Sui siti di Socialee i moduli sono di
     * aziende (brand); candidature, curriculum e «lavora/collabora con noi», nel nome del modulo o nella pagina: altro.
     * Filtro «tandem_forms_lead_type» ($tipo, $nome_modulo, $percorso): nome vuoto = moduli che il plugin non conosce
     * (Brevo, personalizzati), per i quali lo script applica prima la stessa regola sul nome.
     */
    public static function lead_type_for($form_name, $path) {
        $hay = self::norm($form_name . ' ' . $path);
        $type = preg_match('/lavora|candidat|curriculum|(^|[^a-z])cv([^a-z]|$)|collabora[- ]con[- ]noi/', $hay) ? 'altro' : 'brand';
        $type = (string) apply_filters('tandem_forms_lead_type', $type, (string) $form_name, (string) $path);
        return in_array($type, self::LEAD_TYPES, true) ? $type : 'altro';
    }

    /** Nomi dei form conosciuti (chiave di Tandem => nome), cosi' lo script usa gli stessi nomi delle richieste. */
    public static function refresh_known($forms = null) {
        if (!is_array($forms)) $forms = self::known_forms();
        $map = [];
        foreach ($forms as $f) {
            if (count($map) >= 100) break;
            if (!is_array($f) || empty($f['key'])) continue;
            $map[(string) $f['key']] = mb_substr(trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) ($f['name'] ?? '')))), 0, 120);
        }
        set_transient(self::KNOWN, $map, 2 * DAY_IN_SECONDS);
        return $map;
    }

    /** Dal cron ogni 10 minuti: i nomi si ricalcolano solo se mancano (non si interroga il database a ogni visita). */
    public static function maybe_refresh_known() {
        if (function_exists('inft_analytics_endpoint') || empty(self::settings()['measure'])) return;
        if (get_transient(self::KNOWN) === false) self::refresh_known();
    }

    /**
     * Script dell'Analytics in <head>: nessun cookie e nessun dato personale. Invia a Tandem la pagina vista (percorso,
     * sito di provenienza, utm, se c'e' un clic Google/Meta), i form visti per meta' (magnet_view), i form inviati con
     * successo (lead, con il tipo) e i clic su WhatsApp, telefono, email e iscrizione all'app. Mai i valori digitati.
     * Debug: ?debug_analytics stampa gli invii nella console (e, se la pagina non si misura, il motivo nel codice).
     */
    public static function head_js() {
        $off = self::measure_off_reason();
        if ($off !== '') {
            if (isset($_GET['debug_analytics'])) echo "\n<!-- Tandem Analytics: pagina non misurata (" . esc_html($off) . ") -->\n"; // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }
        $ep = esc_url_raw(self::track_url(), ['https', 'http']);
        if (!$ep) return;
        $path = self::request_path();
        $known = get_transient(self::KNOWN);
        if (!is_array($known)) $known = [];
        $forms = [];
        foreach ($known as $key => $name) $forms[(string) $key] = [(string) $name, self::lead_type_for((string) $name, $path)];
        $cfg = ['ep' => $ep, 'page' => self::lead_type_for('', $path), 'forms' => (object) $forms];
        ?>
<script id="tandem-analytics" class="_iub_cs_skip" data-noptimize="1" data-no-optimize="1" data-no-defer="1" data-cfasync="false">
(function () {
  var C = <?php echo wp_json_encode($cfg, JSON_HEX_TAG | JSON_HEX_AMP); ?>;
  if (!C || !C.ep || !window.JSON) return;
  var debug = /[?&]debug_analytics/.test(location.search);
  // chi chiede al browser di non essere tracciato (Global Privacy Control o Do Not Track) non viene contato
  var nav = window.navigator || {};
  if (nav.globalPrivacyControl === true || nav.doNotTrack === '1' || nav.doNotTrack === 'yes' || window.doNotTrack === '1' || nav.msDoNotTrack === '1') {
    if (debug && window.console) console.info('[Tandem] visita non misurata: il browser chiede di non tracciare');
    return;
  }
  var coda = [], timer = null;

  // invio: al massimo 20 eventi per volta, con sendBeacon (o fetch keepalive), senza cookie
  function invia() {
    if (timer) { clearTimeout(timer); timer = null; }
    if (!coda.length) return;
    var ev = coda.splice(0, 20), b = JSON.stringify({ host: location.hostname, events: ev }), ok = false;
    if (debug && window.console) console.info('[Tandem]', ev);
    try { ok = !!(navigator.sendBeacon && navigator.sendBeacon(C.ep, new Blob([b], { type: 'text/plain' }))); } catch (e) {}
    if (!ok && window.fetch) {
      try {
        var pr = fetch(C.ep, { method: 'POST', body: b, keepalive: true, mode: 'cors', credentials: 'omit', headers: { 'content-type': 'text/plain' } });
        if (pr && pr['catch']) pr['catch'](function () {});
      } catch (e) {}
    }
    if (coda.length) invia();
  }
  function evento(e, subito) {
    e.path = location.pathname;
    coda.push(e);
    if (subito) invia(); else if (!timer) timer = setTimeout(invia, 1500);
  }
  // a fine visita parte quello che resta in coda
  window.addEventListener('pagehide', invia);
  document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'hidden') invia(); });

  // pagina vista: della provenienza basta il sito (niente percorso ne' parametri della pagina di partenza)
  function qp(n) {
    var m = new RegExp('[?&]' + n + '=([^&#]*)').exec(location.search);
    if (!m) return '';
    try { return decodeURIComponent(m[1].replace(/\+/g, ' ')); } catch (e) { return m[1]; }
  }
  var ref = /^[a-z][a-z0-9+.\-]*:\/\/[^\/?#]+/i.exec(document.referrer || '');
  evento({
    kind: 'page_view', referrer: ref ? ref[0] : '',
    utm_source: qp('utm_source'), utm_medium: qp('utm_medium'), utm_campaign: qp('utm_campaign'),
    gclid: (qp('gclid') || qp('gbraid') || qp('wbraid')) ? '1' : '', fbclid: qp('fbclid') ? '1' : ''
  }, true);

  function combacia(el, sel) {
    var m = el && (el.matches || el.msMatchesSelector || el.webkitMatchesSelector);
    try { return !!m && m.call(el, sel); } catch (e) { return false; }
  }
  function su(el, sel) {
    if (el && el.nodeType === 3) el = el.parentNode;
    while (el && el.nodeType === 1) { if (combacia(el, sel)) return el; el = el.parentNode; }
    return null;
  }
  function pulisci(s, n) { return String(s || '').replace(/\s+/g, ' ').replace(/^\s+|\s+$/g, '').slice(0, n); }
  function campo(f, sel) { var el = f.querySelector(sel); return el ? String(el.value || '') : ''; }
  function valore(f, sel) { return campo(f, sel).replace(/[^\w\-]/g, ''); }
  function classe(f, c) { return (' ' + (typeof f.className === 'string' ? f.className : '') + ' ').indexOf(' ' + c + ' ') >= 0; }

  // form che lasciano un contatto (email o telefono); ricerche, login, commenti, carrello e cassa no
  var NO = 'form[role="search"], .search-form, .searchform, #commentform, .comment-form, #loginform, form.login, form.register, .woocommerce-form-login, form.cart, form.checkout, .woocommerce-cart-form, .woocommerce-checkout form, .woocommerce-cart form';
  var BREVO = '#sib-form, form[action*="sibforms.com"]';
  function isLead(f) {
    if (!f || f.tagName !== 'FORM' || combacia(f, NO) || su(f, '[data-tandem-ignore], [data-track-ignore], #wpadminbar')) return false;
    if (f.querySelector('input[name="s"], input[type="password"]')) return false;
    return !!f.querySelector('input[type="email"], input[type="tel"], input[name*="email"]:not([type="hidden"]), input[name*="EMAIL"]:not([type="hidden"]), input[name*="telefono"]:not([type="hidden"]), input[name*="phone"]:not([type="hidden"])');
  }
  // chiave e nome come nelle richieste di Tandem (stessa form_key delle integrazioni e del footer di tandem-forms)
  function chiave(f) {
    var a = f.getAttribute('action') || '', m = a.match(/sibforms\.com\/serve\/([A-Za-z0-9_-]+)/);
    if (m) return 'brevo:' + m[1].slice(0, 24);
    var cls = (f.className && typeof f.className === 'string') ? f.className.replace(/^\s+/, '').split(/\s+/)[0] : '';
    var all = Array.prototype.indexOf.call(document.querySelectorAll('form'), f) + 1;
    return 'custom:' + location.pathname + (f.id ? '#' + f.id : cls ? '.' + cls : '#' + all);
  }
  // il nome resta quello della prima lettura: durante l'invio il testo del pulsante cambia («Invio in corso...»)
  function nomeLibero(f) {
    if (f.__tandemNome) return f.__tandemNome;
    var btn = f.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');
    return (f.__tandemNome = f.getAttribute('name') || f.getAttribute('aria-label') || (btn ? String(btn.textContent || btn.value || '') : '') || document.title);
  }
  // form segnalato dal footer di tandem-forms: stessa chiave e, se non letto prima, stesso nome della richiesta
  function dalFooter(d) {
    if (d.form && !d.el.__tandemNome) d.el.__tandemNome = String(d.form);
    var x = info(d.el);
    if (d.form_key) x.key = pulisci(d.form_key, 200);
    return x;
  }
  function info(f) {
    var id = typeof f.id === 'string' ? f.id : '', k, n, m;
    if (classe(f, 'elementor-form') && (m = valore(f, 'input[name="form_id"]'))) { k = 'elementor:' + m; n = f.getAttribute('name') || campo(f, 'input[name="form_name"]') || 'Form Elementor'; }
    else if (classe(f, 'wpcf7-form') && (m = valore(f, 'input[name="_wpcf7"]'))) { k = 'cf7:' + m; n = 'Contact Form 7'; }
    else if (classe(f, 'wpforms-form') && (m = f.getAttribute('data-formid') || (/^wpforms-form-(\d+)/.exec(id) || [])[1])) { k = 'wpforms:' + m; n = 'WPForms'; }
    else if ((m = /^gform_(\d+)$/.exec(id))) { k = 'gravity:' + m[1]; n = 'Gravity Forms'; }
    else if (classe(f, 'frm-fluent-form') && (m = f.getAttribute('data-form_id') || (/^fluentform_(\d+)/.exec(id) || [])[1])) { k = 'fluent:' + m; n = 'Fluent Forms'; }
    else if (id === 'sa-form') { k = 'academy:signup'; n = 'Iscrizione Academy'; }
    else if (id === 'submit-job-form') { k = 'jobmanager:submit'; n = 'Pubblica offerta di lavoro'; }
    else { k = chiave(f); n = nomeLibero(f); }
    if (C.forms[k]) n = C.forms[k][0];
    return { key: pulisci(k, 200), name: pulisci(n, 120) || 'Modulo', el: f };
  }
  var ALTRO = /lavora|candidat|curriculum|(^|[^a-z])cv([^a-z]|$)|collabora[- ]con[- ]noi/;
  function tipo(x) {
    var forza = x.el && su(x.el, '[data-lead-type]'), t = forza ? forza.getAttribute('data-lead-type') : '';
    if (/^(brand|agenzia|creator|altro)$/.test(t)) return t;
    if (C.forms[x.key]) return C.forms[x.key][1];
    return ALTRO.test(x.name.toLowerCase()) ? 'altro' : C.page;
  }

  // lead: solo a invio riuscito, una volta per form anche se piu' plugin lo segnalano
  var ultimi = {};
  function lead(x) {
    if (!x || !x.key) return;
    var ora = new Date().getTime();
    if (ultimi[x.key] && ora - ultimi[x.key] < 4000) return;
    ultimi[x.key] = ora;
    evento({ kind: 'lead', form: x.name, form_key: x.key, lead_type: tipo(x) }, true);
  }
  function leadForm(f) { if (f && f.nodeType === 1 && f.tagName === 'FORM' && isLead(f)) lead(info(f)); }
  function leadChiave(k, n) { lead({ key: k, name: pulisci((C.forms[k] && C.forms[k][0]) || n, 120), el: null }); }

  // Contact Form 7: evento del browser dopo l'invio della mail
  document.addEventListener('wpcf7mailsent', function (e) {
    var t = e.target, d = e.detail || {}, id = parseInt(d.contactFormId, 10);
    var f = t && t.nodeType === 1 ? (t.tagName === 'FORM' ? t : t.querySelector('form')) : null;
    if (f) leadForm(f); else if (id > 0) leadChiave('cf7:' + id, 'Contact Form 7');
  });
  // Fluent Forms, se manda anche l'evento del browser (altrimenti jQuery, sotto)
  document.addEventListener('fluentform_submission_success', function (e) {
    var d = e.detail || {}, f = d.form && (d.form.nodeType ? d.form : d.form[0]);
    leadForm(f || e.target);
  });
  // tandem-forms: form gestito dal server («Form da gestire dal server») inviato con successo
  document.addEventListener('tandem:lead', function (e) {
    var d = e.detail || {};
    if (d.el) lead(dalFooter(d));
  });
  // tandem-forms: form Brevo o personalizzato preso dalla pagina e mandato a Tandem
  function visibile(el) { return !!el && (/--active/.test(el.className || '') || !!(el.offsetWidth || el.offsetHeight)); }
  document.addEventListener('tandem:capture', function (e) {
    var d = e.detail || {}, f = d.el;
    if (!f) return;
    var x = dalFooter(d);
    var box =combacia(f, BREVO) ? (su(f, '.sib-form, .sib-form-container, #sib-form-container') || f.parentNode) : null;
    var ok = box && box.querySelector('#success-message, .sib-form-message-panel--success');
    if (!ok) { lead(x); return; }
    // Brevo: conta quando compare il messaggio di iscrizione riuscita (fino a 20 secondi)
    var prima = visibile(ok), giri = 0, t = setInterval(function () {
      giri++;
      if (!prima && visibile(ok)) { clearInterval(t); lead(x); } else if (giri > 50) clearInterval(t);
    }, 400);
  });
  // Elementor Pro, WPForms, Fluent Forms e Gravity Forms segnalano il successo con eventi jQuery
  var conJQ = false;
  function agganciaJQ() {
    var $ = window.jQuery;
    if (conJQ || !$ || !$.fn || !$.fn.on) return;
    conJQ = true;
    $(document).on('submit_success', function (e) { leadForm(e.target); });
    $(document).on('wpformsAjaxSubmitSuccess', function (e) { leadForm(e.target); });
    $(document).on('fluentform_submission_success', function (e, d) {
      leadForm(e.target && e.target.tagName === 'FORM' ? e.target : (d && d.form && (d.form.nodeType ? d.form : d.form[0])));
    });
    $(document).on('gform_confirmation_loaded', function (e, id) { if (parseInt(id, 10) > 0) leadChiave('gravity:' + parseInt(id, 10), 'Gravity Forms'); });
  }
  // Academy Socialee: il messaggio diventa «ok» solo a iscrizione riuscita, poi la pagina cambia
  function academy() {
    var f = document.getElementById('sa-form'), msg = document.getElementById('sa-form-msg');
    if (!f || !msg || msg.__tandem || !window.MutationObserver) return;
    msg.__tandem = 1;
    new MutationObserver(function () { if (classe(msg, 'ok')) leadForm(f); }).observe(msg, { attributes: true, attributeFilter: ['class'] });
  }

  // form visti: visibili per meta' almeno una volta, una volta per nome di form per pagina
  var cerca = function () {};
  if (window.IntersectionObserver) {
    var visti = {};
    var io = new IntersectionObserver(function (list) {
      var h = window.innerHeight || document.documentElement.clientHeight || 0;
      for (var i = 0; i < list.length; i++) {
        var en = list[i], r = en.intersectionRect || {};
        if (!(en.isIntersecting || en.intersectionRatio > 0)) continue;
        if (en.intersectionRatio < 0.5 && !(h && r.height >= h / 2)) continue;
        io.unobserve(en.target);
        var x = info(en.target);
        if (visti[x.name]) continue;
        visti[x.name] = 1;
        evento({ kind: 'magnet_view', form: x.name, form_key: x.key });
      }
    }, { threshold: [0, 0.1, 0.2, 0.3, 0.4, 0.5] });
    cerca = function () {
      var fs = document.getElementsByTagName('form');
      for (var i = 0; i < fs.length; i++) if (!fs[i].__tandemVisto && isLead(fs[i])) { fs[i].__tandemVisto = 1; io.observe(fs[i]); }
    };
  }
  var pronto = function () { cerca(); academy(); agganciaJQ(); };
  pronto();
  document.addEventListener('DOMContentLoaded', pronto);
  window.addEventListener('load', pronto);
  // form disegnati dopo il caricamento (popup, pagine dinamiche)
  if (window.MutationObserver) {
    var attesa = null;
    new MutationObserver(function () { if (!attesa) attesa = setTimeout(function () { attesa = null; cerca(); academy(); }, 400); })
      .observe(document.documentElement, { childList: true, subtree: true });
  }

  // clic che valgono: WhatsApp, telefono, email, iscrizione all'app
  document.addEventListener('click', function (e) {
    var a = su(e.target, 'a[href]');
    if (!a) return;
    var h = a.getAttribute('href') || '', l = '';
    if (/wa\.me|whatsapp/i.test(h)) l = 'whatsapp';
    else if (/^\s*tel:/i.test(h)) l = 'telefono';
    else if (/^\s*mailto:/i.test(h)) l = 'email';
    else if (/app\.influencee\.it\/iscriviti\/brand/i.test(h)) l = 'iscrizione_brand';
    else if (/app\.influencee\.it\/iscriviti\/creator/i.test(h)) l = 'iscrizione_creator';
    else if (/app\.influencee\.it\/iscriviti/i.test(h)) l = 'iscrizione';
    if (l) evento({ kind: 'cta', label: l }, true);
  }, true);
})();
</script>
        <?php
    }

    /* ------------------------------------------------------------------ aggiornamenti da Tandem */

    /**
     * Ultima versione pubblicata in Tandem: GET <base di Tandem>/api/plugins/tandem-forms, in cache 6 ore (1 ora se Tandem
     * non risponde). null se Tandem non risponde o la risposta non e' valida: in quel caso il plugin non fa nulla.
     */
    private static function remote_info($force = false) {
        $cached = get_transient(self::UPDATE_CACHE);
        if (!$force && is_array($cached) && array_key_exists('info', $cached)) return is_array($cached['info']) ? $cached['info'] : null;
        $info = null;
        $error = '';
        $r = wp_remote_get(self::tandem_base() . self::UPDATE_PATH, [
            'timeout'     => 8,
            'redirection' => 2,
            'headers'     => ['Accept' => 'application/json', 'User-Agent' => 'TandemForms/' . self::VERSION . '; ' . home_url('/')],
        ]);
        if (is_wp_error($r)) {
            $error = $r->get_error_message();
        } elseif ((int) wp_remote_retrieve_response_code($r) !== 200) {
            $error = 'HTTP ' . (int) wp_remote_retrieve_response_code($r);
        } else {
            $info = self::valid_info(json_decode((string) wp_remote_retrieve_body($r), true));
            if (!$info) $error = 'risposta non valida';
        }
        set_transient(self::UPDATE_CACHE, ['info' => $info], $info ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS);
        update_option(self::UPDATE_LAST, ['t' => time(), 'version' => $info ? $info['version'] : '', 'error' => substr($error, 0, 120)], false);
        return $info;
    }

    /** Risposta di Tandem ripulita; null se manca la versione o se il pacchetto non viene da Tandem. */
    private static function valid_info($j) {
        if (!is_array($j)) return null;
        $version = trim((string) ($j['version'] ?? ''));
        if (!preg_match('/^\d+(\.\d+){1,3}$/', $version)) return null;
        $package = self::safe_package((string) ($j['download_url'] ?? ''));
        if ($package === '') return null;
        $sections = [];
        foreach ((array) ($j['sections'] ?? []) as $k => $html) {
            $k = sanitize_key((string) $k);
            if ($k !== '' && is_string($html) && count($sections) < 10) $sections[$k] = wp_kses_post($html);
        }
        $ver = function ($v) { $v = trim((string) $v); return preg_match('/^\d+(\.\d+){0,3}$/', $v) ? $v : ''; };
        return [
            'version'      => $version,
            'download_url' => $package,
            'requires'     => $ver($j['requires'] ?? ''),
            'tested'       => $ver($j['tested'] ?? ''),
            'requires_php' => $ver($j['requires_php'] ?? ''),
            'sections'     => $sections,
        ];
    }

    /** Pacchetto accettato solo in https e sullo stesso host di Tandem (http solo per un Tandem di prova su localhost). */
    private static function safe_package($url) {
        $url = esc_url_raw(trim($url), ['https', 'http']);
        $base = self::tandem_base();
        $host = strtolower((string) wp_parse_url($base, PHP_URL_HOST));
        if ($url === '' || $host === '' || strtolower((string) wp_parse_url($url, PHP_URL_HOST)) !== $host) return '';
        $scheme = strtolower((string) wp_parse_url($url, PHP_URL_SCHEME));
        if ($scheme === 'https') return $url;
        $local = in_array($host, ['localhost', '127.0.0.1'], true) && strtolower((string) wp_parse_url($base, PHP_URL_SCHEME)) === 'http';
        return $scheme === 'http' && $local ? $url : '';
    }

    /** Voce del plugin negli aggiornamenti di WordPress. */
    private static function update_item(array $info) {
        $file = plugin_basename(__FILE__);
        return (object) [
            'id'            => $file,
            'slug'          => self::SLUG,
            'plugin'        => $file,
            'new_version'   => $info['version'],
            'url'           => self::tandem_base(),
            'package'       => $info['download_url'],
            'requires'      => $info['requires'],
            'tested'        => $info['tested'],
            'requires_php'  => $info['requires_php'],
            'icons'         => [],
            'banners'       => [],
            'banners_rtl'   => [],
            'compatibility' => new stdClass(),
        ];
    }

    /** Aggiunge l'aggiornamento quando in Tandem c'e' una versione piu' nuova di quella installata. */
    public static function update_check($transient) {
        if (!is_object($transient)) return $transient;
        $info = self::remote_info();
        if (!$info) return $transient;
        $file = plugin_basename(__FILE__);
        $item = self::update_item($info);
        if (!isset($transient->response) || !is_array($transient->response)) $transient->response = [];
        if (!isset($transient->no_update) || !is_array($transient->no_update)) $transient->no_update = [];
        if (version_compare($info['version'], self::VERSION, '>')) {
            $transient->response[$file] = $item;
            unset($transient->no_update[$file]);
        } else {
            // nessun aggiornamento: la voce in no_update fa comparire «Visualizza dettagli» e gli aggiornamenti automatici
            $transient->no_update[$file] = $item;
            unset($transient->response[$file]);
        }
        return $transient;
    }

    /** Finestra «Visualizza dettagli» del plugin: nome, versione e changelog da Tandem. */
    public static function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || !is_object($args) || ($args->slug ?? '') !== self::SLUG) return $result;
        $info = self::remote_info();
        if (!$info) return $result;
        return (object) [
            'name'          => 'Tandem Forms',
            'slug'          => self::SLUG,
            'version'       => $info['version'],
            'author'        => 'Influencee',
            'homepage'      => self::tandem_base(),
            'requires'      => $info['requires'],
            'tested'        => $info['tested'],
            'requires_php'  => $info['requires_php'],
            'download_link' => $info['download_url'],
            'sections'      => $info['sections'] ?: ['changelog' => '<p>Versione ' . esc_html($info['version']) . '.</p>'],
        ];
    }

    /** «Aggiornamenti automatici da Tandem» accesa: WordPress installa da solo le nuove versioni; spenta: decide WordPress. */
    public static function auto_update($update, $item) {
        if (!is_object($item) || ($item->plugin ?? '') !== plugin_basename(__FILE__)) return $update;
        return empty(self::settings()['autoupdate']) ? $update : true;
    }

    /* ------------------------------------------------------------------ segnale settimanale e form conosciuti */

    public static function integrations() {
        return [
            'elementor'  => defined('ELEMENTOR_PRO_VERSION'),
            'cf7'        => defined('WPCF7_VERSION'),
            'wpforms'    => function_exists('wpforms'),
            'gravity'    => class_exists('GFForms'),
            'fluent'     => defined('FLUENTFORM') || function_exists('wpFluentForm'),
            'jobmanager' => class_exists('WP_Job_Manager'),
            'academy'    => post_type_exists('sa_lead'),
        ];
    }

    /** Form che il plugin trova nel database del sito (anche in popup e modelli non visibili in sitemap). */
    public static function known_forms() {
        global $wpdb;
        $out = [];
        $integ = self::integrations();
        if ($integ['elementor']) {
            $rows = $wpdb->get_results("SELECT p.ID, p.post_type, p.post_title, m.meta_value FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = '_elementor_data' AND m.meta_value LIKE '%\"widgetType\":\"form\"%' AND p.post_status = 'publish' LIMIT 200"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            foreach ((array) $rows as $r) {
                $data = json_decode((string) $r->meta_value, true);
                if (!is_array($data)) continue;
                $page = $r->post_type === 'elementor_library' ? 'Modello Elementor: ' . $r->post_title : get_permalink((int) $r->ID);
                foreach (self::elementor_forms($data) as $id => $f) {
                    $k = 'elementor:' . $id;
                    if (!isset($out[$k])) $out[$k] = ['key' => $k, 'name' => $f['name'] ?: 'Form Elementor', 'type' => 'ELEMENTOR', 'pages' => []];
                    if (count($out[$k]['pages']) < 20) $out[$k]['pages'][] = $page;
                }
            }
        }
        if ($integ['cf7']) foreach (get_posts(['post_type' => 'wpcf7_contact_form', 'numberposts' => 100]) as $p) $out['cf7:' . $p->ID] = ['key' => 'cf7:' . $p->ID, 'name' => $p->post_title, 'type' => 'CF7', 'pages' => []];
        if ($integ['wpforms']) foreach (get_posts(['post_type' => 'wpforms', 'numberposts' => 100]) as $p) $out['wpforms:' . $p->ID] = ['key' => 'wpforms:' . $p->ID, 'name' => $p->post_title, 'type' => 'WPFORMS', 'pages' => []];
        if ($integ['gravity'] && class_exists('GFAPI')) foreach ((array) GFAPI::get_forms() as $f) $out['gravity:' . $f['id']] = ['key' => 'gravity:' . $f['id'], 'name' => $f['title'], 'type' => 'GRAVITY', 'pages' => []];
        if ($integ['fluent']) {
            $t = $wpdb->prefix . 'fluentform_forms';
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $t)) === $t) foreach ((array) $wpdb->get_results("SELECT id, title FROM {$t} LIMIT 100") as $f) $out['fluent:' . $f->id] = ['key' => 'fluent:' . $f->id, 'name' => $f->title, 'type' => 'FLUENT', 'pages' => []]; // phpcs:ignore
        }
        if ($integ['jobmanager']) {
            $pid = (int) get_option('job_manager_submit_job_form_page_id');
            $out['jobmanager:submit'] = ['key' => 'jobmanager:submit', 'name' => 'Pubblica offerta di lavoro', 'type' => 'JOBMANAGER', 'pages' => $pid ? [get_permalink($pid)] : []];
        }
        if ($integ['academy']) $out['academy:signup'] = ['key' => 'academy:signup', 'name' => 'Iscrizione Academy', 'type' => 'ACADEMY', 'pages' => []];
        // Form fatti da altri plugin del sito (pagine proprie, moduli in JavaScript): si dichiarano con il filtro
        // tandem_forms_known_forms: ['key' => 'custom:/percorso/#id', 'name' => ..., 'type' => 'CUSTOM', 'pages' => [url],
        // 'server' => true se il plugin manda le richieste con Tandem_Forms::submit()].
        $extra = apply_filters('tandem_forms_known_forms', []);
        foreach ((array) $extra as $f) {
            if (!is_array($f) || empty($f['key']) || !preg_match('/^[a-z]+:[A-Za-z0-9_\-\/#.:]{1,180}$/', (string) $f['key'])) continue;
            $k = (string) $f['key'];
            if (isset($out[$k])) continue;
            $out[$k] = [
                'key'   => $k,
                'name'  => mb_substr(sanitize_text_field((string) ($f['name'] ?? 'Form')), 0, 120),
                'type'  => in_array(($f['type'] ?? ''), ['CUSTOM', 'OTHER'], true) ? $f['type'] : 'CUSTOM',
                'pages' => array_values(array_slice(array_map('esc_url_raw', (array) ($f['pages'] ?? [])), 0, 20)),
            ];
            // 'server' => true: le richieste arrivano a Tandem dal server del sito (Tandem_Forms::submit), non dal browser
            if (!empty($f['server'])) $out[$k]['server'] = true;
        }
        return array_values(array_slice($out, 0, 200));
    }

    /** Etichette di un form Elementor cercandolo in tutti i contenuti (modelli, popup, sezioni globali). */
    private static function elementor_labels_anywhere($element_id) {
        static $cache = [];
        if ($element_id === '' || !preg_match('/^[a-z0-9]{5,12}$/i', $element_id)) return [];
        if (isset($cache[$element_id])) return $cache[$element_id];
        global $wpdb;
        $cache[$element_id] = [];
        $like = '%' . $wpdb->esc_like('"id":"' . $element_id . '"') . '%';
        $rows = $wpdb->get_col($wpdb->prepare("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' AND meta_value LIKE %s LIMIT 5", $like)); // phpcs:ignore
        foreach ((array) $rows as $raw) {
            $data = json_decode((string) $raw, true);
            $forms = is_array($data) ? self::elementor_forms($data) : [];
            if (!empty($forms[$element_id]['labels'])) {
                $cache[$element_id] = $forms[$element_id]['labels'];
                break;
            }
        }
        return $cache[$element_id];
    }

    /** Widget form dentro i dati di Elementor: id => [name, labels (custom_id => etichetta)]. */
    private static function elementor_forms(array $elements) {
        $found = [];
        $walk = function ($els) use (&$walk, &$found) {
            foreach ((array) $els as $el) {
                if (!is_array($el)) continue;
                if (($el['widgetType'] ?? '') === 'form' && !empty($el['id'])) {
                    $labels = [];
                    foreach ((array) ($el['settings']['form_fields'] ?? []) as $ff) {
                        if (!empty($ff['custom_id'])) $labels[$ff['custom_id']] = (string) ($ff['field_label'] ?? '') ?: (string) ($ff['placeholder'] ?? '');
                    }
                    $found[$el['id']] = ['name' => (string) ($el['settings']['form_name'] ?? ''), 'labels' => $labels];
                }
                if (!empty($el['elements'])) $walk($el['elements']);
            }
        };
        $walk($elements);
        return $found;
    }

    public static function report() {
        $s = self::settings();
        if (!$s['key']) return ['ok' => false, 'error' => 'Chiave di Tandem non impostata'];
        $q = get_option(self::QUEUE, []);
        $forms = self::known_forms();
        self::refresh_known($forms);
        $res = self::send([
            'report'       => true,
            'version'      => self::VERSION,
            'site_url'     => home_url('/'),
            'integrations' => self::integrations(),
            'capture'      => !empty($s['capture']),
            'selectors'    => array_values(array_filter(preg_split('/\n/', (string) $s['selectors']))),
            'relay'        => (string) $s['relay'],
            'queue'        => is_array($q) ? count($q) : 0,
            'forms'        => $forms,
        ], false);
        update_option(self::REPORT, ['t' => time(), 'ok' => $res['ok'], 'msg' => $res['ok'] ? 'Segnale ricevuto da Tandem' : ($res['error'] ?: 'HTTP ' . $res['code'])], false);
        return $res;
    }

    /* ------------------------------------------------------------------ storico */

    /** Fonti dello storico e quante richieste ciascuna ha prima dell'installazione del plugin. */
    private static function history_sources() {
        global $wpdb;
        $cut = gmdate('Y-m-d H:i:s', (int) self::settings()['installed_at'] ?: time());
        $src = [];
        $t = $wpdb->prefix . 'e_submissions';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $t)) === $t) {
            $src['elementor'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE status <> 'trash' AND created_at_gmt < %s", $cut)); // phpcs:ignore
        }
        if (post_type_exists('job_listing')) $src['job'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'job_listing' AND post_status NOT IN ('trash','auto-draft','preview') AND post_date < %s", get_date_from_gmt($cut)));
        // registrazioni che valgono come contatto: niente staff e niente semplici iscritti
        $src['users'] = count(get_users(['role__not_in' => array_merge(self::STAFF_ROLES, ['subscriber']), 'date_query' => [['before' => $cut, 'column' => 'user_registered']], 'fields' => 'ID', 'number' => 5000]));
        if ($src['users'] <= 0) unset($src['users']);
        if (post_type_exists('sa_lead')) $src['academy'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'sa_lead' AND post_status = 'publish' AND post_date_gmt < %s", $cut));
        if (post_type_exists('flamingo_inbound')) $src['flamingo'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'flamingo_inbound' AND post_status = 'publish' AND post_date_gmt < %s", $cut));
        return $src;
    }

    /** ID degli utenti dello staff (amministratori, redattori): esclusi dallo storico. */
    private static function staff_ids() {
        return array_map('intval', get_users(['role__in' => self::STAFF_ROLES, 'fields' => 'ID', 'number' => 500]));
    }

    public static function ajax_history() {
        if (!current_user_can('manage_options')) wp_send_json_error('Permesso negato', 403);
        check_ajax_referer('tandem_forms_history');
        global $wpdb;
        $source = sanitize_key($_POST['source'] ?? '');
        $offset = absint($_POST['offset'] ?? 0);
        $cut = gmdate('Y-m-d H:i:s', (int) self::settings()['installed_at'] ?: time());
        $batch = 15;
        $payloads = [];
        if ($source === 'elementor') {
            $t = $wpdb->prefix . 'e_submissions';
            $v = $wpdb->prefix . 'e_submissions_values';
            $subs = $wpdb->get_results($wpdb->prepare("SELECT id, post_id, element_id, form_name, referer, created_at_gmt FROM {$t} WHERE status <> 'trash' AND created_at_gmt < %s ORDER BY id LIMIT %d OFFSET %d", $cut, $batch, $offset)); // phpcs:ignore
            $labels_cache = [];
            foreach ((array) $subs as $sub) {
                if (!isset($labels_cache[$sub->post_id])) {
                    $data = json_decode((string) get_post_meta((int) $sub->post_id, '_elementor_data', true), true);
                    $labels_cache[$sub->post_id] = is_array($data) ? self::elementor_forms($data) : [];
                }
                $labels = $labels_cache[$sub->post_id][$sub->element_id]['labels'] ?? [];
                if (!$labels) $labels = self::elementor_labels_anywhere((string) $sub->element_id);
                $fields = [];
                foreach ((array) $wpdb->get_results($wpdb->prepare("SELECT `key`, value FROM {$v} WHERE submission_id = %d", $sub->id)) as $row) { // phpcs:ignore
                    $fields[] = ['key' => (string) $row->key, 'label' => $labels[$row->key] ?? '', 'value' => (string) $row->value, 'type' => ''];
                }
                $payloads[] = self::build($fields, [
                    'external_id'  => self::host() . ':elementor-sub:' . $sub->id,
                    'form_key'     => 'elementor:' . $sub->element_id,
                    'form_name'    => $sub->form_name ?: 'Form Elementor',
                    'page_url'     => $sub->referer ?: home_url('/'),
                    'submitted_at' => str_replace(' ', 'T', (string) $sub->created_at_gmt) . 'Z',
                    'history'      => true,
                ]);
            }
        } elseif ($source === 'users') {
            $staff = self::staff_ids();
            $ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->users} WHERE user_registered < %s ORDER BY ID LIMIT %d OFFSET %d", $cut, $batch, $offset)); // phpcs:ignore
            foreach ((array) $ids as $id) {
                $u = in_array((int) $id, $staff, true) ? false : get_userdata((int) $id);
                $payloads[] = $u && self::is_lead_account($u) ? self::user_payload($u, true) : ['email' => '', 'phone' => ''];
            }
        } elseif ($source === 'job' || $source === 'academy' || $source === 'flamingo') {
            $type = $source === 'job' ? 'job_listing' : ($source === 'academy' ? 'sa_lead' : 'flamingo_inbound');
            $status = $source === 'job' ? "post_status NOT IN ('trash','auto-draft','preview')" : "post_status = 'publish'";
            $ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND {$status} AND post_date < %s ORDER BY ID LIMIT %d OFFSET %d", $type, get_date_from_gmt($cut), $batch, $offset)); // phpcs:ignore
            foreach ((array) $ids as $id) {
                $id = (int) $id;
                if ($source === 'job') $payloads[] = self::job_payload($id, true);
                elseif ($source === 'academy') $payloads[] = self::academy_payload($id, true, self::host() . ':academy:' . $id);
                else {
                    $fields = [];
                    foreach ((array) get_post_meta($id) as $mk => $mv) {
                        if (strpos($mk, '_field_') !== 0) continue;
                        $fields[] = ['key' => substr($mk, 7), 'label' => '', 'value' => maybe_unserialize($mv[0]), 'type' => ''];
                    }
                    $p = get_post($id);
                    $payloads[] = self::build($fields, ['external_id' => self::host() . ':flamingo:' . $id, 'form_key' => 'cf7:flamingo', 'form_name' => $p ? $p->post_title : 'Contact Form 7', 'page_url' => home_url('/'), 'submitted_at' => $p ? get_post_time('c', true, $p) : gmdate('c'), 'history' => true]);
                }
            }
        } else {
            wp_send_json_error('Fonte sconosciuta', 400);
        }
        $ok = 0;
        $fail = 0;
        foreach ($payloads as $p) {
            // senza recapiti ma con l'azienda (offerte pubblicate dallo staff): Tandem crea comunque l'azienda
            if (!$p['email'] && !$p['phone'] && empty($p['company'])) continue;
            $r = self::send($p);
            if ($r['ok']) $ok++; else $fail++;
        }
        wp_send_json_success(['done' => count($payloads), 'ok' => $ok, 'fail' => $fail, 'more' => count($payloads) === $batch]);
    }

    /* ------------------------------------------------------------------ pagina di impostazioni */

    public static function menu() {
        add_options_page('Tandem Forms', 'Tandem Forms', 'manage_options', 'tandem-forms', [__CLASS__, 'page']);
    }

    public static function action_test() {
        if (!current_user_can('manage_options')) wp_die('Permesso negato');
        check_admin_referer('tandem_forms_test');
        $ping = self::send(['ping' => true], false);
        if ($ping['ok']) {
            self::report();
            $msg = 'Collegamento riuscito: Tandem riconosce questo sito come «' . ($ping['body']['site'] ?? '') . '».';
        } else {
            $msg = 'Collegamento non riuscito: ' . ($ping['error'] ?: 'HTTP ' . $ping['code']) . '.';
        }
        set_transient('tandem_forms_notice', [$ping['ok'] ? 'success' : 'error', $msg], 60);
        wp_safe_redirect(admin_url('options-general.php?page=tandem-forms'));
        exit;
    }

    /** Indirizzo di Tandem (senza /api/intake): pagina di collegamento e scambio del codice. */
    private static function tandem_base() {
        return untrailingslashit(preg_replace('#/api/intake/?$#', '', (string) self::settings()['endpoint']));
    }

    /** «Collega a Tandem»: apre Tandem, dove un Admin conferma; Tandem rimanda qui un codice usa e getta. */
    public static function action_connect() {
        if (!current_user_can('manage_options')) wp_die('Permesso negato');
        check_admin_referer('tandem_forms_connect');
        $state = wp_generate_password(32, false, false);
        set_transient('tandem_forms_state_' . get_current_user_id(), $state, 15 * MINUTE_IN_SECONDS);
        $url = add_query_arg([
            'site'   => rawurlencode(home_url('/')),
            'return' => rawurlencode(admin_url('admin-post.php?action=tandem_forms_paired')),
            'state'  => $state,
        ], self::tandem_base() . '/connect');
        wp_redirect($url); // phpcs:ignore WordPress.Security.SafeRedirect -- Tandem e' un dominio esterno scelto nelle impostazioni
        exit;
    }

    /** Ritorno da Tandem: scambia il codice con la chiave del sito, che non passa mai dal browser in chiaro due volte. */
    public static function action_paired() {
        if (!current_user_can('manage_options')) wp_die('Permesso negato');
        $back = admin_url('options-general.php?page=tandem-forms');
        $key = 'tandem_forms_state_' . get_current_user_id();
        $state = (string) get_transient($key);
        $got = sanitize_text_field(wp_unslash($_GET['state'] ?? '')); // phpcs:ignore WordPress.Security.NonceVerification
        $code = sanitize_text_field(wp_unslash($_GET['tandem_code'] ?? '')); // phpcs:ignore WordPress.Security.NonceVerification
        delete_transient($key);
        if (!$state || !hash_equals($state, $got)) {
            set_transient('tandem_forms_notice', ['error', 'Collegamento non riuscito: la richiesta è scaduta. Premi di nuovo «Collega a Tandem».'], 60);
        } elseif (!empty($_GET['tandem_error']) || !preg_match('/^[0-9a-f]{64}$/', $code)) { // phpcs:ignore WordPress.Security.NonceVerification
            set_transient('tandem_forms_notice', ['error', 'Collegamento annullato.'], 60);
        } else {
            $r = wp_remote_post(self::tandem_base() . '/api/intake/pair', [
                'timeout' => 15,
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => wp_json_encode(['code' => $code]),
            ]);
            $body = is_wp_error($r) ? [] : json_decode((string) wp_remote_retrieve_body($r), true);
            if (!empty($body['ok']) && !empty($body['key']) && preg_match('/^tdm_[A-Za-z0-9_-]{32,80}$/', $body['key'])) {
                $s = self::settings();
                $s['key'] = $body['key'];
                update_option(self::OPT, $s, false);
                self::report();
                set_transient('tandem_forms_notice', ['success', 'Collegato a Tandem come «' . sanitize_text_field($body['site'] ?? '') . '». Da ora le richieste dei form arrivano in Tandem: puoi inviare anche lo storico qui sotto.'], 60);
            } else {
                $err = is_wp_error($r) ? $r->get_error_message() : (string) ($body['error'] ?? 'risposta non valida');
                set_transient('tandem_forms_notice', ['error', 'Collegamento non riuscito: ' . $err], 60);
            }
        }
        wp_safe_redirect($back);
        exit;
    }

    public static function action_flush() {
        if (!current_user_can('manage_options')) wp_die('Permesso negato');
        check_admin_referer('tandem_forms_flush');
        delete_transient('tandem_forms_lock');
        $n = self::process_queue();
        set_transient('tandem_forms_notice', ['success', $n . ' richieste in coda inviate a Tandem.'], 60);
        wp_safe_redirect(admin_url('options-general.php?page=tandem-forms'));
        exit;
    }

    public static function page() {
        if (!current_user_can('manage_options')) return;
        $s = self::settings();
        $q = get_option(self::QUEUE, []);
        $log = get_option(self::LOG, []);
        $rep = get_option(self::REPORT, []);
        $notice = get_transient('tandem_forms_notice');
        if ($notice) delete_transient('tandem_forms_notice');
        $integ = self::integrations();
        $labels = ['elementor' => 'Elementor Pro', 'cf7' => 'Contact Form 7', 'wpforms' => 'WPForms', 'gravity' => 'Gravity Forms', 'fluent' => 'Fluent Forms', 'jobmanager' => 'WP Job Manager', 'academy' => 'Academy Socialee'];
        $sources = self::history_sources();
        $core = function_exists('inft_analytics_endpoint');
        $source_labels =['elementor' => 'richieste dei form Elementor', 'job' => 'offerte di lavoro pubblicate', 'users' => 'registrazioni al sito', 'academy' => 'iscritti Academy', 'flamingo' => 'messaggi Contact Form 7 (Flamingo)'];
        ?>
        <div class="wrap">
            <h1>Tandem Forms</h1>
            <p>Le richieste dei form di questo sito arrivano in Tandem, nella vista <strong>Richieste</strong>: ogni richiesta crea o aggiorna il contatto e, se è commerciale, apre l'opportunità. Ogni settimana il plugin comunica a Tandem quali form ci sono, così un sito rifatto o un form nuovo non collegato non passano inosservati.</p>
            <?php if ($notice) : ?><div class="notice notice-<?php echo esc_attr($notice[0]); ?>"><p><?php echo esc_html($notice[1]); ?></p></div><?php endif; ?>
            <?php settings_errors(self::OPT); ?>

            <h2>Stato</h2>
            <table class="widefat striped" style="max-width:900px">
                <tbody>
                <tr><td style="width:240px">Chiave di Tandem</td><td><?php echo $s['key'] ? 'impostata (…' . esc_html(substr($s['key'], -4)) . ')' : '<strong>da impostare</strong>'; ?></td></tr>
                <tr><td>Ultimo segnale a Tandem</td><td><?php echo !empty($rep['t']) ? esc_html(wp_date('d/m/Y H:i', (int) $rep['t']) . ' · ' . $rep['msg']) : 'mai'; ?></td></tr>
                <tr><td>Richieste in attesa di invio</td><td><?php echo (int) (is_array($q) ? count($q) : 0); ?><?php if (is_array($q) && $q) : ?> · ultimo motivo: <?php echo esc_html((string) end($q)['why']); ?><?php endif; ?></td></tr>
                <tr><td>Form collegati automaticamente</td><td><?php
                    $on = array_keys(array_filter($integ));
                    echo $on ? esc_html(implode(', ', array_map(function ($k) use ($labels) { return $labels[$k]; }, $on))) : 'nessun plugin di form riconosciuto';
                    echo esc_html($s['capture'] ? ' · più form Brevo e form personalizzati catturati dalla pagina' : '');
                ?></td></tr>
                <tr><td>Analytics del sito</td><td><?php
                    if ($core) echo 'misurate da Influencee Core';
                    elseif (empty($s['measure'])) echo 'spenta';
                    elseif (!$s['key']) echo 'parte quando il sito è collegato a Tandem';
                    elseif (!apply_filters('tandem_forms_measure', true)) echo 'spenta dal filtro <code>tandem_forms_measure</code>';
                    else echo 'attiva · visite inviate a <code>' . esc_html(self::track_url()) . '</code>';
                ?></td></tr>
                <tr><td>Ultimo controllo aggiornamenti</td><td><?php
                    $up = get_option(self::UPDATE_LAST, []);
                    if (!is_array($up) || empty($up['t'])) {
                        echo 'non ancora fatto';
                    } else {
                        echo esc_html(wp_date('d/m/Y H:i', (int) $up['t'])) . ' · ';
                        if (!empty($up['version'])) {
                            $newer = version_compare((string) $up['version'], self::VERSION, '>');
                            echo 'versione disponibile: ' . esc_html($up['version']) . ($newer ? ' (installata ' . esc_html(self::VERSION) . ')' : ' (è quella installata)');
                        } else {
                            echo 'Tandem non ha risposto' . (!empty($up['error']) ? ' (' . esc_html($up['error']) . ')' : '');
                        }
                    }
                    echo ' · aggiornamenti automatici ' . (empty($s['autoupdate']) ? 'decisi da WordPress' : 'attivi');
                ?></td></tr>
                </tbody>
            </table>
            <p>
                <a class="button <?php echo $s['key'] ? '' : 'button-primary'; ?>" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=tandem_forms_connect'), 'tandem_forms_connect')); ?>"><?php echo $s['key'] ? 'Ricollega a Tandem' : 'Collega a Tandem'; ?></a>
                <?php if ($s['key']) : ?><a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=tandem_forms_test'), 'tandem_forms_test')); ?>">Prova il collegamento</a><?php endif; ?>
                <?php if (is_array($q) && $q) : ?><a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=tandem_forms_flush'), 'tandem_forms_flush')); ?>">Invia adesso la coda</a><?php endif; ?>
            </p>

            <form method="post" action="options.php">
                <?php settings_fields('tandem_forms'); ?>
                <h2>Collegamento</h2>
                <table class="form-table" role="presentation">
                    <tr><th scope="row"><label for="tf-endpoint">Indirizzo di Tandem</label></th>
                        <td><input id="tf-endpoint" class="regular-text code" name="<?php echo esc_attr(self::OPT); ?>[endpoint]" value="<?php echo esc_attr($s['endpoint']); ?>"></td></tr>
                    <tr><th scope="row"><label for="tf-key">Chiave del sito</label></th>
                        <td><input id="tf-key" class="regular-text code" name="<?php echo esc_attr(self::OPT); ?>[key]" value="" autocomplete="off" placeholder="<?php echo $s['key'] ? 'Lascia vuoto per tenere la chiave attuale' : 'tdm_…'; ?>">
                            <p class="description">Di solito non serve: «Collega a Tandem» la imposta da solo. Altrimenti in Tandem: Richieste → Siti collegati → «Nuova chiave». La chiave non viene più mostrata qui.</p>
                            <?php if ($s['key']) : ?><label><input type="checkbox" name="<?php echo esc_attr(self::OPT); ?>[remove_key]" value="1"> Rimuovi la chiave</label><?php endif; ?></td></tr>
                </table>

                <h2>Form senza plugin</h2>
                <table class="form-table" role="presentation">
                    <tr><th scope="row">Cattura dalla pagina</th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr(self::OPT); ?>[capture]" value="1" <?php checked(!empty($s['capture'])); ?>> Invia a Tandem anche i form Brevo inseriti nella pagina e i form indicati qui sotto (il form continua a funzionare come prima)</label></td></tr>
                    <tr><th scope="row"><label for="tf-sel">Altri form da catturare</label></th>
                        <td><textarea id="tf-sel" class="large-text code" rows="4" name="<?php echo esc_attr(self::OPT); ?>[selectors]"><?php echo esc_textarea($s['selectors']); ?></textarea>
                            <p class="description">Un selettore CSS per riga, per esempio <code>form.brief-form</code> o <code>#form-contatti</code>. Il controllo settimanale di Tandem segnala i form che mancano.</p></td></tr>
                    <tr><th scope="row"><label for="tf-relay">Form da gestire dal server</label></th>
                        <td><input id="tf-relay" class="regular-text code" name="<?php echo esc_attr(self::OPT); ?>[relay]" value="<?php echo esc_attr($s['relay']); ?>" placeholder="#brevo-form">
                            <p class="description">Per i form che oggi inviano i dati a Brevo direttamente dal browser (con la chiave Brevo visibile nella pagina): il plugin li prende in carico, li manda a Tandem e, se indichi la lista, aggiunge il contatto a Brevo dal server.</p></td></tr>
                    <tr><th scope="row"><label for="tf-relay-msg">Messaggio di conferma</label></th>
                        <td><input id="tf-relay-msg" class="large-text" name="<?php echo esc_attr(self::OPT); ?>[relay_message]" value="<?php echo esc_attr($s['relay_message']); ?>"></td></tr>
                    <tr><th scope="row"><label for="tf-list">Lista Brevo</label></th>
                        <td><input id="tf-list" type="number" min="0" class="small-text" name="<?php echo esc_attr(self::OPT); ?>[relay_list]" value="<?php echo (int) $s['relay_list']; ?>">
                            <input class="regular-text code" name="<?php echo esc_attr(self::OPT); ?>[brevo_key]" value="" autocomplete="off" placeholder="<?php echo $s['brevo_key'] ? 'Chiave Brevo impostata: lascia vuoto per tenerla' : 'Chiave API Brevo (facoltativa)'; ?>">
                            <p class="description">ID della lista Brevo (0 = non aggiungere a Brevo). Senza chiave il plugin usa quella dell'Academy, se c'è.</p></td></tr>
                    <tr><th scope="row"><label for="tf-int">Tipo di richiesta per form</label></th>
                        <td><textarea id="tf-int" class="large-text code" rows="3" name="<?php echo esc_attr(self::OPT); ?>[intents]" placeholder="consulenza creator = richiesta&#10;guida = iscrizione&#10;ambassador = candidatura"><?php echo esc_textarea($s['intents']); ?></textarea>
                            <p class="description">Facoltativo: parola nel nome del form o nell'indirizzo della pagina = richiesta, iscrizione o candidatura. Solo le richieste aprono un'opportunità in Tandem.</p></td></tr>
                </table>

                <h2>Analytics</h2>
                <table class="form-table" role="presentation">
                    <tr><th scope="row">Visite del sito</th>
                        <td><?php if ($core) : ?>
                            <p>Su questo sito le visite le misura già Influencee Core: Tandem Forms non aggiunge niente, così non ci sono doppi conteggi.</p>
                            <?php if (!empty($s['measure'])) : ?><input type="hidden" name="<?php echo esc_attr(self::OPT); ?>[measure]" value="1"><?php endif; ?>
                        <?php else : ?>
                            <label><input type="checkbox" name="<?php echo esc_attr(self::OPT); ?>[measure]" value="1" <?php checked(!empty($s['measure'])); ?>> Misura le visite (Analytics di Tandem, senza cookie)</label>
                            <p class="description">Niente cookie e nessun dato personale: Tandem riconosce la visita con un'impronta che cambia ogni giorno e non conserva l'indirizzo IP. Lo staff collegato non viene contato. I dati sono in Tandem › Marketing › Analytics. Va citato nell'informativa privacy.</p>
                            <p class="description">Chi ha attivo nel browser il segnale «non tracciare» (Global Privacy Control o Do Not Track) non viene contato.</p>
                        <?php endif; ?></td></tr>
                </table>

                <h2>Aggiornamenti</h2>
                <table class="form-table" role="presentation">
                    <tr><th scope="row">Nuove versioni</th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr(self::OPT); ?>[autoupdate]" value="1" <?php checked(!empty($s['autoupdate'])); ?>> Aggiornamenti automatici da Tandem</label>
                            <p class="description">WordPress installa da solo le nuove versioni del plugin pubblicate in Tandem (le controlla un paio di volte al giorno). Spenta: decide WordPress, e l'aggiornamento compare in Plugin come per gli altri.</p></td></tr>
                </table>
                <?php submit_button('Salva'); ?>
            </form>

            <h2>Storico</h2>
            <?php if (!$s['key']) : ?>
                <p>Imposta la chiave per inviare a Tandem anche le richieste arrivate prima dell'installazione.</p>
            <?php elseif (!array_filter($sources)) : ?>
                <p>Nessuna richiesta precedente salvata sul sito.</p>
            <?php else : ?>
                <p>Richieste salvate sul sito prima dell'installazione del plugin: <?php echo esc_html(implode(', ', array_map(function ($k, $n) use ($source_labels) { return $n . ' ' . $source_labels[$k]; }, array_keys($sources), $sources))); ?>. In Tandem quelle più vecchie di 30 giorni entrano già gestite e senza opportunità. Si può ripetere: le richieste già inviate non si duplicano.</p>
                <p><button type="button" class="button" id="tf-history">Invia lo storico a Tandem</button> <span id="tf-history-status"></span></p>
                <script>
                (function () {
                    var btn = document.getElementById('tf-history'), out = document.getElementById('tf-history-status');
                    var sources = <?php echo wp_json_encode(array_keys(array_filter($sources))); ?>, nonce = <?php echo wp_json_encode(wp_create_nonce('tandem_forms_history')); ?>;
                    btn.addEventListener('click', function () {
                        btn.disabled = true;
                        var i = 0, offset = 0, sent = 0, failed = 0;
                        function step() {
                            if (i >= sources.length) { out.textContent = 'Fatto: ' + sent + ' inviate' + (failed ? ', ' + failed + ' in coda per un nuovo tentativo' : '') + '.'; btn.disabled = false; return; }
                            var body = new URLSearchParams({ action: 'tandem_forms_history', _ajax_nonce: nonce, source: sources[i], offset: String(offset) });
                            fetch(ajaxurl, { method: 'POST', body: body, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
                                if (!j.success) throw new Error(j.data || 'errore');
                                sent += j.data.ok; failed += j.data.fail; offset += j.data.done;
                                out.textContent = 'Invio in corso… ' + sent + ' inviate';
                                if (!j.data.more) { i++; offset = 0; }
                                step();
                            }).catch(function (e) { out.textContent = 'Interrotto: ' + e.message + '. Puoi riprendere premendo di nuovo.'; btn.disabled = false; });
                        }
                        step();
                    });
                })();
                </script>
            <?php endif; ?>

            <h2>Ultimi invii</h2>
            <?php if (!$log) : ?>
                <p>Ancora nessun invio.</p>
            <?php else : ?>
                <table class="widefat striped" style="max-width:900px">
                    <thead><tr><th>Quando</th><th>Form</th><th>Chi</th><th>Esito</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($log, 0, 30) as $l) : ?>
                        <tr><td><?php echo esc_html(wp_date('d/m/Y H:i', (int) $l['t'])); ?></td><td><?php echo esc_html($l['form']); ?></td><td><?php echo esc_html($l['who']); ?></td><td><?php echo $l['ok'] ? '✓ ' : '✗ '; echo esc_html($l['msg']); ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
}

register_activation_hook(__FILE__, ['Tandem_Forms', 'activate']);
register_deactivation_hook(__FILE__, ['Tandem_Forms', 'deactivate']);
Tandem_Forms::boot();
