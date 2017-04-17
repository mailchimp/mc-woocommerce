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
