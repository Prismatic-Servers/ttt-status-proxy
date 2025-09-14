<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use xPaw\MinecraftQuery;
use xPaw\MinecraftQueryException;
use xPaw\MinecraftPing;
use xPaw\MinecraftPingException;

header('Content-Type: application/json');

// ---- config ----
$timeout = (int)($_ENV['MC_TIMEOUT'] ?? 3);
$secret  = $_ENV['MC_SECRET'] ?? '';
$specRaw = $_ENV['MC_SERVERS'] ?? '[]';
$spec    = json_decode($specRaw, true) ?: [];

// optional auth
$token = $_GET['token'] ?? '';
if ($secret !== '' && !hash_equals($secret, $token)) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'error'=>'forbidden']); exit;
}

$result = [
  'ok' => true,
  'servers' => [],
];

foreach ($spec as $s) {
  $label  = (string)($s['label'] ?? ($s['host'] ?? 'server'));
  $host   = (string)($s['host']  ?? '');
  $port   = (int)   ($s['port']  ?? 25565);
  $method = strtolower((string)($s['method'] ?? 'query')); // 'query' or 'ping'
  $bedrock= (bool)  ($s['bedrock'] ?? false);

  $entry = [
    'label'       => $label,
    'host'        => $host,
    'port'        => $port,
    'method'      => $method,
    'online'      => false,
    'players'     => null,
    'max_players' => null,
    'name'        => null,
    'error'       => null,
  ];

  try {
    if ($method === 'query') {
      $q = new MinecraftQuery();
      $bedrock ? $q->ConnectBedrock($host, $port, $timeout)
               : $q->Connect($host, $port, $timeout);
      $info = $q->GetInfo() ?? [];
      $entry['online']      = true;
      $entry['players']     = $info['Players']    ?? null;
      $entry['max_players'] = $info['MaxPlayers'] ?? null;
      $entry['name']        = $info['HostName']   ?? null;
    } else {
      $p = new MinecraftPing($host, $port, $timeout);
      $data = $p->Query(); // for <1.7: QueryOldPre17()
      $entry['online']      = true;
      $entry['players']     = $data['players']['online'] ?? null;
      $entry['max_players'] = $data['players']['max']    ?? null;
      $entry['name']        = $data['description']['text'] ?? ($data['description'] ?? null);
    }
  } catch (MinecraftQueryException|MinecraftPingException $e) {
    $entry['error'] = 'unreachable';
  }

  $result['servers'][] = $entry;
}

// modest caching for the whole payload
header('Cache-Control: public, max-age=10');
echo json_encode($result);
