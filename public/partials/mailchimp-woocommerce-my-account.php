<?php $user = get_current_user(); ?>
<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
    <input type="checkbox" name="mailchimp_woocommerce_is_subscribed_checkbox" id="mailchimp_woocommerce_is_subscribed" <?php echo esc_attr( get_user_meta($user->ID, 'mailchimp_woocommerce_is_subscribed', true)  ? 'checked' : '') ?> > <?php esc_html_e('Subscribe Newsletter', 'mailchimp-for-woocommerce')?>
</p>

<?php echo $gdpr_fields ?>