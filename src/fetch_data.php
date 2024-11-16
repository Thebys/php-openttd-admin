<?php

require 'vendor/autoload.php';
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

// Helper function to log data into the database
function logGameStats($serverId, $date, $year, $month, $numPlayers, $numCompanies)
{
    Capsule::table('game_stats')->insert([
        'server_id' => $serverId,
        'date' => $date,
        'year' => $year,
        'month' => $month,
        'num_players' => $numPlayers,
        'num_companies' => $numCompanies,
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
}

// Iterate over each server and fetch data
foreach ($config['servers'] as $server) {
    echo "Connecting to server: ({$server['host']}:{$server['port']})\n";

    $admin = new Thebys\PhpOpenttdStats\OttdAdmin($server['host'], $server['port'], $server['password']);

    if (!$admin->connect()) {
        echo "Failed to connect to {$server['name']}\n";
        continue;
    }

    $admin->join();

    // Fetch game date
    $currentDate = $admin->getDate();
    $startYear = $admin->getServerInfo()['START_YEAR'] ?? 0;
    $currentYear = intval($startYear + ($currentDate - 727000) / 365.25);
    $currentMonth = intval((intval($currentDate) - 727000) / 30.4375);

    // Fetch clients (players)
    $clients = $admin->getClientInfo();
    $numPlayers = count($clients);

    // Fetch companies
    $companies = $admin->getCompanyInfo();
    $numCompanies = count($companies);

    // Get or create server entry in the database
    $serverRecord = Capsule::table('servers')->where('host', $server['host'])->where('port', $server['port'])->first();

    if (!$serverRecord) {
        $serverId = Capsule::table('servers')->insertGetId([
            'host' => $server['host'],
            'port' => $server['port'],
            'name' => $server['host'] . ':' . $server['port'],
            'last_updated' => date('Y-m-d H:i:s'),
        ]);
    } else {
        $serverId = $serverRecord->id;
        Capsule::table('servers')->where('id', $serverId)->update(['last_updated' => date('Y-m-d H:i:s')]);
    }

    // Log the game stats
    logGameStats($serverId, $currentDate, $currentYear, $currentMonth, $numPlayers, $numCompanies);

    echo "Data logged for server: {$server['host']}:{$server['port']} (Players: $numPlayers, Companies: $numCompanies, Year: $currentYear, Month: $currentMonth)\n";
}
