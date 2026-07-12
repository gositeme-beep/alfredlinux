<?php
$paths = [
    '/usr/local/bin/composer',
    '/usr/bin/composer',
    '/usr/share/php/tcpdf/tcpdf.php',
    '/usr/local/lib/php/tcpdf/tcpdf.php',
    '/home/gositeme/public_html/vendor/tecnickcom/tcpdf/tcpdf.php',
    '/home/gositeme/vendor/tecnickcom/tcpdf/tcpdf.php',
    '/home/gositeme/public_html/vendor/mpdf/mpdf/src/Mpdf.php',
    '/home/gositeme/vendor/mpdf/mpdf/src/Mpdf.php',
    '/usr/local/bin/wkhtmltopdf',
    '/usr/bin/wkhtmltopdf',
    '/usr/bin/gs',
    '/usr/local/bin/gs',
];
foreach ($paths as $p) {
    echo $p . ': ' . (file_exists($p) ? '✅ EXISTS' : '❌ not found') . "\n";
}
?>
