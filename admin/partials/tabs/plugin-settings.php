<?php
/**
 * Plugin settings page
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/admin
 */

?>
<input type="hidden" name="mailchimp_active_settings_tab" value="plugin_settings"/>

<?php
$store_id = mailchimp_get_store_id();

$opt           = get_option( 'mailchimp-woocommerce-comm.opt' );
$tower_opt     = get_option( 'mailchimp-woocommerce-tower.opt' );
$admin_email   = mailchimp_get_option( 'admin_email', get_option( 'admin_email' ) );
$comm_enabled  = null !== $opt ? $opt : '0';
$tower_enabled = null !== $tower_opt ? $tower_opt : '0';
?>
<fieldset>
	<legend class="screen-reader-text">
		<span><?php esc_html_e( 'Plugin Settings', 'mailchimp-for-woocommerce' ); ?></span>
	</legend>
	<div class="box ">
		<label for="<?php echo esc_attr( $this->plugin_name ); ?>-tower-support-checkbox-label">
			<h4><?php esc_html_e( 'Remote Diagnostics', 'mailchimp-for-woocommerce' ); ?></h4>
			<p>
				<?php
				/* translators: Placeholders %1$s - admin email address */
				$remote_diagnostics = sprintf(
					__( 'Remote diagnostics for the Mailchimp for WooCommerce plugin allows our development team to troubleshoot syncing issues.', 'mailchimp-for-woocommerce' ),
					esc_html( $admin_email )
				);
				esc_html_e( $remote_diagnostics );
				?>
			</p>
		</label>
		<br/>
		<fieldset>
			<p id="mc-comm-wrapper">
				<label class="el-switch el-checkbox-green">
					<input id="tower_box_switch" type="checkbox" name="switch" value="1"<?php echo '1' === $tower_enabled ? ' checked="checked" ' : ''; ?>>
					<span><?php esc_html_e( 'Enable support', 'mailchimp-for-woocommerce' ); ?></span>
					<br/>
					<span class="mc-tower-save" id="mc-tower-save">Saved</span>
				</label>
			</p>
		</fieldset>
	</div>
	<div class="box ">
		<label for="<?php echo esc_attr( $this->plugin_name ); ?>-newsletter-checkbox-label">
			<h4><?php esc_html_e( 'Communication', 'mailchimp-for-woocommerce' ); ?></h4>
			<p>
				<?php
				/* translators: Placeholders %1$s - admin email address */
				$comms = sprintf(
					__( 'Occasionally we may send you information about how-to\'s, updates, and other news to the store\'s admin email address. Choose whether or not you want to receive these messages at %1$s ', 'mailchimp-for-woocommerce' ),
					$admin_email
				);
				esc_html_e( $comms );
				?>
			</p>
		</label>
		<br/>
		<fieldset>    
			<p id="mc-comm-wrapper">
				<label class="el-switch el-checkbox-green">
					<input id="comm_box_switch" type="checkbox" name="switch" value="1"<?php echo '1' === $comm_enabled ? ' checked="checked" ' : ''; ?>>
					<span><?php esc_html_e( 'Opt-in to our newsletter', 'mailchimp-for-woocommerce' ); ?></span>
					<br/>
					<span class="mc-comm-save" id="mc-comm-save">Saved</span>
				</label>
			</p>
		</fieldset>
	</div>
	<div class="box"></div>
	<div class="box">
		<label for="<?php echo esc_attr( $this->plugin_name ); ?>-newsletter-checkbox-label">
			<h4><?php esc_html_e( 'Disconnect Store', 'mailchimp-for-woocommerce' ); ?></h4>
			<p>
				<?php
				$disconnect_store = sprintf(
					__( 'Disconnect your store from Mailchimp. This action will remove all entries from the database but you will be able to reconnect anytime.', 'mailchimp-for-woocommerce' ),
					$admin_email
				);
				esc_html_e( $disconnect_store );
				?>
			</p>
		</label>
		<p>
			<?php wp_nonce_field( '_disconnect-nonce-' . $store_id, '_disconnect-nonce' ); ?>
			<a id="mailchimp_woocommerce_disconnect" class="mc-woocommerce-disconnect-button button button-default tab-content-submit">
				<?php esc_html_e( 'Disconnect Store', 'mailchimp-for-woocommerce' ); ?>
			</a>
		</p>
	</div>
	<div class="box box-half comm_box_wrapper">
	</div>
</fieldset>
