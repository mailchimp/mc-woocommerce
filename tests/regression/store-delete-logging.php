<?php
/** Run with php tests/regression/store-delete-logging.php. No network or WordPress. */
if (PHP_SAPI !== 'cli') { exit; }
error_reporting(E_ALL & ~E_DEPRECATED);
$checks = 0;
function check($condition, $message) { global $checks; ++$checks; if (!$condition) { throw new RuntimeException($message); } }
$GLOBALS['errors'] = array();
function mailchimp_error($action, $message, $data = array()) { $GLOBALS['errors'][] = array($action, $message, $data); }
$root = dirname(__DIR__, 2);
require $root . '/includes/api/errors/class-mailchimp-error.php';
require $root . '/includes/api/errors/class-mailchimp-rate-limit-error.php';
require $root . '/includes/api/errors/class-mailchimp-server-error.php';
require $root . '/includes/api/class-mailchimp-api.php';
class StoreDeleteFixtureApi extends MailChimp_WooCommerce_MailChimpApi {
    public $outcome;
    public $response;
    public function __construct() {}
    protected function delete($url, $params = null) {
        check($url === 'ecommerce/stores/store-1', 'Delete targets the store');
        $this->last_response = $this->response;
        if ($this->outcome instanceof Throwable) { throw $this->outcome; }
        return $this->outcome;
    }
}
$api = new StoreDeleteFixtureApi();
$run = function ($outcome, $response = null) use ($api) {
    $GLOBALS['errors'] = array();
    $api->outcome = $outcome; $api->response = $response;
    return $api->deleteStore('store-1');
};
$only = function () { check(count($GLOBALS['errors']) === 1, 'Exactly one log entry per failed delete'); return $GLOBALS['errors'][0]; };

check($run(true, array('http_code' => 204, 'data' => null)) === true && !$GLOBALS['errors'], 'Successful delete logs no error');

// Mailchimp answers with an error document.
$body = array('title' => 'Resource Not Found', 'detail' => 'The requested resource could not be found.', 'status' => 404);
check($run(new MailChimp_WooCommerce_Error('Resource Not FoundThe requested resource could not be found.', 404), array('http_code' => 404, 'data' => $body)) === false, 'Refused delete returns false');
list($action, $message, $data) = $only();
check($action === 'store.delete' && strpos($message, 'Store store-1 was not deleted from Mailchimp: Resource Not Found') === 0, 'Log names the store and the reason');
check($data['http_status'] === 404 && $data['title'] === 'Resource Not Found' && $data['detail'] === 'The requested resource could not be found.', 'Log carries the HTTP status and Mailchimp title and detail');
check($data['exception'] === 'MailChimp_WooCommerce_Error', 'Log names the error type');

// The 403 exception has no message of its own.
check($run(new MailChimp_WooCommerce_RateLimitError(), array('http_code' => 403, 'data' => array('title' => 'Forbidden', 'detail' => 'User action required'))) === false, '403 returns false');
list(, $message, $data) = $only();
check(strpos($message, 'forbidden or rate limited') !== false && $data['http_status'] === 403 && $data['detail'] === 'User action required', '403 is explained instead of logged empty');

// No response at all.
check($run(new MailChimp_WooCommerce_Error('CURL error :: Could not resolve host', 500), array('http_code' => 0, 'data' => null)) === false, 'Network failure returns false');
list(, $message, $data) = $only();
check(strpos($message, 'Could not resolve host') !== false && $data['http_status'] === 'no response', 'Network failure is logged as no response');

// Server error with an undecodable body.
check($run(new MailChimp_WooCommerce_ServerError('API response could not be decoded.'), array('http_code' => 502, 'data' => null)) === false, 'Bad gateway returns false');
list(, , $data) = $only();
check($data['http_status'] === 502 && !isset($data['title']), 'Status is logged even without an error document');

// A falsy result without an exception used to fail silently.
check($run(null, array('http_code' => 200, 'data' => null)) === false, 'Unconfirmed delete returns false');
list(, $message, $data) = $only();
check(strpos($message, 'did not confirm') !== false && $data['http_status'] === 200 && !isset($data['exception']), 'Unconfirmed delete is logged');

// PHP errors are not Exceptions and used to escape the handler.
check($run(new TypeError('boom')) === false, 'A PHP error does not escape the delete');
list(, $message, $data) = $only();
check(strpos($message, 'boom') !== false && $data['exception'] === 'TypeError' && $data['http_status'] === 'no response', 'PHP error is logged');

echo "store-delete-logging: ok ({$checks} checks)\n";
