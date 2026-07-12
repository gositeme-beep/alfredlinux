<?php
// Landing for AI Servers — redirect to configurator or show intro
$configuratorUrl = '/ai-servers/configurator.php';
header('Location: ' . $configuratorUrl, true, 302);
exit;
