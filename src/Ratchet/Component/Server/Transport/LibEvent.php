<?php
namespace Ratchet\Component\Server\Transport;
use Ratchet\Component\MessageComponentInterface;

class LibEvent implements TransportInterface {
    const INDEX_SOCKET = 0;
    const INDEX_BUFFER = 1;
    const INDEX_OBJECT = 2;

    protected $_server;

    /*
     * @todo Change to SplObjectStorage
     *  store socket, buffer in Connection
     *  Connection goes in SplObjectStorage
     */
    protected $_connections = array();

    /**
     * {@inheritdoc}
     */
    public function __construct(MessageComponentInterface $server) {
        $this->_server = $server;
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

        $buffer = event_buffer_new($connection, array($this, 'read'), NULL, array($this, 'error'), $id);
        event_buffer_base_set($buffer, $base);
        event_buffer_watermark_set($buffer, EV_READ, 0, 0xffffff);
        event_buffer_priority_set($buffer, 10);
        event_buffer_enable($buffer, EV_READ | EV_PERSIST);

        // remove soon
        $this->_connections[$id] = new \SplFixedArray(3);
        $this->_connections[$id][static::INDEX_SOCKET] = $connection;
        $this->_connections[$id][static::INDEX_BUFFER] = $buffer;
//        $this->_connections[$id][2] = new Connection;

        // new Connection
        // $this->_server->onOpen($conn);
    }

    protected function read($buffer, $id) {
        $msg = '';
        while ($read = event_buffer_read($buffer, 256)) {
            $msg .= $read;
        }

        // Hard-coded for testing
        foreach ($this->_connections as $key => $group) {
            if ($key != $id) {
                event_buffer_write($group[static::INDEX_BUFFER], $msg);
            }
        }

//        $this->_server->onMessage($conn, $msg);
    }

    protected function error($buffer, $error, $id) {
        // Error codes I've received so far:
        // 17: Seems to be disconnect (not an error?)
        // 34: Seems to happen when I send a message to a closed socket (makes sense)
        // 65: Seems to be write timeout (removing timeout fixed this)

        echo "Error: {$error}\n";

        event_buffer_disable($this->_connections[$id][static::INDEX_BUFFER], EV_READ);
        event_buffer_free($this->_connections[$id][static::INDEX_BUFFER]);
        stream_socket_shutdown($this->_connections[$id][static::INDEX_SOCKET], STREAM_SHUT_RDWR);

        unset($this->_connections[$id]);
    }
}