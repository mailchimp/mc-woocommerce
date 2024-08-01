<?php
/**
 * Logs tab template
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/admin
 */

?>
<?php

$files          = defined( 'WC_LOG_DIR' ) && is_dir( WC_LOG_DIR ) ? scandir( WC_LOG_DIR ) : array();
$logs           = array();
if ( ! empty( $files ) ) {
	foreach ( array_reverse( $files ) as $key => $value ) {
		if ( ! in_array( $value, array( '.', '..' ), true ) ) {
			if ( ! is_dir( $value ) && mailchimp_string_contains( $value, 'mailchimp_woocommerce' ) ) {
				$logs[ sanitize_title( $value ) ] = $value;
			}
		}
	}
}

$requested_log_file = \Mailchimp_Woocommerce_DB_Helpers::get_transient( 'mailchimp-woocommerce-view-log-file' );
\Mailchimp_Woocommerce_DB_Helpers::delete_transient( 'mailchimp-woocommerce-view-log-file' );

if ( empty( $requested_log_file ) ) {
	$requested_log_file = ! empty( $_REQUEST['log_file'] ) && check_admin_referer( 'mailchimp_woocommerce_options', 'mailchimp_woocommerce_nonce' ) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['log_file'] ) ) ) : false;
}
if ( ! empty( $requested_log_file ) && isset( $logs[ sanitize_title( $requested_log_file ) ] ) ) {
	$viewed_log = $logs[ sanitize_title( $requested_log_file ) ];
} elseif ( ! empty( $logs ) ) {
	$viewed_log = current( $logs );
} else {
	$viewed_log = null;
}
$handle = ! empty( $viewed_log ) ? substr( $viewed_log, 0, strlen( $viewed_log ) > 37 ? strlen( $viewed_log ) - 37 : strlen( $viewed_log ) - 4 ) : '';

?>
<input type="hidden" name="mailchimp_active_settings_tab" value="<?php echo MC_WC_LOGS_TAB; ?>"/>
<div class="mc-wc-tab-content-wrapper logs">
    <div class="mc-wc-tab-content-box has-underline">
        <div class="mc-wc-tab-content-title">
            <h3><?php esc_html_e('Activity logs', 'mailchimp-for-woocommerce' ); ?></h3>
        </div>
        <div class="mc-wc-tab-content-description-small mc-wc-fz-13">
        <?php esc_html_e('Advanced troubleshooting can be conducted with the logging capability turned on. By default, it’s set to “standard” and you may toggle to either “debug” or “none” as needed. With standard logging, you can see basic information about the data submission to Mailchimp including any errors. “Debug” gives a much deeper insight that is useful to share with support if problems arise.', 'mailchimp-for-woocommerce' ); ?>
        </div>
        <div class="mc-wc-logs-list-choose">
            <p>
            <?php esc_html_e('Logging preferences', 'mailchimp-for-woocommerce' ); ?>
            </p>
            <div class="mc-wc-select-wrapper">
                <select class="mc-wc-select mc-wc-select-not-bold" id="mailchimp-log-pref" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_logging]" required>
                    <?php $logging_preference = mailchimp_environment_variables()->logging; ?>
                    <?php
                    foreach ( array(
                        'none'     => esc_html__( 'None', 'mailchimp-for-woocommerce' ),
                        'debug'    => esc_html__( 'Debug', 'mailchimp-for-woocommerce' ),
                        'standard' => esc_html__( 'Standard', 'mailchimp-for-woocommerce' ),
                    ) as $log_value => $log_label ) {
                        echo '<option value="' . esc_attr( $log_value ) . '" ' . selected( $log_value === $logging_preference, true, false ) . '>' . esc_html( $log_label ) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>

    <div class="mc-wc-tab-content-box">
        <div class="mc-wc-logs-recent-list">
            <p><?php esc_html_e('Recent logs', 'mailchimp-for-woocommerce' ); ?></p>
            <div class="mc-wc-logs-recent-list-wrapper">
                <div class="mc-wc-logs-recent-list-header">
                    <div class="log-file-actions">
                        <div class="mailchimp-select-wrapper view-log-select">
                            <select class="mc-wc-select mc-wc-select-not-bold" id="log_file" name="log_file">
                                <?php foreach ( $logs as $log_key => $log_file ) : ?>
                                    <option value="<?php echo esc_attr( $log_key ); ?>" <?php selected( sanitize_title( $viewed_log ), $log_key ); ?>>
                                        <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), filemtime( WC_LOG_DIR . $log_file ) ) ); ?> -
                                        <?php echo esc_html( $log_file ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="log-actions">
                            <a class="mc-woocommerce-log-button mc-woocommerce-copy-log-button" title="<?php esc_html_e( 'Copy Log to clipboard', 'mailchimp-for-woocommerce' ); ?>" href="#">
                                <span class="clipboard" style="transform: rotate(-45deg) translateY(2px) translateX(-2px);">
                                    <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M7 15H14" stroke="#7C7C7C" stroke-linecap="round"/>
                                        <path d="M7 12H14" stroke="#7C7C7C" stroke-linecap="round"/>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M5.25 2.79175C4.2835 2.79175 3.5 3.50063 3.5 4.37508V18.6251C3.5 19.4995 4.2835 20.2084 5.25 20.2084H15.75C16.7165 20.2084 17.5 19.4995 17.5 18.6251V4.37508C17.5 3.50063 16.7165 2.79175 15.75 2.79175H5.25ZM7.875 4.37508H5.25V18.6251H15.75V4.37508H13.125V5.16675C13.125 5.60397 12.7332 5.95842 12.25 5.95842H8.75C8.26675 5.95842 7.875 5.60397 7.875 5.16675V4.37508Z" fill="#7C7C7C"/>
                                        <mask id="path-4-inside-1_4285_9690" fill="white">
                                        <rect x="6" width="9" height="4" rx="1"/>
                                        </mask>
                                        <rect x="6" width="9" height="4" rx="1" stroke="#7C7C7C" stroke-width="2.8" mask="url(#path-4-inside-1_4285_9690)"/>
                                        <path d="M7 9H14" stroke="#7C7C7C" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <span class="dashicons dashicons-yes yes"></span>
                            </a>
                            <a class="mc-woocommerce-log-button delete-log-button" title="<?php esc_html_e( 'Delete Log', 'mailchimp-for-woocommerce' ); ?>" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'handle' => sanitize_title( $viewed_log ) ), admin_url( 'admin.php?page=mailchimp-woocommerce&tab=logs&mc_action=remove_log' ) ), 'remove_log' ) ); ?>">
                                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_4285_9688)">
                                    <path d="M6.73672 14.25L5.73672 6.75H7.24999L8.24999 14.25H6.73672Z" fill="#7C7C7C"/>
                                    <path d="M9.73673 14.25L10.7367 6.75H12.25L11.25 14.25H9.73673Z" fill="#7C7C7C"/>
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.25 0.75H12.75V3.75H17.25V5.25H15.7566L14.1566 17.25H3.84336L2.24337 5.25H0.75V3.75H5.25V0.75ZM11.25 2.25V3.75H6.75V2.25H11.25ZM14.2434 5.25H3.75664L5.15664 15.75H12.8434L14.2434 5.25Z" fill="#7C7C7C"/>
                                    </g>
                                    <defs>
                                    <clipPath id="clip0_4285_9688">
                                    <rect width="18" height="18" fill="white"/>
                                    </clipPath>
                                    </defs>
                                </svg>
                            </a>
                        </div>
                    </div>
                    
                </div>
                <div class="mc-wc-logs-recent-list-show">
                    <?php if ( isset( $logs ) && isset( $viewed_log ) ) : ?>
                        <div id="log-viewer">
                        <span class="spinner" style="display:none;"></span>
                        <textarea id="log-content" readonly>
                            <?php echo esc_html( file_get_contents( WC_LOG_DIR . $viewed_log ) ); ?>
                        </textarea>
                        <?php else : ?>
                        <div class="updated woocommerce-message inline">
                            <p>
                                <?php esc_html_e( 'There are currently no logs to view.', 'woocommerce' ); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>