<h2>Mailchimp</h2>
    <table class="form-table">
        <tr>
            <th><label for="mailchimp_woocommerce_is_subscribed"><?php esc_html_e('User Subscribed', 'mailchimp-for-woocommerce')?></label></th>
            <td>
                <input type="checkbox" name="mailchimp_woocommerce_is_subscribed_checkbox" id="mailchimp_woocommerce_is_subscribed" <?php echo esc_attr( get_user_meta($user->ID, 'mailchimp_woocommerce_is_subscribed', true)  ? 'checked' : '') ?> >
            </td>
        </tr>
    </table>