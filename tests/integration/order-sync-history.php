<?php
/** Local disposable WooCommerce only: wp eval-file tests/integration/order-sync-history.php */
if (PHP_SAPI !== 'cli') { exit; }
if (getenv('MC_WOO_HISTORY_LOCAL_QA') !== '1' ||
    !in_array(wp_parse_url(home_url(), PHP_URL_HOST), array('localhost', '127.0.0.1'), true) || mailchimp_is_configured()) {
    throw new RuntimeException('Requires an unconfigured disposable localhost WooCommerce site and MC_WOO_HISTORY_LOCAL_QA=1.');
}
require_once ABSPATH . 'wp-admin/includes/template.php';
add_filter('pre_wp_mail', '__return_false');
$checks = 0;
$check = function($condition, $message) use (&$checks) { ++$checks; if (!$condition) { throw new RuntimeException($message); } };
$order = wc_create_order(array('status' => 'pending'));
$order->update_meta_data('_unrelated_fixture', 'preserve');
$order->save_meta_data();
$id = $order->get_id();
$history = new MailChimp_WooCommerce_Order_Sync_History();
$check((new MailChimp_WooCommerce_Single_Order($id))->process() === false, 'Unconfigured attempt must remain skipped');
$read = wc_get_order($id);
$read->read_meta_data(true);
$rows = array_map(function($meta) { return $meta->value; }, array_values($read->get_meta(MailChimp_WooCommerce_Order_Sync_History::META_KEY, false)));
$check(count($rows) === 1 && $rows[0]['reason'] === 'not_configured', 'Real sync job persisted its unconfigured skip');
for ($i = 0; $i < 25; ++$i) { $check(MailChimp_WooCommerce_Order_Sync_History::record($id, 'skipped', 'draft'), 'Real CRUD write'); }
$read = wc_get_order($id);
$read->read_meta_data(true);
$rows = array_map(function($meta) { return $meta->value; }, array_values($read->get_meta(MailChimp_WooCommerce_Order_Sync_History::META_KEY, false)));
$check(count($rows) === 20, 'Real CRUD pruning retains 20 events');
$check($read->get_meta('_unrelated_fixture', true) === 'preserve', 'Pruning preserves unrelated metadata');
// Leave a real unconfigured-job result as the most recent entry for browser QA.
(new MailChimp_WooCommerce_Single_Order($id))->process();
foreach (array('administrator', 'shop_manager', 'customer') as $role) {
    $login = 'history-' . $role;
    $user = get_user_by('login', $login);
    $user_id = $user ? $user->ID : wp_insert_user(array('user_login' => $login, 'user_pass' => wp_generate_password(24), 'role' => $role));
    $check(!is_wp_error($user_id), 'Fixture role user');
    wp_set_current_user($user_id);
    ob_start(); $history->render(wc_get_order($id)); $html = ob_get_clean();
    $check($role === 'customer' ? $html === '' : strpos($html, 'Mailchimp is not configured') !== false, 'Per-order access: ' . $role);
    if ($role !== 'customer') {
        ob_start(); $history->render((object) array('ID' => $id)); $legacy_html = ob_get_clean();
        $check(strpos($legacy_html, 'Mailchimp is not configured') !== false, 'Legacy callback: ' . $role);
    }
}
wp_set_current_user(get_user_by('login', 'history-administrator')->ID);
$empty = wc_create_order(array('status' => 'pending'));
ob_start(); $history->render($empty); $html = ob_get_clean();
$check(strpos($html, 'may already exist') !== false, 'Empty history is explicit');
$mode = MailChimp_WooCommerce_HPOS::enabled() ? 'hpos' : 'legacy';
$url = $mode === 'hpos' ? admin_url('admin.php?page=wc-orders&action=edit&id=' . $id) : admin_url('post.php?post=' . $id . '&action=edit');
echo json_encode(array('checks' => $checks, 'mode' => $mode, 'order_id' => $id, 'empty_order_id' => $empty->get_id(), 'edit_url' => $url)) . "\n";
