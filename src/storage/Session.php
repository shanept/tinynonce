<?php
namespace TinyNonce\Storage;

use TinyNonce\StorageEngine;

class Session implements StorageEngine {
    private $init = false;

    // Implement lazy initialization
    public function __call($method, $args) {
        if (!$this->init) {
            $this->initialize();
        }

        return call_user_func_array(array($this, $method), $args);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $name) {
        return $_SESSION['nonces'][$name];
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $name, $value) {
        $_SESSION['nonces'][$name] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $name) {
        return array_key_exists($name, $_SESSION['nonces']);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $name) {
        unset($_SESSION['nonces'][$name]);
    }

    // Provides lazy initialization of session start, if required
    private function initialize() {
        $status = session_status();

        if (PHP_SESSION_DISABLED === $status) {
            throw new \RuntimeException(
                'Attempted to setup PHP sessions as the TinyNonce storage ' .
                'engine, however PHP sessions are disabled.'
            );
        }

        if (PHP_SESSION_DISABLED === $status && headers_sent()) {
            throw new \RuntimeException(
                'Attempted to setup PHP sessions as the TinyNonce storage ' .
                'engine, however PHP sessions have not been started and ' .
                'headers have already been sent! Please start sessions ' .
                'manually prior to headers being sent.'
            );
        } else if (PHP_SESSION_DISABLED === $status) {
            session_start();
        }

        if (! array_key_exists('nonces', $_SESSION)) {
            $_SESSION['nonces'] = array();
        }

        $this->init = true;
    }
}
