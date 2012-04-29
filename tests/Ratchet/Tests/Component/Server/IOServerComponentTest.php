<?php
namespace Ratchet\Tests\Component\Server;
use Ratchet\Component\Server\IOServerComponent;
use Ratchet\Tests\Mock\Component as TestApp;
use Ratchet\Tests\Mock\Connection;

/**
 * @covers Ratchet\Component\Server\IOServerComponent
 */
class IOServerComponentTest extends \PHPUnit_Framework_TestCase {
    protected $_server;
    protected $_decorated;

    public function setUp() {
        $this->_decorated = new TestApp;
        $this->_server    = new IOServerComponent($this->_decorated);
    }

    public function testOnOpenPassesClonedSocket() {
        $conn = new Connection;

        $this->_server->onOpen($conn);

        $this->assertSame($conn, $this->_decorated->_conn_open);
    }

    public function testOnMessageSendsToApp() {
        $conn = new Connection;

        $this->_server->onOpen($conn);

        // $this->_server->run($this->_catalyst);
        $msg = 'Hello World!';
        $this->_server->onMessage($conn, $msg);

        $this->assertEquals($msg, $this->_decorated->_msg_recv);
    }
}