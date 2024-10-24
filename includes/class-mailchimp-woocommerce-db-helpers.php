<?php

class Mailchimp_Woocommerce_DB_Helpers
{

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

        $result = $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option, $serialized_value, $autoload ) );

        if ( ! $result ) {
            return false;
        }

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

        if ( empty( $option ) ) {
            return false;
        }

        $passed_default = func_num_args() > 1;

        $suppress = $wpdb->suppress_errors();
        $row      = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );
        $wpdb->suppress_errors( $suppress );

        if ( is_object( $row ) ) {
            $value = $row->option_value;
        } else {
            /** This filter is documented in wp-includes/option.php */
            return apply_filters( "default_option_{$option}", $default_value, $option, $passed_default );
        }

        // If home is not set, use siteurl.
        if ( 'home' === $option && '' === $value ) {
            return self::get_option( 'siteurl' );
        }

        if ( in_array( $option, array( 'siteurl', 'home', 'category_base', 'tag_base' ), true ) ) {
            $value = untrailingslashit( $value );
        }

        return apply_filters( "option_{$option}", maybe_unserialize( $value ), $option );
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

        $value     = sanitize_option( $option, $value );
        $old_value = self::get_option( $option );

        //  If the new and old values are the same, no need to update.
        if ( $value === $old_value || maybe_serialize( $value ) === maybe_serialize( $old_value ) ) {
            return false;
        }

        if (!$old_value) {
            self::add_option( $option, $value );
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

        $result = $wpdb->update( $wpdb->options, $update_args, array( 'option_name' => $option ) );
        if ( ! $result ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Delete site option
     *
     * @param $option
     * @return bool
     */
    public static function delete_option($option) {
        global $wpdb;

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