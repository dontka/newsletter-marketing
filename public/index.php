<?php
session_start();

require __DIR__ . '/../src/Lib/Env.php';
Env::load(__DIR__ . '/../.env');
require __DIR__ . '/../src/App.php';

$app = new App();
$app->run();
