<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use xPaw\SourceQuery\SourceQuery;

// -------- config from env --------
$timeout = (int)($_ENV['GMOD_TIMEOUT'] ?? 3);
$secret  = $_ENV['GMOD_SECRET'] ?? '';              // optional shared token
$allow   = array_filter(array_map('trim', explode(',', $_ENV['GMOD_ALLOWED_HOSTS'] ?? ''))); // e.g. "91.229.114.95, mc.playprismatic.net"

// -------- input --------
$host  = $_GET['host'] ?? '91.229.114.95';
$port  = (int)($_GET['port'] ?? 27015);
$token = $_GET['token'] ?? '';

// optional auth
if ($secret !== '' && !hash_equals($secret, $token)) {
  http_response_code(403);
  header('Content-Type: application/json');
  echo json_encode(['ok'=>false,'error'=>'forbidden']);
  exit;
}
// optional host allowlist
if ($allow && !in_array($host, $allow, true)) {
  http_response_code(400);
  header('Content-Type: application/json');
  echo json_encode(['ok'=>false,'error'=>'host_not_allowed']);
  exit;
}

$q = new SourceQuery();
header('Content-Type: application/json');
try {
  $q->Connect($host, $port, $timeout, SourceQuery::SOURCE);
  $info = $q->GetInfo() ?? [];
  // short cache hint (your Laravel will also cache)
  header('Cache-Control: public, max-age=10');
  echo json_encode([
    'ok'          => true,
    'online'      => true,
    'name'        => $info['HostName']   ?? null,
    'map'         => $info['Map']        ?? null,
    'players'     => $info['Players']    ?? null,
    'max_players' => $info['MaxPlayers'] ?? null,
  ]);
} catch (Throwable $e) {
  http_response_code(502);
  header('Cache-Control: no-store');
  echo json_encode(['ok'=>false,'online'=>false,'error'=>'unreachable']);
} finally {
  $q->Disconnect();
}
