<?php
namespace Ratchet\Component\WAMP;
use Ratchet\Component\ComponentInterface;
use Ratchet\Resource\ConnectionInterface;

/**
 * A (not literal) extension of Ratchet\Component\ComponentInterface
 * onMessage is replaced by various types of messages for this protocol (pub/sub or rpc)
 * @todo Thought: URI as class.  Class has short and long version stored (if as prefix)
 */
interface WAMPServerComponentInterface extends ComponentInterface {
    /**
     * An RPC call has been received
     * @param Ratchet\Resource\Connection
     * @param string
     * @param ...
     * @param array Call parameters received from the client
     * @return Ratchet\Resource\Command\CommandInterface|null
     */
    function onCall(ConnectionInterface $conn, $id, $procURI, array $params);

    /**
     * A request to subscribe to a URI has been made
     * @param Ratchet\Resource\Connection
     * @param ...
     * @return Ratchet\Resource\Command\CommandInterface|null
     */
    function onSubscribe(ConnectionInterface $conn, $uri);

    /**
     * A request to unsubscribe from a URI has been made
     * @param Ratchet\Resource\Connection
     * @param ...
     * @return Ratchet\Resource\Command\CommandInterface|null
     */
    function onUnSubscribe(ConnectionInterface $conn, $uri);

    /**
     * A client is attempting to publish content to a subscribed connections on a URI
     * @param Ratchet\Resource\Connection
     * @param ...
     * @param string
     * @return Ratchet\Resource\Command\CommandInterface|null
     */
    function onPublish(ConnectionInterface $conn, $uri, $event);
}