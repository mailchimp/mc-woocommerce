<?php
/**
 * Logs page
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/admin
 */

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

$requested_log_file = get_site_transient( 'mailchimp-woocommerce-view-log-file' );
delete_site_transient( 'mailchimp-woocommerce-view-log-file' );

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


<fieldset>
	<legend class="screen-reader-text">
		<span><?php esc_html_e( 'Logging Preferences', 'mailchimp-for-woocommerce' ); ?></span>
	</legend>
	<div class="box" >
		<label for="<?php echo esc_attr( $this->plugin_name ); ?>-logging"><h3>
				<?php esc_html_e( 'Logging Preferences', 'mailchimp-for-woocommerce' ); ?>
			</h3>
		</label>
	</div>
	<div class="box box-half">
		<p>
			<?php
			esc_html_e(
				'Advanced troubleshooting can be conducted with the logging capability turned on.
            By default, it’s set to “standard” and you may toggle to either “debug” or “none” as needed.
            With standard logging, you can see basic information about the data submission to Mailchimp including any errors.
            “Debug” gives a much deeper insight that is useful to share with support if problems arise.',
				'mailchimp-for-woocommerce'
			);
			?>
		</p>
	</div>
	<div class="box box-half">
		<div class="log-select mailchimp-select-wrapper">
			<select id="mailchimp-log-pref" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_logging]" required>
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
</fieldset>
<fieldset>
	<div class="box fieldset-header" >
		<h3>
			<?php esc_html_e( 'Recent Logs', 'mailchimp-for-woocommerce' ); ?>
		</h3>
	</div>
	<div class="box log-file-actions">
		<input type="hidden" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_active_tab]" value="logs"/>
		<div class="mailchimp-select-wrapper view-log-select">
			<select id="log_file" name="log_file">
				<?php foreach ( $logs as $log_key => $log_file ) : ?>
					<option value="<?php echo esc_attr( $log_key ); ?>" <?php selected( sanitize_title( $viewed_log ), $log_key ); ?>>
						<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), filemtime( WC_LOG_DIR . $log_file ) ) ); ?> -
						<?php echo esc_html( $log_file ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div id="log-actions">
			<?php if ( ! empty( $handle ) ) : ?>
				<a class="mc-woocommerce-log-button mc-woocommerce-copy-log-button" title="<?php esc_html_e( 'Copy Log to clipboard', 'mailchimp-for-woocommerce' ); ?>" href="#">
					<span class="dashicons dashicons-clipboard clipboard" style="transform: rotate(-45deg) translateY(2px) translateX(-2px);"></span>
					<span class="dashicons dashicons-yes yes"></span>
				</a>
				<a class="mc-woocommerce-log-button delete-log-button" title="<?php esc_html_e( 'Delete Log', 'mailchimp-for-woocommerce' ); ?>" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'handle' => sanitize_title( $viewed_log ) ), admin_url( 'admin.php?page=mailchimp-woocommerce&tab=logs&mc_action=remove_log' ) ), 'remove_log' ) ); ?>">
					<span class="dashicons dashicons-trash"></span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</fieldset>
<div class="box">
	<?php if ( isset( $logs ) && isset( $viewed_log ) ) : ?>
		<div id="log-viewer">
			<span class="spinner" style="display:none;"></span>
			<textarea id="log-content" readonly>
				<?php echo esc_html( file_get_contents( WC_LOG_DIR . $viewed_log ) ); ?>
			</textarea>
		</div>
	<?php else : ?>
		<div class="updated woocommerce-message inline">
			<p>
				<?php esc_html_e( 'There are currently no logs to view.', 'woocommerce' ); ?>
			</p>
		</div>
	<?php endif; ?>
</div>
