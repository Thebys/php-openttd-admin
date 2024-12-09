<?php
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} else if (file_exists('../vendor/autoload.php')) {
    require '../vendor/autoload.php';
} else {
    echo "Autoload file not found. Please run 'composer install'.";
    exit;
}

$config = require 'config.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Initialize Eloquent ORM
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'sqlite',
    'database'  => $config['database_path'],
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();
