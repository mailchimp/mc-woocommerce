<?php

/**
 * Created by Vextras.
 *
 * Name: Alejandro Giraldo
 * Email: alejandro@vextras.com
 * Date: 04/02/16
 * Time: 10:55 PM
 */
class MailChimp_WooCommerce_WebHooks_Sync extends Mailchimp_Woocommerce_Job
{
    /**
     * Handle job
     * @return void
     */
    public function handle()
    {
        $this->subscribeWebhook();
    }

    /**
     * Subscribe mailchimp webhook
     * @return void|bool 
     */
    public function subscribeWebhook()
    {
        try {
            if (!mailchimp_is_configured()) {
                return null;
            }

            $list = mailchimp_get_list_id();
            $key = mailchimp_get_data('webhook.token');
            $url = mailchimp_get_webhook_url();
            $api = mailchimp_get_api();

            // if we have a key, and we have a url, but the token is not in the url,
            // we need to delete the webhook and re-attach it.
            if (!empty($key) && !empty($url) && (!mailchimp_string_contains($url, $key) || !$api->hasWebhook($list, $url))) {
                $match = MailChimp_WooCommerce_Rest_Api::url('member-sync');
                mailchimp_log('webhooks', "found discrepancy on audience webhook - deleting invalid hooks");
                $api->webHookDelete($list, $match);
                $url = null;
                $key = null;
            }

            // for some reason the webhook url does not work with ?rest_route style, permalinks should be defined also 
            if (!$url && get_option('permalink_structure') !== '') {
                $key = mailchimp_create_webhook_token();
                $url = mailchimp_build_webhook_url($key);
                mailchimp_set_data('webhook.token', $key);
                //requesting api webhooks subscription
                $webhook = $api->webHookSubscribe($list, $url);
                //if no errors let save the url
                mailchimp_set_webhook_url($webhook['url']);
                mailchimp_log('webhooks', "added webhook to audience");
            }
        } catch (\Throwable $e) {
            mailchimp_set_data('webhook.token', false);
            mailchimp_set_webhook_url(false);
            mailchimp_error('webhook', $e->getMessage());
        }
        return false;
    }
}