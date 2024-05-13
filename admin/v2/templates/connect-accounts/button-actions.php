<?php
/**
 * Button actions (Create account and connect) template
 *
 */
$show_connection_messages = false;
?>

<fieldset class="full connect-button">
	<input type="hidden" name="mailchimp_active_settings_tab" value="api_key"/>
	<legend class="screen-reader-text">
		<span><?php esc_html_e( 'Connect your store to Mailchimp', 'mailchimp-for-woocommerce' ); ?></span>
	</legend>
	<div class="mc-wc-actions">
        <a id="mailchimp-oauth-connect" class="mc-wc-btn mc-wc-btn-primary oauth-connect"><?php esc_html_e( 'Connect Account', 'mailchimp-for-woocommerce' );  ?></a>
		<a class="mc-wc-btn mc-wc-btn-primary-outline create-account" href='#mc-woocommerce-create-account'><?php esc_html_e( 'Create Account', 'mailchimp-for-woocommerce' ); ?></a>
	</div>
	
	<input type="hidden" id="<?php echo esc_attr( $this->plugin_name ); ?>-mailchimp-api-key" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_api_key]" value="<?php echo isset( $options['mailchimp_api_key'] ) ? esc_html( $options['mailchimp_api_key'] ) : ''; ?>" required/>
    <?php if ($show_connection_messages) : ?>
	<p id="mailchimp-oauth-waiting" class="oauth-description"><?php esc_html_e( 'Connecting. A new window will open with Mailchimp\'s OAuth service. Please log-in and we will take care of the rest.', 'mailchimp-for-woocommerce' ); ?></p>
	<p id="mailchimp-oauth-error" class="oauth-description"><?php esc_html_e( 'Error, can\'t login.', 'mailchimp-for-woocommerce' ); ?></p>
	<p id="mailchimp-oauth-connecting" class="oauth-description"><?php esc_html_e( 'Connection in progress', 'mailchimp-for-woocommerce' ); ?><span class="spinner" style="visibility:visible; margin: 0 10px;"></span></p>
	<p id="mailchimp-oauth-connected" class="oauth-description "><?php esc_html_e( 'Connected! Please wait while loading next step', 'mailchimp-for-woocommerce' ); ?></p>
    <?php endif; ?>
</fieldset>
<?php include_once 'create-account-popup.php'; ?>