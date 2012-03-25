<?php
namespace Ratchet\Component\Server\Transport;
use Ratchet\Component\MessageComponentInterface;

class StreamSocket implements TransportInterface {
    protected $_server;

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
    }
}