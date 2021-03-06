<?php
namespace Ratchet\Component\Session;
use Ratchet\Component\MessageComponentInterface;
use Ratchet\Resource\ConnectionInterface;
use Ratchet\Component\Session\Storage\VirtualSessionStorage;
use Ratchet\Component\Session\Serialize\HandlerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NullSessionHandler;

/**
 * This component will allow access to session data from your website for each user connected
 * Symfony HttpFoundation is required for this component to work
 * Your website must also use Symfony HttpFoundation Sessions to read your sites session data
 * If your are not using at least PHP 5.4 you must include a SessionHandlerInterface stub (is included in Symfony HttpFoundation, loaded w/ composer)
 */
class SessionComponent implements MessageComponentInterface {
    /**
     * @var Ratchet\Component\MessageComponentInterface
     */
    protected $_app;

    /**
     * Selected handler storage assigned by the developer
     * @var SessionHandlerInterface
     */
    protected $_handler;

    /**
     * Null storage handler if no previous session was found
     * @var SessionHandlerInterface
     */
    protected $_null;

    /**
     * @var Ratchet\Component\Session\Serialize\HandlerInterface
     */
    protected $_serializer;

    /**
     * @param Ratchet\Component\MessageComponentInterface
     * @param SessionHandlerInterface
     * @param array
     * @param Ratchet\Component\Session\Serialize\HandlerInterface
     * @throws RuntimeException If unable to match serialization methods
     */
    public function __construct(MessageComponentInterface $app, \SessionHandlerInterface $handler, array $options = array(), HandlerInterface $serializer = null) {
        $this->_app     = $app;
        $this->_handler = $handler;
        $this->_null    = new NullSessionHandler;

        ini_set('session.auto_start', 0);
        ini_set('session.cache_limiter', '');
        ini_set('session.use_cookies', 0);

        $this->setOptions($options);

        if (null === $serializer) {
            $serialClass = __NAMESPACE__ . "\\Serialize\\{$this->toClassCase(ini_get('session.serialize_handler'))}Handler"; // awesome/terrible hack, eh?
            if (!class_exists($serialClass)) {
                throw new \RuntimeExcpetion('Unable to parse session serialize handler');
            }

            $serializer = new $serialClass;
        }

        $this->_serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    function onOpen(ConnectionInterface $conn) {
        if (null === ($id = $conn->WebSocket->headers->getCookie(ini_get('session.name')))) {
            $saveHandler = $this->_null;
            $id = '';
        } else {
            $saveHandler = $this->_handler;
        }

        $conn->Session = new Session(new VirtualSessionStorage($saveHandler, $id, $this->_serializer));

        if (ini_get('session.auto_start')) {
            $conn->Session->start();
        }

        return $this->_app->onOpen($conn);
    }

    /**
     * {@inheritdoc}
     */
    function onMessage(ConnectionInterface $from, $msg) {
        return $this->_app->onMessage($from, $msg);
    }

    /**
     * {@inheritdoc}
     */
    function onClose(ConnectionInterface $conn) {
        // "close" session for Connection

        return $this->_app->onClose($conn);
    }

    /**
     * {@inheritdoc}
     */
    function onError(ConnectionInterface $conn, \Exception $e) {
        return $this->_app->onError($conn, $e);
    }

    /**
     * Set all the php session. ini options
     * © Symfony
     * @param array
     * @return array
     */
    protected function setOptions(array $options) {
        $all = array(
            'auto_start', 'cache_limiter', 'cookie_domain', 'cookie_httponly',
            'cookie_lifetime', 'cookie_path', 'cookie_secure',
            'entropy_file', 'entropy_length', 'gc_divisor',
            'gc_maxlifetime', 'gc_probability', 'hash_bits_per_character',
            'hash_function', 'name', 'referer_check',
            'serialize_handler', 'use_cookies',
            'use_only_cookies', 'use_trans_sid', 'upload_progress.enabled',
            'upload_progress.cleanup', 'upload_progress.prefix', 'upload_progress.name',
            'upload_progress.freq', 'upload_progress.min-freq', 'url_rewriter.tags'
        );

        foreach ($all as $key) {
            if (!array_key_exists($key, $options)) {
                $options[$key] = ini_get("session.{$key}");
            } else {
                ini_set("session.{$key}", $options[$key]);
            }
        }

        return $options;
    }

    /**
     * @param string Input to convert
     * @return string
     */
    protected function toClassCase($langDef) {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $langDef)));
    }
}