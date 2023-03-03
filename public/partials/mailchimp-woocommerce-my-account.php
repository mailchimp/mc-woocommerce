<?php
$user = wp_get_current_user();
$mailchimp_user_subscription_status = ($user && $user->ID) ? get_user_meta($user->ID, 'mailchimp_woocommerce_is_subscribed', true) : false;
if ($mailchimp_user_subscription_status !== false && $mailchimp_user_subscription_status !== 'archived') {
    $mailchimp_my_account = '<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">';
    $mailchimp_my_account .= '<label for="mailchimp_woocommerce_is_subscribed">';
    $mailchimp_my_account .= '<input type="radio" class="woocommerce-form__input woocommerce-form__input-radio input-radio" name="mailchimp_woocommerce_is_subscribed_radio" id="mailchimp_woocommerce_is_subscribed" value="1"';
    $mailchimp_my_account .= $mailchimp_user_subscription_status === '1' ? ' checked="checked"' : '';
    $mailchimp_my_account .= '>';
    $mailchimp_my_account .= __( 'Subscribe to our newsletter', 'mailchimp-for-woocommerce' );
    $mailchimp_my_account .= '</label>';
    $mailchimp_my_account .= '</p>';

    $mailchimp_my_account .= '<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">';
    $mailchimp_my_account .= '<label for="mailchimp_woocommerce_is_unsubscribed">';
    $mailchimp_my_account .= '<input type="radio" class="woocommerce-form__input woocommerce-form__input-radio input-radio" name="mailchimp_woocommerce_is_subscribed_radio" id="mailchimp_woocommerce_is_unsubscribed" value="unsubscribed"';
    $mailchimp_my_account .= $mailchimp_user_subscription_status === 'unsubscribed' ? ' checked="checked"' : '';
    $mailchimp_my_account .= '>';
    $mailchimp_my_account .= __( 'Unsubscribe from our newsletter', 'mailchimp-for-woocommerce' );
    $mailchimp_my_account .= '</label>';
    $mailchimp_my_account .= '</p>';

    $mailchimp_my_account .= '<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">';
    $mailchimp_my_account .= '<label for="mailchimp_woocommerce_is_transactional">';
    $mailchimp_my_account .= '<input type="radio" class="woocommerce-form__input woocommerce-form__input-radio input-radio" name="mailchimp_woocommerce_is_subscribed_radio" id="mailchimp_woocommerce_is_transactional" value="0"';
    $mailchimp_my_account .= $mailchimp_user_subscription_status === '0' ? ' checked="checked"' : '';
    $mailchimp_my_account .= '>';
    $mailchimp_my_account .= __( 'Receive Order Updates', 'mailchimp-for-woocommerce' );
    $mailchimp_my_account .= '</label>';
    $mailchimp_my_account .= '</p>';


    echo wp_kses(
        apply_filters( 'mailchimp_woocommerce_my_account_field', $mailchimp_my_account, $mailchimp_user_subscription_status ),
        mailchimp_expanded_alowed_tags()
    );
    if (isset($gdpr_fields) && $gdpr_fields) {
        echo wp_kses($gdpr_fields, mailchimp_expanded_alowed_tags());
    }
} else {
    echo '<input type="hidden" name="mailchimp_woocommerce_is_subscribed_radio" id="mailchimp_woocommerce_is_subscribed" value="' . ($mailchimp_user_subscription_status === 'archived' ? 'archived' : '1') . '">';
}

?>