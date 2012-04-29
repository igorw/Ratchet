<?php
namespace Ratchet\Component\Server;
use Ratchet\Component\MessageComponentInterface;
use Ratchet\Resource\ConnectionInterface;
use Ratchet\Resource\Command\CommandInterface;
use Ratchet\Component\Server\SocketServerAdapter\Server;

/**
 * Creates an open-ended socket to listen on a port for incomming connections.  Events are delegated through this to attached applications
 */
class IOServerComponent implements MessageComponentInterface {
    /**
     * The decorated application to send events to
     * @var Ratchet\Component\ComponentInterface
     */
    protected $_decorating;

    /**
     * Number of bytes to read in the TCP buffer at a time
     * Default is (currently) 4kb
     * @var int
     */
    protected $_buffer_size = 4096;

    public function __construct(MessageComponentInterface $component) {
        $this->_decorating = $component;
    }

    /**
     * Set the incoming buffer size in bytes
     * @param int
     * @return App
     * @throws InvalidArgumentException If the parameter is less than 1
     * @deprecated ?
     */
    public function setBufferSize($recv_bytes) {
        if ((int)$recv_bytes < 1) {
            throw new \InvalidArgumentException('Invalid number of bytes set, must be more than 0');
        }

        $this->_buffer_size = (int)$recv_bytes;

        return $this;
    }

    /**
     * @param int Port to run the server on
     * @param string Address to open the server on, '0.0.0.0' (default) is open to all network connections
     * @todo See how exceptions are handled here - not sure if they'll break out of the closures
     */
    public function run($port, $address = '0.0.0.0') {
        $server = new Server($address, $port);
        $that   = $this;

        gc_enable();
        set_time_limit(0);
        ob_implicit_flush();

        declare(ticks = 1);

        $server->on('connect', function($conn) use ($that) {
// Need to figure this out
$conn->resourceId = uniqid();

            try {
                $that->execute($that->onOpen($conn));

                $conn->on('data', function($data) use ($conn, $that) {
                    try {
                        $that->execute($that->onMessage($conn, $data));
                    } catch (\Exception $e) {
                        $that->execute($that->onError($conn, $e));
                    }
                });

                $conn->on('end', function() use ($conn, $that) {
                    try {
                        $that->execute($that->onClose($conn));
                    } catch (\Exception $e) {
                        $that->execute($that->onError($conn, $e));
                    }
                });

                $conn->on('error', function($msg, $context) use ($conn, $that) {
                    try {
                        throw new \Exception($msg);
                    } catch (\Exception $e) {
                        $that->execute($that->onError($conn, $e));
                    }
                });
            } catch (\Exception $e) {
                $that->execute($that->onError($conn, $e));
            }
        });

        $server->run();
    }

    /**
     * @param Ratchet\Resource\Command\CommandInterface
     */
    public function execute(CommandInterface $command = null) {
        while ($command instanceof CommandInterface) {
            try {
                $new_res = $command->execute($this);
            } catch (\Exception $e) {
                break;
                // trigger new error
                // $new_res = $this->onError($e->getSocket()); ???
                // this is dangerous territory...could get in an infinte loop...Exception might not be Ratchet\Exception...$new_res could be ActionInterface|Composite|NULL...
            }

            $command = $new_res;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onOpen(ConnectionInterface $conn) {
        return $this->_decorating->onOpen($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function onMessage(ConnectionInterface $from, $msg) {
        return $this->_decorating->onMessage($from, $msg);
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $conn) {
        return $this->_decorating->onClose($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        return $this->_decorating->onError($conn, $e);
    }
}