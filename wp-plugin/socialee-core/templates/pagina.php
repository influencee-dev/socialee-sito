<?php
/**
 * Template delle pagine Socialee: HTML5 pulito, niente Elementor.
 * wp_head/wp_footer restano: Rank Math (SEO), iubenda (cookie), pixel Meta, tandem-forms, Brevo.
 * Intestazione e piè di pagina li stampa shell.php (wp_body_open e wp_footer).
 */
defined( 'ABSPATH' ) || exit;
$scl_page = scl_current_page();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<main id="main-content" class="socialee-main">
<?php echo scl_page_html( $scl_page ); // phpcs:ignore WordPress.Security.EscapeOutput -- HTML del sito, dal plugin Socialee Pagine ?>
</main>
<?php wp_footer(); ?>
</body>
</html>
