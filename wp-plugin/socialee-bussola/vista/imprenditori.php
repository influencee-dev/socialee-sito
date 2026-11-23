<?php
/**
 * «Che tipo di imprenditore sei?»: è il quiz della Bussola (stesse 77 affermazioni, stessi punteggi, stesso report),
 * con l'introduzione per imprenditori, il modulo alla fine e la guida del settore. Tutto in vista/quiz.php.
 */
defined( 'ABSPATH' ) || exit;

$GLOBALS['scb_imprenditori'] = true;
require SCB_DIR . 'vista/quiz.php';
