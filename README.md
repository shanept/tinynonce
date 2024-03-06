# tinynonce
*No hard requirements^* PHP library for generating and validating CSRF tokens, or nonces.  

^ Different storage engines have different requirements. This is inherent in their behaviour. It is left to the developer to determine the best storage engine for their environment and use case.

### Usage
TinyNonce requires 2 components upon initialization:

* Secret Key: A randomly generated secret key, 12 characters or longer.
* Storage Engine: A simple storage engine with which it will store nonce data. Must implement TinyNonce\StorageEngine. See Storage Backends section.

```php
use TinyNonce\Nonce;
use TinyNonce\Storage\Session as SessionStorage;

// Please don't use this in your code...
// Just go here: https://www.random.org/passwords/
$key = 'MySecretKey';

// We will use the Session storage engine.
// For more options, see the Storage Backend section.
$storage = new SessionStorage;

// Instantiate the nonce class.
$nonce = new Nonce($key, $storage);


/**
 * Nonce Creation
 */

// Creates a nonce value for "contact_form_3" and returns it.
$mynonce_value = $nonce->create('contact_form_3');

// Creates a 7 digit nonce for "ajax_request", expiring in 30 seconds.
$ajax_nonce = $nonce->create('ajax_request', 30, 7);

// Creates a nonce that expires prior to creation.
// Not sure why you might want to do this...
$expired = $nonce->create('die-nonce', -10);


/**
 * Nonce, are you there?
 */

// As this nonce is already expired, TinyNonce will tell us we don't have it.
$nonce->has('die-nonce'); // returns false

// We can override this feature by specifying to allow expired results.
$nonce->has('die-nonce'); // returns true


/**
 * Retrieving nonce values
 */

// We can retrieve the nonce value after creation
$nonce->get('ajax_request'); // same value as $ajax_nonce

// If our nonce is expired, we get "false"
$nonce->get('die-nonce'); // returns false

// We can retrieve expired nonces like this
$nonce->get('die-nonce', true) // same value as $expired

// Or we will get false if the nonce doesn't exist
$nonce->get('404'); // returns false


/**
 * Deleting nonces
 */

// Deleted nonces no longer exist, nor are they expired.
$nonce->has('contact_form_3');         // returns true
$nonce->delete('contact_form_3');
$nonce->has('contact_form_3');         // returns false
$nonce->has('contact_form_3', true);   // returns false

// Deleting non-existant nonces does nothing.
$nonce->has('404');     // returns false
$nonce->delete('404');
$nonce->has('404');     // returns false


/**
 * Nonce verification
 */

// Verifies the stored value matches a provided value
$nonce->verify('ajax_request', $ajax_request); // returns true

// Note this will no longer verify.
// This is because nonces are deleted upon successful verification.
$nonce->verify('ajax_request', $ajax_request); // returns false

// We can override this with the $clear parameter.
$ajax_request = $nonce->create('ajax_request');
$nonce->verify('ajax_request', $ajax_request, false); // returns true
$nonce->verify('ajax_request', $ajax_request, false); // returns true again

// If we attempt to validate an expired nonce, it will not work.
$nonce->verify('die-nonce', $expired); // returns false

// As the expired nonce was not verified, it was not deleted. It is important
// to note the nonce library does not automatically perform garbage collection
// on nonces.
$nonce->has('die-nonce');           // returns false
$nonce->has('die-nonce', true');    // returns true
```

### API

#### TinyNonce::Nonce class
```php
/**
 * @param string $secret The nonce secret key
 * @param TinyNonce\StorageEngine $storage The backend to use for storage
 */
public function __construct(string $secret, StorageEngine $storage)

/**
 * Returns a stored nonce
 *
 * @param string $name           The nonce name
 * @param bool   $allow_expired  Allow returning of expired nonces. Default false
 *
 * @return string|false Returns the nonce, or false on failure
 */
public function get(string $name, bool $allow_expired=false)

/**
 * Returns whether we have a nonce with a specific name
 *
 * @param string $name           The nonce name
 * @param bool   $allow_expired  Allow returning of expired nonces. Default false
 *
 * @return bool
 */
public function has(string $name, bool $allow_expired=false)

/**
 * Generates a nonce for use in a form.
 *
 * @param string $name    The nonce name
 * @param int    $expiry  Lifetime of the nonce. Defaults to Nonce::$expiry
 * @param int    $length  The string length. Defaults to Nonce::$length
 *
 * @return string  The generated nonce
 */
public function create($name, $expiry=null, $length=null)

/**
 * Deletes a stored nonce.
 *
 * @param string $name The nonce name
 */
public function delete($name)

/**
 * Validate the form-supplied nonce against the stored nonce
 *
 * @param string $name        The name of the nonce to be verified against
 * @param string $form_value  The value supplied by the form to be verified
 * @param bool   $clear       Clears the nonce if successfully verified. Default true
 *
 * @return bool
 */
public function verify(string $name, string $form_value, bool $clear=true)
```

### Storage Backend
TinyNonce currently supports the following storage engines:

* TinyNonce\Storage\Session: PHP Session based storage.

All new engines are required to implement the TinyNonce\StorageEngine interface.
