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
	protected $skip_creation = false;

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

	        $this->cleanHooks();

			if ($this->skip_creation === true) {
				return true;
			}

            $list = mailchimp_get_list_id();
            $api = mailchimp_get_api();
	        $key = mailchimp_create_webhook_token();
	        $url = mailchimp_build_webhook_url($key);

	        mailchimp_set_data('webhook.token', $key);
	        //requesting api webhooks subscription
	        $webhook = $api->webHookSubscribe($list, $url);
	        //if no errors let save the url
	        mailchimp_set_webhook_url($webhook['url']);
	        mailchimp_log('webhooks', "added webhook to audience");

			return true;
        } catch (Throwable $e) {
            mailchimp_error('webhook', $e->getMessage());
            mailchimp_set_data('webhook.token', false);
            mailchimp_set_webhook_url(false);
        }
        return false;
    }

	/**
	 * @return array|null
	 */
	public function cleanHooks()
	{
		$this->skip_creation = false;

		if (!mailchimp_is_configured()) {
			return null;
		}

		$token = mailchimp_get_data('webhook.token');
		$list = mailchimp_get_list_id();
		$api = mailchimp_get_api();
		$deleted = [];
		try {
			$hooks = $api->getWebHooks($list);
			foreach ($hooks['webhooks'] as $hook) {
				$href = isset($hook['url']) ? $hook['url'] : (isset($hook['href']) ? $hook['href'] : null);
				if ($href && mailchimp_string_contains($href, 'mailchimp-for-woocommerce/v1/member-sync')) {
					if (!empty($token) && mailchimp_string_contains($href, $token)) {
						$this->skip_creation = true;
						continue;
					}
					$api->deleteWebhookByID($list, $hook['id']);
					$deleted[] = $hook['id'];
					mailchimp_log('webhooks', "Deleted old plugin webhook id {$hook['id']} :: {$href}");
				}
			}
			if ($this->skip_creation) {
				mailchimp_log('webhooks', "Verified existing hook in Mailchimp");
				return $deleted;
			}
			mailchimp_set_data('webhook.token', false);
			mailchimp_set_webhook_url(false);
		} catch (Throwable $e) {
			mailchimp_error('webhook deletion error', $e->getMessage());
		}
		return $deleted;
	}
}