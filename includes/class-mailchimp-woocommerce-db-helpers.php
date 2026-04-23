<?php

class Mailchimp_Woocommerce_DB_Helpers
{
    protected static $option_cache = [];

    /**
     * Per-request memo of option names whose row is confirmed absent in the
     * DB. Lets repeated get_option() calls for a missing row skip the SELECT
     * while still letting each caller apply its own default — no cross-caller
     * default contamination (the bug that got us into this mess the first
     * time). Invalidated by bust_cache() on any write.
     */
    protected static $missing_rows = [];

    /**
     * TEMP LOGGING — gated by the MAILCHIMP_LOG_OPTIONS constant. Define it
     * as true in wp-config.php to enable add/update/delete tracing; leave
     * undefined (or false) in production. Remove along with the call sites
     * once the add/update flow is verified.
     */
    /**
     * Reentrancy guard. mailchimp_log() / mailchimp_debug() ultimately call
     * mailchimp_environment_variables() which calls get_option() which calls
     * trace_read() which calls mailchimp_log() — infinite loop, stack
     * overflow, white screen. While this flag is true, the helper's own
     * logging and tracing are suppressed so we don't recurse back through
     * the option pipeline.
     */
    protected static $in_logging = false;

    protected static function log_op($channel, $event, array $context) {
        if ( ! defined( 'MAILCHIMP_LOG_OPTIONS' ) || ! MAILCHIMP_LOG_OPTIONS ) {
            return;
        }
        if ( self::$in_logging ) {
            return;
        }
        if ( ! function_exists( 'mailchimp_log' ) ) {
            return; // bootstrap hasn't finished loading yet
        }
        self::$in_logging = true;
        try {
            mailchimp_log( $channel, $event, $context );
        } finally {
            self::$in_logging = false;
        }
    }

    /**
     * TEMP TRACER — gated by the MAILCHIMP_TRACE_OPTIONS constant. When
     * enabled, logs every get_option() call through the helper with the
     * source (cache_hit / missing_memo / db_query), the currently-firing
     * WP hook, and a caller frame. Use this to identify which code paths
     * read which options and WHEN during the request lifecycle so you can
     * tune the preload lists.
     *
     * Define MAILCHIMP_TRACE_OPTIONS = true in wp-config.php to enable.
     * Leave undefined/false in production — a single trace pays for a
     * debug_backtrace and a log write per read.
     */
    protected static function trace_read($option, $source) {
        if ( ! defined( 'MAILCHIMP_TRACE_OPTIONS' ) || ! MAILCHIMP_TRACE_OPTIONS ) {
            return;
        }
        // Reentrancy guard — mailchimp_log() indirectly calls get_option()
        // via mailchimp_environment_variables(), which would re-enter this
        // function and recurse until the stack blows up.
        if ( self::$in_logging ) {
            return;
        }
        if ( ! function_exists( 'mailchimp_log' ) ) {
            return; // bootstrap hasn't finished loading yet
        }

        $hook = function_exists( 'current_filter' ) ? current_filter() : '';
        $caller = '';

        // Walk back past trace_read and get_option to the real caller.
        $frames = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 6 );
        foreach ( $frames as $frame ) {
            if ( ! isset( $frame['function'] ) ) continue;
            if ( $frame['function'] === 'trace_read' ) continue;
            if ( $frame['function'] === 'get_option' && isset( $frame['class'] ) && $frame['class'] === __CLASS__ ) continue;
            $file = isset( $frame['file'] ) ? basename( $frame['file'] ) : '?';
            $line = isset( $frame['line'] ) ? $frame['line'] : '?';
            $fn   = ( isset( $frame['class'] ) ? $frame['class'] . '::' : '' ) . $frame['function'];
            $caller = "{$fn} ({$file}:{$line})";
            break;
        }

        self::$in_logging = true;
        try {
            mailchimp_log( 'db_helpers.trace', 'get_option', array(
                'option' => $option,
                'source' => $source,      // cache_hit | missing_memo | db_query
                'hook'   => $hook ?: '(no active hook)',
                'caller' => $caller,
            ) );
        } finally {
            self::$in_logging = false;
        }
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
        unset(self::$missing_rows[$option]);

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
            self::trace_read($option, 'cache_hit');
            // Apply option_{$option} filter on every read — matches WP core
            // get_option(). Preloaded values are cached raw (unfiltered) so
            // this is the one place the filter gets applied for them.
            return apply_filters( "option_{$option}", self::$option_cache[$option], $option );
        }

        // If we've already confirmed this row doesn't exist earlier in this
        // request, skip the DB round-trip and let each caller apply its own
        // default via the default_option_{$option} filter.
        if (isset(self::$missing_rows[$option])) {
            self::trace_read($option, 'missing_memo');
            return apply_filters("default_option_{$option}", $default_value, $option, $passed_default);
        }

        self::trace_read($option, 'db_query');

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
            // Row is missing. Memoize the absence (not the default value) so
            // we don't repeat the SELECT for this option later in the same
            // request. Each caller still gets its own default applied.
            self::$missing_rows[$option] = true;
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

        // Cache the raw unserialized value; apply option_{$option} filter
        // on the way out so every read (cache hit or DB fetch) sees the
        // same filter pipeline. This mirrors WP core get_option() and
        // avoids any risk of a pre-init filter callback causing
        // _load_textdomain_just_in_time doing_it_wrong notices from
        // preload warming.
        $value = maybe_unserialize( $value );
        self::$option_cache[$option] = $value;

        return apply_filters( "option_{$option}", $value, $option );
    }

    /**
     * Warm the per-request option cache with a batch of owned option rows in
     * a single SELECT, so subsequent get_option() calls for those keys are
     * served from memory instead of the DB.
     *
     * Use this on admin pages (or any request that is known to read many
     * options) to collapse N lookups into one. Non-owned option names are
     * silently skipped — we can't guarantee cache coherence for options that
     * don't flow through this class on writes.
     *
     * Keys already in self::$option_cache or already memoized as missing are
     * skipped, so calling preload() repeatedly with overlapping key sets is
     * safe and cheap.
     *
     * @param array $options list of option names to preload
     * @return int number of option rows actually fetched from the DB
     */
    public static function preload(array $options) {
        global $wpdb;

        $to_fetch = array();
        foreach ($options as $option) {
            if (!is_string($option) || $option === '') continue;
            if (!self::is_owned_option($option)) continue;
            if (array_key_exists($option, self::$option_cache)) continue;
            if (isset(self::$missing_rows[$option])) continue;
            $to_fetch[$option] = true; // dedupe
        }

        if (empty($to_fetch)) {
            return 0;
        }

        $to_fetch = array_keys($to_fetch);
        $placeholders = implode(',', array_fill(0, count($to_fetch), '%s'));

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT option_name, option_value FROM $wpdb->options WHERE option_name IN ($placeholders)",
            $to_fetch
        ) );

        $found = array();
        if (is_array($rows)) {
            foreach ($rows as $row) {
                // Store the raw unserialized value — do NOT apply
                // option_{$option} filters here. Preload runs on
                // plugins_loaded @ 1 which is BEFORE init, and any filter
                // callback that calls __() triggers WP 6.7+'s
                // _load_textdomain_just_in_time doing_it_wrong notice.
                //
                // The filter is applied lazily in get_option() on every
                // read — matching WP core's get_option() semantics, which
                // also re-applies the filter on every cache-hit.
                self::$option_cache[ $row->option_name ] = maybe_unserialize( $row->option_value );
                $found[ $row->option_name ] = true;
            }
        }

        // Everything we asked for that didn't come back is confirmed missing
        // for the rest of this request.
        foreach ($to_fetch as $option) {
            if (!isset($found[$option])) {
                self::$missing_rows[$option] = true;
            }
        }

        // Only log a preload that actually did work. Calls that find every
        // key already cached still hit this method but cost nothing, so
        // dropping them from the log keeps signal:noise high.
        if ( count( $to_fetch ) > 0 ) {
            self::log_op('db_helpers.preload', 'preload.result', array(
                'requested' => count($to_fetch),
                'found'     => count($found),
                'missing'   => count($to_fetch) - count($found),
            ));
        }

        return count($found);
    }

    /**
     * Warm the caches for a batch of transient keys in a single SELECT.
     *
     * Why this is needed: get_transient($key) internally calls WP core's
     * get_option('_transient_' . $key) and get_option('_transient_timeout_'
     * . $key). Core's get_option consults WP's own wp_cache_* layer — NOT
     * our helper's $option_cache static. So populating our helper's static
     * cache has no effect on transient reads.
     *
     * What this does: fetches the value+timeout rows for each transient in
     * one batched query, then populates WP core's options cache group via
     * wp_cache_set(). Subsequent native get_transient() calls for those
     * keys read from cache with zero DB activity, regardless of whether a
     * Redis drop-in is active — because wp_cache_* is WP's canonical cache
     * layer that every reader consults first.
     *
     * Expired transients are intentionally NOT primed. WP core's native
     * path cleans them up on the next read.
     *
     * @param array $keys transient keys (without the "_transient_" prefix)
     * @return int number of transients actually primed into the cache
     */
    public static function preload_transients(array $keys) {
        global $wpdb;

        if (empty($keys)) {
            return 0;
        }

        // Expand each logical transient key into the two wp_options row
        // names WP core stores them under.
        $row_names = array();
        $valid_keys = array();
        foreach ($keys as $key) {
            if (!is_string($key) || $key === '') continue;
            $valid_keys[] = $key;
            $row_names[] = '_transient_' . $key;
            $row_names[] = '_transient_timeout_' . $key;
        }

        if (empty($row_names)) {
            return 0;
        }

        // Skip rows already in WP's options cache — avoids wasting a query
        // when an earlier preload (or another plugin) has already warmed
        // them. wp_cache_get returns false on true miss.
        $to_fetch = array();
        foreach ($row_names as $row_name) {
            if ( wp_cache_get( $row_name, 'options' ) === false ) {
                $to_fetch[] = $row_name;
            }
        }

        if (empty($to_fetch)) {
            return 0;
        }

        $to_fetch = array_values( array_unique( $to_fetch ) );
        $placeholders = implode( ',', array_fill( 0, count( $to_fetch ), '%s' ) );

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT option_name, option_value FROM $wpdb->options WHERE option_name IN ($placeholders)",
            $to_fetch
        ) );

        $by_name = array();
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $by_name[ $row->option_name ] = $row->option_value;
            }
        }

        $primed = 0;
        foreach ($valid_keys as $key) {
            $value_name   = '_transient_' . $key;
            $timeout_name = '_transient_timeout_' . $key;
            $value_raw    = isset( $by_name[ $value_name ] )   ? $by_name[ $value_name ]   : null;
            $timeout_raw  = isset( $by_name[ $timeout_name ] ) ? $by_name[ $timeout_name ] : null;

            // Expired transient: leave the cache cold. WP core will delete
            // the stale rows on the next get_transient() call. Priming
            // expired data would cause core to trigger DB deletes on read,
            // which is worse than not priming.
            if ( $timeout_raw !== null && (int) $timeout_raw < time() ) {
                continue;
            }

            // Missing transient: nothing to cache. Don't negatively-prime
            // either — WP core's "no row" path is already cheap after one
            // miss (alloptions check) for non-autoloaded options, and
            // negatively-priming complicates set_transient's semantics.
            if ( $value_raw === null ) {
                continue;
            }

            // Prime WP core's options cache with the raw (still-serialized)
            // values. Core's get_option() applies maybe_unserialize on its
            // way out, so storing the raw string matches what core would
            // have cached after its own DB fetch.
            wp_cache_set( $value_name, $value_raw, 'options' );
            if ( $timeout_raw !== null ) {
                wp_cache_set( $timeout_name, $timeout_raw, 'options' );
            }

            // Also populate our helper's static cache in case anything
            // reads the raw transient row through our helper's get_option.
            self::$option_cache[ $value_name ] = maybe_unserialize( $value_raw );

            $primed++;
        }

        self::log_op( 'db_helpers.preload', 'preload_transients.result', array(
            'requested' => count( $valid_keys ),
            'fetched'   => count( $to_fetch ) / 2,
            'primed'    => $primed,
        ) );

        return $primed;
    }

    /**
     * Update site option
     *
     * @param $option
     * @param $value
     * @return bool
     */
    public static function update_option($option, $value, $autoload = null) {
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
            return self::add_option( $option, $value, $autoload );
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

        // If the caller explicitly asked for an autoload value, honor it —
        // this is what the third argument was meant to do and didn't, because
        // update_option() used to only accept two params.
        if ( $autoload !== null ) {
            $resolved_autoload = static::determine_option_autoload_value( $option, $value, $serialized_value, $autoload );
            if ( $resolved_autoload !== $raw_autoload ) {
                $update_args['autoload'] = $resolved_autoload;
            }
        } elseif ( in_array( $raw_autoload, $allow_values, true ) ) {
            // Caller didn't pass one — re-evaluate if the current stored
            // autoload is an "auto*" value (i.e. previously auto-decided).
            $resolved_autoload = static::determine_option_autoload_value( $option, $value, $serialized_value, null );
            if ( $resolved_autoload !== $raw_autoload ) {
                $update_args['autoload'] = $resolved_autoload;
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
        // Delegate to WP core. With an external object cache (Redis, etc.)
        // this is a single cache-layer write with native TTL and zero
        // wp_options I/O. Without one, core handles the same _transient_* /
        // _transient_timeout_* row pair we were managing by hand, but with
        // fewer round-trips and proper locking.
        return set_transient( $transient, $value, (int) $expiration );
    }

    /**
     * Get site transient
     *
     * @param $transient
     * @return mixed
     */
    public static function get_transient( $transient ) {
        return get_transient( $transient );
    }

    /**
     * Delete site transient
     *
     * @param $transient
     * @return bool
     */
    public static function delete_transient( $transient ) {
        return delete_transient( $transient );
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