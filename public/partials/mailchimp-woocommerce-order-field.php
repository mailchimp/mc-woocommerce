<div class="form-field form-field-wide">
    <h4><?= __('Set user status in mailchimp as subscribed.', 'woocommerce-mailchimp')?></h4>
    <?php
    $is_subscribed = get_post_meta( $order->get_id(), 'mailchimp_woocommerce_is_subscribed', true );
    $cbvalue = $is_subscribed ? 'yes' : 'no';
    ?>
    <div class="mailchimp-woocommerce-subscribe">
        <?php
        woocommerce_wp_checkbox( array(
            'id' => 'mailchimp_woocommerce_is_subscribed',
            'label' => 'Subscribe User?',
            'value' => $cbvalue,
            'style' => 'width: auto;',
        ) );
        ?>
    </div>
</div>