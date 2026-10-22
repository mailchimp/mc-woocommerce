<?php
/** Run with: php tests/regression/customer-partial-address.php */
if (PHP_SAPI !== 'cli') { exit; } // CLI only - never executable over the web
// Mailchimp copies a customer's address into the contact's ADDRESS merge field. An address
// without a street, city or postcode (common for digital products, where checkout collects
// only a postcode and country) becomes a partial ADDRESS, and Mailchimp then ignores later
// merge-field updates to that contact while still answering 200. Partial addresses must
// not be sent; complete ones still are.
error_reporting(E_ALL & ~E_DEPRECATED);

function mailchimp_array_remove_empty($data) {
    if (empty($data) || !is_array($data)) {
        return array();
    }
    foreach ($data as $key => $value) {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            unset($data[$key]);
        }
    }
    return $data;
}

require __DIR__ . '/../../includes/api/assets/class-mailchimp-address.php';
require __DIR__ . '/../../includes/api/assets/class-mailchimp-customer.php';

function customer_with_address(array $fields) {
    $customer = new MailChimp_WooCommerce_Customer();
    $customer->setId('customer-id');
    $customer->setEmailAddress('reader@example.com');
    $address = $customer->getAddress();
    foreach ($fields as $setter => $value) {
        $address->$setter($value);
    }
    return $customer;
}

$failures = 0;
function check($label, $ok) {
    global $failures;
    echo ($ok ? 'PASS' : 'FAIL') . " - $label\n";
    if (!$ok) $failures++;
}

$postcode_only = customer_with_address(array('setPostalCode' => '10036', 'setCountry' => 'US'))->toArray();
check('a postcode-and-country address is not sent', !isset($postcode_only['address']));

$no_postcode = customer_with_address(array('setAddress1' => '1 Main St', 'setCity' => 'Dublin', 'setCountry' => 'IE'))->toArray();
check('an address without a postcode is not sent', !isset($no_postcode['address']));

$complete = customer_with_address(array(
    'setAddress1' => '1 Main St', 'setCity' => 'New York', 'setPostalCode' => '10036', 'setCountry' => 'US',
))->toArray();
check('a complete address is sent', isset($complete['address']['address1'], $complete['address']['postal_code']));

exit($failures ? 1 : 0);
