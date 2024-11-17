<?php
require '../src/bootstrap.php';
use Illuminate\Database\Capsule\Manager as Capsule;

header('Content-Type: application/json');
try {
    $serverId = $_GET['server_id'] ?? 1;
    $companies = Capsule::table('company_info')->where('server_id', $serverId)->get();
    echo json_encode($companies->toArray());
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
