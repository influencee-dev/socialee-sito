<?php
/**
 * Guscio comune delle pagine della Bussola: stile Socialee (nero, bianco, fucsia; Archivo Black e Inter come il sito),
 * intestazione con il logo e «Esci». Contrasti: testo fucsia su bianco #B8005F (6,6:1), bottoni #D60074 con testo
 * bianco (5,1:1); il fucsia del brand #E6007E resta per bordi, barre e decorazioni.
 */

defined( 'ABSPATH' ) || exit;

function scb_css() {
	return <<<'CSS'
:root{--nero:#000;--ink:#0a0a0a;--bianco:#fff;--off:#f5f5f5;--linea:#e3e3e3;--sub:#545454;--sub-scuro:#b5b5b5;--fucsia:#E6007E;--fucsia-cta:#D60074;--fucsia-testo:#B8005F;--fucsia-tenue:#fff0f8;--display:'Archivo Black','Arial Black',Impact,sans-serif;--corpo:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif}
*{box-sizing:border-box}
html{-webkit-text-size-adjust:100%}
body{margin:0;background:var(--nero);color:var(--ink);font-family:var(--corpo);font-size:16px;line-height:1.55;-webkit-font-smoothing:antialiased}
button,input{font:inherit;color:inherit}
button{cursor:pointer}
a{color:var(--fucsia-testo)}
:focus-visible{outline:3px solid var(--fucsia);outline-offset:3px}
.salta{position:absolute;left:-9999px;top:8px;background:#fff;color:#000;padding:10px 14px;border-radius:8px;z-index:9}
.salta:focus{left:16px}
.b-top{max-width:1000px;margin:0 auto;padding:18px 16px;display:flex;align-items:center;justify-content:space-between;gap:14px;color:#fff}
.b-marchio{display:flex;align-items:center;gap:14px;min-width:0}
.b-logo{display:block;flex:none}
.b-logo img{display:block;height:28px;width:auto}
.b-titolo{font-family:var(--display);text-transform:uppercase;font-size:13px;letter-spacing:.04em;line-height:1.15;border-left:2px solid var(--fucsia);padding-left:12px}
.b-titolo small{display:block;font-family:var(--corpo);text-transform:none;letter-spacing:0;font-size:12px;color:var(--sub-scuro);font-weight:500}
.b-nav{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.b-nav a{color:#fff;text-decoration:none;font-size:13px;font-weight:600;border:1px solid #3a3a3a;border-radius:999px;padding:8px 14px;min-height:40px;display:inline-flex;align-items:center}
.b-nav a:hover{border-color:var(--fucsia)}
.layout{max-width:1000px;margin:0 auto;padding:8px 16px 56px}
.card{background:#fff;border-radius:22px;padding:clamp(22px,4.5vw,44px);margin-bottom:18px}
.eyebrow{font-size:12px;letter-spacing:.14em;text-transform:uppercase;font-weight:700;color:var(--fucsia-testo)}
h1,h2,h3{margin:0 0 14px;line-height:1.05;letter-spacing:-.01em}
h1,h2{font-family:var(--display);text-transform:uppercase;font-weight:400}
h1{font-size:clamp(30px,6vw,58px)}
h2{font-size:clamp(22px,3.6vw,32px)}
h3{font-size:18px;font-weight:800}
h1 em,h2 em{font-style:normal;color:var(--fucsia)}
p{margin:0 0 16px}
.lead{font-size:clamp(16px,2vw,19px);color:#333;max-width:700px}
.muted{color:var(--sub)}
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:24px 0}
.stat{background:var(--nero);color:#fff;border-radius:16px;padding:16px}
.stat strong{font-family:var(--display);font-weight:400;font-size:26px;display:block;color:var(--fucsia)}
.stat span{font-size:13px;color:#d6d6d6}
.notice{border-radius:14px;padding:14px 16px;background:var(--fucsia-tenue);color:#5c0030;font-size:14px;margin:22px 0;border-left:4px solid var(--fucsia)}
.check{display:flex;gap:12px;align-items:flex-start;font-size:15px;margin-top:18px}
.check input{width:22px;height:22px;accent-color:var(--fucsia-cta);flex:none;margin-top:1px}
.btn{background:var(--fucsia-cta);border:2px solid var(--fucsia-cta);border-radius:999px;padding:12px 22px;min-height:48px;color:#fff;font-weight:700;display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;text-align:center}
.btn:hover{background:#b8005f;border-color:#b8005f}
.btn:disabled{opacity:.45;cursor:not-allowed}
.btn.ghost{background:#fff;color:var(--ink);border-color:var(--ink)}
.btn.ghost:hover{background:var(--off)}
.btn.secondary{background:var(--ink);border-color:var(--ink);color:#fff}
.btn.secondary:hover{background:#2a2a2a}
.btn.danger{color:#a3002f;background:#fff;border-color:#e0a3b4}
.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:24px}
.field{width:100%;max-width:480px;border:2px solid #cfcfcf;border-radius:12px;padding:12px 14px;min-height:48px;background:#fff;color:var(--ink)}
.field:focus{border-color:var(--fucsia);outline:none;box-shadow:0 0 0 3px rgba(230,0,126,.25)}
.spazio{height:8px}
.hidden{display:none!important}
.privacy{font-size:13px;color:var(--sub)}
.b-foot{max-width:1000px;margin:0 auto;padding:0 16px 40px;color:var(--sub-scuro);font-size:12px;text-align:center}
.b-foot a{color:#fff}
@media(max-width:650px){.b-titolo{font-size:11px}.b-logo img{height:22px}.grid{grid-template-columns:1fr}.card{border-radius:18px}.b-nav a{padding:6px 12px}}
@media print{body{background:#fff}.b-top,.b-foot,.salta{display:none!important}.layout{padding:0;max-width:100%}.card{padding:0;border-radius:0}}
CSS;
}

/** Apre il documento. $extra_css = stile della singola pagina. */
function scb_apri( $titolo, $extra_css = '', $nav = true, $opz = array() ) {
	$n   = esc_attr( scb_nonce() );
	$opz = wp_parse_args(
		$opz,
		array( 'etichetta' => 'La Bussola Interna', 'sotto' => 'Edizione Team', 'robots' => 'noindex,nofollow,noarchive,nosnippet', 'referrer' => 'no-referrer', 'descrizione' => '', 'canonical' => '', 'piede' => 'Socialee · Influencee SRL · La Bussola Interna è un prototipo per percorsi formativi volontari, non adatto a decisioni sul personale.' )
	);
	$GLOBALS['scb_piede'] = $opz['piede'];
	?><!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="<?php echo esc_attr( $opz['robots'] ); ?>">
<meta name="referrer" content="<?php echo esc_attr( $opz['referrer'] ); ?>">
<?php if ( $opz['descrizione'] ) : ?>
<meta name="description" content="<?php echo esc_attr( $opz['descrizione'] ); ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?php echo esc_attr( $titolo ); ?>">
<meta property="og:description" content="<?php echo esc_attr( $opz['descrizione'] ); ?>">
<meta property="og:locale" content="it_IT">
<meta property="og:site_name" content="Socialee">
<?php endif; ?>
<?php if ( $opz['canonical'] ) : ?>
<link rel="canonical" href="<?php echo esc_url( $opz['canonical'] ); ?>">
<meta property="og:url" content="<?php echo esc_url( $opz['canonical'] ); ?>">
<?php endif; ?>
<meta name="color-scheme" content="light">
<title><?php echo esc_html( false !== strpos( $titolo, 'Socialee' ) ? $titolo : $titolo . ' · Socialee' ); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" nonce="<?php echo $n; // phpcs:ignore WordPress.Security.EscapeOutput ?>" href="https://fonts.googleapis.com/css2?family=Archivo+Black&amp;family=Inter:wght@400;500;600;700;800&amp;display=swap">
<style nonce="<?php echo $n; // phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo scb_css() . $extra_css; // phpcs:ignore WordPress.Security.EscapeOutput -- CSS nostro ?></style>
</head>
<body>
<a class="salta" href="#contenuto">Vai al contenuto</a>
<header class="b-top">
	<div class="b-marchio">
		<a class="b-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Socialee — home"><img src="https://socialee.it/wp-content/uploads/2026/09/socialee-logo.webp" alt="Socialee" width="1380" height="240"></a>
		<div class="b-titolo"><?php echo esc_html( $opz['etichetta'] ); ?><small><?php echo esc_html( $opz['sotto'] ); ?></small></div>
	</div>
	<?php if ( $nav && is_user_logged_in() ) : ?>
	<nav class="b-nav" aria-label="Bussola">
		<?php if ( scb_puo_vedere_aggregati() ) : ?>
			<?php if ( 'dashboard' === scb_quale_pagina() ) : ?>
				<a href="<?php echo esc_url( home_url( SCB_BASE ) ); ?>">Quiz</a>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( SCB_BASE . 'dashboard/' ) ); ?>">Dashboard</a>
			<?php endif; ?>
		<?php endif; ?>
		<a href="<?php echo esc_url( wp_logout_url( home_url( SCB_BASE ) ) ); ?>">Esci</a>
	</nav>
	<?php endif; ?>
</header>
<main class="layout" id="contenuto">
	<?php
}

/**
 * Lo script esce solo in ASCII: ogni carattere non ASCII diventa \uXXXX. Sul sito vero il plugin dei cookie (iubenda)
 * rilegge tutta la pagina e scrive «à» come &#224; anche dentro gli script, dove il browser non lo ritraduce.
 */
function scb_js_ascii( $js ) {
	return preg_replace_callback(
		'/[^\x00-\x7F]/u',
		function ( $m ) {
			$c = mb_ord( $m[0], 'UTF-8' );
			if ( $c > 0xFFFF ) {
				$c -= 0x10000;
				return sprintf( '\\u%04x\\u%04x', 0xD800 + ( $c >> 10 ), 0xDC00 + ( $c & 0x3FF ) );
			}
			return sprintf( '\\u%04x', $c );
		},
		(string) $js
	);
}

/** Chiude il documento. $js = script della pagina (con il nonce della CSP). */
function scb_chiudi( $js = '' ) {
	$n  = esc_attr( scb_nonce() );
	$js = scb_js_ascii( $js );
	?>
</main>
<footer class="b-foot"><?php echo esc_html( isset( $GLOBALS['scb_piede'] ) ? $GLOBALS['scb_piede'] : 'Socialee · Influencee SRL' ); ?></footer>
	<?php if ( $js ) : ?>
<script nonce="<?php echo $n; // phpcs:ignore WordPress.Security.EscapeOutput ?>">
<?php echo $js; // phpcs:ignore WordPress.Security.EscapeOutput -- script nostro ?>
</script>
	<?php endif; ?>
</body>
</html>
	<?php
}
