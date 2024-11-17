<?php
require '../src/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

header('Content-Type: application/json');
try {   
    $servers = Capsule::table('servers')->get();
    echo json_encode($servers->toArray());
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
