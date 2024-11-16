<?php

require 'vendor/autoload.php';

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
    $table->string('ip');
    $table->integer('admin_port');
    $table->string('name');
    $table->timestamp('last_updated')->useCurrent();
});

Capsule::schema()->create('game_stats', function ($table) {
    $table->increments('id');
    $table->integer('server_id')->unsigned();
    $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
    $table->integer('date');
    $table->integer('year');
    $table->integer('month');
    $table->integer('num_players')->default(0);
    $table->integer('num_companies')->default(0);
    $table->timestamp('timestamp')->useCurrent();
});

