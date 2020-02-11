<?php
if (!empty( $_REQUEST['handle'])) {
    if (!empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'remove_log')) {
        $log_handler = new WC_Log_Handler_File();
        $log_handler->remove($_REQUEST['handle']);
        wp_redirect('admin.php?page=mailchimp-woocommerce&tab=logs');
    }
}
$files  = defined('WC_LOG_DIR') ? @scandir( WC_LOG_DIR ) : array();
$logs = array();
if (!empty($files)) {
    foreach ($files as $key => $value) {
        if (!in_array( $value, array( '.', '..' ))) {
            if (!is_dir($value) && mailchimp_string_contains($value, 'mailchimp_woocommerce')) {
                $logs[sanitize_title($value)] = $value;
            }
        }
    }
}

$requested_log_file = get_site_transient('mailchimp-woocommerce-view-log-file');
delete_site_transient('mailchimp-woocommerce-view-log-file');

if (empty($requested_log_file)) {
    $requested_log_file = !empty($_REQUEST['log_file']) ? $_REQUEST['log_file'] : false;
}
if (!empty($requested_log_file) && isset($logs[sanitize_title($requested_log_file)])) {
    $viewed_log = $logs[sanitize_title($requested_log_file)];
} elseif (!empty($logs)) {
    $viewed_log = current( $logs );
}
$handle = !empty($viewed_log) ? substr($viewed_log, 0, strlen($viewed_log) > 37 ? strlen($viewed_log) - 37 : strlen($viewed_log) - 4) : '';
?>


<fieldset>
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Logging Preference', 'mailchimp-for-woocommerce');?></span>
    </legend>
    
    <div class="box fieldset-header" >
        <label for="<?php echo $this->plugin_name; ?>-logging"><h2 style="padding-top: 1em;"><?php esc_html_e('Logging Preferences', 'mailchimp-for-woocommerce');?></h2></label>
    </div>

    <div class="box box-half">
        <p>
            <?php esc_html_e('Advanced troubleshooting can be conducted with the logging capability turned on.
            By default, it’s set to “standard” and you may toggle to either “debug” or “none” as needed.
            With standard logging, you can see basic information about the data submission to Mailchimp including any errors.
            “Debug” gives a much deeper insight that is useful to share with support if problems arise.', 'mailchimp-for-woocommerce');
            ?>
        </p>
    </div>
    <div class="box box-half">
        <div class="mailchimp-select-wrapper">
            <select name="<?php echo $this->plugin_name; ?>[mailchimp_logging]" required>
                <?php $logging_preference = mailchimp_environment_variables()->logging; ?>
                <?php
                foreach(array('none' => esc_html__('None', 'mailchimp-for-woocommerce'), 'debug' => esc_html__('Debug', 'mailchimp-for-woocommerce'), 'standard' => esc_html__('Standard', 'mailchimp-for-woocommerce')) as $log_value => $log_label) {
                    echo '<option value="'.esc_attr($log_value).'" '.selected($log_value === $logging_preference, true, false ) . '>' . esc_html($log_label) . '</option>';
                }
                ?>
            </select>
        </div>
    </div>
</fieldset>

<fieldset>
    <div class="box fieldset-header" >
        <h2>
            <?php esc_html_e('Recent Logs', 'mailchimp-for-woocommerce'); ?>
        </h2>
    </div>
    
    <div class="box">
        <input type="hidden" name="<?php echo $this->plugin_name; ?>[mailchimp_active_tab]" value="logs"/>
        <div class="mailchimp-select-wrapper view-log-select">
            <select id="log_file" name="log_file">
                <?php foreach ( $logs as $log_key => $log_file ) : ?>
                    <option value="<?php echo esc_attr( $log_key ); ?>" <?php selected( sanitize_title( $viewed_log ), $log_key ); ?>><?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), filemtime( WC_LOG_DIR . $log_file ) ); ?> - <?php echo esc_html( $log_file ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="submit" class="button tab-content-submit view-log-submit" value="<?php esc_attr_e( 'View', 'woocommerce' ); ?>" />
    </div>

</fieldset>
<div class="box">
    <?php if (isset($logs) && isset($viewed_log)) : ?>
        <div id="log-viewer">
            <div style="height: 100px;">
                <?php if ( ! empty( $handle ) ) : ?>
                    <a style="display:inline-block" class="mc-woocommerce-delete-log-button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'handle' => sanitize_title($viewed_log) ), admin_url( 'admin.php?page=mailchimp-woocommerce&tab=logs&mc_action=remove_log' ) ), 'remove_log' ) ); ?>">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14 1.41L12.59 0L7 5.59L1.41 0L0 1.41L5.59 7L0 12.59L1.41 14L7 8.41L12.59 14L14 12.59L8.41 7L14 1.41Z" fill="#3C3C3C"/>
                        </svg>
                        <?php esc_html_e('Delete log', 'mailchimp-for-woocommerce'); ?>
                        
                    </a>
                    <a style="display:inline-block" class="mc-woocommerce-copy-log-button" href="#">
                        <?php esc_html_e('Copy log', 'mailchimp-for-woocommerce'); ?>
                    </a>
                <?php endif; ?>
            </div>
            <div>
                <pre id="log-text"><?php echo esc_html( file_get_contents( WC_LOG_DIR . $viewed_log ) ); ?></pre>
            </div>
        </div>
    <?php else : ?>
        <div class="updated woocommerce-message inline"><p><?php _e( 'There are currently no logs to view.', 'woocommerce' ); ?></p></div>
    <?php endif; ?>
</div>