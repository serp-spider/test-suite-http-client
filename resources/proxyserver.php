<?php

use Serps\ProxyServer\ProxyServer;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}else{
    require __DIR__ . '/../../../autoload.php';
}
// CREATE SERVER

$server = new ProxyServer();
$server->listenSocks4(20104);
$server->listenSocks5(20105);
$server->listenHttp(20106);


$server->getLoop()->run();