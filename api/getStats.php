<?php
require '../src/bootstrap.php';
use Illuminate\Database\Capsule\Manager as Capsule;

header('Content-Type: application/json');

$serverId = $_GET['server_id'] ?? 1;
$stats = Capsule::table('company_stats')->where('server_id', $serverId)->get();
echo json_encode($stats);