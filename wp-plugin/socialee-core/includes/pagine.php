<?php
/**
 * Motore delle pagine: un plugin (Socialee Pagine) registra una cartella con
 *   pages/meta.json        percorso → slug (e dati di controllo)
 *   pages/<slug>.html      contenuto della pagina (HTML5, niente Elementor)
 *   shell/header.html      intestazione del sito
 *   shell/footer.html      piè di pagina del sito
 *   assets/sito.css        CSS del sito
 * e il Core serve quel contenuto allo stesso URL della pagina WordPress, con il suo template.
 * Title, description, canonical e dati strutturati restano di Rank Math (stessa pagina WordPress, stessi dati).
 */

defined( 'ABSPATH' ) || exit;

/** Cartella del contenuto: la prima registrata con il filtro scl_page_sources. */
function scl_source() {
	$s = (array) apply_filters( 'scl_page_sources', array() );
	foreach ( $s as $src ) {
		if ( ! empty( $src['dir'] ) && is_dir( $src['dir'] ) ) {
			return array( 'dir' => trailingslashit( $src['dir'] ), 'url' => trailingslashit( $src['url'] ), 'version' => isset( $src['version'] ) ? $src['version'] : SCL_VERSION );
		}
	}
	return null;
}

function scl_pages() {
	static $pages = null;
	if ( null !== $pages ) {
		return $pages;
	}
	$pages = array();
	$src   = scl_source();
	if ( $src && is_readable( $src['dir'] . 'pages/meta.json' ) ) {
		$m     = json_decode( (string) file_get_contents( $src['dir'] . 'pages/meta.json' ), true );
		$pages = is_array( $m ) ? $m : array();
	}
	return $pages;
}

function scl_request_path() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( $home && '/' !== $home && 0 === strpos( $path, $home ) ) {
		$path = '/' . substr( $path, strlen( $home ) );
	}
	return '/' . trim( $path, '/' ) . ( '/' === $path || '' === trim( $path, '/' ) ? '' : '/' );
}

/** La pagina del plugin per questa richiesta, se accesa. */
function scl_current_page() {
	static $cur = false;
	if ( false !== $cur ) {
		return $cur;
	}
	$cur = null;
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() ) {
		return $cur;
	}
	$s = scl_settings();
	if ( empty( $s['pagine'] ) ) {
		return $cur;
	}
	// confronto con la pagina com'era: ?socialee_originale=1 (solo per chi può modificare)
	if ( isset( $_GET['socialee_originale'] ) && current_user_can( 'edit_pages' ) ) {
		return $cur;
	}
	$path  = scl_request_path();
	$pages = scl_pages();
	if ( isset( $pages[ $path ] ) && ! in_array( $path, (array) $s['off'], true ) ) {
		$cur = array_merge( $pages[ $path ], array( 'path' => $path ) );
	}
	return $cur;
}

/** HTML del contenuto della pagina. */
function scl_page_html( $page ) {
	$src  = scl_source();
	$file = $src ? $src['dir'] . 'pages/' . basename( (string) $page['slug'] ) . '.html' : '';
	return ( $file && is_readable( $file ) ) ? (string) file_get_contents( $file ) : '';
}

/** Le nostre pagine non sono mai 404, anche se la pagina WordPress manca. */
add_filter(
	'pre_handle_404',
	function ( $pre ) {
		if ( scl_current_page() ) {
			status_header( 200 );
			return true;
		}
		return $pre;
	}
);

add_filter(
	'template_include',
	function ( $tpl ) {
		return scl_current_page() ? SCL_DIR . 'templates/pagina.php' : $tpl;
	},
	999
);

add_filter(
	'body_class',
	function ( $c ) {
		if ( scl_shell_qui() ) {
			$c[] = 'socialee-kit'; // al posto di elementor-kit-1665 (stili globali del kit, in sito.css)
		}
		if ( scl_current_page() ) {
			$c[] = 'socialee-sito';
			$c   = array_values( array_filter( $c, function ( $x ) { return false === strpos( $x, 'elementor' ); } ) );
		}
		return $c;
	}
);

/** Sulle nostre pagine niente CSS e JS di Elementor (il CSS del tema Hello resta: il tema rimane attivo). */
function scl_is_elementor_handle( $h ) {
	return (bool) preg_match( '/^(elementor|e-|swiper|font-awesome|elementor-icons|google-fonts-|pro-elements)/', (string) $h );
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! scl_current_page() && ! scl_shell_attiva() ) {
			return;
		}
		$src = scl_source();
		if ( $src && is_readable( $src['dir'] . 'assets/sito.css' ) ) {
			wp_enqueue_style( 'socialee-sito', $src['url'] . 'assets/sito.css', array(), $src['version'] );
		}
		if ( ! scl_current_page() ) {
			return;
		}
		foreach ( array( wp_styles(), wp_scripts() ) as $reg ) {
			foreach ( (array) $reg->queue as $h ) {
				if ( scl_is_elementor_handle( $h ) ) {
					$reg === wp_styles() ? wp_dequeue_style( $h ) : wp_dequeue_script( $h );
				}
			}
		}
	},
	9999
);
