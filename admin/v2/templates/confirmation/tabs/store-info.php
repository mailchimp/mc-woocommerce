<?php
/**
 * Store tab template
 *
 * @package    MailChimp_WooCommerce
 * @subpackage MailChimp_WooCommerce/admin
 */

?>
<?php
    $current_currency      = isset( $options['store_currency_code'] ) ? $options['store_currency_code'] : get_woocommerce_currency();
    $current_currency_data = MailChimp_WooCommerce_CurrencyCodes::getCurrency( $current_currency );
    $checkout_page_id = get_option('woocommerce_checkout_page_id');
    if ( ! isset( $options ) ) {
        $options = array();
    }
    $permission_cap_settings = ( array_key_exists( 'mailchimp_permission_cap', $options ) && ! is_null( $options['mailchimp_permission_cap'] ) ) ? $options['mailchimp_permission_cap'] : 'administrator';
    if ($permission_cap_settings === 'manage_options') {
        $permission_cap_settings = 'manage_woocommerce';
    }
    $checkbox_default_settings = ( array_key_exists( 'mailchimp_checkbox_defaults', $options ) && ! is_null( $options['mailchimp_checkbox_defaults'] ) ) ? $options['mailchimp_checkbox_defaults'] : 'check';
	$is_has_wc_checkout_block = has_block( 'woocommerce/checkout', (int) $checkout_page_id );

    $default_opt_in_settings = array(
        [ 'label' => esc_html__( 'Checked by default', 'mailchimp-for-woocommerce' ), 'value' => 'check' ],
        [ 'label' => esc_html__( 'Unchecked by default', 'mailchimp-for-woocommerce' ), 'value' => 'uncheck' ],
    );

    $opt_in_settings = apply_filters('mailchimp_checkout_opt_in_options', $default_opt_in_settings);;
?>
<input type="hidden" name="mailchimp_active_settings_tab" value="<?php echo MC_WC_STORE_INFO_TAB; ?>"/>
<input type="hidden" value="<?php echo ( esc_html( isset( $current_currency_data ) ? $current_currency . ' | ' . $current_currency_data['name'] : $current_currency ) ); ?>" disabled/>
	<input type="hidden" value="<?php echo esc_attr( mailchimp_get_timezone( true ) ); ?>" disabled/>
<div class="mc-wc-tab-content-wrapper store-info">
    <div class="mc-wc-tab-content-box">
        <div class="mc-wc-tab-content-title">
            <h3><?php esc_html_e('Store settings', 'mailchimp-for-woocommerce' ); ?></h3>
        </div>
        <div class="mc-wc-tab-content-description">
            <?php
                echo wp_kses_post(sprintf(
                /* translators: %1$s - The Currency name and format (ex: USD | US Dollar) %2$s - Timezone name or offset (ex: America/New_York or UTC-4:00) %3$s and %5$s- <a> tag open %4$s - </a> tag close*/
                    __( 'We\'ve detected that your WooCommerce store\'s currency is <b>%1$s</b> (%3$schange%4$s), and the WordPress timezone is a <b>%2$s</b> (%5$schange%4$s). Please apply your locale settings. If you\'re unsure about these, use the defaults.', 'mailchimp-for-woocommerce' ),
                    esc_attr( isset( $current_currency_data ) ? $current_currency . ' | ' . $current_currency_data['name'] : $current_currency ),
                    mailchimp_get_timezone( true ) ,
                    '<a href="' . admin_url( 'admin.php?page=wc-settings#woocommerce_currency' ) . '" title="' . __( 'General Settings' ) . '">',
                    '</a>',
                    '<a href="' . admin_url( 'options-general.php#timezone_string' ) . '" title="' . __( 'WooCommerce Settings' ) . '">'
                ));
            ?>
        </div>
        <div class="mc-wc-locale">
            <label><?php esc_html_e('Locale', 'mailchimp-for-woocommerce' ); ?></label>
            <div class="mc-wc-select-wrapper">
                <select class="mc-wc-select mc-wc-select-not-bold" name="<?php echo esc_attr( $this->plugin_name ); ?>[store_locale]" required>
                    <option disabled selected value=""><?php esc_html_e( "Select store's locale", 'mailchimp-for-woocommerce' ); ?></option>
                    <?php
                    $selected_locale = isset( $options['store_locale'] ) && ! empty( $options['store_locale'] ) ? $options['store_locale'] : get_locale();
                    ?>
                    <?php foreach ( MailChimp_Api_Locales::all() as $locale_key => $local_value ) : ?>
                        <option value="<?php echo esc_attr( $locale_key ); ?>" <?php selected( $locale_key === $selected_locale ); ?>><?php esc_html_e( $local_value ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="mc-wc-tab-content-box permission-setting">
        <div class="mc-wc-tab-content-description">
            <?php esc_html_e('Select the minimum permission capability to manage Mailchimp for Woocommerce options.', 'mailchimp-for-woocommerce' ); ?>
        </div>
        <div class="mc-wc-permission-wrapper">
            <label><?php esc_html_e('Plugin permission level', 'mailchimp-for-woocommerce' ); ?></label>
            <div class="mc-wc-permission-choose">
                <div class="mc-wc-radio">
                    <label class="mc-wc-radio-label">
                        <input type="radio" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_permission_cap]" value="manage_woocommerce"<?php echo 'manage_woocommerce' === $permission_cap_settings ? ' checked="checked" ' : ''; ?>>
                        <?php esc_html_e('Shop Managers and Administrators', 'mailchimp-for-woocommerce' ); ?>
                    </label>
                </div>
                <div class="mc-wc-radio">
                    <label class="mc-wc-radio-label">
                        <input type="radio" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_permission_cap]" value="administrator"<?php echo 'administrator' === $permission_cap_settings ? ' checked="checked" ' : ''; ?>>
                        <?php esc_html_e('Administrators Only', 'mailchimp-for-woocommerce' ); ?>
                    </label>
                </div>
            </div>
		</div>
    </div>

    <div class="mc-wc-tab-content-box checkout-page-setting">
        <div class="mc-wc-tab-content-title">
            <h3><?php esc_html_e('Checkout page settings', 'mailchimp-for-woocommerce' ); ?></h3>
        </div>
        <div class="mc-wc-tab-content-description">
        <?php
        if ( $is_has_wc_checkout_block ):
            echo sprintf(
                __( 'Your checkout page is using WooCommerce blocks. Settings are available within the block options while editing the <a href=%s target="_blank">checkout page</a>.', 'mailchimp-for-woocommerce' ),
                get_the_permalink($checkout_page_id)
            );
        else:
            echo sprintf(
                __( 'Your checkout page is using WooCommerce the Classic Checkout Shortcode. To change the opt-in checkbox at checkout, input one of the <a href=%s target="_blank">available WooCommerce form actions</a>.', 'mailchimp-for-woocommerce' ),
                esc_url( 'https://woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/')
            );
        endif;
        ?>
        </div>
        <?php if ( ! $is_has_wc_checkout_block ): ?>
            <div class="mc-wc-input-wrapper">
                <input class="mc-wc-input style-2" type="text" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_checkbox_action]" value="<?php echo isset( $options['mailchimp_checkbox_action'] ) ? esc_html( $options['mailchimp_checkbox_action'] ) : 'woocommerce_after_checkout_billing_form'; ?>" />
                <p class="description"><?php esc_html_e( 'Enter a WooCommerce form action', 'mailchimp-for-woocommerce' ); ?></p>
            </div>

            <div class="mc-wc-permission-wrapper">
                <label><?php esc_html_e('Checkbox display options', 'mailchimp-for-woocommerce' ); ?></label>
                <div class="mc-wc-permission-choose">
                    <?php foreach ($opt_in_settings as $attribute) : ?>
                    <div class="mc-wc-radio">
                        <label class="mc-wc-radio-label">
                            <input type="radio" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_checkbox_defaults]" value="<?php echo esc_attr($attribute['value']); ?>"<?php echo $attribute['value'] === $checkbox_default_settings ? ' checked="checked" ' : ''; ?>>
                            <?php echo $attribute['label']; ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mc-wc-input-wrapper">
                <h4 class="opt-in-checkbox-label"><?php esc_html_e( 'Checkbox message', 'mailchimp-for-woocommerce' ); ?></h4>
                <textarea rows="3" class="opt-in-checkbox-text" id="<?php echo esc_attr( $this->plugin_name ); ?>-newsletter-checkbox-label" name="<?php echo esc_attr( $this->plugin_name ); ?>[newsletter_label]"><?php echo isset( $options['newsletter_label'] ) ? esc_html( $options['newsletter_label'] ) : ''; ?></textarea>
                <p class="description">
                    <?php echo esc_html( __( 'HTML tags allowed: <a href="" target="" title=""></a> and <br>', 'mailchimp-for-woocommerce' ) ); ?><br/>
                    <?php echo esc_html( __( 'Leave it blank to use language translation files (.po / .mo), translating the string: "Subscribe to our newsletter".', 'mailchimp-for-woocommerce' ) ); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <div class="mc-wc-tab-content-box product-image-setting">
        <div class="mc-wc-tab-content-title">
            <h3><?php esc_html_e('Product image size', 'mailchimp-for-woocommerce' ); ?></h3>
        </div>
        <div class="mc-wc-tab-content-description">
        <?php
            esc_html_e( 'Define the product image size used by abandoned carts, order notifications, and product recommendations.', 'mailchimp-for-woocommerce' );
        ?>
        </div>
        <div class="mc-wc-product-images">
            <select class="mc-wc-select mc-wc-select-not-bold" name="<?php echo esc_attr( $this->plugin_name ); ?>[mailchimp_product_image_key]">
                <?php
                $enable_auto_subscribe = ( array_key_exists( 'mailchimp_product_image_key', $options ) && ! is_null( $options['mailchimp_product_image_key'] ) ) ? $options['mailchimp_product_image_key'] : 'medium';
                foreach ( mailchimp_woocommerce_get_all_image_sizes_list() as $key => $value ) {
                    echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key === $enable_auto_subscribe, true, false ) . '>' . esc_html( $value ) . '</option>';
                }
                ?>
            </select>
        </div>
    </div>
</div>