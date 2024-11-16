<?php
require '../src/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

header('Content-Type: application/json');

$servers = Capsule::table('servers')->get();
echo json_encode($servers->toArray());
