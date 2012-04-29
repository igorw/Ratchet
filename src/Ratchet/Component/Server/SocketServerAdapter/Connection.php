<?php
namespace Ratchet\Component\Server\SocketServerAdapter;
use Igorw\SocketServer\Connection as SSConnection;
use Ratchet\Resource\ConnectionInterface;

class Connection extends SSConnection implements ConnectionInterface {
    public $remoteAddress;

    public function __construct($socket, $server) {
        $this->remoteAddress = stream_socket_get_name($socket, true);

        parent::__construct($socket, $server);
    }
}