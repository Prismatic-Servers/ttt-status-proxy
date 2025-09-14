<?php

require 'src/MinecraftQuery.php';
require 'src/MinecraftQueryException.php';

use xPaw\MinecraftQuery;
use xPaw\MinecraftQueryException;

$q = new MinecraftQuery();

try {
    $q->Connect('mc.playprismatic.net', 42069, 2);
    $info = $q->GetInfo();

    $online = $info['Players'] ?? 0;
    $max    = $info['MaxPlayers'] ?? 0;

    echo "Players: $online / $max";
} catch (MinecraftQueryException $e) {
    echo 'Query failed: ' . $e->getMessage();
}

