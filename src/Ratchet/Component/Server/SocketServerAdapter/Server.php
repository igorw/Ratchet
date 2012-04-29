<?php
namespace Ratchet\Component\Server\SocketServerAdapter;
use Igorw\SocketServer\Server as SocketServer;

class Server extends SocketServer {
    public function createConnection($socket) {
        return new Connection($socket, $this);
    }
}