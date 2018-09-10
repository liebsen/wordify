<?php 

$container = $app->getContainer();
$config = $container['spot']->mapper("App\Config")
    ->all();

foreach($config as $item){
    putenv("$item->config_key=$item->config_value");
}

// remove when old tool is obsolete
require __DIR__ . "/auth.php";   
require __DIR__ . "/account.php";
require __DIR__ . "/routesv1.php";   
require __DIR__ . "/routesv2.php";   
