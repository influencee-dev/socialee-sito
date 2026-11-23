<?php
/**
 * Plugin Name:       Socialee Bussola
 * Description:       «Che imprenditore sei?» (quiz pubblico per imprenditori su /che-imprenditore-sei/, contatti in Brevo, guida trend del settore in regalo) e «La Bussola Interna — Team Edition» su socialee.it/bussola-team/, libero per chi ha il link (non è nel menu né su Google). Le risposte restano nel browser di chi compila: niente server, niente database, niente statistiche né pixel su queste pagine. La dashboard degli aggregati anonimi è solo per gli amministratori.
 * Version:           1.1.0
 * Author:            Influencee SRL (Socialee)
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Text Domain:       socialee-bussola
 */

defined( 'ABSPATH' ) || exit;

define( 'SCB_VERSION', '1.1.0' );
define( 'SCB_DIR', plugin_dir_path( __FILE__ ) );

/** Permessi: chi compila il quiz, chi apre la dashboard degli aggregati. */
const SCB_CAP       = 'socialee_bussola';
const SCB_CAP_ADMIN = 'socialee_bussola_admin';
const SCB_RUOLO     = 'socialee_team';
const SCB_RUOLI_V   = '1';


require_once SCB_DIR . 'includes/accesso.php';
require_once SCB_DIR . 'includes/pagine.php';
require_once SCB_DIR . 'includes/admin.php';
require_once SCB_DIR . 'includes/imprenditori.php';

register_activation_hook( __FILE__, 'scb_ruoli' );
