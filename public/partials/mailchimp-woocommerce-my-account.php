<?php $user = wp_get_current_user(); ?>
<?php $mailchimp_user_is_subscribed = ($user && $user->ID) ? get_user_meta($user->ID, 'mailchimp_woocommerce_is_subscribed', true) : false; ?>

<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
    <input type="checkbox"
           class='woocommerce-form__input woocommerce-form__input-checkbox input-checkbox'
           name="mailchimp_woocommerce_is_subscribed_checkbox"
           id="mailchimp_woocommerce_is_subscribed"
        <?php echo !empty($mailchimp_user_is_subscribed) ? esc_attr('checked') : ''; ?>>
    <?php esc_html_e('Subscribe Newsletter', 'mailchimp-for-woocommerce')?>
</p>

<?php if (isset($gdpr_fields) && $gdpr_fields) { echo wp_kses_post($gdpr_fields); } ?>