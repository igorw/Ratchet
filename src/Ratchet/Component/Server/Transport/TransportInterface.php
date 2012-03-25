<?php
namespace Ratchet\Component\Server\Transport;
use Ratchet\Component\MessageComponentInterface;

interface TransportInterface {
    /**
     * @param Ratchet\Component\MessageComponentInterface
     */
    function __construct(MessageComponentInterface $server);

    /**
     * @param int
     */
    function run($port);
}