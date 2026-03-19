<?php
/**
 * @author mail[at]doerr-softwaredevelopment[dot]com Joachim Doerr
 * @package redaxo5
 * @license MIT
 */

include __DIR__ . '/install.php';

// Datapane-Struktur zum Backend hinzufügen / Forcal Multiuser aktivieren
if (rex_addon::get('forcal')->hasConfig()) {
    $config = rex_addon::get('forcal')->getConfig();
    if (!isset($config['forcal_multiuser'])) {
        $config['forcal_multiuser'] = 1;
        rex_addon::get('forcal')->setConfig($config);
    }
}

// Neue Rechte für Benutzer registrieren
if (rex::isBackend() && rex::getUser()) {
    rex_perm::register('forcal[userpermissions]', null, rex_perm::OPTIONS);
}
