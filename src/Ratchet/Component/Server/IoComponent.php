<?php
namespace Ratchet\Component\Server;
use Ratchet\Component\MessageComponentInterface;
use Ratchet\Resource\ConnectionInterface;
use Ratchet\Component\Server\Transport\TransportInterface;
use Ratchet\Component\Server\Transport\LibEvent;
use Ratchet\Component\Server\Transport\StreamSocket;
use Ratchet\Resource\Command\CommandInterface;

/**
 */
class IoComponent implements MessageComponentInterface {
    /**
     * @var Ratchet\Component\ComponentInterface
     */
    protected $_decorating;

    /**
     * @var Ratchet\Component\Server\Transport\TransportInterface
     */
    protected $_transport;

    public function __construct(MessageComponentInterface $component, Transport $transport = null) {
        $this->_decorating = $component;

        if (null === $transport) {
            if (function_exists('event_buffer_read')) {
                $transport = new LibEvent($this);
            } else {
                $transport = new StreamSocket($this);
            }
        }

        $this->_transport = $transport;
    }

    /**
     * @param int Port to listen on
     */
    public function run($port) {
        $this->_transport = $this->_transport->run($port);
    }

    /**
     * {@inheritdoc}
     */
    public function onOpen(ConnectionInterface $conn) {
        $this->handleCommands($this->_decorating->onOpen($conn));
    }

    /**
     * {@inheritdoc}
     */
    public function onMessage(ConnectionInterface $from, $msg) {
        $this->handleCommands($this->_decorating->onMessage($from, $msg));
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $conn) {
        $this->handleCommands($this->_decorating->onClose($conn));
    }

    /**
     * {@inheritdoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $this->handleCommands($this->_decorating->onError($conn, $e));
    }

    protected function handleCommands(CommandInterface $command = null) {
        // loop through, do things
    }
}