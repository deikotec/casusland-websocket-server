<?php

namespace Servers;

use Ratchet\WebSocket\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use ParagonIE\ConstantTime\Base64UrlSafe;




class LotteryWebSockets implements MessageComponentInterface
{

    protected $clients;
    protected $daemons;
    private   $con;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->daemons = new \SplObjectStorage;
        $this->con = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {

        $query = $conn->httpRequest->getUri()->getQuery();
        parse_str($query, $params);
        $payload = $params["X-Custom-Header"];

        $payload = base64_decode($payload);
        $payload = json_decode($payload, true);

        if(isset($payload["daemons"])){
            $daemons = $payload["daemons"];
            $this->daemons->attach($conn);
            echo "se conecto un proveedor de servicios $daemons, su conexión es la: ({$conn->resourceId})\n";
            return;
        }

       if(isset($payload["duid"]) && isset($payload["sub"]) && isset($payload["rol"])){
            if ($payload["rol"] === '3') {
                $userId = Base64UrlSafe::decode($payload["sub"]);

                $conn->payload = $payload;
                $this->clients->attach($conn);
                echo "Se ha establecido la conexión ({$conn->resourceId}) con el dispositivo: ".$payload['duid']."\n";
            }
       }else{
           //cerramos la conexion por que no se mandaron los datos validos
            $conn->close();
       }

    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients);
        echo sprintf('Conexion %d envio el mensaje "%s" para %d cliente%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // The sender is not the receiver, send to each client connected
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
    // Puedes llamar a este método para enviar un mensaje a todos los clientes conectados
    public function broadcast($message)
    {
        foreach ($this->clients as $client) {
            $client->send($message);
        }
    }


}