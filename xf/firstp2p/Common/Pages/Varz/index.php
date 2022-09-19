<?php
header("Content-type:application/json");

include __DIR__ . "/../../Extensions/Varz/VarzAdapter.php";
include __DIR__ . "/../../Extensions/Cache/LocalCache.php";
include __DIR__ . "/../../Library/OsLib.php";

use NCFGroup\Common\Extensions\Varz\VarzAdapter;
if (array_key_exists('op', $_GET) && $_GET['op'] == 'flush') {
    $s = new VarzAdapter();
    $s->flush();
}

$s = new VarzAdapter();
$s->output();
