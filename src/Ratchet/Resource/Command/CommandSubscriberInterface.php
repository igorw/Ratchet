<?php
namespace Ratchet\Resource\Command;

interface CommandSubscriberInterface {
    function onCommand(CommandInterface $command);
}