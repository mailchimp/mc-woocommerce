<?php

// If this file is called directly, abort.
if (!defined( 'WPINC')) {
    die;
}

$mailchimp_woocommerce_spl_autoloader = true;

spl_autoload_register(function($class) {
    $classes = array(
        // helper classes
        'Mailchimp_Woocommerce_DB_Helpers' => 'includes/class-mailchimp-woocommerce-db-helpers.php',

        // includes root
        'MailChimp_Service' => 'includes/class-mailchimp-woocommerce-service.php',
        'MailChimp_WooCommerce_Options' => 'includes/class-mailchimp-woocommerce-options.php',
        'MailChimp_Newsletter' => 'includes/class-mailchimp-woocommerce-newsletter.php',
        'MailChimp_Sms_Consent' => 'includes/class-mailchimp-woocommerce-sms-consent.php',
        'MailChimp_WooCommerce_Loader' => 'includes/class-mailchimp-woocommerce-loader.php',
        'MailChimp_WooCommerce_i18n' => 'includes/class-mailchimp-woocommerce-i18n.php',
        'MailChimp_WooCommerce_Deactivator' => 'includes/class-mailchimp-woocommerce-deactivator.php',
        'MailChimp_WooCommerce_Activator' => 'includes/class-mailchimp-woocommerce-activator.php',
        'MailChimp_WooCommerce' => 'includes/class-mailchimp-woocommerce.php',
        'MailChimp_WooCommerce_Privacy' => 'includes/class-mailchimp-woocommerce-privacy.php',
        'Mailchimp_Woocommerce_Deactivation_Survey' => 'includes/class-mailchimp-woocommerce-deactivation-survey.php',
        'MailChimp_WooCommerce_Rest_Api' => 'includes/class-mailchimp-woocommerce-rest-api.php',
        'Mailchimp_Wocoomerce_CLI' => 'includes/class-mailchimp-woocommerce-cli.php',
        'MailChimp_WooCommerce_HPOS' => 'includes/class-mailchimp-woocommerce-hpos.php',
        'Mailchimp_Woocommerce_Block_Editor' => 'includes/class-mailchimp-woocommerce-block-editor.php',

        // includes/api/assets
        'MailChimp_WooCommerce_Address' => 'includes/api/assets/class-mailchimp-address.php',
        'MailChimp_WooCommerce_Cart' => 'includes/api/assets/class-mailchimp-cart.php',
        'MailChimp_WooCommerce_Customer' => 'includes/api/assets/class-mailchimp-customer.php',
        'MailChimp_WooCommerce_LineItem' => 'includes/api/assets/class-mailchimp-line-item.php',
        'MailChimp_WooCommerce_Order' => 'includes/api/assets/class-mailchimp-order.php',
        'Mailchimp_WooCommerce_Product_Category' => 'includes/api/assets/class-mailchimp-product-category.php',
        'MailChimp_WooCommerce_Product' => 'includes/api/assets/class-mailchimp-product.php',
        'MailChimp_WooCommerce_ProductVariation' => 'includes/api/assets/class-mailchimp-product-variation.php',
        'MailChimp_WooCommerce_PromoCode' => 'includes/api/assets/class-mailchimp-promo-code.php',
        'MailChimp_WooCommerce_PromoRule' => 'includes/api/assets/class-mailchimp-promo-rule.php',
        'MailChimp_WooCommerce_Store' => 'includes/api/assets/class-mailchimp-store.php',
        'MailChimp_WooCommerce_SmsProgram' => 'includes/api/assets/class-mailchimp-sms-program.php',

        // includes/api/errors
        'MailChimp_WooCommerce_Error' => 'includes/api/errors/class-mailchimp-error.php',
        'MailChimp_WooCommerce_RateLimitError' => 'includes/api/errors/class-mailchimp-rate-limit-error.php',
        'MailChimp_WooCommerce_ServerError' => 'includes/api/errors/class-mailchimp-server-error.php',

        // includes/api/helpers
        'MailChimp_WooCommerce_CurrencyCodes' => 'includes/api/helpers/class-mailchimp-woocommerce-api-currency-codes.php',
        'MailChimp_Api_Locales' => 'includes/api/helpers/class-mailchimp-woocommerce-api-locales.php',

        // includes/api
        'MailChimp_WooCommerce_MailChimpApi' => 'includes/api/class-mailchimp-api.php',
        'MailChimp_WooCommerce_Api' => 'includes/api/class-mailchimp-woocommerce-api.php',
        'MailChimp_WooCommerce_CreateListSubmission' => 'includes/api/class-mailchimp-woocommerce-create-list-submission.php',
        'MailChimp_WooCommerce_Transform_Customers' => 'includes/api/class-mailchimp-woocommerce-transform-customers.php',
        'MailChimp_WooCommerce_Transform_Coupons' => 'includes/api/class-mailchimp-woocommerce-transform-coupons.php',
        'MailChimp_WooCommerce_Transform_Orders' => 'includes/api/class-mailchimp-woocommerce-transform-orders-wc3.php',
        'MailChimp_WooCommerce_Transform_Products' => 'includes/api/class-mailchimp-woocommerce-transform-products.php',
        'MailChimp_WooCommerce_Transform_Product_Categories' => 'includes/api/class-mailchimp-woocommerce-transform-product-categories.php',

        // includes/processes
        'Mailchimp_Woocommerce_Job' => 'includes/processes/class-mailchimp-woocommerce-job.php',
        'MailChimp_WooCommerce_Abstract_Sync' => 'includes/processes/class-mailchimp-woocommerce-abstract-sync.php',
        'MailChimp_WooCommerce_Cart_Update' => 'includes/processes/class-mailchimp-woocommerce-cart-update.php',
        'MailChimp_WooCommerce_Process_Customers' => 'includes/processes/class-mailchimp-woocommerce-process-customers.php',
        'MailChimp_WooCommerce_Process_Coupons' => 'includes/processes/class-mailchimp-woocommerce-process-coupons.php',
        'MailChimp_WooCommerce_Process_Orders' => 'includes/processes/class-mailchimp-woocommerce-process-orders.php',
        'MailChimp_WooCommerce_Process_Products' => 'includes/processes/class-mailchimp-woocommerce-process-products.php',
        'MailChimp_WooCommerce_Process_Product_Categories' => 'includes/processes/class-mailchimp-woocommerce-process-product-categories.php',

        'MailChimp_WooCommerce_SingleCoupon' => 'includes/processes/class-mailchimp-woocommerce-single-coupon.php',
        'MailChimp_Woocommerce_Single_Customer' => 'includes/processes/class-mailchimp-woocommerce-single-customer.php',
        'MailChimp_WooCommerce_Single_Order' => 'includes/processes/class-mailchimp-woocommerce-single-order.php',
        'MailChimp_WooCommerce_Single_Product' => 'includes/processes/class-mailchimp-woocommerce-single-product.php',
        'MailChimp_WooCommerce_Single_Product_Variation' => 'includes/processes/class-mailchimp-woocommerce-single-product-variation.php',
        'Mailchimp_WooCommerce_Single_Product_Category' => 'includes/processes/class-mailchimp-woocommerce-single-product-category.php',
        'MailChimp_WooCommerce_User_Submit' => 'includes/processes/class-mailchimp-woocommerce-user-submit.php',

        'MailChimp_WooCommerce_Process_Full_Sync_Manager' => 'includes/processes/class-mailchimp-woocommerce-full-sync-manager.php',
        'MailChimp_WooCommerce_Subscriber_Sync' => 'includes/processes/class-mailchimp-woocommerce-subscriber-sync.php',
        'MailChimp_WooCommerce_WebHooks_Sync' => 'includes/processes/class-mailchimp-woocommerce-webhooks-sync.php',
        'Mailchimp_Woocommerce_Complete_Resource_Sync' => 'includes/processes/class-mailchimp-woocommerce-complete-resource-sync.php',

        'MailChimp_WooCommerce_Public' => 'public/class-mailchimp-woocommerce-public.php',
        'MailChimp_WooCommerce_Admin' => 'admin/class-mailchimp-woocommerce-admin.php',
        'Mailchimp_Woocommerce_Event' => 'admin/v2/processes/class-mailchimp-woocommerce-event.php',

        'MailChimp_WooCommerce_Fix_Duplicate_Store' => 'includes/api/class-mailchimp-woocommerce-fix-duplicate-store.php',
        'MailChimp_WooCommerce_Logs' => 'includes/api/class-mailchimp-woocommerce-logs.php',
        'MailChimp_WooCommerce_Tower' => 'includes/api/class-mailchimp-woocommerce-tower.php',
        'MailChimp_WooCommerce_Log_Viewer' => 'includes/api/class-mailchimp-woocommerce-log-viewer.php',
        'MailChimp_WooCommerce_Enhanced_Logger' => 'includes/class-mailchimp-woocommerce-enhanced-logger.php',

        // pixel tracking
        'MailChimp_WooCommerce_Pixel_Tracking' => 'includes/tracking/class-mailchimp-woocommerce-pixel-tracking.php',
    );

    // if the file exists, require it
    $path = plugin_dir_path( __FILE__ );
    if (array_key_exists($class, $classes) && file_exists($path.$classes[$class])) {
        require $path.$classes[$class];
    }
});

/**
 * Build (and cache per request) the environment-variables snapshot. This
 * function gets called from dozens of logging / version / config sites per
 * request — caching eliminates repeated option lookups (especially for
 * 'mailchimp-woocommerce-sync.initial_sync' whose row may not exist, which
 * would otherwise cost a SELECT per call).
 *
 * Pass $refresh = true to force a rebuild (e.g. after saving settings in an
 * admin handler that needs to see the new value within the same request).
 *
 * @param bool $refresh
 * @return object
 */
function mailchimp_environment_variables($refresh = false) {
    static $cached = null;

    if ($refresh) {
        $cached = null;
    }
    if ($cached !== null) {
        return $cached;
    }

    global $wp_version;
    $o = mailchimp_get_admin_options();

    $cached = (object) array(
        'repo' => 'master',
        'environment' => 'production', // staging or production
        'version' => '6.1',
        'php_version' => phpversion(),
        'wp_version' => (empty($wp_version) ? 'Unknown' : $wp_version),
        'wc_version' => function_exists('WC') ? WC()->version : null,
        'logging' => ($o && is_array($o) && isset($o['mailchimp_logging'])) ? $o['mailchimp_logging'] : 'standard',
        'initial_sync' => \Mailchimp_Woocommerce_DB_Helpers::get_option("mailchimp-woocommerce-sync.initial_sync", false)
    );

    return $cached;
}

/**
 * Option keys that are read on virtually every WP request that loads this
 * plugin — not just admin. These are the options touched by update_db_check,
 * update_plugin_check, mailchimp_environment_variables, and other early
 * code paths that fire between plugins_loaded @ 12 and admin_init.
 *
 * This set is preloaded on plugins_loaded @ priority 1 so every subsequent
 * read in the same request hits the in-memory cache.
 */
function mailchimp_preload_early_keys() {
    return array(
        'mailchimp-woocommerce',                                 // main options blob
        'mailchimp_woocommerce_version',                         // version compare
        'mailchimp-woocommerce-sync.initial_sync',               // mailchimp_environment_variables
        'mailchimp-woocommerce_cart_table_add_index_update',     // update_db_check flag
        'mailchimp-woocommerce_woo_currency_update',             // update_db_check flag
        'mailchimp_woocommerce_db_mailchimp_carts',              // MailChimp_Service::wooIsRunning (fires on woocommerce_init)
    );
}

/**
 * Runs on plugins_loaded @ 1 — earlier than the plugin's own @ 12 bootstrap.
 * By the time update_db_check / update_plugin_check /
 * mailchimp_environment_variables execute, their lookups are warm in the
 * helper's per-request cache.
 *
 * Fires on every request (admin + public) because the early code path runs
 * either way. Cost is one SELECT ... IN (...) per request.
 */
function mailchimp_preload_early() {
    if (!class_exists('\\Mailchimp_Woocommerce_DB_Helpers')) return;

    /**
     * Filter the early preload list for very-early hooks (before admin_init).
     *
     * @param array $keys
     */
    $keys = apply_filters('mailchimp_preload_early_options', mailchimp_preload_early_keys());

    if (!empty($keys)) {
        \Mailchimp_Woocommerce_DB_Helpers::preload((array) $keys);
    }
}
add_action('plugins_loaded', 'mailchimp_preload_early', 1);

/**
 * Baseline option keys that admin-only code paths read. The early set is
 * preloaded separately on plugins_loaded @ 1; the helper dedupes, so listing
 * them again here is harmless but unnecessary.
 */
function mailchimp_admin_preload_base_keys() {
    return array(
        'mailchimp-woocommerce-store_id',
        'mailchimp-woocommerce-mailchimp_login_id',
        'mailchimp-woocommerce-mailchimp_user_id',
        'mailchimp-woocommerce-saved_user_id',
        'mailchimp-woocommerce-account_name',
        'mailchimp-woocommerce-comm.opt',
        'mailchimp-woocommerce-sync.started_at',
        'mailchimp-woocommerce-sync.completed_at',
        'mailchimp_woocommerce_db_mailchimp_carts',
        'mailchimp-woocommerce-SERVER_ADDR',
        'mailchimp_woocommerce_plugin_do_activation_redirect',
        'mailchimp-woocommerce-waiting-for-login',
        'mailchimp-woocommerce-resource-last-updated',
    );
}

/**
 * Per-tab option keys on the Mailchimp settings page. The main page uses
 * ?tab=<id> to switch between Overview/Store/Audience/Logs/Advanced, so
 * different tabs read different option sets. Keys here are additive to the
 * base and screen lists.
 */
function mailchimp_admin_preload_keys_for_tab($tab) {
    $map = array(
        'store_info' => array(
            // Read by the SMS consent block on the Store tab.
            'mailchimp-woocommerce-tower.opt',
        ),
        'audience' => array(
            'mailchimp-woocommerce-cached-api-lists',
        ),
        'advanced' => array(
            'mailchimp-woocommerce-tower.opt',
            'mailchimp-woocommerce-SERVER_ADDR',
        ),
    );
    return isset( $map[ $tab ] ) ? $map[ $tab ] : array();
}

/**
 * Per-screen option keys. Add entries here (or via the filter) when a
 * specific admin page is known to read a batch of options that aren't in the
 * base list. Keys are additive — the base set is always included.
 */
function mailchimp_admin_preload_keys_for_screen($screen_id) {
    $map = array(
        // Main Mailchimp settings page
        'woocommerce_page_mailchimp-woocommerce' => array(
            'mailchimp-woocommerce-cached-api-lists',
            'mailchimp-woocommerce-cached-api-ping-check',
            'mailchimp-woocommerce-waiting-for-login',
            'mailchimp-woocommerce-tower.opt',
            'mailchimp-woocommerce-errors.store_info',
            'mailchimp-woocommerce-validation.api.ping',
        ),
        // WooCommerce dashboard — reads just enough to render the plugin's widgets
        'dashboard' => array(
            'mailchimp-woocommerce-sync.orders.started_at',
            'mailchimp-woocommerce-sync.orders-queueing.completed_at',
            'mailchimp-woocommerce-sync.products.started_at',
            'mailchimp-woocommerce-sync.products-queueing.completed_at',
        ),
    );

    return isset($map[$screen_id]) ? $map[$screen_id] : array();
}

/**
 * Transient keys (WITHOUT the _transient_ prefix) that the admin side reads
 * on a typical request. Preloading these collapses N × 2 wp_options SELECTs
 * down to one batched SELECT per request, and primes WP core's options
 * cache so subsequent native get_transient() calls hit memory with no DB
 * activity — even on sites without Redis.
 *
 * Keys that include a list_id are appended in mailchimp_preload_for_admin()
 * once the list_id is resolvable (options cache is warm by that point).
 */
function mailchimp_admin_preload_transient_keys() {
    return array(
        'mailchimp-woocommerce-cached-api-ping-check',   // 10min TTL
        'mailchimp-woocommerce-cached-api-account-name', // 24h TTL
        'mailchimp-woocommerce-cached-api-lists',        // 1h TTL
        'mailchimp_woocommerce_store_id_verified',       // 10min TTL
    );
}

/**
 * Is this request headed for a Mailchimp plugin admin page or one of our
 * AJAX actions? Returns false for every other admin page (post editor,
 * users, other plugins' settings, etc.) so we don't burn a DB query
 * preloading options nobody on that page will read.
 */
function mailchimp_is_plugin_admin_context() {
    if ( ! is_admin() ) {
        return false;
    }

    // AJAX: only preload if the action is one of ours. Browsing other
    // plugins' AJAX actions shouldn't pull in our options.
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        $action = '';
        if ( isset( $_REQUEST['action'] ) && is_string( $_REQUEST['action'] ) ) {
            $action = $_REQUEST['action'];
        }
        return $action !== ''
            && ( strpos( $action, 'mailchimp' ) === 0
              || strpos( $action, 'mc_woocommerce' ) === 0
              || strpos( $action, 'mailchimp_woocommerce' ) === 0 );
    }

    // Regular admin requests — ?page= is set before admin_init fires.
    if ( isset( $_GET['page'] ) && is_string( $_GET['page'] )
        && strpos( $_GET['page'], 'mailchimp-woocommerce' ) !== false ) {
        return true;
    }

    // Fallback via screen id (populated once admin_init has run for real
    // menu pages, catches WooCommerce-subscreen variants).
    if ( function_exists( 'get_current_screen' ) ) {
        $screen = get_current_screen();
        if ( $screen && isset( $screen->id )
            && strpos( $screen->id, 'mailchimp-woocommerce' ) !== false ) {
            return true;
        }
    }

    return false;
}

/**
 * Single entry point for warming the option cache on admin requests. Hooked
 * on admin_init (so get_current_screen() is reliable) and safe to no-op when
 * the helper isn't available or this isn't a Mailchimp-related admin view.
 */
function mailchimp_preload_for_admin() {
    if ( ! mailchimp_is_plugin_admin_context() ) return;
    if ( ! class_exists( '\\Mailchimp_Woocommerce_DB_Helpers' ) ) return;

    $screen_id = function_exists('get_current_screen') && get_current_screen()
        ? get_current_screen()->id
        : null;

    // ?tab= is the plugin's own sub-page routing, so we can preload keys
    // specific to whichever tab is being rendered.
    $tab = isset( $_GET['tab'] ) && is_string( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : '';

    $keys = array_merge(
        mailchimp_admin_preload_base_keys(),
        mailchimp_admin_preload_keys_for_screen($screen_id),
        $tab !== '' ? mailchimp_admin_preload_keys_for_tab($tab) : array()
    );

    /**
     * Third-party / internal extension point. Filter callbacks receive the
     * merged key list and the current screen id, can add/remove/inspect.
     *
     * @param array       $keys      option names to preload
     * @param string|null $screen_id current admin screen id (may be null early in admin_init)
     */
    $keys = apply_filters('mailchimp_preload_options', $keys, $screen_id);

    if (!empty($keys)) {
        \Mailchimp_Woocommerce_DB_Helpers::preload((array) $keys);
    }

    // Warm transient caches in a second batched query. Adding list_id-scoped
    // transient keys once options are warm (so mailchimp_get_list_id()
    // doesn't trigger its own DB read).
    $transient_keys = mailchimp_admin_preload_transient_keys();

    if ( function_exists( 'mailchimp_get_list_id' ) && ( $list_id = mailchimp_get_list_id() ) ) {
        $transient_keys[] = "mailchimp-woocommerce-gdpr-fields.{$list_id}";
        $transient_keys[] = "mailchimp_sms_program_{$list_id}";
        $transient_keys[] = "mailchimp_sms_status_{$list_id}";
    }

    /**
     * Filter the admin transient preload list.
     *
     * @param array       $transient_keys
     * @param string|null $screen_id
     */
    $transient_keys = apply_filters('mailchimp_preload_transients', $transient_keys, $screen_id);

    if (!empty($transient_keys)) {
        \Mailchimp_Woocommerce_DB_Helpers::preload_transients((array) $transient_keys);
    }
}
add_action('admin_init', 'mailchimp_preload_for_admin', 1);

/**
 * Public/frontend preload. Fires on wp hook (after query parsing, before
 * template rendering — well before wp_footer). Only the options the public
 * side of the plugin actually reads during a page render.
 */
function mailchimp_preload_for_public() {
    if (is_admin()) return;
    if (!class_exists('\\Mailchimp_Woocommerce_DB_Helpers')) return;

    $keys = array(
        'mailchimp-woocommerce-code-snippet',
        'mailchimp-woocommerce-script_fragment',
    );

    /**
     * Filter the public preload list.
     *
     * @param array $keys
     */
    $keys = apply_filters('mailchimp_preload_public_options', $keys);

    if (!empty($keys)) {
        \Mailchimp_Woocommerce_DB_Helpers::preload((array) $keys);
    }
}
add_action('wp', 'mailchimp_preload_for_public', 1);

/**
 * Is this REST request headed for one of our routes? Matches URI against
 * our namespace so preloads don't fire for WooCommerce, WP core, or other
 * plugins' REST endpoints.
 */
function mailchimp_is_plugin_rest_context() {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
    if ( $uri === '' ) {
        return false;
    }
    // Matches /wp-json/mailchimp-for-woocommerce/v1/... as well as
    // ?rest_route=/mailchimp-for-woocommerce/v1/... (permalink-off sites).
    return strpos( $uri, '/mailchimp-for-woocommerce/' ) !== false;
}

/**
 * REST API requests (wp-json/...) don't fire admin_init, so the admin
 * preload never runs. Scoped to our REST namespace only — other plugins'
 * REST endpoints shouldn't trigger our preload work.
 */
function mailchimp_preload_for_rest() {
    if ( ! mailchimp_is_plugin_rest_context() ) return;
    if ( ! class_exists( '\\Mailchimp_Woocommerce_DB_Helpers' ) ) return;

    $keys = array(
        'mailchimp-woocommerce-store_id',
        'mailchimp-woocommerce-store-id-last-verified',
        'mailchimp-woocommerce-sync.internal_counter',
        'mailchimp-woocommerce-sync.orders.count',
        'mailchimp-woocommerce-sync.products.count',
        'mailchimp-woocommerce-sync.customers.count',
        'mailchimp-woocommerce-sync.coupons.count',
        'mailchimp-woocommerce-sync.started_at',
        'mailchimp-woocommerce-sync.completed_at',
        'mailchimp-woocommerce-sync.last_loop_at',
        'mailchimp_woocommerce_db_mailchimp_carts',
    );

    /**
     * Filter the REST preload list.
     *
     * @param array $keys
     */
    $keys = apply_filters('mailchimp_preload_rest_options', $keys);

    if (!empty($keys)) {
        \Mailchimp_Woocommerce_DB_Helpers::preload((array) $keys);
    }

    // Transients that REST endpoints commonly read (store-id freshness,
    // ping-check). Same rationale as the admin preload — cheap batched
    // fetch that eliminates per-read wp_options I/O on sites without
    // Redis.
    $rest_transient_keys = apply_filters('mailchimp_preload_rest_transients', array(
        'mailchimp_woocommerce_store_id_verified',
        'mailchimp-woocommerce-cached-api-ping-check',
    ));

    if (!empty($rest_transient_keys)) {
        \Mailchimp_Woocommerce_DB_Helpers::preload_transients((array) $rest_transient_keys);
    }
}
add_action('rest_api_init', 'mailchimp_preload_for_rest', 1);

/**
 * @param Mailchimp_Woocommerce_Job $job
 * @param int $delay
 *
 * @return false|int|string
 */
function mailchimp_as_push( Mailchimp_Woocommerce_Job $job, $delay = 0 ) {			
    global $wpdb;
    $current_page = isset($job->current_page) && $job->current_page >= 0 ? $job->current_page : false;
    $job_id = isset($job->id) ? $job->id : ($current_page ? $job->current_page : get_class($job));
    $message = ($job_id != get_class($job)) ? ' :: '. (isset($job->current_page) ? 'page ' : 'obj_id ') . $job_id : '';
    $attempts = $job->get_attempts() > 0 ? ' attempt:' . $job->get_attempts() : '';

    if ($job->get_attempts() <= 5) {
        $job_class = get_class($job);

        $args = array(
            'job' => maybe_serialize($job),
            'obj_id' => $job_id,
            'created_at'   => gmdate( 'Y-m-d H:i:s', time() )
        );
        
        $existing_actions =  function_exists('as_get_scheduled_actions') ? as_get_scheduled_actions(array(
            'hook' => $job_class,
            'status' => ActionScheduler_Store::STATUS_PENDING,
            'group' => 'mc-woocommerce',
            'args' => array(
                'obj_id' => isset($job->id) ? $job->id : null), 
            )
        ) : null;

        if (!empty($existing_actions)) {
            try {
                as_unschedule_action($job_class, array('obj_id' => $job->id), 'mc-woocommerce');

                $table = $wpdb->prefix . "mailchimp_jobs";
                $serialized_job = maybe_serialize($job);
                $created_at = gmdate('Y-m-d H:i:s');

                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE $table 
                             SET job = %s, created_at = %s 
                             WHERE obj_id = %s AND job LIKE %s",
                        $serialized_job,
                        $created_at,
                        $job->id,
                        '%' . $job_class . '%'
                    )
                );
            } catch (Exception $e) {
            }
        } else {
            $inserted = $wpdb->insert($wpdb->prefix."mailchimp_jobs", $args);
            if (!$inserted) {
                if ($wpdb->last_error) {
                    mailchimp_debug('database error on mailchimp_jobs insert', $wpdb->last_error);
                }
                try {
                    if (mailchimp_string_contains($wpdb->last_error, 'Table')) {
                        mailchimp_debug('DB Issue: `mailchimp_job` table was not found!', 'Creating Tables');
                        install_mailchimp_queue();
                        $inserted = $wpdb->insert($wpdb->prefix."mailchimp_jobs", $args);
                        if (!$inserted) {
                            mailchimp_debug('Queue Job '.get_class($job), $wpdb->last_error);
                        }
                    }
                } catch (Exception $e) {
                    mailchimp_error_trace($e, 'trying to create queue tables');
                }
            }
        }
        
        $action_args = array(
            'obj_id' => $job_id,
        );

        // create the action to be handled in X seconds ( default time )
        $fire_at = strtotime( '+'.$delay.' seconds' );
        // if we have a prepend command, that means it's live traffic, put it to the front of the sync process.
        if (isset($job->prepend_to_queue) && $job->prepend_to_queue) {
            $sync_started_at = (int) \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-sync.started_at');
            if ($sync_started_at > 0) {
                $fire_at = $sync_started_at;
                mailchimp_debug('action_scheduler. '.get_class($job), "Pushed job {$job_id} to the front of the queue for live traffic");
            }
        }

        $action = as_schedule_single_action( $fire_at, get_class($job), $action_args, "mc-woocommerce");
      
        if (!empty($existing_actions)) {
            mailchimp_debug('action_scheduler.reschedule_job', get_class($job) . ($delay > 0 ? ' restarts in '.$delay. ' seconds' : ' re-queued' ) . $message . $attempts);
        } else if (!empty($action)) {
            mailchimp_log('action_scheduler.queue_job', get_class($job) . ($delay > 0 ? ' starts in '.$delay. ' seconds' : ' queued' ) . $message . $attempts." with id {$action}");
        } else {
            mailchimp_debug("action_scheduler.queue_job.fail", get_class($job). " :: no action id was saved while trying to schedule action!");
        }
    
        return $action;	
    } else {
        $job->set_attempts(0);
        mailchimp_log('action_scheduler.fail_job', get_class($job) . ' cancelled. Too many attempts' . $message . $attempts);
        return false;
    }
}


/**
 * We will allow people to filter delay value to specific jobs.
 * add_filter( 'mailchimp_handle_or_queue_{$resource}_delay', 'custom_handle_or_queue_resource_function', 10, 1 );
 * where $resource is one of the following - product, order, customer, coupon
 *
 * @param Mailchimp_Woocommerce_Job $job
 * @param int $delay
 */
function mailchimp_handle_or_queue(Mailchimp_Woocommerce_Job $job, $delay = 0)
{
    if ($job instanceof MailChimp_WooCommerce_Single_Order && isset($job->id) && empty($job->gdpr_fields)) {
        // if this is a order process already queued - just skip this
        if (get_transient("mailchimp_order_being_processed_{$job->id}") == true) {
            mailchimp_debug('queue', "Not queuing up order {$job->id} because it's already queued");
            return;
        }
        // tell the system the order is already queued for processing in this saving process - and we don't need to process it again.
        set_transient( "mailchimp_order_being_processed_{$job->id}", true, 30);
    }
	// Allow sites to alter whether the order or product is synced.
	// $job should contain at least the ID of the order/product as $job->id.
    $filter_delay = null;

    if ( $job instanceof \MailChimp_WooCommerce_Single_Order ) {
        $filter_delay = apply_filters('mailchimp_handle_or_queue_order_delay', $delay);

        if ( apply_filters( 'mailchimp_should_push_order', $job->id ) === false ) {
			mailchimp_debug( 'action_scheduler.queue_job.order', "Order {$job->id} not pushed do to filter." );
			return null;
		}
	} else if ( $job instanceof \MailChimp_WooCommerce_Single_Product ) {
        $filter_delay = apply_filters('mailchimp_handle_or_queue_product_delay', $delay);

        if ( apply_filters( 'mailchimp_should_push_product', $job->id ) === false ) {
			mailchimp_debug( 'action_scheduler.queue_job.product', "Product {$job->id} not pushed do to filter." );
			return null;
		}
	} else if ( $job instanceof \MailChimp_WooCommerce_Single_Product_Variation ) {
		$filter_delay = apply_filters('mailchimp_handle_or_queue_product_variation_delay', $delay);

		if ( apply_filters( 'mailchimp_should_push_product_variations', $job->id ) === false ) {
			mailchimp_debug( 'action_scheduler.queue_job.product_variation', "Product {$job->id} not pushed do to filter." );
			return null;
		}
	} else if ( $job instanceof \MailChimp_WooCommerce_User_Submit ) {
        $filter_delay = apply_filters('mailchimp_handle_or_queue_customer_delay', $delay);
    } else if ( $job instanceof \MailChimp_WooCommerce_SingleCoupon ) {
        $filter_delay = apply_filters('mailchimp_handle_or_queue_coupon_delay', $delay);

        if ( apply_filters( 'mailchimp_should_push_coupon', $job->id ) === false ) {
            mailchimp_debug( 'action_scheduler.queue_job.order', "Coupon {$job->id} not pushed do to filter." );
            return null;
        }
    }

    $filter_delay = !is_null($filter_delay) && is_int($filter_delay) ? $filter_delay : $delay;
    $as_job_id = mailchimp_as_push($job, $filter_delay);
    
    if (!is_int($as_job_id)) {
        mailchimp_log('action_scheduler.queue_fail', get_class($job) .' FAILED :: as_job_id: '.$as_job_id);
    }
}

/**
 * @param $job_hook
 *
 * @return int
 */
function mailchimp_get_remaining_jobs_count($job_hook) {
    $existing_actions =  function_exists('as_get_scheduled_actions') ? as_get_scheduled_actions(
        array(
            'hook' => $job_hook, 
            'status' => ActionScheduler_Store::STATUS_PENDING,  
            'group' => 'mc-woocommerce', 
            'per_page' => -1,
        ), 'ids'
    ) : null;
    // mailchimp_log('sync.full_sync_manager.queue', "counting {$job_hook} actions:", array($existing_actions));		
    return count($existing_actions);
}

function mailchimp_submit_subscribed_only() {
    return ! (bool) mailchimp_get_option('mailchimp_ongoing_sync_status', '1');
}

function get_woo_session($key, $default = null)
{
    if (!function_exists('WC')) return $default;

    if (!($woo = WC()) || empty(WC()->session)) {
        return $default;
    }

    // not really sure why this would be the case, but if there is no session we can't get it anyway.
    if (!is_object($woo->session) || !method_exists($woo->session, 'get')) {
        return $default;
    }

    return $woo->session->get($key, $default);
}

/**
 * @return bool
 */
function mailchimp_sync_existing_contacts_only() {
    return mailchimp_get_option('mailchimp_auto_subscribe', '1') === '2';
}

/**
 * @return bool
 */
function mailchimp_carts_disabled() {
    return mailchimp_get_option('mailchimp_cart_tracking', 'all') === 'disabled';
}

/**
 * @return bool
 */
function mailchimp_carts_subscribers_only() {
    return mailchimp_get_option('mailchimp_cart_tracking', 'all') === 'subscribed';
}

/**
 * @return bool
 */
function mailchimp_landing_tracking_enabled() {
    return (bool) mailchimp_get_option('mailchimp-woocommerce-enable-landing-tracking', false);
}

/**
 * @param $email
 * @return string|null
 */
function mailchimp_get_subscriber_status($email) {
    try {
        return mailchimp_get_api()->member(mailchimp_get_list_id(), $email)['status'];
    } catch (Exception $e) {
        return null;
    }
}

/**
 * @param false $force
 *
 * @return bool
 * @throws MailChimp_WooCommerce_Error
 * @throws MailChimp_WooCommerce_RateLimitError
 * @throws MailChimp_WooCommerce_ServerError
 */
function mailchimp_list_has_double_optin($force = false) {
    if (!mailchimp_is_configured()) {
        return false;
    }

    $key = 'double_optin';

    $double_optin = mailchimp_get_transient($key);

    if (!$force && (isset($double_optin['value']) && ($double_optin['value'] === 'yes' || $double_optin['value'] === 'no'))) {
        return $double_optin['value'] === 'yes';
    }

    try {
        $data = mailchimp_get_api()->getList(mailchimp_get_list_id());
        $double_optin = array_key_exists('double_optin', $data) ? ($data['double_optin'] ? 'yes' : 'no') : 'no';
        mailchimp_set_transient($key, $double_optin, 600);
        mailchimp_debug('mailchimp.doi', 'pulled the list again');
        return $double_optin === 'yes';
    } catch (Exception $e) {
        mailchimp_error('api.list', __('Error retrieving list for double_optin check', 'mailchimp-for-woocommerce'));
        throw $e;
    }
}


/**
 * @return bool
 */
function mailchimp_is_configured() {
    return (bool) (mailchimp_get_api_key() && mailchimp_get_list_id());
}

/**
 * @return bool
 */
function mailchimp_action_scheduler_exists() {
    return ( did_action( 'plugins_loaded' ) && ! doing_action( 'plugins_loaded' ) && class_exists( 'ActionScheduler', false ) );
}

/**
 * @return bool|int
 */
function mailchimp_get_api_key() {
    return mailchimp_get_option('mailchimp_api_key', false);
}

/**
 * @return bool|int
 */
function mailchimp_get_list_id() {
    return mailchimp_get_option('mailchimp_list', false);
}

/**
 * @param $key
 *
 * @return string
 */
function mailchimp_build_webhook_url( $key ) {
	$rest_url = MailChimp_WooCommerce_Rest_Api::url('member-sync');
	$qs = mailchimp_string_contains($rest_url, '/wp-json/') ? '?' : '&';
    return $rest_url.$qs."auth={$key}";
}

/**
 * Generate random string
 * @return string
 */
function mailchimp_create_webhook_token(){
    return md5( trim( strtolower(get_bloginfo('url') . '|' . time() . '|' . mailchimp_get_list_id() . '|' . wp_salt() )  ) );
}
/**
 * @param $url
 */
function mailchimp_set_webhook_url( $url ) {
    \Mailchimp_Woocommerce_DB_Helpers::update_option('mc-mailchimp_webhook_url', $url);
}
/**
 * Returns webhookurl option
 * @return string
 */
function mailchimp_get_webhook_url() {
    return \Mailchimp_Woocommerce_DB_Helpers::get_option('mc-mailchimp_webhook_url', false);
}
/**
 * Returns webhook url
 * @return array Common localhost ips
 */
function mailchimp_common_loopback_ips(){
    return array(
	    '127.0.0.1',
	    '0:0:0:0:0:0:0:1',
	    '::1'
    );
}

/**
 * @return mixed|string
 */
function mailchimp_get_store_id() {
    $store_id = mailchimp_get_data('store_id', false);

    // "Was this store_id verified against Mailchimp recently?" — tracked as a
    // transient with a 10-minute TTL instead of a timestamp option that we
    // manually subtract against time(). With an object cache (Redis), the
    // read and the write are zero wp_options queries; without one, they're
    // still lighter than the old get_option + compare + update_option dance
    // and self-expire so there's no timestamp row accumulating stale values.
    $verified_transient = 'mailchimp_woocommerce_store_id_verified';

    if (!empty($store_id) && get_transient($verified_transient)) {
        return $store_id;
    }

    $api = mailchimp_get_api();
    if (mailchimp_is_configured()) {
        // let's retrieve the store for this domain, through the API
        $store = $api->getStoreIfAvailable($store_id);
        // if there's no store, try to fetch from mc a store related to the current domain
        if (!$store) {
            $stores = $api->stores();
            if (!empty($stores)) {
                //iterate thru stores, find correct store ID and save it to db
                foreach ($stores as $mc_store) {
                    if ($mc_store->getDomain() === get_option('siteurl')) {
                        // Route through the helper so our in-process caches
                        // and object-cache invalidations stay consistent.
                        \Mailchimp_Woocommerce_DB_Helpers::update_option('mailchimp-woocommerce-store_id', $mc_store->getId(), 'yes');
                        $store_id = $mc_store->getId();
                    }
                }
            }
        }
    }

    if (empty($store_id)) {
        mailchimp_set_data('store_id', $store_id = uniqid());
    }

    // Mark this store_id as verified for the next 10 minutes.
    set_transient($verified_transient, 1, 600);

    return $store_id;
}

/**
 * @param null $email
 * @param null $order
 *
 * @return false|mixed|void
 */
function mailchimp_get_user_tags_to_update($email = null, $order = null) {
    $tags = mailchimp_get_option('mailchimp_user_tags');
    $formatted_tags = array();
    
    if (!empty($tags)) {
        $tags = explode(',', $tags);

        foreach ($tags as $tag) {
            $formatted_tags[] = array("name" => $tag, "status" => 'active');
        }
    }

    // apply filter to user custom tags addition/removal
    $formatted_tags = apply_filters('mailchimp_user_tags', $formatted_tags, $email, $order);

    return empty($formatted_tags) ? false : $formatted_tags;
}

/**
 * @return bool|MailChimp_WooCommerce_MailChimpApi
 */
function mailchimp_get_api() {

    if (($api = MailChimp_WooCommerce_MailChimpApi::getInstance())) {
        return $api;
    }

    if (($key = mailchimp_get_api_key())) {
        return MailChimp_WooCommerce_MailChimpApi::constructInstance($key);
    }

    return false;
}

/**
 * @param $key
 * @param null $default
 * @return null
 */
function mailchimp_get_option($key, $default = null) {
    $options = mailchimp_get_admin_options();

    if (!is_array($options)) {
        return $default;
    }
    if (!array_key_exists($key, $options)) {
        return $default;
    }
    return $options[$key];
}

/**
 * @param $default
 * @return false
 */
function mailchimp_get_admin_options($default = array()) {
    if (wp_using_ext_object_cache()) {
        return \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce', $default);
    }

    $options = wp_cache_get('mailchimp-woocommerce-options', 'mailchimp-woocommerce');

    if (!$options) {
        $options =\Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce', $default);

        wp_cache_set('mailchimp-woocommerce-options', $options, 'mailchimp-woocommerce', 10);
    }

    return $options;
}

/**
 * @param $key
 * @param null $default
 * @return mixed
 */
function mailchimp_get_data($key, $default = null) {
    return \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-'.$key, $default);
}

/**
 * @param $key
 * @param $value
 * @param string $autoload
 * @return bool
 */
function mailchimp_set_data($key, $value, $autoload = 'yes') {
    return \Mailchimp_Woocommerce_DB_Helpers::update_option('mailchimp-woocommerce-'.$key, $value, $autoload);
}

/**
 * @param $date
 *
 * @return DateTime
 * @throws Exception
 */
function mailchimp_date_utc($date) {
    $timezone = wc_timezone_string();
    if (is_numeric($date)) {
        $stamp = $date;
        $date = new DateTime('now', new DateTimeZone($timezone));
        $date->setTimestamp($stamp);
    } else {
        $date = new DateTime($date, new DateTimeZone($timezone));
    }

    $date->setTimezone(new DateTimeZone('UTC'));
    return $date;
}

/**
 * @param $date
 *
 * @return DateTime|false
 */
function mailchimp_date_local($date) {
    try {
	    $timezone = str_replace(':', '', mailchimp_get_timezone());

	    if (is_numeric($date)) {
		    $stamp = $date;
		    $date = new DateTime('now', new DateTimeZone('UTC'));
		    $date->setTimestamp($stamp);
	    } else {
		    $date = new DateTime($date, new DateTimeZone('UTC'));
	    }

	    $date->setTimezone(new DateTimeZone($timezone));
	    return $date;
    } catch (Exception $e) {
    	return false;
    }
}

/**
 * @param $data
 *
 * @return array
 */
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

/**
 * @return array
 */
function mailchimp_get_timezone_list() {
    $zones_array = array();
    $timestamp = time();
    $current = date_default_timezone_get();

    foreach(timezone_identifiers_list() as $key => $zone) {
        date_default_timezone_set($zone);
        $zones_array[$key]['zone'] = $zone;
        $zones_array[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
    }

    date_default_timezone_set($current);

    return $zones_array;
}

/**
 *  Gets the current tomezone from wordpress settings
 *
 * @param false $humanReadable
 *
 * @return mixed|string|void
 */
function mailchimp_get_timezone($humanReadable = false) {
    // Per-request memo — the store_info template calls this multiple times
    // per render, and each call used to hit get_option() twice for the
    // same two core options. Static keyed by $humanReadable so both return
    // shapes stay cached.
    static $cache = array();
    $key = $humanReadable ? 'human' : 'raw';
    if ( isset( $cache[ $key ] ) ) {
        return $cache[ $key ];
    }

    // get timezone data from options
    $timezone_string = get_option( 'timezone_string' );
    $offset  = get_option( 'gmt_offset' );

    $signal = ($offset <=> 0 ) < 0 ? "-" : "+";
    $offset = sprintf('%1s%02d:%02d', $signal, abs((int) $offset), abs(fmod($offset, 1) * 60));

    // shows timezone name + offset in hours and minutes, or only the timezone name. If no timezone string is set, show only offset
    if (!$humanReadable && $timezone_string) {
        $timezone = $timezone_string;
    }
    else if ($humanReadable && $timezone_string) {
        $timezone = "UTC" . $offset .' '. $timezone_string;
    }
    else if ($humanReadable && !$timezone_string) {
         $timezone = "UTC" . $offset;
    }
    else if (!$timezone_string) {
        $timezone = $offset;
    }

    $cache[ $key ] = $timezone;
    return $timezone;
}

/**
 * Get all the registered image sizes along with their dimensions
 *
 * @global array $_wp_additional_image_sizes
 *
 * @link http://core.trac.wordpress.org/ticket/18947 Reference ticket
 *
 * @return array $image_sizes The image sizes
 */
function mailchimp_woocommerce_get_all_image_sizes() {
    // Per-request memo — image sizes are set by themes/plugins at bootstrap
    // and don't change within a request. Previously this function ran up
    // to 16 get_option() calls on every invocation (4 sizes × up to 4
    // reads each, because the crop option was read twice by a
    // `get_option(...) ? get_option(...) : false` ternary). Callers on
    // the settings page fire this multiple times per render, so a static
    // memo eliminates N × requests worth of duplicate work.
    static $cached = null;
    if ( $cached !== null ) {
        return $cached;
    }

    // WP 5.3+ — canonical API that returns { size => [width, height, crop] }
    // for every registered size (core + theme + plugin), already applies
    // the intermediate_image_sizes_advanced filter, and only reads the
    // underlying options it actually needs.
    if ( function_exists( 'wp_get_registered_image_subsizes' ) ) {
        $cached = wp_get_registered_image_subsizes();
        return $cached;
    }

    // Legacy fallback for pre-5.3. Single read per option (no double-
    // reading the crop value) and uses the default argument of
    // get_option() to express "false when missing".
    global $_wp_additional_image_sizes;
    $image_sizes = array();
    foreach ( get_intermediate_image_sizes() as $size ) {
        $image_sizes[ $size ] = array(
            'width'  => (int) get_option( "{$size}_size_w" ),
            'height' => (int) get_option( "{$size}_size_h" ),
            'crop'   => (bool) get_option( "{$size}_crop", false ),
        );
    }
    if ( ! empty( $_wp_additional_image_sizes ) ) {
        $image_sizes = array_merge( $image_sizes, $_wp_additional_image_sizes );
    }

    $cached = $image_sizes;
    return $cached;
}

/**
 * @return array
 */
function mailchimp_woocommerce_get_all_image_sizes_list() {
    $response = array();
    foreach (mailchimp_woocommerce_get_all_image_sizes() as $key => $data) {
        $label = ucwords(str_replace('_', ' ', $key));
        $label = __($label);
        $data['height'] = $data['height'] !== 0 ? $data['height'] : 'auto';
        $data['width'] = $data['width'] !== 0 ? $data['width'] : 'auto';
        $response[$key] = "{$label} ({$data['width']} x {$data['height']})";
    }
    return $response;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-mailchimp-woocommerce-activator.php
 * @throws MailChimp_WooCommerce_Error
 * @throws MailChimp_WooCommerce_RateLimitError
 * @throws MailChimp_WooCommerce_ServerError
 */
function activate_mailchimp_woocommerce() {

    // if we don't have any of these dependencies,
    // we need to display a horrible error message before the plugin is installed.
    mailchimp_check_curl_is_installed();
    // good to go - activate the plugin.
    MailChimp_WooCommerce_Activator::activate();
}

function mailchimp_check_curl_is_installed() {
    if (!function_exists('curl_exec')) {
        // Deactivate the plugin
        deactivate_plugins(__FILE__);
        $error_message = __('The MailChimp For WooCommerce plugin requires <a href="https://www.php.net/manual/en/book.curl.php/">curl</a> to be enabled!', 'woocommerce');
        wp_die($error_message);
    }
    return true;
}

/**
 * Create the queue tables
 */
function install_mailchimp_queue() {
    MailChimp_WooCommerce_Activator::create_queue_tables();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-mailchimp-woocommerce-deactivator.php
 */
function deactivate_mailchimp_woocommerce() {
    MailChimp_WooCommerce_Deactivator::deactivate();
}

/**
 * @param $action
 * @param $message
 * @param null $data
 */
function mailchimp_debug($action, $message, $data = null) {
    if (mailchimp_environment_variables()->logging === 'debug' && function_exists('wc_get_logger')) {
        if (is_array($data) && !empty($data)) $message .= " :: ".wc_print_r($data, true);
        wc_get_logger()->debug("{$action} :: {$message}", array('source' => 'mailchimp_woocommerce'));
    }
}

function mailchimp_enhanced_log($action, $message, $data = null) {
    if (mailchimp_environment_variables()->logging === 'debug' && function_exists('wc_get_logger')) {
        if (is_array($data) && !empty($data)) $message .= " :: ".wc_print_r($data, true);
        wc_get_logger()->warning("{$action} :: {$message}", array('source' => 'mailchimp_woocommerce'));
    }
}

function mailchimp_enhanced_error($action, $message, $data = null) {
    if (mailchimp_environment_variables()->logging === 'debug' && function_exists('wc_get_logger')) {
        if (is_array($data) && !empty($data)) $message .= " :: ".wc_print_r($data, true);
        wc_get_logger()->emergency("{$action} :: {$message}", array('source' => 'mailchimp_woocommerce'));
    }
}

/**
 * @param $action
 * @param $message
 * @param array $data
 */
function mailchimp_log($action, $message, $data = array()) {
    if (mailchimp_environment_variables()->logging !== 'none' && function_exists('wc_get_logger')) {
        if (is_array($data) && !empty($data)) $message .= " :: ".wc_print_r($data, true);
        wc_get_logger()->notice("{$action} :: {$message}", array('source' => 'mailchimp_woocommerce'));
    }
}

/**
 * @param $action
 * @param $message
 * @param array $data
 * @return void
 */
function mailchimp_error($action, $message, $data = array()) {
    if (mailchimp_environment_variables()->logging !== 'none' && function_exists('wc_get_logger')) {
        if ($message instanceof Exception) $message = mailchimp_error_trace($message);
        if (is_array($data) && !empty($data)) $message .= " :: ".wc_print_r($data, true);
        wc_get_logger()->error("{$action} :: {$message}", array('source' => 'mailchimp_woocommerce'));
    }
}

/**
 * @param $e
 * @param string $wrap
 *
 * @return string
 */
function mailchimp_error_trace($e, $wrap = "") {
	if ($e && $e instanceof Exception) {
		$error = "Error Code {$e->getCode()} :: {$e->getMessage()} on {$e->getLine()} in {$e->getFile()}";
	} else {
		$error = "";
	}
    if (empty($wrap)) return $error;
    return "{$wrap} :: {$error}";
}

/**
 *  Determine if a given string contains a given substring.
 *
 * @param $haystack
 * @param $needles
 *
 * @return bool
 */
function mailchimp_string_contains($haystack, $needles) {
    $has_mb = function_exists('mb_strpos');
    foreach ((array) $needles as $needle) {
        $has_needle = $needle != '';
        // make sure the server has "mb_strpos" otherwise this fails. Fallback to "strpos"
        $position = $has_mb ? mb_strpos($haystack, $needle) : strpos($haystack, $needle);
        if ($has_needle && $position !== false) {
            return true;
        }
    }
    return false;
}

/**
 * @return int
 */
function mailchimp_get_coupons_count() {
    $posts = mailchimp_count_posts('shop_coupon');

    $total = 0;
    foreach ($posts as $status => $count) {
        $total += $count;
    }
    return $total;
}

/**
 * @return int
 */
function mailchimp_get_product_count() {
    $posts = mailchimp_count_posts('product');

    $total = 0;
    foreach ($posts as $status => $count) {
        $total += $count;
    }

    return $total;
}


function mailchimp_get_product_categories_count() {
    global $wpdb;

    $query = "SELECT COUNT( * ) FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s";
    $terms = $wpdb->get_var($wpdb->prepare($query, 'product_cat'));

    return $terms;
}

/**
 * @return int
 */
function mailchimp_get_order_count() {
    return wc_orders_count('completed');
//    $posts = mailchimp_count_posts('shop_order');
//    unset($posts['auto-draft'], $posts['trash']);
//    $total = 0;
//    foreach ($posts as $status => $count) {
//        $total += $count;
//    }
//    return $total;
}

/**
 * @return int
 */
function mailchimp_get_customer_lookup_count() {
    global $wpdb;
    $query = "SELECT COUNT(DISTINCT email) as distinct_count
                FROM {$wpdb->prefix}wc_customer_lookup";

    return $wpdb->get_var($query);
}

/**
 * @return int
 */
function mailchimp_get_customer_lookup_count_all() {
    global $wpdb;
    $query = "SELECT COUNT(email) as distinct_count FROM {$wpdb->prefix}wc_customer_lookup";

    return $wpdb->get_var($query);
}

/**
 * @param $type
 * @return array|null|object
 */
function mailchimp_count_posts($type) {
    global $wpdb;
    if ($type === 'shop_order') {
        $query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s";
        $posts = $wpdb->get_results( $wpdb->prepare($query, $type, 'wc-completed'));
    } else {
        $query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s";
        $posts = $wpdb->get_results( $wpdb->prepare($query, $type, 'publish'));
    }

    $response = array();
    foreach ($posts as $post) {
        $response[$post->post_status] = $post->num_posts;
    }

    return $response;
}

/**
 * @param $resource
 * @param $by
 * @return bool|null
 */
function mailchimp_register_synced_resource($resource, $by = 1) {
    if (!in_array($resource, array('orders', 'products', 'customers', 'coupons'))) {
        return null;
    }
    // if we're done syncing we don't want to keep increasing this number
    if (mailchimp_is_done_syncing()) {
        return null;
    }
    return Mailchimp_Woocommerce_DB_Helpers::increment("mailchimp-woocommerce-sync.{$resource}.count", $by);
}

/**
 * @param $resource
 * @return int
 */
function mailchimp_get_synced_resource_count($resource) {
    if (!in_array($resource, array('orders', 'products', 'customers', 'coupons'))) {
        return 0;
    }
    return (int) Mailchimp_Woocommerce_DB_Helpers::get_option("mailchimp-woocommerce-sync.{$resource}.count", 0);
}

/**
 * @return object|null
 */
function mailchimp_get_local_sync_counts() {
    // this will only work if they clicked on a start sync after this feature was added in October 2024
    if (!Mailchimp_Woocommerce_DB_Helpers::get_option("mailchimp-woocommerce-sync.internal_counter")) {
        return null;
    }
    return (object) array(
        'orders' => mailchimp_get_synced_resource_count('orders'),
        'products' => mailchimp_get_synced_resource_count('products'),
        'customers' => mailchimp_get_synced_resource_count('customers'),
        'coupons' => mailchimp_get_synced_resource_count('coupons'),
    );
}

/**
 * @return bool
 * @throws MailChimp_WooCommerce_Error
 * @throws MailChimp_WooCommerce_RateLimitError
 * @throws MailChimp_WooCommerce_ServerError
 */
function mailchimp_update_connected_site_script() {
    // pull the store ID
    $store_id = mailchimp_get_store_id();

    // if the api is configured
    if ($store_id && ($api = mailchimp_get_api())) {
        // if we have a store
        if (($store = $api->getStore($store_id))) {
            return mailchimpi_refresh_connected_site_script($store);
        }
    }
    return false;
}

/**
 * @return bool|DateTime
 */
function mailchimp_get_updated_connected_site_since_as_date_string() {
    $updated_at = \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-script_updated_at', false);
    if (empty($updated_at)) return '';
    try {
        $date = new DateTime();
        $date->setTimestamp($updated_at);
        return $date->format('D, M j, Y g:i A');
    } catch (Exception $e) {
        return '';
    }
}

/**
 * @return int
 */
function mailchimp_get_updated_connected_site_since() {
    $updated_at = \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-script_updated_at', false);
    return empty($updated_at) ? 1000000 : (time() - $updated_at);
}

/**
 * @param int $seconds
 * @return bool
 */
function mailchimp_should_update_connected_site_script($seconds = 600) {
    return mailchimp_get_updated_connected_site_since() >= $seconds;
}

/**
 * @throws MailChimp_WooCommerce_Error
 * @throws MailChimp_WooCommerce_RateLimitError
 * @throws MailChimp_WooCommerce_ServerError
 */
function mailchimp_update_connected_site_script_from_cdn() {
    if (mailchimp_is_configured() && mailchimp_should_update_connected_site_script() && ($store_id = mailchimp_get_store_id())) {
        try {
            // pull the store, refresh the connected site url
            mailchimpi_refresh_connected_site_script(mailchimp_get_api()->getStore($store_id));
        } catch (Exception $e) {
            mailchimp_error("admin.update_connected_site_script", $e->getMessage());
        }
    }
}

/**
 * @param MailChimp_WooCommerce_Store $store
 * @return bool
 */
function mailchimpi_refresh_connected_site_script(MailChimp_WooCommerce_Store $store) {

    $api = mailchimp_get_api();

    $url = $store->getConnectedSiteScriptUrl();
    $fragment = $store->getConnectedSiteScriptFragment();

    // if script data is not available from store, try connected-sites endpoint
    if (!$url || !$fragment) {
        $connected_site_data = $api->checkConnectedSite($store->getId());
        
        if ($connected_site_data && isset($connected_site_data['site_script'])) {
            $url = isset($connected_site_data['site_script']['url']) ? $connected_site_data['site_script']['url'] : '';
            $fragment = isset($connected_site_data['site_script']['fragment']) ? $connected_site_data['site_script']['fragment'] : '';
        }
    }

    // if it's not empty we need to set the values
    if ($url && $fragment) {

        // update the options for script_url and script_fragment
        \Mailchimp_Woocommerce_DB_Helpers::update_option('mailchimp-woocommerce-script_url', $url);
        \Mailchimp_Woocommerce_DB_Helpers::update_option('mailchimp-woocommerce-script_fragment', $fragment);
        \Mailchimp_Woocommerce_DB_Helpers::update_option('mailchimp-woocommerce-script_updated_at', time());

        // check to see if the site is connected
        if (!$api->checkConnectedSite($store->getId())) {

            // if it's not, connect it now.
            $api->connectSite($store->getId());
        }

        return true;
    }
    return false;
}

/**
 * @return string|false
 */
function mailchimp_get_connected_site_script_url() {
    return \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-script_url', false);
}

/**
 * @return string|false
 */
function mailchimp_get_connected_site_script_fragment() {
    return \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-script_fragment', false);
}

/**
 * @param $email
 * @return bool
 */
function mailchimp_email_is_allowed($email) {
    if (!is_email($email) || mailchimp_email_is_amazon($email) || mailchimp_email_is_privacy_protected($email)) {
        return false;
    }
    return true;
}

/**
 * @param $email
 * @return bool
 */
function mailchimp_email_is_privacy_protected($email) {
    return $email === 'deleted@site.invalid';
}

/**
 * @param $email
 * @return bool
 */
function mailchimp_email_is_amazon($email) {
    return mailchimp_string_contains($email, '@marketplace.amazon.');
}

/**
 * @param $str
 * @return string
 */
function mailchimp_hash_trim_lower($str) {
    return md5(trim(strtolower($str)));
}

/**
 * @param $email
 * @return mixed
 */
function mailchimp_get_wc_customer($email) {
    global $wpdb;
    return $wpdb->get_row( "SELECT * FROM `{$wpdb->prefix}wc_customer_lookup` WHERE `email` = '{$email}'" );
}

/**
 * @param $key
 * @param null $default
 * @return mixed|null
 */
function mailchimp_get_transient($key, $default = null) {
    // Native WP transients hit the object cache (Redis) directly with zero
    // DB queries when an ext object cache is available. The prior helper
    // path read the transient value + timeout as two separate wp_options
    // SELECTs regardless of cache layer — that's the read overhead we're
    // trying to eliminate.
    $transient = get_transient("mailchimp-woocommerce.{$key}");
    return $transient === false ? $default : $transient;
}

/**
 * @param $key
 * @param $value
 * @param int $seconds
 * @return bool
 */
function mailchimp_set_transient($key, $value, $seconds = 60) {
    // Wrap in ['value' => ...] so legitimately-cached false values remain
    // distinguishable from "no transient" (get_transient returns false on
    // miss). No explicit delete-before-set — native set_transient handles
    // the update-vs-add path internally.
    return set_transient(
        "mailchimp-woocommerce.{$key}",
        array( 'value' => $value, 'expires' => time() + $seconds ),
        (int) $seconds
    );
}

/**
 * @param $key
 * @return bool
 */
function mailchimp_delete_transient($key) {
    return delete_transient("mailchimp-woocommerce.{$key}");
}

/**
 * @param $key
 * @param null $default
 * @return mixed|null
 */
function mailchimp_get_transient_value($key, $default = null) {
    $transient = mailchimp_get_transient($key, false);
    return (is_array($transient) && array_key_exists('value', $transient)) ? $transient['value'] : $default;
}

/**
 * @param $key
 * @param $value
 * @return bool|null
 */
function mailchimp_check_serialized_transient_changed($key, $value) {
    if (($saved = mailchimp_get_transient_value($key)) && !empty($saved)) {
        return serialize($saved) === serialize($value);
    }
    return null;
}

/**
 * @param $email
 * @return bool|string
 */
function mailchimp_get_transient_email_key($email) {
    $email = md5(trim(strtolower($email)));
    return empty($email) ? false : 'MailChimp_WooCommerce_User_Submit@'.$email;
}

/**
 * @param $email
 * @param $status_meta
 * @param int $seconds
 * @return bool
 */
function mailchimp_tell_system_about_user_submit($email, $status_meta, $seconds = 60) {
   return mailchimp_set_transient(mailchimp_get_transient_email_key($email), $status_meta, $seconds);
}

/**
 * @param $subscribed
 * @return array|false
 */
function mailchimp_get_subscriber_status_options($subscribed) {
    try {
        $requires = mailchimp_list_has_double_optin();
    } catch (Exception $e) {
        return false;
    }

    // if it's true - we set this value to NULL so that we do a 'pending' association on the member.
    $status_if_new = $requires ? null : $subscribed;
    $status_if_update = $requires ? 'pending' : $subscribed;

    // set an array of status meta that we will use for comparison below to the transient data
    return array(
        'requires_double_optin' => $requires,
        'created' => $status_if_new,
        'updated' => $status_if_update
    );
}

function mailchimp_check_if_on_sync_tab() {
    if ((isset($_GET['page']) && $_GET['page'] === 'mailchimp-woocommerce')) {
        $options = mailchimp_get_admin_options();
        if (isset($_GET['tab'])) {
            if ($_GET['tab'] === 'sync') {
                return true;
            }
            return false;
        }
        else if (isset($options['active_tab']) && $options['active_tab'] === 'sync') {
			return true;
		}
    }
    return false;
}

function mailchimp_flush_database_tables() {
    try {
        /** @var \ */
        global $wpdb;
        
        mailchimp_delete_as_jobs();
        
        $wpdb->query("TRUNCATE `{$wpdb->prefix}mailchimp_carts`");
        $wpdb->query("TRUNCATE `{$wpdb->prefix}mailchimp_jobs`");
    } catch (Exception $e) {}
}

function mailchimp_flush_sync_job_tables() {
    try {
        /** @var \ */
        global $wpdb;
        
        mailchimp_delete_as_jobs();
        
        $wpdb->query("TRUNCATE `{$wpdb->prefix}mailchimp_jobs`");
    } catch (Exception $e) {}
}

function mailchimp_delete_as_jobs() {

    $existing_as_actions = function_exists('as_get_scheduled_actions') ? as_get_scheduled_actions(
        array(
            'status' => ActionScheduler_Store::STATUS_PENDING,  
            'group' => 'mc-woocommerce',
            'per_page' => -1,
        )
    ) : null;
    
    if (!empty($existing_as_actions)) {
        foreach ($existing_as_actions as $as_action) {
            try {
                as_unschedule_action($as_action->get_hook(), $as_action->get_args(), 'mc-woocommerce');    # code...
            } catch (Exception $e) {}
        }
        return true;
    }
    return false;
}

function mailchimp_flush_sync_pointers() {
    // clean up the initial sync pointers
    \Mailchimp_Woocommerce_DB_Helpers::delete_option( 'mailchimp-woocommerce-resource-last-updated' );
    \Mailchimp_Woocommerce_DB_Helpers::delete_option( 'mailchimp-woocommerce-sync.started_at' );
    \Mailchimp_Woocommerce_DB_Helpers::delete_option( 'mailchimp-woocommerce-sync.completed_at' );
    foreach (array('customers', 'orders', 'products', 'product_categories', 'coupons') as $resource_type) {
        mailchimp_flush_specific_resource_pointers($resource_type);
    }
}

function mailchimp_flush_specific_resource_pointers($resource_type) {
    \Mailchimp_Woocommerce_DB_Helpers::delete_option("mailchimp-woocommerce-sync.{$resource_type}.started_at");
    \Mailchimp_Woocommerce_DB_Helpers::delete_option("mailchimp-woocommerce-sync.{$resource_type}-queueing.completed_at");
    \Mailchimp_Woocommerce_DB_Helpers::delete_option("mailchimp-woocommerce-sync.{$resource_type}.completed_at");
    \Mailchimp_Woocommerce_DB_Helpers::delete_option("mailchimp-woocommerce-sync.{$resource_type}.current_page");
}

/**
 * To be used when running clean up for uninstalls or store disconnection.
 */
function mailchimp_clean_database() {
    global $wpdb;
    
    // delete custom tables data
    mailchimp_flush_database_tables();

    // delete plugin options
    $plugin_options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'mailchimp%woocommerce%'" );

    foreach( $plugin_options as $option ) {
        \Mailchimp_Woocommerce_DB_Helpers::delete_option( $option->option_name );
    }
}

/**
 * @return bool
 */
function mailchimp_has_started_syncing() {
    return (bool) \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-sync.started_at');
//    $sync_completed_at = get_option('mailchimp-woocommerce-sync.completed_at');
//    return ($sync_completed_at < $sync_started_at);
}

function mailchimp_waiting_for_account_confirmation() {
    $waiting_login = \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-waiting-for-login');

    return $waiting_login === 'waiting';
}

/**
 * @return bool
 */
function mailchimp_is_done_syncing() {
    $sync_started_at = \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-sync.started_at');
    $sync_completed_at = \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-sync.completed_at');
    if ($sync_completed_at == false) return false;
    else return ($sync_completed_at >= $sync_started_at);
}

/**
 * @return bool
 */
function mailchimp_allowed_to_prepend_jobs_to_sync() {
    return (bool) \Mailchimp_Woocommerce_DB_Helpers::get_option('mailchimp-woocommerce-sync.internal_counter');
}

/**
 * @return bool
 */
function mailchimp_should_prepend_live_traffic_to_queue() {
    return mailchimp_allowed_to_prepend_jobs_to_sync() && !mailchimp_is_done_syncing();
}

function run_mailchimp_woocommerce() {
    $env = mailchimp_environment_variables();
    $plugin = new MailChimp_WooCommerce($env->environment, $env->version);
    $plugin->run();
}

function mailchimp_on_all_plugins_loaded() {
    run_mailchimp_woocommerce();
}

function mailchimp_get_allowed_capability() {
    $capability = 'manage_options';
    if (current_user_can('manage_woocommerce') && mailchimp_get_option('mailchimp_permission_cap') == 'manage_woocommerce') {
        return 'manage_woocommerce';
    }
    return apply_filters('mailchimp_allowed_capability', $capability);
}

/**
 * @throws MailChimp_WooCommerce_Error
 * @throws MailChimp_WooCommerce_RateLimitError
 * @throws MailChimp_WooCommerce_ServerError
 */
function mailchimp_update_communication_status() {
    $plugin_admin = MailChimp_WooCommerce_Admin::instance();
    $original_opt = $plugin_admin->getData('comm.opt',0);
    $options = $plugin_admin->getOptions();
    if (is_array($options) && array_key_exists('admin_email', $options)) {
        $plugin_admin->mailchimp_set_communications_status_on_server($original_opt, $options['admin_email']);    
    }
    // communication is ready lets define the webhooks
    $plugin_admin->defineWebhooks();
}

/**
 *
 */
function mailchimp_remove_communication_status() {
    $plugin_admin = MailChimp_WooCommerce_Admin::instance();
    $original_opt = $plugin_admin->getData('comm.opt',0);
    $options = $plugin_admin->getOptions();
    if (is_array($options) && array_key_exists('admin_email', $options)) {
        $remove = true;
        $plugin_admin->mailchimp_set_communications_status_on_server($original_opt, $options['admin_email'], $remove);
    }
}

/**
 * Removes any Woocommece inbox notes this plugin created.
 */
function mailchimp_remove_activity_panel_inbox_notes() {
    if ( ! class_exists( '\Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes' ) ) {
        return;
    }

    // if we can't use woocommerce for some reason - just return null
    if (!function_exists('WC')) {
        return;
    }

    // if we do not have the ability to use notes, just cancel out here.
    if (!method_exists(WC(), 'is_wc_admin_active') || !WC()->is_wc_admin_active()) {
        return;
    }

    try {
	    Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes::delete_notes_with_name( 'mailchimp-for-woocommerce-incomplete-install' );
    } catch (Exception $e) {
        // do nothing.
    }
}

// Print notices outside woocommerce admin bar
function mailchimp_settings_errors() {
    $settings_errors = get_settings_errors();
    $notices_html = '';
    foreach ($settings_errors as $notices) {
        $notices_html .= '<div id="setting-error-'. $notices['code'].'" class="notice notice-'. $notices['type'].' inline is-dismissible"><p>' . $notices['message'] . '</p></div>';
    }
    return $notices_html;
}

/**
 * @param null $user_email
 * @param null $language
 * @param string $caller
 * @param string $status_if_new
 * @param null $order
 * @param null $gdpr_fields
 * @param null|bool $live_traffic
 *
 * @throws MailChimp_WooCommerce_Error
 * @throws MailChimp_WooCommerce_RateLimitError
 * @throws MailChimp_WooCommerce_ServerError
 */
function mailchimp_member_data_update($user_email = null, $language = null, $caller = '', $status_if_new = 'transactional', $order = null, $gdpr_fields = null, $live_traffic = null) {
    mailchimp_debug('debug', "mailchimp_member_data_update", array(
        'user_email' => $user_email,
        'user_language' => $language,
        'caller' => $caller,
        'status_if_new' => $status_if_new,
        'gdpr_fields' => $gdpr_fields,
    ));
    if (!$user_email) return;
    
    $hash = md5(strtolower(trim($user_email)));
    $gdpr_fields_to_save = null;

    if ($caller !== 'cart' || !mailchimp_get_transient($caller . ".member.{$hash}")) {
        $list_id = mailchimp_get_list_id();
        try {
            if (!empty($gdpr_fields) && is_array($gdpr_fields)) {
                $gdpr_fields_to_save = [];
                foreach ($gdpr_fields as $id => $value) {
                    $gdpr_field['marketing_permission_id'] = $id;
                    $gdpr_field['enabled'] = (bool) $value;
                    $gdpr_fields_to_save[] = $gdpr_field;
                }
            }

            $merge_fields = $order ? apply_filters('mailchimp_get_ecommerce_merge_tags', array(), $order) : array();

            if (!is_array($merge_fields)) $merge_fields = array();

            try {
                $should_doi = $live_traffic && mailchimp_list_has_double_optin();
            } catch (\Exception $e) {
                $should_doi = false;
            }

            $api = mailchimp_get_api();

            if ($caller === 'cart') {
                $status = mailchimp_get_subscriber_status($user_email);

                if (in_array($status, ['transactional', 'subscribed', 'pending'])) {
                    $status_if_new = $status;
                }
            }

            $result = $api
                ->useAutoDoi($should_doi)
                ->update(
                    $list_id,
                    $user_email,
                    $status_if_new,
                    $merge_fields,
                    null,
                    $language,
                    $gdpr_fields_to_save,
                    $caller === 'cart'
                );

            // if we are passing over a value that's not subscribed and mailchimp returns subscribed
            // we need to set the user meta properly.
            if (!in_array($status_if_new, ['subscribed', 'pending'], true) && in_array($result['status'], ['subscribed', 'pending'], true)) {
                $user = get_user_by('email', $user_email);
                if ($user && $user->ID > 0) {
                    mailchimp_log('integration_logic', "mailchimp_member_data_update set the user meta for {$user_email} to subscribed because it was out of sync.");
                    update_user_meta($user->ID, 'mailchimp_woocommerce_is_subscribed', '1');
                }
            }

            // set transient to prevent too many calls to update language
            mailchimp_set_transient($caller . ".member.{$hash}", true, 3600);
            mailchimp_log($caller . '.member.updated', "Updated {$user_email} subscriber status to {$result['status']}".(!empty($language) ? "and language to {$language}" : ""));
        } catch (Exception $e) {
            $merge_fields = $order ? apply_filters('mailchimp_get_ecommerce_merge_tags', array(), $order) : array();
            if (!is_array($merge_fields)) $merge_fields = array();

            if ($e->getCode() == 404) {
                if (!empty($gdpr_fields) && is_array($gdpr_fields)) {
                    $gdpr_fields_to_save = [];
                    foreach ($gdpr_fields as $id => $value) {
                        $gdpr_field['marketing_permission_id'] = $id;
                        $gdpr_field['enabled'] = (bool) $value;
                        $gdpr_fields_to_save[] = $gdpr_field;
                    }
                }
                // member doesn't exist yet, create as transactional ( or what was passed in the function args )
                mailchimp_get_api()->subscribe($list_id, $user_email, $status_if_new, $merge_fields, array(), $language, $gdpr_fields_to_save);
                // set transient to prevent too many calls to update language
                mailchimp_set_transient($caller . ".member.{$hash}", true, 3600);
                mailchimp_log($caller . '.member.created', "Added {$user_email} as transactional, setting language to [{$language}]");
            } else if (strpos($e->getMessage(), 'compliance state') !== false) {
                mailchimp_get_api()->update($list_id, $user_email, 'pending', $merge_fields);
                mailchimp_log($caller . '.member.sync', "Update {$user_email} Using Double Opt In", $merge_fields);
            } else {
                mailchimp_error($caller . '.member.sync.error', $e->getMessage());
            }
        }
    }
}


/**
 * Check if SMS consent is enabled for this store.
 *
 * Consolidates all the checks needed to determine if the SMS consent feature
 * should be active — used by both classic and block checkout, as well as
 * order/customer processing hooks.
 *
 * Three conditions must be met:
 * 1. The store country is eligible for SMS.
 * 2. The SMS program is active in Mailchimp (cached for 10 minutes via the API layer).
 * 3. The merchant opted in via EITHER:
 *    a. Classic checkout: the admin option "mailchimp_sms_consent_enabled" is toggled on.
 *    b. Block checkout: the "usingSmsConsent" attribute is true on the SMS block.
 *
 * @return bool
 */
function mailchimp_sms_consent_enabled() {
    if (!MailChimp_Sms_Consent::isEligibleCountry()) {
        return false;
    }

    // The SMS program must be active in Mailchimp.
    // isSmsProgramActive() already uses a 10-minute transient cache via getCachedSmsProgram().
    if (!MailChimp_Sms_Consent::isSmsProgramActive()) {
        return false;
    }

    // Classic checkout path — admin toggled the option on.
    $options = mailchimp_get_admin_options();
    if (!empty($options['mailchimp_sms_consent_enabled'])) {
        return true;
    }

    // Block checkout path — check the block's "usingSmsConsent" attribute.
    return mailchimp_sms_block_enabled_in_checkout();
}

/**
 * Check whether the SMS consent block is present on the checkout page
 * and has its "usingSmsConsent" attribute set to true.
 *
 * Parses the checkout page's post_content to read the saved block attributes,
 * since has_block() only checks for presence, not attribute values.
 *
 * @return bool
 */
function mailchimp_sms_block_enabled_in_checkout() {
    $blocks = mailchimp_parse_checkout_page_blocks();
    if (empty($blocks)) return false;
    return mailchimp_find_sms_block_attribute($blocks, 'usingSmsConsent');
}

function mailchimp_parse_checkout_page_blocks()
{
    if (!function_exists('parse_blocks') || !function_exists('wc_get_page_id')) {
        return false;
    }
    $checkout_page_id = wc_get_page_id('checkout');
    if (!$checkout_page_id || $checkout_page_id < 1) {
        return false;
    }

    $checkout_post = get_post($checkout_page_id);
    if (!$checkout_post || empty($checkout_post->post_content)) {
        return false;
    }

    return parse_blocks($checkout_post->post_content);
}

function mailchimp_checkout_using_blocks()
{
    $checkout_page_id = get_option('woocommerce_checkout_page_id');

    return has_block( 'woocommerce/checkout', (int) $checkout_page_id );
}

function mailchimp_find_sms_block()
{
    $blocks = mailchimp_parse_checkout_page_blocks();
    foreach ($blocks as $block) {
        if ($block['blockName'] !== 'woocommerce/mailchimp-sms-consent') {
            continue;
        }
        return $block;
    }
    return false;
}

/**
 * Recursively search parsed blocks for the SMS consent block
 * and return the value of the given attribute.
 *
 * @param array $blocks Parsed blocks from parse_blocks().
 * @param string $attribute The attribute key to look for.
 * @return bool
 */
function mailchimp_find_sms_block_attribute($blocks, $attribute) {
    foreach ($blocks as $block) {
        if ($block['blockName'] === 'woocommerce/mailchimp-sms-consent') {
            return !empty($block['attrs'][$attribute]);
        }
        if (!empty($block['innerBlocks'])) {
            $result = mailchimp_find_sms_block_attribute($block['innerBlocks'], $attribute);
            if ($result) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Sync SMS consent data to Mailchimp for a member
 *
 * IMPORTANT: This function respects AC8 - if a customer places an order WITHOUT
 * checking SMS consent, we should NOT change their existing SMS subscription status.
 * We only sync when the customer explicitly opts in.
 *
 * @param string|null $user_email The user's email address (can be null for email-less checkout)
 * @param int|null $order_id The WooCommerce order ID (optional)
 * @param int|null $user_id The WordPress user ID (optional)
 * @param string $caller The calling context for logging
 * @param string|null $email_status The status of user's email subscription for contact upserts.
 * @return bool True if SMS was synced, false otherwise
 */
function mailchimp_member_sms_update($user_email = null, $order_id = null, $user_id = null, $caller = 'order', $email_status = null) {
    if (!mailchimp_sms_consent_enabled()) {
        return false;
    }

    $sms_data = null;

    // Try to get SMS data from order first
    if ($order_id) {
        $sms_data = MailChimp_Sms_Consent::getSmsDataFromOrder($order_id);
    }

    // If no order SMS data, try from user
    if (!$sms_data && $user_id) {
        $sms_data = MailChimp_Sms_Consent::getSmsDataFromUser($user_id);
    }

    // If still no SMS data, try current user if logged in
    if (!$sms_data && is_user_logged_in()) {
        $sms_data = MailChimp_Sms_Consent::getSmsDataFromUser(get_current_user_id());
    }

    // No SMS consent data found - this is expected when customer doesn't check the box
    // Per AC8: Do NOT change existing SMS subscription status in this case
    if (!$sms_data || empty($sms_data['phone'])) {
        mailchimp_debug('sms.sync', "No SMS consent data found - preserving existing status");
        return false;
    }

    // Only sync if they explicitly opted in (AC8 compliance)
    if (empty($sms_data['subscribed'])) {
        mailchimp_debug('sms.sync', "SMS consent not checked - preserving existing status");
        return false;
    }

    $list_id = mailchimp_get_list_id();
    if (!$list_id) {
        return false;
    }

    // Handle email-less checkout (AC10)
    $identifier = $user_email;
    if (empty($identifier) && $order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            $identifier = $order->get_billing_email();
        }
    }

    $transient_key = $caller . ".sms.member." . md5(($sms_data['phone'].'.'.$identifier));

    // Check if we've already synced recently
    if (mailchimp_get_transient($transient_key)) {
        mailchimp_debug('sms.sync', "Skipping SMS sync for phone {$sms_data['phone']} - recently synced");
        return true;
    }

    try {
        $api = mailchimp_get_api();

        mailchimp_debug('sms.sync', "Syncing SMS consent", array(
            'email' => $identifier,
            'phone' => $sms_data['phone'],
            'subscribed' => $sms_data['subscribed'],
        ));

        // Sync SMS consent to Mailchimp
        // preserve_existing = true means we won't unsubscribe someone who's already subscribed
        $result = $api->subscribeSms($list_id, $identifier, $sms_data['phone'], $sms_data['subscribed'], true, $email_status);

        mailchimp_log('sms_consent_sync', 'caller', [
            'caller' => $caller,
            'user_id' => $user_id,
            'sms_data' => $sms_data,
            'email' => $identifier,
            'result' => $result
        ]);

        // Set transient to prevent duplicate calls
        mailchimp_set_transient($transient_key, true, 3600);

        mailchimp_log($caller . '.sms.synced', "SMS consent synced :: phone: {$sms_data['phone']}" . ($identifier ? " :: email: {$identifier}" : " (email-less)"));
        return true;

    } catch (Exception $e) {
        mailchimp_error($caller . '.sms.sync.error', $e->getMessage() . " :: phone: {$sms_data['phone']}");
        return false;
    }
}

/**
 * @param $name
 * @param $value
 * @param $expire
 * @param $path
 * @param string $domain
 * @param bool $secure
 * @param false $httponly
 * @param string $samesite
 */
function mailchimp_set_cookie($name, $value, $expire, $path, $domain = '', $secure = true, $httponly = false, $samesite = 'Strict') {

    if (PHP_VERSION_ID < 70300) {
        @setcookie($name, $value, $expire, $path . '; samesite=' . $samesite, $domain, $secure, $httponly);
        return;
    }

    // allow the cookie options to be filtered
    $cookie_data = apply_filters('mailchimp_cookie_data', [
        'name' => $name,
        'options' => [
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain,
            'samesite' => $samesite,
            'secure' => $secure,
            'httponly' => $httponly,
        ],
    ]);

    // if the filter doesn't return a valid set of options, we need to ignore this cookie.
    if (!$cookie_data || !is_array($cookie_data) || !array_key_exists('options', $cookie_data)) {
        return;
    }

    @setcookie($name, $value, $cookie_data['options']);
}

/**
 * We will allow people to filter this value - turn it off if they would like.
 * add_filter( 'mailchimp_allowed_to_use_cookie', 'custom_cookie_callback_function', 10, 1 );
 *
 * @param $cookie
 *
 * @return bool
 */
function mailchimp_allowed_to_use_cookie($cookie) {
    $result = apply_filters('mailchimp_allowed_to_use_cookie', $cookie);
    if (is_bool($result)) return $result;
    return $result === $cookie;
}

// the cookie name will be whatever we're trying to set, but the most simple
// return the $cookie_name if you will allow it -
// otherwise it is going to turn this feature off.

/**
 * @return mixed|null
 */
function mailchimp_get_outbound_ip() {
    // if we have a dedicated IP address, and have set a configuration for it, we'll use it here.
    if (defined('MAILCHIMP_USE_OUTBOUND_IP') && !empty(MAILCHIMP_USE_OUTBOUND_IP)) {
        return MAILCHIMP_USE_OUTBOUND_IP;
    }
    return null;
}

/**
 * @return bool
 */
function mailchimp_render_gdpr_fields() {
    if (defined('MAILCHIMP_RENDER_GDPR_FIELDS') && !MAILCHIMP_RENDER_GDPR_FIELDS) {
        return false;
    }
    return true;
}

function mailchimp_expanded_alowed_tags() {
	$my_allowed = wp_kses_allowed_html( 'post' );
	// iframe
	$my_allowed['iframe'] = array(
		'src'             => array(),
		'height'          => array(),
		'width'           => array(),
		'frameborder'     => array(),
		'allowfullscreen' => array(),
	);
	// form fields - input
	$my_allowed['input'] = array(
		'class' => array(),
		'id'    => array(),
		'name'  => array(),
		'value' => array(),
		'type'  => array(),
		'checked' => array(),
	);
	// select
	$my_allowed['select'] = array(
		'class'  => array(),
		'id'     => array(),
		'name'   => array(),
		'value'  => array(),
		'type'   => array(),
	);
	// select options
	$my_allowed['option'] = array(
		'selected' => array(),
	);
	// style
	$my_allowed['style'] = array(
		'types' => array(),
	);

	return $my_allowed;
}

/**
 * @param $user_id
 *
 * @return DateTime|false|null
 */
function mailchimp_get_marketing_status_updated_at($user_id) {
	if (empty($user_id) || !is_numeric($user_id)) {
		return null;
	}
	$value = get_user_meta($user_id, 'mailchimp_woocommerce_marketing_status_updated_at', true);
	return !empty($value) && is_numeric($value) ? mailchimp_date_local($value) : null;
}

// Add WP CLI commands
if (defined( 'WP_CLI' ) && WP_CLI) {
    try {
	    /**
	     * @param $args
	     * @param $assoc_args
	     */
        function mailchimp_cli_push_command( $args, $assoc_args ) {
	        if (!class_exists('WP_CLI')) {
	        	return;
	        }
            if (is_array($args) && isset($args[0])) {
                switch($args[0]) {

                    case 'product_sync':
                        mailchimp_handle_or_queue(new MailChimp_WooCommerce_Process_Products());
                        WP_CLI::success("queued up the product sync!");
                        break;

                    case 'order_sync':
                        mailchimp_handle_or_queue(new MailChimp_WooCommerce_Process_Orders());
                        WP_CLI::success("queued up the order sync!");
                        break;

                    case 'order':
                        if (!isset($args[1])) {
                            wp_die('You must specify an order id as the 2nd parameter.');
                        }
                        mailchimp_handle_or_queue(new MailChimp_WooCommerce_Single_Order($args[1]));
                        WP_CLI::success("queued up the order {$args[1]}!");
                        break;

                    case 'product':
                        if (!isset($args[1])) {
                            wp_die('You must specify a product id as the 2nd parameter.');
                        }
                        mailchimp_handle_or_queue(new MailChimp_WooCommerce_Single_Product($args[1]));
                        WP_CLI::success("queued up the product {$args[1]}!");
                        break;
                }
            }
        }
        if (class_exists('WP_CLI')) {
	        WP_CLI::add_command( 'mailchimp_push', 'mailchimp_cli_push_command');
	        WP_CLI::add_command( 'queue', 'Mailchimp_Wocoomerce_CLI' );
        }
    } catch (Exception $e) {}
}

function mailchimp_account_events() {
    return array(
        'account:land_on_signup' => array(
            'initiative_name' => 'poppin_smu',
            'scope_area' => 'signup',
            'screen' => 'login_signup_page',
            'object' => 'account',
            'object_detail' => 'account_signup',
            'action' => 'started',
            'ui_object' => 'screen',
            'ui_object_detail' => 'sign_up',
            'ui_action' => 'viewed',
            'ui_access_point' => 'center',
        ),
        'account:type_in_email_field' => array(
            'initiative_name' => 'poppin_smu',
            'scope_area' => 'signup',
            'screen' => 'login_signup_enter_field',
            'object_detail' => 'account_signup',
            'action' => 'engaged',
            'ui_object' => 'field',
            'ui_object_detail' => 'email',
            'ui_action' => 'filled_field',
            'ui_access_point' => 'center',
        ),
        'account:sign_up_button_click' => array(
            'initiative_name' => 'poppin_smu',
            'scope_area' => 'signup',
            'screen' => 'login_signup_page',
            'object' => 'account',
            'object_detail' => 'account_signup',
            'action' => 'clicked',
            'ui_object' => 'button',
            'ui_object_detail' => 'sign_up',
            'ui_action' => 'clicked',
            'ui_access_point' => 'signup_page_signup_button',
        ),
        'account:login_signup_success' => array(
            'initiative_name' => 'poppin_smu',
            'scope_area' => 'signup',
            'screen' => 'login_signup_success',
            'object' => 'account',
            'object_detail' => 'account_signup',
            'action' => 'created',
            'ui_object' => 'screen',
            'ui_object_detail' => 'account_verification',
            'ui_action' => 'viewed',
            'ui_access_point' => 'center',
        ),
        'account:verify_email' => array(
            'initiative_name' => 'poppin_smu',
            'scope_area' => 'signup',
            'screen' => 'app_signup_confirm',
            'object' => 'account',
            'object_detail' => 'account_signup',
            'action' => 'clicked',
            'ui_object' => 'button',
            'ui_object_detail' => 'account_verification',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        // App Setup: Connect Accounts
        'connect_accounts:click_start' => [
            'event' => 'integration:started',
            'entry' => ['account_create_api', false],
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=create-mailchimp-account'),
            'object' => 'integration',
            'object_detail' => 'connect_accounts',
            'action' => 'started',
            'ui_object' => "button",
            'ui_object_detail' => "create_account",
            'ui_action' => "clicked",
            'ui_access_point' => "center",
            'description' => 'Connect Accounts: Clicks to create account',
        ],
        'connect_accounts:view_screen' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=create-mailchimp-account'),
            'object' => 'integration',
            'object_detail' => 'connect_accounts',
            'action' => 'viewed',
            'ui_object' => "'",
            'ui_object_detail' => "'",
            'ui_action' => "'",
            'ui_access_point' => "'",
        ),
        'connect_accounts:click_create_account' => [
            'event' => 'integration:viewed',
            'screen' => admin_url('admin.php?page=create-mailchimp-account'),
            'object' => 'integration',
            'object_detail' => 'create_account_form',
            'action' => 'engaged',
            'ui_object' => 'button',
            'ui_object_detail' => 'activate_account',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
            'entry' => ['account_create_api', false],
            'description' => 'Clicked the Activate Account button in the Create account flow',
        ],
        'connect_accounts:view_create_account' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=create-mailchimp-account'),
            'object' => 'integration',
            'object_detail' => 'create_account_form',
            'action' => 'viewed',
            'ui_object' => 'page',
            'ui_object_detail' => 'sign_up',
            'ui_action' => 'loaded',
            'ui_access_point' => 'center',
        ),
        'connect_accounts:activate_account' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=create-mailchimp-account'),
            'object' => 'integration',
            'object_detail' => 'create_account_form',
            'action' => 'engaged',
            'ui_object' => 'button',
            'ui_object_detail' => 'activate_account',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'connect_accounts:create_account_complete' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=create-mailchimp-account'),
            'object' => 'integration',
            'object_detail' => 'connect_accounts',
            'action' => 'completed',
            'ui_object' => 'action',
            'ui_object_detail' => 'create_account_finish',
            'ui_action' => 'completed',
            'ui_access_point' => 'modal',
        ),
        'connect_accounts_oauth:start' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=create-mailchimp-account'),
            'object' => 'integration',
            'object_detail' => 'connect_accounts_oauth',
            'action' => 'started',
            'ui_object' => 'button',
            'ui_object_detail' => 'connect',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'connect_accounts_oauth:complete' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=create-mailchimp-account'),
            'object' => 'integration',
            'object_detail' => 'connect_accounts_oauth',
            'action' => 'completed',
            'ui_object' => "'",
            'ui_object_detail' => "'",
            'ui_action' => "'",
            'ui_access_point' => "'",
        ),
        // App Setup: Review Settings
        'review_settings:view_screen' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce'),
            'object' => 'integration',
            'object_detail' => 'review_settings',
            'action' => 'viewed',
            'ui_object' => "'",
            'ui_object_detail' => "'",
            'ui_action' => "'",
            'ui_access_point' => "'",
        ),
        'review_settings:sync_as_subscribed' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce'),
            'object' => 'integration',
            'object_detail' => 'review_settings',
            'action' => 'engaged',
            'ui_object' => 'radio_button',
            'ui_object_detail' => 'sync_subscribed',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'review_settings:sync_as_non_subscribed' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce'),
            'object' => 'integration',
            'object_detail' => 'review_settings',
            'action' => 'engaged',
            'ui_object' => 'radio_button',
            'ui_object_detail' => 'sync_non_subscribed',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'review_settings:sync_existing_only' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce'),
            'object' => 'integration',
            'object_detail' => 'review_settings',
            'action' => 'engaged',
            'ui_object' => 'radio_button',
            'ui_object_detail' => 'sync_existing',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'review_settings:sync_new_non_subscribed' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce'),
            'object' => 'integration',
            'object_detail' => 'review_settings',
            'action' => 'engaged',
            'ui_object' => 'checkbox',
            'ui_object_detail' => 'sync_new_non_subscribed',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'review_settings:sync_subscribed_only' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce'),
            'object' => 'integration',
            'object_detail' => 'review_settings',
            'action' => 'engaged',
            'ui_object' => 'checkbox',
            'ui_object_detail' => 'sync_subscribed_only',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'review_settings:add_new_tag' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce'),
            'object' => 'integration',
            'object_detail' => 'review_settings',
            'action' => 'engaged',
            'ui_object' => 'button',
            'ui_object_detail' => 'add',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'review_settings:sync_now_bottom' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce'),
            'object' => 'integration',
            'object_detail' => 'review_settings',
            'action' => 'engaged',
            'ui_object' => 'button',
            'ui_object_detail' => 'sync_now',
            'ui_action' => 'clicked',
            'ui_access_point' => 'bottom',
        ),
        'review_settings:sync_now_center' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce'),
            'object' => 'integration',
            'object_detail' => 'review_settings',
            'action' => 'engaged',
            'ui_object' => 'button',
            'ui_object_detail' => 'sync_now',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        // App Setup: Sync Overview
        'audience_stats:view_screen' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=sync'),
            'object' => 'integration',
            'object_detail' => 'audience_stats',
            'action' => 'viewed',
            'ui_object' => "'",
            'ui_object_detail' => "'",
            'ui_action' => "'",
            'ui_access_point' => "'",
        ),
        'audience_stats:continue_to_mailchimp' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=sync'),
            'object' => 'integration',
            'object_detail' => 'audience_stats',
            'action' => 'engaged',
            'ui_object' => 'button',
            'ui_object_detail' => 'continue_to_mailchimp',
            'ui_action' => 'clicked',
            'ui_access_point' => 'top',
        ),
        'audience_stats:leave_review' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=sync'),
            'object' => 'integration',
            'object_detail' => 'audience_stats',
            'action' => 'engaged',
            'ui_object' => 'button',
            'ui_object_detail' => 'leave_us_a_review',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'audience_stats:recommendation_1' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=sync'),
            'object' => 'integration',
            'object_detail' => 'audience_stats',
            'action' => 'engaged',
            'ui_object' => 'button',
            'ui_object_detail' => 'recommendation_1',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'audience_stats:recommendation_2' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=sync'),
            'object' => 'integration',
            'object_detail' => 'audience_stats',
            'action' => 'engaged',
            'ui_object' => 'button',
            'ui_object_detail' => 'recommendation_2',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'audience_stats:recommendation_3' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=sync'),
            'object' => 'integration',
            'object_detail' => 'audience_stats',
            'action' => 'engaged',
            'ui_object' => 'button',
            'ui_object_detail' => 'recommendation_3',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        // App navigation
        'navigation_store:view' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=store_info'),
            'object' => 'integration',
            'object_detail' => 'store_settings',
            'action' => 'viewed',
            'ui_object' => "'",
            'ui_object_detail' => "'",
            'ui_action' => "'",
            'ui_access_point' => "'",
        ),
        'navigation_store:change_locale' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=store_info'),
            'object' => 'integration',
            'object_detail' => 'store_settings',
            'action' => 'engaged',
            'ui_object' => 'dropdown',
            'ui_object_detail' => 'locale',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'navigation_store:plugin_permission' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=store_info'),
            'object' => 'integration',
            'object_detail' => 'store_settings',
            'action' => 'engaged',
            'ui_object' => 'radio_button',
            'ui_object_detail' => 'plugin_permission',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'navigation_store:checkout_page_settings' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=store_info'),
            'object' => 'integration',
            'object_detail' => 'store_settings',
            'action' => 'engaged',
            'ui_object' => 'text_field',
            'ui_object_detail' => 'checkout_page_settings',
            'ui_action' => 'filled',
            'ui_access_point' => 'center',
        ),
        'navigation_store:product_image_size' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=store_info'),
            'object' => 'integration',
            'object_detail' => 'store_settings',
            'action' => 'engaged',
            'ui_object' => 'text_field',
            'ui_object_detail' => 'product_image_size',
            'ui_action' => 'filled',
            'ui_access_point' => 'center',
        ),
        // Audience Tab
        'navigation_audience:view' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=newsletter_settings'),
            'object' => 'integration',
            'object_detail' => 'audience_settings',
            'action' => 'viewed',
            'ui_object' => "'",
            'ui_object_detail' => "'",
            'ui_action' => "'",
            'ui_access_point' => "'",
        ),
        'navigation_audience:abandoned_cart' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=newsletter_settings'),
            'object' => 'integration',
            'object_detail' => 'audience_settings',
            'action' => 'engaged',
            'ui_object' => 'link',
            'ui_object_detail' => 'abandoned_cart_automations',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'navigation_audience:cart_tracking_all' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=newsletter_settings'),
            'object' => 'integration',
            'object_detail' => 'audience_settings',
            'action' => 'engaged',
            'ui_object' => 'radio_button',
            'ui_object_detail' => 'cart_tracking_all',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'navigation_audience:cart_tracking_only_subs' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=newsletter_settings'),
            'object' => 'integration',
            'object_detail' => 'audience_settings',
            'action' => 'engaged',
            'ui_object' => 'radio_button',
            'ui_object_detail' => 'cart_tracking_only_subscribed',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'navigation_audience:cart_tracking_disabled' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=newsletter_settings'),
            'object' => 'integration',
            'object_detail' => 'audience_settings',
            'action' => 'engaged',
            'ui_object' => 'radio_button',
            'ui_object_detail' => 'cart_tracking_disabled',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'navigation_audience:sync_new_non_subscribed' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=newsletter_settings'),
            'object' => 'integration',
            'object_detail' => 'audience_settings',
            'action' => 'engaged',
            'ui_object' => 'checkbox',
            'ui_object_detail' => 'sync_non_subscribed',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'navigation_audience:sync_subscribed_only' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=newsletter_settings'),
            'object' => 'integration',
            'object_detail' => 'audience_settings',
            'action' => 'engaged',
            'ui_object' => 'checkbox',
            'ui_object_detail' => 'sync_subscribed_only',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'navigation_audience:add_new_tag' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=newsletter_settings'),
            'object' => 'integration',
            'object_detail' => 'audience_settings',
            'action' => 'engaged',
            'ui_object' => 'text_field',
            'ui_object_detail' => 'new_tag',
            'ui_action' => 'filled',
            'ui_access_point' => 'center',
        ),
        // Logs Tab
        'navigation_logs:view' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=logs'),
            'object' => 'integration',
            'object_detail' => 'log_settings',
            'action' => 'viewed',
            'ui_object' => "'",
            'ui_object_detail' => "'",
            'ui_action' => "'",
            'ui_access_point' => "'",
        ),
        'navigation_logs:preferences' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=logs'),
            'object' => 'integration',
            'object_detail' => 'log_settings',
            'action' => 'engaged',
            'ui_object' => 'dropdown',
            'ui_object_detail' => 'log_preferences',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'navigation_logs:selection' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=logs'),
            'object' => 'integration',
            'object_detail' => 'log_settings',
            'action' => 'engaged',
            'ui_object' => 'dropdown',
            'ui_object_detail' => 'log_selection',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'navigation_logs:save' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=logs'),
            'object' => 'integration',
            'object_detail' => 'log_settings',
            'action' => 'engaged',
            'ui_object' => 'icon',
            'ui_object_detail' => 'save_logs',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'navigation_logs:delete' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=logs'),
            'object' => 'integration',
            'object_detail' => 'log_settings',
            'action' => 'engaged',
            'ui_object' => 'icon',
            'ui_object_detail' => 'delete_logs',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        // Advanced Tab
        'navigation_advanced:view' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=plugin_settings'),
            'object' => 'integration',
            'object_detail' => 'advanced_settings',
            'action' => 'viewed',
            'ui_object' => "'",
            'ui_object_detail' => "'",
            'ui_action' => "'",
            'ui_access_point' => "'",
        ),
        'navigation_advanced:enable_support' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=plugin_settings'),
            'object' => 'integration',
            'object_detail' => 'advanced_settings',
            'action' => 'engaged',
            'ui_object' => 'checkbox',
            'ui_object_detail' => 'enable_support',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'navigation_advanced:opt_in_email' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=plugin_settings'),
            'object' => 'integration',
            'object_detail' => 'advanced_settings',
            'action' => 'engaged',
            'ui_object' => 'checkbox',
            'ui_object_detail' => 'opt_in_email',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
        'navigation_advanced:disconnect' => array(
            'initiative_name' => 'strategic_partners',
            'scope_area' => 'embedded_app',
            'screen' => admin_url('admin.php?page=mailchimp-woocommerce&tab=plugin_settings'),
            'object' => 'integration',
            'object_detail' => 'advanced_settings',
            'action' => 'engaged',
            'ui_object' => 'button',
            'ui_object_detail' => 'disconnect',
            'ui_action' => 'clicked',
            'ui_access_point' => 'center',
        ),
    );
}