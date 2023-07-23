<?php

require_once('../vendor/autoload.php');
require_once('LotteryResultWebSocket.php');

use Servers\LotteryWebSockets;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;


$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new LotteryWebSockets()
        )
    )
    ,
    9090
);

$server->run();