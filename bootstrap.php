<?php

require 'vendor/autoload.php';
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
