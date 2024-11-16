<?php

use Illuminate\Database\Capsule\Manager as Capsule;

require 'bootstrap.php';

$servers = Capsule::table('servers')->get();
$companies = Capsule::table('company_info')->get();
$stats = Capsule::table('company_stats')->get();

echo "<br/>Servers:<br/>";
foreach ($servers as $server) {
    echo "ID: {$server->id}, Name: {$server->server_name}, Host: {$server->host}, Port: {$server->port}, Last Updated: {$server->last_updated}<br/>";
}

echo "<br/><br/>Company Info:<br/>";
foreach ($companies as $company) {
    echo "Server ID: {$company->server_id}, Company ID: {$company->company_id}, Name: {$company->company_name}, Manager: {$company->manager}<br/>";
}

echo "<br/><br/>Company Stats:<br/>";
foreach ($stats as $stat) {
    // debug all stats
    var_dump($stat);
}
