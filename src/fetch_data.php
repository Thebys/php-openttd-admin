<?php

use Illuminate\Database\Capsule\Manager as Capsule;

require 'bootstrap.php';

$config = require __DIR__ . '/config.php';
foreach ($config['servers'] as $server) {
    // Ensure the server array has an 'id' key
    if (!isset($server['id'])) {
        echo "Server ID is missing for host: {$server['host']}\n";
        continue; // Skip this server if 'id' is not set
    }

    $admin = new Thebys\PhpOpenttdStats\OttdAdmin($server['host'], $server['port'], $server['password']);

    if (!$admin->connect()) {
        echo "Failed to connect to the server.\n";
        exit;
    }

    $serverInfo = $admin->join();
    $serverName = $serverInfo['SERVER_NAME'] ?? 'Unknown';

    // Update server information
    Capsule::table('servers')->updateOrInsert(
        ['host' => $server['host'], 'port' => $server['port']],
        ['server_name' => $serverName, 'last_updated' => date('Y-m-d H:i:s')]
    );

    // Fetch company info
    $companyInfo = $admin->getCompanyInfo();
    foreach ($companyInfo as $company) {
        Capsule::table('company_info')->updateOrInsert(
            ['server_id' => $server['id'], 'company_id' => $company['COMPANY_ID']],
            [
                'company_name' => $company['COMPANY_NAME'] ?? 'Unknown',
                'manager' => $company['MANAGER'] ?? 'Unknown',
                'color' => $company['COLOR'] ?? '15',
                'timestamp' => date('Y-m-d H:i:s')
            ]
        );
    }

    foreach ($companyInfo as $company) {
        // Fetch company stats and economy and insert new row.
        $companyEconomy = $admin->getCompanyEconomy($company['COMPANY_ID']);
        $companyStats = $admin->getCompanyStats($company['COMPANY_ID']);
        Capsule::table('company_stats')->insert(
            [
                'server_id' => $server['id'],
                'company_id' => $company['COMPANY_ID'],
                'money' => $companyEconomy['MONEY'] ?? 0,
                'loan' => $companyEconomy['LOAN'] ?? 0,
                'income' => $companyEconomy['INCOME'] ?? 0,
                'value_lastq' => $companyEconomy['VALUE_LASTQ'] ?? 0,
                'value_prevq' => $companyEconomy['VALUE_PREVQ'] ?? 0,
                'perf_lastq' => $companyEconomy['PERF_LASTQ'] ?? 0,
                'perf_prevq' => $companyEconomy['PERF_PREVQ'] ?? 0,
                'deliver_lastq' => $companyEconomy['DELIVER_LASTQ'] ?? 0,
                'deliver_prevq' => $companyEconomy['DELIVER_PREVQ'] ?? 0,
                'trains_count' => $companyStats['TRAINS_COUNT'] ?? 0,
                'lorries_count' => $companyStats['LORRIES_COUNT'] ?? 0,
                'busses_count' => $companyStats['BUSSES_COUNT'] ?? 0,
                'planes_count' => $companyStats['PLANES_COUNT'] ?? 0,
                'ships_count' => $companyStats['SHIPS_COUNT'] ?? 0,
                'train_stations_count' => $companyStats['TRAIN_STATIONS_COUNT'] ?? 0,
                'lorry_stations_count' => $companyStats['LORRY_STATIONS_COUNT'] ?? 0,
                'bus_stops_count' => $companyStats['BUS_STOPS_COUNT'] ?? 0,
                'airports_count' => $companyStats['AIRPORTS_COUNT'] ?? 0,
                'harbours_count' => $companyStats['HARBOURS_COUNT'] ?? 0,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        );
    }

    echo "Data fetching completed successfully for {$serverName}!\n";
}
