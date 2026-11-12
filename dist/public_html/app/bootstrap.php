<?php
/**
 * Démarrage commun à toutes les pages : configuration, contenu, fonctions utilitaires.
 * Vous n'avez normalement pas besoin de modifier ce fichier.
 */
declare(strict_types=1);

define('ROOT', dirname(__DIR__));          // = public_html
define('APP', __DIR__);                    // = public_html/app

require APP . '/vendor/autoload.php';
foreach (['Config', 'Content', 'Markdown', 'Media', 'Gallery', 'Donations', 'Epc', 'Payments', 'Smtp', 'Contact'] as $lib) {
    require_once APP . '/lib/' . $lib . '.php';
}
require_once APP . '/lib/helpers.php';

Config::load();

if (Config::get('debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}
date_default_timezone_set('Europe/Brussels');
mb_internal_encoding('UTF-8');
