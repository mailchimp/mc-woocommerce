<?php

class Mailchimp_Woocommerce_DB_Helpers
{
    protected static $option_cache = [];

    /**
     * TEMP LOGGING — gated by the MAILCHIMP_LOG_OPTIONS constant. Define it
     * as true in wp-config.php to enable add/update/delete tracing; leave
     * undefined (or false) in production. Remove along with the call sites
     * once the add/update flow is verified.
     */
    protected static function log_op($channel, $event, array $context) {
        if ( ! defined( 'MAILCHIMP_LOG_OPTIONS' ) || ! MAILCHIMP_LOG_OPTIONS ) {
            return;
        }
        mailchimp_log( $channel, $event, $context );
    }

    /**
     * Option-name prefixes this plugin owns. bust_cache() will only touch the
     * WP object cache for options matching one of these — every other option
     * is treated as foreign and left completely alone so we can't disturb
     * caches owned by WooCommerce, WP core, or any other plugin.
     */
    protected static $owned_prefixes = array(
        'mailchimp_woocommerce',   // covers 'mailchimp_woocommerce' and 'mailchimp_woocommerce_*'
        'mailchimp-woocommerce',   // covers 'mailchimp-woocommerce-*'
        'mc-woocommerce',          // legacy slug used in a few places
    );

    /**
     * Is this option owned by us? Only owned options get full object-cache
     * invalidation. Anything else only clears our in-memory static cache.
     */
    protected static function is_owned_option($option) {
        if ( ! is_string( $option ) || $option === '' ) {
            return false;
        }
        foreach ( self::$owned_prefixes as $prefix ) {
            if ( strpos( $option, $prefix ) === 0 ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Bust every cache layer that could hold a stale copy of $option, then
     * optionally prime the new value back into the object cache so the next
     * read is both fresh and fast.
     *
     * SCOPE: only options owned by this plugin (see $owned_prefixes) get
     * object-cache invalidation. For any other option we only clear our own
     * per-request static cache — we never touch Redis entries or the shared
     * 'alloptions' blob for options we don't own, even if this helper is
     * accidentally called with one.
     *
     * Why: writes in this class go straight through $wpdb and never touch the
     * WP object cache (Redis, Memcached, etc.). Core get_option() calls made
     * elsewhere in the site hit that cache and would otherwise see stale data
     * until the key expires or the 'alloptions' blob is flushed.
     */
    protected static function bust_cache($option, $new_value = null, $prime = false) {
        // Always clear our per-request static cache — it's local to this class.
        unset(self::$option_cache[$option]);

        // Everything below touches the shared WP object cache, so gate it on
        // ownership to avoid stepping on other plugins / core.
        if ( ! self::is_owned_option( $option ) ) {
            return;
        }

        // notoptions: surgical rewrite. Only touch the array if OUR key is
        // memoized as missing — don't flush the whole site-wide memo.
        $notoptions = wp_cache_get( 'notoptions', 'options' );
        if ( is_array( $notoptions ) && isset( $notoptions[ $option ] ) ) {
            unset( $notoptions[ $option ] );
            wp_cache_set( 'notoptions', $notoptions, 'options' );
        }

        // alloptions: only modify the blob if our key is ALREADY there (i.e.
        // the option is autoloaded). This prevents us from injecting a
        // non-autoloaded option into the autoload cache, and matches WP
        // core's own update_option() pattern.
        $alloptions = wp_cache_get( 'alloptions', 'options' );
        if ( is_array( $alloptions ) && isset( $alloptions[ $option ] ) ) {
            if ( $prime ) {
                $alloptions[ $option ] = maybe_serialize( $new_value );
            } else {
                unset( $alloptions[ $option ] );
            }
            wp_cache_set( 'alloptions', $alloptions, 'options' );
        }

        // Single-key cache: prime with the new value, or drop the stale entry.
        if ( $prime ) {
            wp_cache_set( $option, $new_value, 'options' );
        } else {
            wp_cache_delete( $option, 'options' );
        }
    }

    /**
     * Add site option
     *
     * @param $option
     * @param $value
     * @param $autoload
     * @return bool
     */
    public static function add_option($option, $value = '', $autoload = null) {
        global $wpdb;

        // make sure we unset the values if it were there before.
        unset(self::$option_cache[$option]);

        if ( is_scalar( $option ) ) {
            $option = trim( $option );
        }

        if ( empty( $option ) ) {
            return false;
        }

        wp_protect_special_option( $option );

        if ( is_object( $value ) ) {
            $value = clone $value;
        }

        $value = sanitize_option( $option, $value );

        $serialized_value = maybe_serialize( $value );

        $autoload = static::determine_option_autoload_value( $option, $value, $serialized_value, $autoload );

        self::bust_cache( $option );

        $result = $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option, $serialized_value, $autoload ) );

        // TEMP LOGGING — gated by MAILCHIMP_LOG_OPTIONS; remove once verified
        self::log_op('db_helpers.add_option', 'add_option.result', [
            'option'   => $option,
            'value'    => $value,
            'autoload' => $autoload,
            'rows'     => $result,
            'success'  => $result !== false,
            'db_error' => $wpdb->last_error ?: null,
        ]);

        if ( $result === false ) {
            return false;
        }

        self::bust_cache( $option, $value, true );

        return true;
    }

    /**
     * get site option
     *
     * @param $option
     * @param $default_value
     * @return false
     */
    public static function get_option($option, $default_value = false) {
        global $wpdb;

        if ( is_scalar( $option ) ) {
            $option = trim( $option );
        }

        if (empty($option)) {
            return false;
        }

        $passed_default = func_num_args() > 1;

        if (array_key_exists($option, self::$option_cache)) {
            return self::$option_cache[$option];
        }

        $suppress = $wpdb->suppress_errors();
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1",
                $option
            )
        );
        $wpdb->suppress_errors($suppress);

        if (is_object($row)) {
            $value = $row->option_value;
        } else {
            // Row is missing. Return the (filtered) default but DO NOT cache
            // it — caching a default would mask "row missing" on subsequent
            // calls and makes the cached value depend on whichever default
            // the first caller happened to pass. Let each caller get its
            // own default until the row actually exists.
            return apply_filters("default_option_{$option}", $default_value, $option, $passed_default);
        }

        if ($option === 'home' && $value === '') {
            $value = self::get_option('siteurl');
            self::$option_cache[$option] = $value;
            return $value;
        }

        if (in_array($option, ['siteurl', 'home', 'category_base', 'tag_base'], true)) {
            $value = untrailingslashit($value);
        }

        $value = apply_filters("option_{$option}", maybe_unserialize($value), $option);

        self::$option_cache[$option] = $value;

        return $value;
    }

    /**
     * Update site option
     *
     * @param $option
     * @param $value
     * @return bool
     */
    public static function update_option($option, $value) {
        global $wpdb;

        if ( is_scalar( $option ) ) {
            $option = trim( $option );
        }

        if ( empty( $option ) ) {
            return false;
        }

        wp_protect_special_option( $option );

        if ( is_object( $value ) ) {
            $value = clone $value;
        }

        $value = sanitize_option( $option, $value );

        // Existence check straight from the DB — DO NOT trust get_option()
        // for this. get_option() caches the (filtered) default value in
        // self::$option_cache when the row is missing, so a later call with
        // a different default returns the earlier default instead of the
        // truth. That poisoning is exactly what caused update_option to
        // attempt $wpdb->update() against a row that didn't exist, log
        // success with rows=0, and silently drop the write.
        $row_exists = (bool) $wpdb->get_var( $wpdb->prepare(
            "SELECT 1 FROM $wpdb->options WHERE option_name = %s LIMIT 1",
            $option
        ) );

        if ( ! $row_exists ) {
            // TEMP LOGGING — gated by MAILCHIMP_LOG_OPTIONS; remove once verified
            self::log_op('db_helpers.update_option', 'update_option.route_to_add', [
                'option' => $option,
                'value'  => $value,
                'reason' => 'row_missing',
            ]);
            return self::add_option( $option, $value );
        }

        $old_value = self::get_option( $option );

        //  If the new and old values are the same, no need to update.
        if ( $value === $old_value || maybe_serialize( $value ) === maybe_serialize( $old_value ) ) {
            // TEMP LOGGING — gated by MAILCHIMP_LOG_OPTIONS; remove once verified
            self::log_op('db_helpers.update_option', 'update_option.blocked_same_value', [
                'option'    => $option,
                'value'     => $value,
                'old_value' => $old_value,
            ]);
            return false;
        }

        $serialized_value = maybe_serialize( $value );

        $update_args = array(
            'option_value' => $serialized_value,
        );

        // Retrieve the current autoload value to reevaluate it in case it was set automatically.
        $raw_autoload = $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );
        $allow_values = array( 'auto-on', 'auto-off', 'auto' );


        if ( in_array( $raw_autoload, $allow_values, true ) ) {
            $autoload = static::determine_option_autoload_value( $option, $value, $serialized_value, null );
            if ( $autoload !== $raw_autoload ) {
                $update_args['autoload'] = $autoload;
            }
        }

        self::bust_cache( $option );

        // Pre-write raw read on the SAME $wpdb connection. Comparing this to
        // $old_value (from get_option) tells us whether a read replica is
        // serving stale data vs. what the primary currently holds.
        $pre_raw = null;
        if ( defined( 'MAILCHIMP_LOG_OPTIONS' ) && MAILCHIMP_LOG_OPTIONS ) {
            $pre_raw = $wpdb->get_var( $wpdb->prepare(
                "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1",
                $option
            ) );
        }

        $result = $wpdb->update( $wpdb->options, $update_args, array( 'option_name' => $option ) );

        // Post-write raw read. After a write, most read/write-split drivers
        // (HyperDB, LudicrousDB, managed-host proxies) pin this connection to
        // the primary for the rest of the request — so this value is
        // authoritative for what actually landed on disk.
        $post_raw = null;
        $dup_count = null;
        if ( defined( 'MAILCHIMP_LOG_OPTIONS' ) && MAILCHIMP_LOG_OPTIONS ) {
            $post_raw = $wpdb->get_var( $wpdb->prepare(
                "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1",
                $option
            ) );
            $dup_count = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $wpdb->options WHERE option_name = %s",
                $option
            ) );
        }

        // TEMP LOGGING — gated by MAILCHIMP_LOG_OPTIONS; remove once verified
        self::log_op('db_helpers.update_option', 'update_option.result', [
            'option'    => $option,
            'value'     => $value,
            'old_value' => $old_value,
            'pre_raw'   => $pre_raw,   // what raw wpdb saw BEFORE the update
            'post_raw'  => $post_raw,  // what raw wpdb sees AFTER the update
            'row_count' => $dup_count, // >1 means duplicate option_name rows
            'rows'      => $result,
            'success'   => $result !== false,
            'noop'      => $result === 0,
            'db_error'  => $wpdb->last_error ?: null,
        ]);

        // $wpdb->update() returns rows-changed (0 when value is already current),
        // or false on real error. Only false is a failure.
        if ( $result === false ) {
            return false;
        }

        self::bust_cache( $option, $value, true );

        return true;
    }

    /**
     * Delete site option
     *
     * @param $option
     * @return bool
     */
    public static function delete_option($option) {
        global $wpdb;

        self::bust_cache( $option );

        if ( is_scalar( $option ) ) {
            $option = trim( $option );
        }

        if ( empty( $option ) ) {
            return false;
        }

        wp_protect_special_option( $option );

        // Get the ID, if no ID then return.
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT autoload FROM $wpdb->options WHERE option_name = %s", $option ) );
        if ( is_null( $row ) ) {
            return false;
        }

        $result = $wpdb->delete( $wpdb->options, array( 'option_name' => $option ) );

        // TEMP LOGGING — gated by MAILCHIMP_LOG_OPTIONS; remove once verified
        self::log_op('db_helpers.delete_option', 'delete_option.result', [
            'option'   => $option,
            'rows'     => $result,
            'success'  => (bool) $result,
            'db_error' => $wpdb->last_error ?: null,
        ]);

        if ( $result ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $key
     * @param $by
     * @return bool
     */
    public static function increment($key, $by = 1)
    {
        $option = (int) static::get_option( $key, 0);
        return static::add_option($key, $option+$by);
    }

    /**
     * @param $key
     * @param $by
     * @return bool|null
     */
    public static function decrement($key, $by = 1)
    {
        $option = (int) static::get_option( $key, 0);
        if ($option <= 0) {
            return null;
        }
        return static::add_option($key, $option-$by);
    }


    /**
     * Set site transient
     *
     * @param $transient
     * @param $value
     * @param $expiration
     * @return bool
     */
    public static function set_transient( $transient, $value, $expiration = 0 ) {
        $expiration = (int) $expiration;

        // Filters a specific transient before its value is set.
        $value = apply_filters( "pre_set_transient_{$transient}", $value, $expiration, $transient );

        //Filters the expiration for a transient before its value is set.
        $expiration = apply_filters( "expiration_of_transient_{$transient}", $expiration, $value, $transient );

        $transient_timeout = '_transient_timeout_' . $transient;
        $transient_option  = '_transient_' . $transient;

        if ( false === self::get_option( $transient_option ) ) {
            $autoload = true;
            if ( $expiration ) {
                $autoload = false;
                self::add_option( $transient_timeout, time() + $expiration, false );
            }
            $result = self::add_option( $transient_option, $value,  $autoload );
        } else {
            /*
             * If expiration is requested, but the transient has no timeout option,
             * delete, then re-create transient rather than update.
             */
            $update = true;

            if ( $expiration ) {
                if ( false === self::get_option( $transient_timeout ) ) {
                    self::delete_option( $transient_option );
                    self::add_option( $transient_timeout, time() + $expiration, false );
                    $result = self::add_option( $transient_option, $value, false );
                    $update = false;
                } else {
                    self::update_option( $transient_timeout, time() + $expiration );
                }
            }

            if ( $update ) {
                $result = self::update_option( $transient_option, $value );
            }
        }

        return $result;
    }

    /**
     * Get site transient
     *
     * @param $transient
     * @return mixed
     */
    public static function get_transient($transient) {
        $transient_option = '_transient_' . $transient;

        $alloptions = wp_load_alloptions();

        if ( ! isset( $alloptions[ $transient_option ] ) ) {
            $transient_timeout = '_transient_timeout_' . $transient;

            $timeout = self::get_option( $transient_timeout );
            if ( false !== $timeout && $timeout < time() ) {
                self::delete_option( $transient_option );
                self::delete_option( $transient_timeout );
                $value = false;
            }
        }

        if ( ! isset( $value ) ) {
            $value = self::get_option( $transient_option );
        }

        return apply_filters( "transient_{$transient}", $value, $transient );
    }

    /**
     * Delete site transient
     *
     * @param $transient
     * @return bool
     */
    public static function delete_transient($transient) {
        $option_timeout = '_transient_timeout_' . $transient;
        $option         = '_transient_' . $transient;
        $result         = self::delete_option( $option );

        if ( $result ) {
            self::delete_option( $option_timeout );
        }

        return $result;
    }

    protected static function determine_option_autoload_value($option, $value, $serialized_value, $autoload = null)
    {
        if (function_exists('wp_determine_option_autoload_value')) {
            return wp_determine_option_autoload_value( $option, $value, $serialized_value, $autoload );
        }
        // Check if autoload is a boolean.
        if ( is_bool( $autoload ) ) {
            return $autoload ? 'on' : 'off';
        }
        switch ( $autoload ) {
            case 'on':
            case 'yes':
                return 'on';
            case 'off':
            case 'no':
                return 'off';
        }
        $autoload = apply_filters( 'wp_default_autoload_value', null, $option, $value, $serialized_value );
        if ( is_bool( $autoload ) ) {
            return $autoload ? 'auto-on' : 'auto-off';
        }
        return 'auto';
    }
}