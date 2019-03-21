<input type="hidden" name="mailchimp_active_settings_tab" value="api_key"/>

<h2 style="padding-top: 1em;"><?php esc_html_e('API Information', $this->plugin_name);?></h2>
<p><?php esc_html_e('To find your Mailchimp API key, log into your account settings > Extras > API keys. From there, either grab an existing key or generate a new one for your WooCommerce store.', $this->plugin_name);?></p>

<!-- remove some meta and generators from the <head> -->
<fieldset>
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Mailchimp API Key', $this->plugin_name);?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-mailchimp-api-key">
        <input style="width: 30%;" type="password" id="<?php echo $this->plugin_name; ?>-mailchimp-api-key" name="<?php echo $this->plugin_name; ?>[mailchimp_api_key]" value="<?php echo isset($options['mailchimp_api_key']) ? $options['mailchimp_api_key'] : '' ?>" />
        <span><?php esc_html_e('Enter your Mailchimp API key.', $this->plugin_name); ?></span>
    </label>
</fieldset>
