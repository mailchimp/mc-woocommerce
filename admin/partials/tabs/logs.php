<?php
if (!empty( $_REQUEST['handle'])) {
    if (!empty($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'remove_log')) {
        $log_handler = new WC_Log_Handler_File();
        $log_handler->remove($_REQUEST['handle']);
        wp_redirect('options-general.php?page=mailchimp-woocommerce&tab=logs');
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

<h2 style="padding-top: 1em;">Logging Preference</h2>
<p>
    Advanced troubleshooting can be conducted with the logging capability turned on.
    By default, it’s set to “none” and you may toggle to either “standard” or “debug” as needed.
    With standard logging, you can see basic information about the data submission to Mailchimp including any errors.
    “Debug” gives a much deeper insight that is useful to share with support if problems arise.
</p>
<fieldset>
    <legend class="screen-reader-text">
        <span>Logging Preference</span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-logging">
        <select name="<?php echo $this->plugin_name; ?>[mailchimp_logging]" style="width:30%" required>
            <?php $logging_preference = mailchimp_environment_variables()->logging; ?>
            <?php
            foreach(array('none' => 'None', 'debug' => 'Debug', 'standard' => 'Standard',) as $log_value => $log_label) {
                echo '<option value="'.esc_attr($log_value).'" '.selected($log_value === $logging_preference, true, false ) . '>' . esc_html($log_label) . '</option>';
            }
            ?>
        </select>
    </label>
</fieldset>

<?php submit_button('Save all changes', 'primary','submit', TRUE);?>

<?php if (isset($logs) && isset($viewed_log)) : ?>
    <div id="log-viewer-select">
        <div class="alignleft">
            <h2>
                <?php echo esc_html( $viewed_log ); ?>
                <?php if ( ! empty( $handle ) ) : ?>
                    <a class="page-title-action" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'handle' => sanitize_title($viewed_log) ), admin_url( 'options-general.php?page=mailchimp-woocommerce&tab=logs&mc_action=remove_log' ) ), 'remove_log' ) ); ?>" class="button"><?php esc_html_e( 'Delete log', 'woocommerce' );?></a>
                <?php endif; ?>
            </h2>
        </div>
        <div class="alignright">
            <form action="<?php echo admin_url( 'options-general.php?page=mailchimp-woocommerce&tab=logs&mc_action=view_log' ); ?>" method="post">
                <input type="hidden" name="<?php echo $this->plugin_name; ?>[mailchimp_active_tab]" value="logs"/>
                <select name="log_file">
                    <?php foreach ( $logs as $log_key => $log_file ) : ?>
                        <option value="<?php echo esc_attr( $log_key ); ?>" <?php selected( sanitize_title( $viewed_log ), $log_key ); ?>><?php echo esc_html( $log_file ); ?> (<?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), filemtime( WC_LOG_DIR . $log_file ) ); ?>)</option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" class="button" value="<?php esc_attr_e( 'View', 'woocommerce' ); ?>" />
            </form>
        </div>
        <div class="clear"></div>
    </div>
    <div id="log-viewer">
        <pre><?php echo esc_html( file_get_contents( WC_LOG_DIR . $viewed_log ) ); ?></pre>
    </div>
<?php else : ?>
    <div class="updated woocommerce-message inline"><p><?php _e( 'There are currently no logs to view.', 'woocommerce' ); ?></p></div>
<?php endif; ?>
