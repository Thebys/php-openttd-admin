<?php

require '../vendor/autoload.php';
require 'OttdAdmin.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$config = require __DIR__ . '/config.php';

// Initialize Capsule
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'sqlite',
    'database'  => $config['database_path'],
    'prefix'    => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Fetch servers data
$servers = Capsule::table('servers')->get();
$gameStats = Capsule::table('game_stats')->orderBy('timestamp', 'desc')->limit(10)->get();

echo "Servers:<br/>";
foreach ($servers as $server) {
    echo "ID: {$server->id}, Name: {$server->name}, Host: {$server->host}, Port: {$server->port}, Last Updated: {$server->last_updated}<br/>";
}

echo "<br/><br/>Latest Game Stats:<br/>";
foreach ($gameStats as $stat) {
    echo "Server ID: {$stat->server_id}, Date: {$stat->date}, Year: {$stat->year}, Month: {$stat->month}, Players: {$stat->num_players}, Companies: {$stat->num_companies}, Timestamp: {$stat->timestamp}<br/>";
}
