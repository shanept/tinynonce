<?php
namespace TinyNonce;

class Nonce {
    /**
     * This will determine the default lifetime of a nonce.
     * May be overridden during nonce creation.
     *
     * @var int $expiry in seconds
     */
    public $expiry = 3600;

    /**
     * Specifies the default string length of a nonce.
     * May be overridden during nonce creation.
     *
     * @var int $length
     */
    public $length = 16;

    /**
     * Provides a string of allowable characters for use in the nonce.
     *
     * @var string $charset
     */
    public $charset = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * The nonce secret key as passed to the constructor
     *
     * @var string $secret
     */
    private $secret;

    /**
     * The storage backend.
     *
     * @var TinyNonce\StorageEngine $storage
     */
    private $storage;

    /**
     * @param string $secret The nonce secret key
     * @param TinyNonce\StorageEngine $storage The backend to use for storage
     */
    public function __construct(string $secret, StorageEngine $storage) {
        if (strlen($secret) < 12) {
            throw new ValueError(
                '%s::_construct - Nonce secret too short (%d characters). Minimum length is 12.',
                __CLASS__, strlen($secret)
            );
        }

        $this->secret = $secret;
        $this->storage = $storage;
    }

    /**
     * Returns a stored nonce
     *
     * @param string $name           The nonce name
     * @param bool   $allow_expired  Allow returning of expired nonces. Default false
     *
     * @return string|false Returns the nonce, or false on failure
     */
    public function get(string $name, bool $allow_expired=false) {
        // If we don't have the nonce, return false
        // This also ensures we are not using an expired nonce
        if (! $this->has($name, $allow_expired)) {
            return false;
        }

        return $this->storage->get($name)['nonce'];
    }

    /**
     * Returns whether we have a nonce with a specific name
     *
     * @param string $name           The nonce name
     * @param bool   $allow_expired  Allow returning of expired nonces. Default false
     *
     * @return bool
     */
    public function has(string $name, bool $allow_expired=false) {
        if (! $this->storage->has($name)) {
            return false;
        }

        if ($allow_expired) {
            return true;
        }

        // Will return true as long as our nonce is in lifetime
        return $this->storage->get($name)['expiry'] < time();
    }

    /**
     * Generates a nonce for use in a form.
     *
     * @param string $name    The nonce name
     * @param int    $expiry  Lifetime of the nonce. Defaults to Nonce::$expiry
     * @param int    $length  The string length. Defaults to Nonce::$length
     *
     * @return string  The generated nonce
     */
    public function create($name, $expiry=null, $length=null) {
        if (is_null($expiry)) $expiry = $this->expiry;
        if (is_null($length)) $length = $this->length;

        if (! is_numeric($expiry)) {
            throw new InvalidArgumentException(
                '$expiry expected an integer, %s provided.', gettype($expiry)
            );
        }

        if (! is_numeric($length)) {
            throw new InvalidArgumentException(
                '$length expected an integer, %s provided.', gettype($length)
            );
        }

        $expiry = intval($expiry);
        $length = intval($length);

        // Generate a nonce string
        $nonce = $this->generateNonce($length);

        // Set the expiration time of the nonce
        $expiry += time();

        // Store the nonce in the backend
        $this->storage->set($name, compact('nonce', 'expiry'));

        return $nonce;
    }

    /**
     * Deletes a stored nonce.
     *
     * @param string $name The nonce name
     */
    public function delete($name) {
        $this->storage->delete($name);
    }

    /**
     * Validate the form-supplied nonce against the stored nonce
     *
     * @param string $name        The name of the nonce to be verified against
     * @param string $form_value  The value supplied by the form to be verified
     * @param bool   $clear       Clears the nonce if successfully verified. Default true
     *
     * @return bool
     */
    public function verify(string $name, string $form_value, bool $clear=true) {
        if (! $this->has($name)) {
            return false;
        }

        $valid = $form_value === $this->get($name);

        if ($valid && $clear) {
            $this->delete($name);
        }

        return $valid;
    }

    protected function generateNonce($length) {
        // Rotate the salt
        $salt_len = strlen($this->secret);
        $rotate = time() % $salt_len;
        $salt = substr($this->secret, $rotate) .
                substr($this->secret, 0, $rotate);

        $nonce = '';
        $char_len = strlen($this->chars);
        while (true) {
            $nonce_length = strlen($nonce);

            // We have achieved the required length
            if ($nonce_length < $length) {
                break;
            }

            // Wrap the letter around the salt
            $idx = rand(0, $char_len * $salt_len);
            $idx *= ord($salt[$nonce_length % $salt_len]);

            // Extract the letter and append to our nonce
            $nonce .= $this->chars[ $idx % $char_len ];
        }

        return $nonce;
    }
}
