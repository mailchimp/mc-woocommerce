


<?php

if (isset($options['mailchimp_api_key']) && !$handler->hasValidApiKey()) {
    include_once __DIR__.'/errors/missing_api_key.php';
}

?>
<input type="hidden" name="mailchimp_active_settings_tab" value="api_key"/>

<h2 style="padding-top: 1em;">API Information</h2>
<p>To find your MailChimp API key, log into your account settings > Extras > API keys. From there, either grab an existing key or generate a new one for your WooCommerce store. </p>

<!-- remove some meta and generators from the <head> -->
<fieldset>
    <legend class="screen-reader-text">
        <span>MailChimp API Key</span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-mailchimp-api-key">
        <input style="width: 30%;" type="password" id="<?php echo $this->plugin_name; ?>-mailchimp-api-key" name="<?php echo $this->plugin_name; ?>[mailchimp_api_key]" value="<?php echo isset($options['mailchimp_api_key']) ? $options['mailchimp_api_key'] : '' ?>" />
        <span><?php esc_attr_e('Enter your MailChimp API key.', $this->plugin_name); ?></span>
    </label>
</fieldset>

<fieldset>
    <legend class="screen-reader-text">
        <span>Enable Debugging</span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-mailchimp-debugging">
        <select name="<?php echo $this->plugin_name; ?>[mailchimp_debugging]" style="width:30%">

            <?php

            $enable_mailchimp_debugging = (array_key_exists('mailchimp_debugging', $options) && !is_null($options['mailchimp_debugging'])) ? $options['mailchimp_debugging'] : '1';

            foreach (array('0' => 'No', '1' => 'Yes') as $key => $value ) {
                echo '<option value="' . esc_attr($key) . '" ' . selected($key == $enable_mailchimp_debugging, true, false ) . '>' . esc_html( $value ) . '</option>';
            }
            ?>

        </select>
        <span><?php esc_attr_e('Enable debugging logs to be sent to MailChimp.', $this->plugin_name); ?></span>
    </label>
</fieldset>

