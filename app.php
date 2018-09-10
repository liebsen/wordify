<?php 

/*
 * This file is part of the TBOC Refocus project
 *
 * Copyright (c) 2018 Martin Frith
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://bitbucket.com/marsvieyra/sandbox
 *
 */

//date_default_timezone_set("EST");
date_default_timezone_set('America/Argentina/Buenos_Aires');

require __DIR__ . "/vendor/autoload.php";

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

$app = new \Slim\App([
    "settings" => [
        "displayErrorDetails" => true
    ]
]);

require __DIR__ . "/config/functions.php";
require __DIR__ . "/config/dependencies.php";
require __DIR__ . "/config/handlers.php";
require __DIR__ . "/config/middleware.php";
require __DIR__ . "/config/routes.php";	

$app->run();