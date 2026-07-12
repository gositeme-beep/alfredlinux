<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$song = preg_replace("/[^0-9a-zA-Z\-]/", "", $_GET["song"] ?? "");
if (!$song) { echo json_encode(["error" => "no song"]); exit; }

$map = [
    "01" => "01-Shema-Yisrael", "02" => "01-Shema-Yisrael",
    "03" => "02-Most-High", "04" => "02-Most-High",
    "05" => "03-Heavens-Declare", "06" => "03-Heavens-Declare",
    "07" => "04-Light-Of-The-World", "08" => "04-Light-Of-The-World",
    "09" => "05-Seraphim", "10" => "05-Seraphim",
    "11" => "06-Full-Of-Mercy", "12" => "06-Full-Of-Mercy",
    "13" => "07-Redeemer", "14" => "07-Redeemer",
    "15" => "08-Beloved", "16" => "08-Beloved",
    "17" => "09-Shofar", "18" => "09-Shofar",
    "19" => "10-Truth-Of-The-LORD", "20" => "10-Truth-Of-The-LORD",
    "21" => "11-Yeshua", "22" => "11-Yeshua",
    "23" => "12-Your-Mercy", "24" => "12-Your-Mercy",
    "25" => "13-Zion", "26" => "13-Zion",
    "27" => "13-Zion",
];

$num = str_pad($song, 2, "0", STR_PAD_LEFT);
$key = $map[$num] ?? null;
if (!$key) { echo json_encode(["lyrics" => "", "found" => false]); exit; }

$path = __DIR__ . "/music/lyrics/{$key}.txt";
if (!file_exists($path)) { echo json_encode(["lyrics" => "", "found" => false]); exit; }

$raw = file_get_contents($path);
$lines = explode("\n", $raw);
$content = [];
$skip = true;
foreach ($lines as $line) {
    $t = trim($line);
    if ($skip) {
        // Start capturing at first Hebrew character
        if (preg_match("/[\x{0590}-\x{05FF}]/u", $t)) { $skip = false; $content[] = $line; }
        continue;
    }
    $content[] = $line;
}

echo json_encode(["lyrics" => implode("\n", $content), "found" => true, "song" => $key], JSON_UNESCAPED_UNICODE);
