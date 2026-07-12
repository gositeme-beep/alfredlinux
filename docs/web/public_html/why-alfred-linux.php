<?php
$validLangs = ['en','fr','he'];
$lang = (isset($_GET['lang']) && in_array($_GET['lang'], $validLangs, true)) ? $_GET['lang'] : 'en';

if ($lang === 'fr') {
    require __DIR__ . '/includes/why-fr.inc.php';
} elseif ($lang === 'he') {
    require __DIR__ . '/includes/why-he.inc.php';
} else {
    require __DIR__ . '/includes/why-en.inc.php';
}
