<?php

require '../vendor/autoload.php';

$config = require __DIR__ . '/config.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Initialize Capsule
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'sqlite',
    'database'  => $config['database_path'],
    'prefix'    => '',
]); 
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Check if database exists
if (!file_exists($config['database_path'])) {
    echo "Database does not exist, creating...";
    // Create an empty database file
    touch($config['database_path']);
} 
Capsule::schema()->create('servers', function ($table) {
    $table->increments('id');
    $table->string('host');
    $table->integer('port');
    $table->string('server_name')->nullable();
    $table->timestamp('last_updated')->useCurrent();
});

Capsule::schema()->create('company_info', function ($table) {
    $table->increments('id');
    $table->integer('server_id');
    $table->integer('company_id');
    $table->string('company_name');
    $table->string('manager');
    $table->integer('color');
    $table->timestamp('timestamp')->useCurrent();
});

Capsule::schema()->create('company_stats', function ($table) {
    $table->increments('id');
    $table->integer('server_id');
    $table->integer('company_id');
    $table->integer('money');
    $table->integer('loan');
    $table->integer('income');
    $table->integer('value_lastq');
    $table->integer('value_prevq');
    $table->integer('perf_lastq');
    $table->integer('perf_prevq');
    $table->integer('deliver_lastq');
    $table->integer('deliver_prevq');
    $table->integer('trains_count');
    $table->integer('lorries_count');
    $table->integer('busses_count');
    $table->integer('planes_count');
    $table->integer('ships_count');
    $table->integer('train_stations_count');
    $table->integer('lorry_stations_count');
    $table->integer('bus_stops_count');
    $table->integer('airports_count');
    $table->integer('harbours_count');
    $table->timestamp('timestamp')->useCurrent();
});
