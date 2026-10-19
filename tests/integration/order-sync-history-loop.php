<?php
/**
 * Real WooCommerce/Action Scheduler regression for sc-52738.
 * Run on an unconfigured disposable localhost site in BOTH HPOS and legacy mode:
 * MC_WOO_HISTORY_LOCAL_QA=1 wp eval-file tests/integration/order-sync-history-loop.php
 */
if (PHP_SAPI !== 'cli') { exit; }
if (getenv('MC_WOO_HISTORY_LOCAL_QA') !== '1' ||
    !in_array(wp_parse_url(home_url(), PHP_URL_HOST), array('localhost', '127.0.0.1'), true) ||
    mailchimp_is_configured() || wp_using_ext_object_cache()) {
    throw new RuntimeException('Requires an unconfigured disposable localhost site, no external object cache, and MC_WOO_HISTORY_LOCAL_QA=1.');
}

// Exercise real orders, hooks, SQL job payloads and the scheduler. Only the
// external service is excluded: a normal order-type skip records the history.
// Fail closed if that path ever tries to make a Mailchimp request.
class Mailchimp_History_No_Network_Api extends MailChimp_WooCommerce_MailChimpApi {
    public static function install() { static::$instance = new self(); }
    public static function reset() { static::$instance = null; }
    protected function url($extra = '', $params = null) {
        throw new RuntimeException('This integration test must not contact Mailchimp.');
    }
}

$checks = 0;
$check = function($condition, $message) use (&$checks) {
    ++$checks;
    if (!$condition) { throw new RuntimeException($message); }
};
$hook = 'MailChimp_WooCommerce_Single_Order';
$pending = function($id) use ($hook) {
    return as_get_scheduled_actions(array(
        'hook' => $hook, 'args' => array('obj_id' => $id),
        'group' => 'mc-woocommerce', 'status' => ActionScheduler_Store::STATUS_PENDING,
        'per_page' => -1,
    ), 'ids');
};
$clear = function($id) use ($hook) {
    mailchimp_delete_job_by_id($id, $hook);
    delete_transient("mailchimp_order_being_processed_{$id}");
};
$rows = function($id) {
    $order = wc_get_order($id);
    $order->read_meta_data(true);
    return array_values($order->get_meta(MailChimp_WooCommerce_Order_Sync_History::META_KEY, false));
};
$skip = function() { return array(); };
$no_mail = function() { return false; };
$no_http = function() { return new WP_Error('local_qa', 'Network disabled for history regression.'); };
$orders = array();
$observed_updates = array();
$observe = function($id) use (&$observed_updates) { $observed_updates[] = $id; };
$nested = null;
$fail = null;
add_filter('pre_wp_mail', $no_mail);
add_filter('pre_http_request', $no_http);
add_action('woocommerce_update_order', $observe, 1);

try {
    $check(has_action('woocommerce_update_order', array(MailChimp_Service::instance(), 'handleOrderUpdate')) !== false, 'Real plugin update hook is registered');
    $check(has_action($hook, array(MailChimp_Service::instance(), 'mailchimp_process_single_job')) !== false, 'Real plugin job runner is registered');
    foreach (array('primary', 'other') as $name) {
        $order = wc_create_order(array('status' => 'processing'));
        $orders[$name] = $order->get_id();
        // HPOS suppresses a meta-triggered full save within the same second.
        // An old persisted date is essential: freshly created mocks hide the bug.
        $order->set_date_modified(time() - 600);
        $order->update_meta_data('_history_loop_unrelated', 'preserve');
        $order->save();
    }
    $id = $orders['primary'];
    $other_id = $orders['other'];
    $observed_updates = array();
    Mailchimp_History_No_Network_Api::install();
    wp_cache_set('mailchimp-woocommerce-options', array(
        'mailchimp_api_key' => 'local-fixture', 'mailchimp_list' => 'local-fixture',
    ), 'mailchimp-woocommerce');
    add_filter('mailchimp_should_push_order_post_type_list', $skip);

    // Real order-update hook -> serialized SQL payload -> Action Scheduler ->
    // real sync job -> history CRUD -> HPOS update hook. No stub order/storage.
    do_action('woocommerce_update_order', $id, wc_get_order($id));
    $actions = $pending($id);
    $check(count($actions) === 1, 'An order update queues one real sync action');
    delete_transient("mailchimp_order_being_processed_{$id}"); // Simulate expiry before the 90-second job.
    ActionScheduler_QueueRunner::instance()->process_action($actions[0], 'History integration');
    $check(ActionScheduler::store()->get_status($actions[0]) === ActionScheduler_Store::STATUS_COMPLETE, 'Real scheduler completed the job');
    $events = $rows($id);
    $check(count($events) === 1 && $events[0]->value['reason'] === 'order_type_filtered', 'Real sync job persisted its skip');
    $check(count($pending($id)) === 0, 'History must not schedule another order sync (HPOS resync loop)');
    $backfill = MailChimp_WooCommerce_HPOS::enabled() && get_option('woocommerce_custom_orders_table_data_sync_enabled') === 'yes';
    global $wpdb;
    $check((int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}mailchimp_jobs WHERE obj_id = %s", $id)) === 0, 'Processed SQL payload is gone; no replacement payload');
    if (MailChimp_WooCommerce_HPOS::enabled()) {
        $check(count(array_filter($observed_updates, function($value) use ($id) { return $value === $id; })) >= 2, 'Real HPOS metadata write fired its update hook');
    }

    // Real edits still queue after history recording has returned.
    $clear($id);
    $order = wc_get_order($id);
    $order->set_customer_note('A real subsequent edit');
    $order->set_date_modified(time() - 600);
    $order->save();
    $check(count($pending($id)) === 1, 'A genuine edit still queues a sync');
    $actions = $pending($id);
    delete_transient("mailchimp_order_being_processed_{$id}");
    ActionScheduler_QueueRunner::instance()->process_action($actions[0], 'History integration');
    $check(ActionScheduler::store()->get_status($actions[0]) === ActionScheduler_Store::STATUS_COMPLETE, 'A subsequent edit job completes');
    $check(count($rows($id)) === 2, 'A subsequent edit records one more history entry');
    $check(count($pending($id)) === 0, 'A subsequent edit job does not restart the loop');
    $clear($id);
    $order = wc_get_order($id);
    $order->set_status('completed');
    $order->save();
    $check(count($pending($id)) === 1, 'A genuine status change still queues a sync');
    $clear($id);

    // A save of another order during the history write must not be suppressed.
    $nested = function($meta_id, $object_id, $key) use ($id, $other_id) {
        if ((int) $object_id === $id && $key === MailChimp_WooCommerce_Order_Sync_History::META_KEY) {
            $other = wc_get_order($other_id);
            $other->set_customer_note('Another order edited during history storage');
            $other->save();
        }
    };
    add_action('added_order_meta', $nested, 10, 3);
    $check(MailChimp_WooCommerce_Order_Sync_History::record($id, 'synced', 'order_updated'), 'Success history can still be recorded');
    remove_action('added_order_meta', $nested, 10);
    $check(count($pending($id)) === 0, 'History does not queue the recorded order');
    $check(count($pending($other_id)) === 1, 'Another order still syncs during history recording');
    $clear($other_id);

    // Pruning can itself cause HPOS update hooks, not only the first insert.
    for ($i = 0; $i < 25; ++$i) {
        $order = wc_get_order($id);
        $order->set_date_modified(time() - 600);
        $order->save();
        $clear($id);
        $check(MailChimp_WooCommerce_Order_Sync_History::record($id, 'retrying', 'rate_limited'), 'History write with retention');
        $check(count($pending($id)) === 0, 'Neither history insertion nor pruning queues an update');
    }
    $check(count($rows($id)) === 20, 'Real storage retains the newest 20 entries');
    $check(wc_get_order($id)->get_meta('_history_loop_unrelated') === 'preserve', 'Unrelated metadata survives pruning');
    if ($backfill) {
        $check(get_post_meta($id, '_history_loop_unrelated', true) === 'preserve', 'HPOS compatibility backfill preserves unrelated metadata');
    }

    // A storage callback failure must not leave a guard suppressing later edits.
    $fail = function($meta_id, $object_id, $key) use ($id) {
        if ((int) $object_id === $id && $key === MailChimp_WooCommerce_Order_Sync_History::META_KEY) {
            throw new RuntimeException('Deliberate local persistence callback failure');
        }
    };
    add_action('added_order_meta', $fail, 10, 3);
    $check(!MailChimp_WooCommerce_Order_Sync_History::record($id, 'failed', 'api_error'), 'Persistence exception is contained');
    remove_action('added_order_meta', $fail, 10);
    $clear($id);
    $order = wc_get_order($id);
    $order->set_customer_note('Real edit after failed history write');
    $order->save();
    $check(count($pending($id)) === 1, 'Genuine edits still queue after a history exception');
    echo json_encode(array('checks' => $checks, 'wordpress' => get_bloginfo('version'), 'woocommerce' => WC_VERSION,
        'php' => PHP_VERSION, 'storage' => MailChimp_WooCommerce_HPOS::enabled() ? 'hpos' : 'legacy',
        'compatibility_sync' => $backfill, 'result' => 'passed')) . "\n";
} finally {
    remove_action('woocommerce_update_order', $observe, 1);
    if ($nested) { remove_action('added_order_meta', $nested, 10); }
    if ($fail) { remove_action('added_order_meta', $fail, 10); }
    remove_filter('mailchimp_should_push_order_post_type_list', $skip);
    wp_cache_delete('mailchimp-woocommerce-options', 'mailchimp-woocommerce');
    Mailchimp_History_No_Network_Api::reset();
    foreach ($orders as $id) {
        $clear($id);
        $order = wc_get_order($id);
        if ($order) { $order->delete(true); }
    }
    remove_filter('pre_wp_mail', $no_mail);
    remove_filter('pre_http_request', $no_http);
}
