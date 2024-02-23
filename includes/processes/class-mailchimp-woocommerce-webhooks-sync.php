<?php

/**
 * Created by Vextras.
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

			if (true === $this->skip_creation) {
				return null;
			}

			$list = mailchimp_get_list_id();
			$api = mailchimp_get_api();

			// we used to check for this until we fixed the url delimiter problem
			// get_option('permalink_structure') !== ''
			$key = mailchimp_create_webhook_token();
			$url = mailchimp_build_webhook_url($key);
			mailchimp_set_data('webhook.token', $key);
			//requesting api webhooks subscription
			$webhook = $api->webHookSubscribe($list, $url);
			//if no errors let save the url
			mailchimp_set_webhook_url($webhook['url']);
			mailchimp_log('webhooks', "added webhook to audience");
		} catch (Throwable $e) {
			mailchimp_error('webhook', $e->getMessage());
			mailchimp_set_data('webhook.token', false);
			mailchimp_set_webhook_url(false);
		}
		return false;
	}

	/**
	 * @return array|bool|null
	 */
	public function cleanHooks($disconnect = false)
	{
		if (!mailchimp_is_configured()) {
			return null;
		}
		$token = mailchimp_get_data('webhook.token');
		$list = mailchimp_get_list_id();
		$api = mailchimp_get_api();
		$deleted = [];
		try {
			$rest_url = MailChimp_WooCommerce_Rest_Api::url('member-sync');
			$hooks = $api->getWebHooks($list);

			foreach ($hooks['webhooks'] as $hook) {
				$href = isset($hook['url']) ? $hook['url'] : (isset($hook['href']) ? $hook['href'] : null);
				if ($href && mailchimp_string_contains($href, $rest_url)) {
					if (!$disconnect && !empty($token) && mailchimp_string_contains($href, $token)) {
						$this->skip_creation = true;
						mailchimp_log('webhooks', "Verified webhook {$hook['id']}");
						continue;
					}
					$api->deleteWebhookByID($list, $hook['id']);
					$deleted[] = $hook['id'];
					mailchimp_log('webhooks', "Deleted old plugin webhook id {$hook['id']}");
				}
			}

			if ($this->skip_creation) {
				return $deleted;
			}

			mailchimp_set_data('webhook.token', false);
			mailchimp_set_webhook_url(false);

			return true;
		} catch (Throwable $e) {
			mailchimp_error('webhook deletion error', $e->getMessage());
		}
		return $deleted;
	}
}