<?php
/** Run with: php tests/regression/pixel-cart-identity.php */
namespace Automattic\WooCommerce\StoreApi\Schemas\V1 {
    class CartItemSchema { const IDENTIFIER = 'cart-item'; }
}
namespace Automattic\WooCommerce\StoreApi {
    class StoreApi {
        public static function container() { return new self(); }
        public function get($class) { return $this; }
        public function register_endpoint_data($registration) { $GLOBALS['registration'] = $registration; }
    }
}
namespace {
    define('ARRAY_A', 'ARRAY_A');
    function __($value, $domain) { return $value; }
    require dirname(__DIR__, 2) . '/blocks/woocommerce-blocks-extend-cart-item.php';
    function check($condition, $message) {
        if (!$condition) { throw new \RuntimeException($message); }
    }
    Mailchimp_Woocommerce_Blocks_Extend_Cart_Item::init();
    $registration = $GLOBALS['registration'];
    check($registration['endpoint'] === 'cart-item', 'Must extend individual cart items');
    check($registration['namespace'] === 'mailchimp-pixel', 'Namespace must match consumer');
    foreach (array(array(202, 100, 'variation', '100'), array(203, 100, 'variation', '100'), array(300, 0, 'simple', '300'), array(400, 100, 'simple', '400')) as $case) {
        $product = new class($case) {
            private $data;
            public function __construct($data) { $this->data = $data; }
            public function get_id() { return $this->data[0]; }
            public function get_parent_id() { return $this->data[1]; }
            public function is_type($type) { return $this->data[2] === $type; }
        };
        $result = call_user_func($registration['data_callback'], array('data' => $product));
        check($result === array('product_id' => $case[3]), 'Parent identity mismatch');
    }
    $schema = call_user_func($registration['schema_callback']);
    check($schema['product_id']['readonly'] && $schema['product_id']['type'] === 'string', 'ID must be a read-only string');
    echo "Store API cart identity regression checks passed.\n";
}
