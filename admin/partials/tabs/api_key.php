<input type="hidden" name="mailchimp_active_settings_tab" value="api_key"/>

<!-- remove some meta and generators from the <head> -->
<fieldset class="full">
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Connect your store to Mailchimp', 'mailchimp-for-woocommerce');?></span>
    </legend>
    
    
    <a id="mailchimp-oauth-connect" class="button button-primary tab-content-submit oauth-connect"><?php $has_valid_api_key ? esc_html_e('Reconnect', 'mailchimp-for-woocommerce') : esc_html_e('Connect', 'mailchimp-for-woocommerce');?></a>
    <h4><?php esc_html_e('Connect your store to Mailchimp', 'mailchimp-for-woocommerce'); ?></h4>
    <input type="hidden" id="<?php echo $this->plugin_name; ?>-mailchimp-api-key" name="<?php echo $this->plugin_name; ?>[mailchimp_api_key]" value="<?php echo isset($options['mailchimp_api_key']) ? $options['mailchimp_api_key'] : '' ?>" required/>
    <?php if ($has_valid_api_key) :?>
        <p id="mailchimp-oauth-api-key-valid"><?php esc_html_e('Already connected. You can reconnect with another Mailchimp account if you want.' , 'mailchimp-for-woocommerce');?></p>
    <?php endif;?>
    <p id="mailchimp-oauth-waiting" class="oauth-description"><?php esc_html_e('Connecting. A new window will open with Mailchimp\'s OAuth service. Please log-in an we will take care of the rest.' , 'mailchimp-for-woocommerce');?></p>
    <p id="mailchimp-oauth-error" class="oauth-description"><?php esc_html_e('Error, can\'t login.' , 'mailchimp-for-woocommerce');?></p>
    <p id="mailchimp-oauth-connecting" class="oauth-description"><?php esc_html_e('Connection in progress' , 'mailchimp-for-woocommerce');?></p>
    <p id="mailchimp-oauth-connected" class="oauth-description "><?php esc_html_e('Connected! Please wait while loading next step', 'mailchimp-for-woocommerce');?></p>
</fieldset>

