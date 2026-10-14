<?php

/** Bounded diagnostic history of decisions made by the order sync job. */
class MailChimp_WooCommerce_Order_Sync_History {
    const META_KEY = '_mailchimp_woocommerce_sync_history';
    const MAX_ENTRIES = 20;

    public static function outcomes() {
        return array(
            'synced' => __('Synced', 'mailchimp-for-woocommerce'),
            'skipped' => __('Skipped', 'mailchimp-for-woocommerce'),
            'retrying' => __('Retry scheduled', 'mailchimp-for-woocommerce'),
            'failed' => __('Attempt error', 'mailchimp-for-woocommerce'),
        );
    }

    public static function reasons() {
        return array(
            'not_configured' => __('Mailchimp is not configured. Connect the plugin before syncing orders.', 'mailchimp-for-woocommerce'),
            'order_number_unavailable' => __('The order number could not be loaded.', 'mailchimp-for-woocommerce'),
            'order_type_filtered' => __('This order type was excluded by the order-type filter.', 'mailchimp-for-woocommerce'),
            'user_role_filtered' => __('The customer user role was excluded by the sync rules.', 'mailchimp-for-woocommerce'),
            'existing_member_status' => __('Existing-members-only sync: the audience contact has an ineligible status.', 'mailchimp-for-woocommerce'),
            'audience_lookup_failed' => __('Existing-members-only sync: the audience lookup failed. Review the plugin logs for details.', 'mailchimp-for-woocommerce'),
            'update_without_remote_order' => __('This was an update-only attempt, but the order was not found in Mailchimp.', 'mailchimp-for-woocommerce'),
            'draft' => __('Checkout-draft orders cannot be submitted.', 'mailchimp-for-woocommerce'),
            'new_status_ignored' => __('This new order was excluded by the configured order-status rules.', 'mailchimp-for-woocommerce'),
            'bad_email' => __('The order has an invalid customer email address.', 'mailchimp-for-woocommerce'),
            'amazon_order' => __('Amazon orders are excluded from syncing.', 'mailchimp-for-woocommerce'),
            'privacy_restricted' => __('The order is privacy restricted and was not sent.', 'mailchimp-for-woocommerce'),
            'subscribers_only' => __('The customer did not opt in, and the plugin is set to sync subscribers only.', 'mailchimp-for-woocommerce'),
            'new_financial_status' => __('This new order is pending payment, failed, or cancelled and was not submitted by this attempt.', 'mailchimp-for-woocommerce'),
            'initial_financial_status' => __('Initial sync: this new order does not have an eligible financial status.', 'mailchimp-for-woocommerce'),
            'empty_response' => __('The order request returned an empty response. Successful sync could not be confirmed.', 'mailchimp-for-woocommerce'),
            'order_created' => __('Mailchimp accepted the new order.', 'mailchimp-for-woocommerce'),
            'order_updated' => __('Mailchimp accepted the order update.', 'mailchimp-for-woocommerce'),
            'rate_limit_lookup' => __('The Mailchimp order lookup was rate limited; a retry was scheduled.', 'mailchimp-for-woocommerce'),
            'rate_limited' => __('Mailchimp rate limited this attempt; a retry was scheduled.', 'mailchimp-for-woocommerce'),
            'server_error' => __('Mailchimp returned a server error. Review the plugin logs and queue for retry details.', 'mailchimp-for-woocommerce'),
            'api_error' => __('A Mailchimp request failed. Review the plugin logs for details.', 'mailchimp-for-woocommerce'),
            'unexpected_error' => __('This attempt failed unexpectedly. Review the plugin logs for details.', 'mailchimp-for-woocommerce'),
            'follow_up_error' => __('The order was accepted, but a follow-up operation failed. Review the plugin logs for details.', 'mailchimp-for-woocommerce'),
            'customer_recovered' => __('Mailchimp accepted the order after the existing customer was replaced.', 'mailchimp-for-woocommerce'),
        );
    }

    /** Store no email addresses, names, credentials, payloads or exception text. */
    public static function record($order_id, $outcome, $reason, $full_sync = false, $order_status = null) {
        if ((!is_int($order_id) && (!is_string($order_id) || !ctype_digit($order_id))) || (int) $order_id <= 0) {
            return false;
        }
        if (!is_string($outcome) || !is_string($reason) || !isset(self::outcomes()[$outcome]) || !isset(self::reasons()[$reason])) {
            return false;
        }
        try {
            $order = wc_get_order($order_id);
            if (!$order instanceof WC_Order || !$order->get_id()) {
                return false;
            }
            $order->read_meta_data(true);
            $rows = self::rows($order);
            // Separate metadata rows avoid replacing a concurrent writer's entire history.
            foreach (array_slice($rows, 0, max(0, count($rows) - self::MAX_ENTRIES + 1)) as $row) {
                $order->delete_meta_data_by_mid($row['id']);
            }
            $order->add_meta_data(self::META_KEY, array(
                'timestamp' => time(),
                'outcome' => $outcome,
                'reason' => $reason,
                'mode' => $full_sync ? 'initial' : 'live',
                'order_status' => substr(sanitize_key(is_string($order_status) ? $order_status : $order->get_status()), 0, 40),
            ), false);
            $order->save_meta_data();
            return true;
        } catch (Throwable $e) {
            // Diagnostics must never fail a sync or expose a raw API exception.
            error_log('Mailchimp order sync history could not be stored.');
            return false;
        }
    }

    private static function rows($order) {
        $rows = array();
        foreach ($order->get_meta_data() as $meta) {
            $data = $meta->get_data();
            if ($data['key'] === self::META_KEY) {
                $rows[] = $data;
            }
        }
        usort($rows, function($a, $b) { return (int) $a['id'] <=> (int) $b['id']; });
        return $rows;
    }

    public function register_meta_box() {
        if (!current_user_can('edit_shop_orders')) {
            return;
        }
        $screen = MailChimp_WooCommerce_HPOS::enabled() ? wc_get_page_screen_id('shop-order') : 'shop_order';
        add_meta_box('mailchimp-order-sync-history', __('Mailchimp order sync history', 'mailchimp-for-woocommerce'),
            array($this, 'render'), $screen, 'normal', 'default');
    }

    public function render($post_or_order) {
        $order_id = $post_or_order instanceof WC_Order ? $post_or_order->get_id() : (isset($post_or_order->ID) ? $post_or_order->ID : 0);
        if (!$order_id || !current_user_can('edit_shop_order', $order_id)) {
            return;
        }
        try {
            $order = wc_get_order($order_id);
            if (!$order instanceof WC_Order) {
                throw new RuntimeException('Order unavailable');
            }
            $rows = array_reverse(array_slice(self::rows($order), -self::MAX_ENTRIES));
        } catch (Throwable $e) {
            echo '<p role="alert">' . esc_html__('Unable to load order sync history. Check the WooCommerce logs.', 'mailchimp-for-woocommerce') . '</p>';
            return;
        }
        if (!$rows) {
            echo '<p>' . esc_html__('No order sync history has been recorded yet. The order may already exist in Mailchimp. New sync attempts will appear here; refresh this page after an attempt.', 'mailchimp-for-woocommerce') . '</p>';
            return;
        }
        $outcomes = self::outcomes();
        $reasons = self::reasons();
        echo '<p>' . esc_html__('Recent sync events, newest first. Refresh this page after a sync attempt.', 'mailchimp-for-woocommerce') . '</p>';
        echo '<table class="widefat striped"><thead><tr><th scope="col">' . esc_html__('Time', 'mailchimp-for-woocommerce') . '</th><th scope="col">' . esc_html__('Result', 'mailchimp-for-woocommerce') . '</th><th scope="col">' . esc_html__('Reason', 'mailchimp-for-woocommerce') . '</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            $event = $row['value'];
            if (!is_array($event) || empty($event['timestamp']) || !is_int($event['timestamp']) || $event['timestamp'] < 0 ||
                !isset($event['outcome'], $event['reason'], $event['mode'], $event['order_status']) ||
                !is_string($event['outcome']) || !is_string($event['reason']) || !is_string($event['order_status']) ||
                !in_array($event['mode'], array('initial', 'live'), true) ||
                !isset($outcomes[$event['outcome']], $reasons[$event['reason']])) {
                echo '<tr><td colspan="3">' . esc_html__('A history entry could not be displayed.', 'mailchimp-for-woocommerce') . '</td></tr>';
                continue;
            }
            $mode = $event['mode'] === 'initial' ? __('Initial sync', 'mailchimp-for-woocommerce') : __('Live sync', 'mailchimp-for-woocommerce');
            echo '<tr><td>' . esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) $event['timestamp'])) . '</td><td><strong>' . esc_html($outcomes[$event['outcome']]) . '</strong></td><td>' . esc_html($reasons[$event['reason']]) . '<br><small>' . esc_html($mode . ' · ' . sprintf(__('WooCommerce status: %s', 'mailchimp-for-woocommerce'), wc_get_order_status_name($event['order_status']))) . '</small></td></tr>';
        }
        echo '</tbody></table><p><small>' . esc_html(sprintf(__('Up to %1$d recent events. Times use the site timezone (%2$s). History starts when this feature is installed; it is not a live Mailchimp status check.', 'mailchimp-for-woocommerce'), self::MAX_ENTRIES, wp_timezone_string())) . '</small></p>';
    }
}
