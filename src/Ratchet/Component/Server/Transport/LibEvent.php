<?php
namespace Ratchet\Component\Server\Transport;
use Ratchet\Component\MessageComponentInterface;
use Ratchet\Resource\Connection;

class LibEvent implements TransportInterface {
    const INDEX_SOCKET = 0;
    const INDEX_BUFFER = 1;
    const INDEX_OBJECT = 2;

    /**
     * @param Ratchet\Component\Server\IoComponent
     */
    protected $_server;

    /*
     * @var SplObjectStorage
     */
    protected $_connections;

    /**
     * {@inheritdoc}
     */
    public function __construct(MessageComponentInterface $server) {
        $this->_server      = $server;
        $this->_connections = new \SplObjectStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function run($port) {
        if (false === $socket = stream_socket_server("tcp://0.0.0.0:{$port}")) {
            throw new \InvalidArgumentException("Unable to bind to {$port}");
        }
        stream_set_blocking($socket, 0);

        $base  = event_base_new();
        $event = event_new();

        event_set($event, $socket, EV_READ | EV_PERSIST, array($this, 'accept'), $base);
        event_base_set($event, $base);
        event_add($event);
        event_base_loop($base);
    }

    protected function accept($socket, $flag, $base) {
        static $id = 0;

        $connection = stream_socket_accept($socket);
        stream_set_blocking($connection, 0);

        $id += 1;

        $proxy = new Connection;
        $proxy->libevent = new \StdClass;
        $proxy->libevent->socket = $connection;

        $buffer = event_buffer_new($connection, array($this, 'read'), NULL, array($this, 'error'), $proxy);
        event_buffer_base_set($buffer, $base);
        event_buffer_watermark_set($buffer, EV_READ, 0, 0xffffff);
        event_buffer_priority_set($buffer, 10);
        event_buffer_enable($buffer, EV_READ | EV_PERSIST);

        $proxy->libevent->buffer = $buffer;
        $proxy->libevent->id     = $id;

        $this->_connections->attach($proxy);
        $this->_server->onOpen($proxy);
    }

    protected function read($buffer, $proxy) {
        $msg = '';
        while ($read = event_buffer_read($buffer, 256)) {
            $msg .= $read;
        }

        $this->_server->onMessage($proxy, $msg);
    }

    protected function error($buffer, $error, $proxy) {
        // Error codes I've received so far:
        // 17: Seems to be disconnect (not an error?)
        // 34: Seems to happen when I send a message to a closed socket (makes sense)
        // 65: Seems to be write timeout (removing timeout fixed this)

        event_buffer_disable($proxy->libevent->buffer, EV_READ);
        event_buffer_free($proxy->libevent->buffer);
        stream_socket_shutdown($proxy->libevent->socket, STREAM_SHUT_RDWR);

        ($error == 17 ? $this->_server->onClose($proxy) : $this->_server->onError($proxy));
        $this->_connections->detach($proxy);
    }
}