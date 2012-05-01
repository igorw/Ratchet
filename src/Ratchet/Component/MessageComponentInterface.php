<?php
namespace Ratchet\Component;
use Ratchet\Resource\ConnectionInterface;
use Ratchet\Resource\Command\CommandSubscriberInterface;
use Ratchet\Resource\Command\CommandInterface;

interface MessageComponentInterface extends ComponentInterface {
    /**
     * Triggered when a client sends data through the socket
     * @param Ratchet\Resource\ConnectionInterface The socket/connection that sent the message to your application
     * @param string The message received
     * @return Ratchet\Resource\Command\CommandInterface|null
     * @throws Exception
     */
    function onMessage(ConnectionInterface $from, $msg);

    function subscribeCommand(CommandSubscriberInterface $subscriber);
    function unSubscribeCommand(CommandSubscriberInterface $subscriber);
    function notifyCommand(CommandInterface $command);
}