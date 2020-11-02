<?php
// if we don't have a valid api key we need to redirect back to the 'api_key' tab.
if (!$handler->hasValidApiKey() || (!isset($mailchimp_lists) && ($mailchimp_lists = $handler->getMailChimpLists()) === false)) {
    wp_redirect('admin.php?page=mailchimp-woocommerce&tab=api_key&error_notice=missing_api_key');

}

// if we don't have valid store information, we need to redirect back to the 'store_info' tab.
if (!$handler->hasValidStoreInfo()) {
    wp_redirect('admin.php?page=mailchimp-woocommerce&tab=store_info&error_notice=missing_store');
}

$list_is_configured = isset($options['mailchimp_list']) && (!empty($options['mailchimp_list'])) && array_key_exists($options['mailchimp_list'], $mailchimp_lists);

?>

<?php if(($newsletter_settings_error = $this->getData('errors.mailchimp_list', false))) : ?>
    <div class="error notice is-dismissable">
        <p><?php echo $newsletter_settings_error; ?></p>
    </div>
<?php endif; ?>

<input type="hidden" name="mailchimp_active_settings_tab" value="newsletter_settings"/>
<fieldset>
    <legend class="screen-reader-text">
        <span><?php esc_html_e('Audience Settings', 'mailchimp-for-woocommerce');?></span>
    </legend>
    
    <div class="box fieldset-header no-padding" >
        <h3><?php esc_html_e('Sync audience with your store', 'mailchimp-for-woocommerce');?></h3>
    </div>

    <div class="box" >
        <label for="<?php echo $this->plugin_name; ?>-mailchimp-list-label">
            <strong><?php esc_html_e('Audience name', 'mailchimp-for-woocommerce'); ?></strong>
        </label>
        <div class="mailchimp-select-wrapper">
            <select name="<?php echo $this->plugin_name; ?>[mailchimp_list]" required <?php echo ($list_is_configured || $only_one_list) ? 'disabled' : '' ?>>

                <?php if(!isset($allow_new_list) || $allow_new_list === true): ?>
                    <option value="create_new"><?php esc_html_e('Create New Audience', 'mailchimp-for-woocommerce');?></option>
                <?php endif ?>

                <?php if(isset($allow_new_list) && $allow_new_list === false): ?>
                    <option value="">-- <?php esc_html_e('Select Audience', 'mailchimp-for-woocommerce');?> --</option>
                <?php endif; ?>

                <?php
                if (is_array($mailchimp_lists)) {
                    $selected_list = isset($options['mailchimp_list']) ? $options['mailchimp_list'] : null;
                    foreach ($mailchimp_lists as $key => $value ) {
                        echo '<option value="' . esc_attr( $key ) . '" ' . selected(((string) $key === (string) $selected_list || $only_one_list), true, false) . '>' . esc_html( $value ) . '</option>';
                    }
                }
                ?>
            </select>
        </div>
    </div>

    <div class="box" >
        <?php $enable_auto_subscribe = (array_key_exists('mailchimp_auto_subscribe', $options) && !is_null($options['mailchimp_auto_subscribe'])) ? $options['mailchimp_auto_subscribe'] : '1'; ?>
        <label>
            <input
                    type="checkbox"
                    name="<?php echo $this->plugin_name; ?>[mailchimp_auto_subscribe]"
                    id="<?php echo $this->plugin_name; ?>[mailchimp_auto_subscribe]"
                <?= $list_is_configured ? 'disabled': '' ?>
                    value=1
                <?= $enable_auto_subscribe ? 'checked' : ''?>>
            <strong><?php esc_html_e('During initial sync, auto subscribe the existing customers.', 'mailchimp-for-woocommerce'); ?></strong>
        </label>
    </div>

    <div class="box fieldset-header" >
        <h3><?php esc_html_e('Audience Defaults', 'mailchimp-for-woocommerce');?></h3>
    </div>
    <div class="box box-half">
        <label for="<?php echo $this->plugin_name; ?>-campaign-from-name-label"> 
            <?php esc_html_e('From Name', 'mailchimp-for-woocommerce'); ?>
            <span class="required-field-mark">*</span>
        </label>
        <input type="text" id="<?php echo $this->plugin_name; ?>-campaign-from-name-label" name="<?php echo $this->plugin_name; ?>[campaign_from_name]" value="<?php echo isset($options['campaign_from_name']) ? $options['campaign_from_name'] : get_option('blogname') ?>" required/>
    </div>

    <div class="box box-half">
        <label for="<?php echo $this->plugin_name; ?>-campaign-from-email-label">
            <?php esc_html_e('From Email', 'mailchimp-for-woocommerce'); ?>
            <span class="required-field-mark">*</span>
        </label>
        <input type="email" id="<?php echo $this->plugin_name; ?>-campaign-from-email-label" name="<?php echo $this->plugin_name; ?>[campaign_from_email]" value="<?php echo isset($options['campaign_from_email']) ? $options['campaign_from_email'] : get_option('admin_email') ?>" required/>
    </div>

    <div class="box box-half">
        <label for="<?php echo $this->plugin_name; ?>-campaign-subject-label">
            <?php esc_html_e('Subject', 'mailchimp-for-woocommerce'); ?>
            <span class="required-field-mark">*</span>
        </label>
        <input type="text" id="<?php echo $this->plugin_name; ?>-campaign-subject-label" name="<?php echo $this->plugin_name; ?>[campaign_subject]" value="<?php echo isset($options['campaign_subject']) ? $options['campaign_subject'] : esc_html__('Store Newsletter', 'mailchimp-for-woocommerce'); ?>" required/>
    </div>

    <div class="box box-half">
        <label for="<?php echo $this->plugin_name; ?>-campaign-language-label">
            <?php esc_html_e('Language', 'mailchimp-for-woocommerce'); ?>
            <span class="required-field-mark">*</span>
        </label>
        
        <div class="mailchimp-select-wrapper">
            <select id="<?php echo $this->plugin_name; ?>-campaign-language-label" name="<?php echo $this->plugin_name; ?>[campaign_language]" required>
                <?php $selected_locale = isset($options['store_locale']) && !empty($options['store_locale']) ? $options['store_locale'] : get_locale(); ?> ?>
                <?php
                foreach(MailChimp_Api_Locales::all() as $locale_key => $local_value) {
                    echo '<option value="' . esc_attr( $locale_key ) . '" ' . selected($locale_key === $selected_locale, true, false ) . '>' . esc_html( $local_value ) . '</option>';
                }
                ?>
            </select>    
        </div>
    </div>

    <div class="box">
        <label for="<?php echo $this->plugin_name; ?>-campaign-permission-reminder-label">
            <?php esc_html_e('Permission reminder message', 'mailchimp-for-woocommerce'); ?>
            <span class="required-field-mark">*</span>
        </label>
        <textarea
            rows="4"
            id="<?php echo $this->plugin_name; ?>-campaign-permission-reminder-label"
            name="<?php echo $this->plugin_name; ?>[campaign_permission_reminder]"
            required><?php echo isset($options['campaign_permission_reminder']) ? $options['campaign_permission_reminder'] : sprintf(/* translators: %s - plugin name. */esc_html__( 'You were subscribed to the newsletter from %s', 'mailchimp-for-woocommerce' ),get_option('blogname'));?>
        </textarea>
    </div>



    <div class="optional-settings-content">
        <div class="box fieldset-header" >
            <h3><?php esc_html_e('Opt-in Settings', 'mailchimp-for-woocommerce');?></h3>
        </div>

        <div class="box box-half">
            <label for="<?php echo $this->plugin_name; ?>-newsletter-checkbox-label">
                <h4><?php esc_html_e('Message for the opt-in checkbox', 'mailchimp-for-woocommerce'); ?></h4>
                <p><?php _e('The call-to-action text that prompts customers to subscribe to your newsletter at checkout.', 'mailchimp-for-woocommerce');?> </p>
            </label>
        </div>

        <div class="box box-half">
            <textarea rows="3" id="<?php echo $this->plugin_name; ?>-newsletter-checkbox-label" name="<?php echo $this->plugin_name; ?>[newsletter_label]"><?php echo isset($options['newsletter_label']) ? esc_html($options['newsletter_label']) : esc_html__('Subscribe to our newsletter', 'mailchimp-for-woocommerce'); ?></textarea>
            <p class="description"><?= esc_html(__('HTML tags allowed: <a href="" target="" title=""></a> and <br>', 'mailchimp-for-woocommerce')); ?></p>
        </div>

        <div class="box box-half margin-large">
            <label>
                <h4><?php esc_html_e('Checkbox Display Options', 'mailchimp-for-woocommerce');?></h4>
                <p><?php _e('Choose how you want the opt-in to your newsletter checkbox to render at checkout', 'mailchimp-for-woocommerce');?> </p>
            </label>
        </div>


        <div class="box box-half margin-large">
            <?php $checkbox_default_settings = (array_key_exists('mailchimp_checkbox_defaults', $options) && !is_null($options['mailchimp_checkbox_defaults'])) ? $options['mailchimp_checkbox_defaults'] : 'check'; ?>
            <label class="radio-label">
                <input type="radio" name="<?php echo $this->plugin_name; ?>[mailchimp_checkbox_defaults]" value="check"<?php if($checkbox_default_settings === 'check') echo ' checked="checked" '; ?>><?php esc_html_e('Visible, checked by default', 'mailchimp-for-woocommerce');?><br>
            </label>
            <label class="radio-label">
                <input type="radio" name="<?php echo $this->plugin_name; ?>[mailchimp_checkbox_defaults]" value="uncheck"<?php if($checkbox_default_settings === 'uncheck') echo ' checked="checked" '; ?>><?php esc_html_e('Visible, unchecked by default', 'mailchimp-for-woocommerce');?><br/>
            </label>
            <label class="radio-label">
                <input type="radio" name="<?php echo $this->plugin_name; ?>[mailchimp_checkbox_defaults]" value="hide"<?php if($checkbox_default_settings === 'hide') echo ' checked="checked" '; ?>><?php esc_html_e('Hidden, unchecked by default', 'mailchimp-for-woocommerce');?><br/>
            </label>
        </div>


        <div class="box box-half margin-large">
            <label for="<?php echo $this->plugin_name; ?>-newsletter-checkbox-action">
                <h4><?php esc_html_e('Advanced Checkbox Settings', 'mailchimp-for-woocommerce');?></h4>
                <p><?= sprintf(/* translators: %s - Woocommerce Actions documentation URL. */wp_kses( __( 'To change the location of the opt-in <br/>checkbox at checkout, input one of the <a href=%s target=_blank>available WooCommerce form actions</a>.', 'mailchimp-for-woocommerce' ), array(  'a' => array( 'href' => array(), 'target'=> '_blank' ) ) ), esc_url( 'https://docs.woocommerce.com/wc-apidocs/hook-docs.html' ) ); ?></p>
            </label>
        </div>

        <div class="box box-half margin-large">
            <input type="text" id="<?php echo $this->plugin_name; ?>-newsletter-checkbox-action" name="<?php echo $this->plugin_name; ?>[mailchimp_checkbox_action]" value="<?php echo isset($options['mailchimp_checkbox_action']) ? $options['mailchimp_checkbox_action'] : 'woocommerce_after_checkout_billing_form' ?>" />
            <p class="description"><?php esc_html_e('Enter a WooCommerce form action', 'mailchimp-for-woocommerce'); ?></p>
        </div>

        <div class="box fieldset-header" >
            <h3><?php esc_html_e('Subscriber Settings', 'mailchimp-for-woocommerce');?></h3>
        </div>

        <div class="box box-half" >
            <label for="<?php echo $this->plugin_name; ?>-user-tags">
                <h4><?php esc_html_e('Tags', 'mailchimp-for-woocommerce');?></h4>
                <p><?= __( 'Add a comma-separated list of tags to apply to a subscriber in Mailchimp after a transaction occurs', 'mailchimp-for-woocommerce' ); ?></p>
            </label>
        </div>

        <div class="box box-half" >
            <input type="text" id="<?php echo $this->plugin_name; ?>-user-tags" name="<?php echo $this->plugin_name; ?>[mailchimp_user_tags]" value="<?php echo isset($options['mailchimp_user_tags']) ? str_replace(',',', ',$options['mailchimp_user_tags']) : '' ?>" />   
        </div>

        <div class="box fieldset-header" >
            <h3><?php esc_html_e('Product Settings', 'mailchimp-for-woocommerce');?></h3>
        </div>


        <div class="box box-half">
            <label for="<?php echo $this->plugin_name; ?>[mailchimp_product_image_key]">
                <h4><?php esc_html_e('Product Image Size', 'mailchimp-for-woocommerce');?></h4>
                <p><?= __( 'Define the product image size used by abandoned carts, order notifications, and product recommendations.', 'mailchimp-for-woocommerce' ); ?></p>
            </label>
        </div>

        <div class="box box-half" >
            <div class="mailchimp-select-wrapper">
                <select name="<?php echo $this->plugin_name; ?>[mailchimp_product_image_key]">
                    <?php
                    $enable_auto_subscribe = (array_key_exists('mailchimp_product_image_key', $options) && !is_null($options['mailchimp_product_image_key'])) ? $options['mailchimp_product_image_key'] : 'medium';
                    foreach (mailchimp_woocommerce_get_all_image_sizes_list() as $key => $value ) {
                        echo '<option value="' . esc_attr( $key ) . '" ' . selected($key == $enable_auto_subscribe, true, false ) . '>' . esc_html( $value ) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>
</fieldset>
