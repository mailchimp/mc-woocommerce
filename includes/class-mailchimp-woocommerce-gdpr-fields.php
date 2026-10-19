<?php

/** Cache-only field reads for checkout; all remote work runs in the scheduler. */
class MailChimp_WooCommerce_GDPR_Fields {
    const HOOK = 'mailchimp_woocommerce_refresh_gdpr_fields';
    const GROUP = 'mc-woocommerce';

    /** Definitions are not the sampled audience member's consent. */
    private static function definitions($fields) {
        if (!is_array($fields)) { return null; }
        $definitions = array();
        foreach ($fields as $field) {
            if (!is_array($field) || empty($field['marketing_permission_id']) ||
                !is_string($field['marketing_permission_id']) || !isset($field['text']) || !is_string($field['text'])) {
                return null;
            }
            $definitions[] = array('marketing_permission_id' => $field['marketing_permission_id'], 'text' => $field['text'], 'enabled' => false);
        }
        return $definitions;
    }

    /** @return array|null Null means unknown, distinct from a successful empty response. */
    public static function get($list_id, $minutes = 5) {
        if (!is_string($list_id) || $list_id === '') { return null; }
        $key = "mailchimp-woocommerce-gdpr-fields.{$list_id}";
        $fields = self::definitions(get_transient($key));
        if ($fields !== null) {
            // Preserve a warm cache from versions before the durable fallback.
            if (get_option($key . '.last-good', null) !== $fields) {
                update_option($key . '.last-good', $fields, false);
            }
            return $fields;
        }
        if (!get_transient($key . '.retry')) {
            $args = array($list_id, max(1, (int) $minutes));
            try {
                if (function_exists('as_has_scheduled_action') && function_exists('as_enqueue_async_action')) {
                    if (!as_has_scheduled_action(self::HOOK, $args, self::GROUP)) {
                        as_enqueue_async_action(self::HOOK, $args, self::GROUP, true);
                    }
                } elseif (!wp_next_scheduled(self::HOOK, $args)) {
                    wp_schedule_single_event(time(), self::HOOK, $args);
                }
            } catch (Exception $e) {
                // Scheduling failure must not prevent checkout from rendering.
                mailchimp_error('gdpr.fields', 'Unable to schedule GDPR field refresh; retaining cached fields.');
            }
        }
        return self::definitions(get_option($key . '.last-good', null));
    }

    public static function refresh($list_id, $minutes = 5) {
        // Ignore jobs queued before disconnecting or switching audiences.
        if (!mailchimp_is_configured() || $list_id !== mailchimp_get_list_id()) { return; }
        $key = "mailchimp-woocommerce-gdpr-fields.{$list_id}";
        if (self::definitions(get_transient($key)) !== null || get_transient($key . '.retry')) { return; }
        set_transient($key . '.retry', true, 60);
        try {
            $fields = self::definitions(mailchimp_get_api()->getGDPRFields($list_id));
            if ($fields === null) { throw new UnexpectedValueException('Invalid GDPR field definitions'); }
            $filtered_minutes = (int) apply_filters('mailchimp_checkout_overwrite_gdpr_cache_minutes', $minutes);
            $ttl = 60 * max(1, $filtered_minutes > 0 ? $filtered_minutes : (int) $minutes);
            update_option($key . '.last-good', $fields, false);
            set_transient($key, $fields, $ttl);
            delete_transient($key . '.retry');
        } catch (Exception $e) {
            // Keep last-known-good definitions and rate-limit retries during an outage.
            set_transient($key . '.retry', true, 60);
            mailchimp_error('gdpr.fields', 'Unable to refresh GDPR field definitions; retaining cached fields.');
        }
    }
}
