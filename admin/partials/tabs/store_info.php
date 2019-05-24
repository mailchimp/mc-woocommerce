<?php

$handler = MailChimp_WooCommerce_Admin::connect();

// if we don't have valid campaign defaults we need to redirect back to the 'campaign_defaults' tab.
if (!$handler->hasValidApiKey()) {
    wp_redirect('admin.php?page=mailchimp-woocommerce&tab=api_key&error_notice=missing_api_key');
}

?>
<input type="hidden" name="mailchimp_active_settings_tab" value="store_info"/>

<fieldset class="half">
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Store Name', 'mc-woocommerce');?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-store-name-label">
            <span>
                <?php
            if (!empty($options['store_name']) ) {
                esc_html_e('Name', $this->plugin_name);
            } else {
                esc_html_e('Name', $this->plugin_name); echo '<span style="color:red;">*</span>';
            }
            ?>
        </span>
    </label>
    <input required type="text" id="<?php echo $this->plugin_name; ?>-store-name-label" name="<?php echo $this->plugin_name; ?>[store_name]" value="<?php echo isset($options['store_name']) ? $options['store_name'] : get_option('blogname') ?>" />
</fieldset>

<fieldset class="half">
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Email', 'mc-woocommerce');?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-admin-email-label">
            <span>
                <?php
            if (!empty($options['admin_email']) ) {
                esc_html_e('Email', $this->plugin_name);
            } else {
                esc_html_e('Email', $this->plugin_name); echo '<span style="color:red;">*</span>';
            }
            ?>
        </span>
    </label>
    <input required type="email" id="<?php echo $this->plugin_name; ?>-admin-email-label" name="<?php echo $this->plugin_name; ?>[admin_email]" value="<?php echo isset($options['admin_email']) ? $options['admin_email'] : get_option('admin_email') ?>" />
</fieldset>

<fieldset class="half">
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Street Address', 'mc-woocommerce');?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-store-address-label">
            <span>
                <?php
            if (!empty($options['store_street']) ) {
                esc_html_e('Street address', $this->plugin_name);
            } else {
                esc_html_e('Street address', $this->plugin_name); echo '<span style="color:red;">*</span>';
            }
            ?>
        </span>
    </label>
    <input required type="text" id="<?php echo $this->plugin_name; ?>-store-address-label" name="<?php echo $this->plugin_name; ?>[store_street]" value="<?php echo isset($options['store_street']) ? $options['store_street'] : '' ?>" />
</fieldset>

<fieldset class="half">
    <legend class="screen-reader-text">
        <span><?php esc_html_e('City', 'mc-woocommerce');?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-store-city-label">
            <span>
                <?php
            if (!empty($options['store_city']) ) {
                esc_html_e('City', $this->plugin_name);
            } else {
                esc_html_e('City', $this->plugin_name); echo '<span style="color:red;">*</span>';
            }
            ?>
        </span>
    </label>
    <input required type="text" id="<?php echo $this->plugin_name; ?>-store-city-label" name="<?php echo $this->plugin_name; ?>[store_city]" value="<?php echo isset($options['store_city']) ? $options['store_city'] : '' ?>" />
</fieldset>

<fieldset class="half">
    <legend class="screen-reader-text">
        <span><?php esc_html_e('State', 'mc-woocommerce');?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-store-state-label">
            <span>
                <?php
            if (!empty($options['store_state']) ) {
                esc_html_e('State', $this->plugin_name);
            } else {
                esc_html_e('State', $this->plugin_name); echo '<span style="color:red;">*</span>';
            }
            ?>
        </span>
    </label>
    <input required type="text" id="<?php echo $this->plugin_name; ?>-store-state-label" name="<?php echo $this->plugin_name; ?>[store_state]" value="<?php echo isset($options['store_state']) ? $options['store_state'] : '' ?>" />
</fieldset>

<fieldset class="half">
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Postal Code', 'mc-woocommerce');?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-store-postal-code-label">
            <span>
                <?php
            if (!empty($options['store_postal_code']) ) {
                esc_html_e('Postal Code', $this->plugin_name);
            } else {
                esc_html_e('Postal Code', $this->plugin_name); echo '<span style="color:red;">*</span>';
            }
            ?>
        </span>
    </label>
    <input required type="text" id="<?php echo $this->plugin_name; ?>-store-postal-code-label" name="<?php echo $this->plugin_name; ?>[store_postal_code]" value="<?php echo isset($options['store_postal_code']) ? $options['store_postal_code'] : '' ?>" />
</fieldset>

<fieldset class="half">
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Country', 'mc-woocommerce');?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-store-country-label">
            <span>
                <?php
            if (!empty($options['store_country'])) {
                esc_html_e('Country', $this->plugin_name);
            } else {
                esc_html_e('Country', $this->plugin_name); echo '<span style="color:red;">*</span>';
            }
            ?>
        </span>
    </label>
    <input required type="text" id="<?php echo $this->plugin_name; ?>-store-country-label" name="<?php echo $this->plugin_name; ?>[store_country]" value="<?php echo isset($options['store_country']) ? $options['store_country'] : 'US' ?>" />
</fieldset>

<fieldset class="half">
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Phone Number', 'mc-woocommerce');?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-store-phone-label">
            <span>
                <?php
            if (!empty($options['store_phone']) ) {
                esc_html_e('Phone Number', $this->plugin_name);
            } else {
                esc_html_e('Phone Number', $this->plugin_name); echo '<span style="color:red;">*</span>';
            }
            ?>
        </span>
    </label>
    <input type="text" id="<?php echo $this->plugin_name; ?>-store-phone-label" name="<?php echo $this->plugin_name; ?>[store_phone]" value="<?php echo isset($options['store_phone']) ? $options['store_phone'] : '' ?>" />
</fieldset>

<h2 style="padding-top: 1em;"><?= __('Locale Settings', $this->plugin_name);?></h2>

<p><?= __('Please apply your locale settings. If you\'re unsure about these, use the defaults.', $this->plugin_name);?></p>

<fieldset class="third">
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Locale', 'mc-woocommerce');?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-store-locale-label">
        <span><?php esc_html_e('Locale', $this->plugin_name); ?></span>
    </label>
    <div class="mailchimp-select-wrapper">
        <select name="<?php echo $this->plugin_name; ?>[store_locale]" required>
            <?php $selected_locale = isset($options['store_locale']) && !empty($options['store_locale']) ? $options['store_locale'] : 'en'; ?>
            <?php
            foreach(MailChimp_Api_Locales::simple() as $locale_key => $local_value) {
                echo '<option value="' . esc_attr( $locale_key ) . '" ' . selected($locale_key === $selected_locale, true, false ) . '>' . esc_html( $local_value ) . '</option>';
            }
            ?>
        </select>
    </div>
</fieldset>

<fieldset class="third">
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Currency Code', 'mc-woocommerce');?></span>
    </legend>
    <?php $all_currencies = MailChimp_WooCommerce_CurrencyCodes::lists();?>
    <label for="<?php echo $this->plugin_name; ?>-store-currency-code-label">
        <span><?php esc_html_e('Woocommerce Currency', $this->plugin_name); ?></span>
    </label>
    <input type="text" value="<?php echo isset($options['store_currency_code']) ? $options['store_currency_code'] . ' | ' . $all_currencies[$options['store_currency_code']]: '' ?>" disabled/>
</fieldset>

<fieldset class="third">
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Timezone', 'mc-woocommerce');?></span>
    </legend>
    <label for="<?php echo $this->plugin_name; ?>-store-timezone-label">
        <span><?php esc_html_e('Timezone', $this->plugin_name); ?></span>
    </label>
    <div class="mailchimp-select-wrapper">
        <select name="<?php echo $this->plugin_name; ?>[store_timezone]" required>
            <?php $selected_timezone = isset($options['store_timezone']) && !empty($options['store_timezone']) ? $options['store_timezone'] : 'America/New_York'; ?>
            <?php
                foreach(mailchimp_get_timezone_list() as $t) {
                echo '<option value="' . esc_attr( $t['zone'] ) . '" ' . selected($t['zone'] === $selected_timezone, true, false ) . '>' . esc_html( $t['diff_from_GMT'] . ' - ' . $t['zone'] ) . '</option>';
                }
            ?>
        </select>
    </div>
</fieldset>


