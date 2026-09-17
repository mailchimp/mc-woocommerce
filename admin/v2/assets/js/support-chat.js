/**
 * Zendesk support chat, loaded on demand.
 *
 * Nothing from Zendesk is requested until the merchant clicks the support button.
 * The click fetches the store identity from admin-ajax first, hands it to Zendesk
 * before the snippet boots (settings + identify/prefill), then opens the chat.
 */
(function () {
	'use strict';

	var config = window.mailchimpSupportChat;
	var loading = null;

	function zE() {
		return window.zE.apply(null, arguments);
	}

	function fetchIdentity() {
		var body = new URLSearchParams();
		body.append('action', 'mailchimp_woocommerce_support_chat_identity');
		body.append('nonce', config.nonce);

		return fetch(config.ajaxUrl, {method: 'POST', credentials: 'same-origin', body: body})
			.then(function (response) { return response.json(); })
			.then(function (json) {
				if (!json || !json.success) {
					throw new Error('support identity request failed');
				}
				return json.data;
			});
	}

	function injectScript(id) {
		return new Promise(function (resolve, reject) {
			if (window.zE) {
				return resolve();
			}
			// read by the snippet when it boots, so the chat carries our tags from the start
			window.zESettings = {
				webWidget: {
					launcher: {chatLabel: {'*': config.l10n.chat_label}},
					chat: {tags: chatTags(id)}
				}
			};
			var script = document.createElement('script');
			script.id = 'ze-snippet';
			script.async = true;
			script.src = config.scriptUrl;
			script.onload = function () {
				window.zE ? resolve() : reject(new Error('zE is missing after load'));
			};
			script.onerror = function () {
				reject(new Error('Failed to load ' + config.scriptUrl));
			};
			document.body.appendChild(script);
		});
	}

	function chatTags(id) {
		var tags = ['integration:WooCommerce', 'domain:' + id.woo.domain];
		if (id.woo.email) {
			tags.push('woo_email:' + id.woo.email);
			tags.push('woo_owner:' + id.woo.owner);
		}
		if (id.mailchimp.user_id) {
			tags.push('mc_user_id:' + id.mailchimp.user_id);
			if (id.mailchimp.plan) {
				tags.push('mc_plan:' + id.mailchimp.plan);
			}
		}
		if (id.mailchimp.store_id) {
			tags.push('mc_store_id:' + id.mailchimp.store_id);
		}
		tags.push('sync_status:' + id.woo.sync_status);
		return tags;
	}

	function setupWidget(id) {
		var visitor = {name: id.woo.owner, email: id.woo.email};

		// identify sets the chat visitor; prefill fills the pre-chat form so it's one click to start
		zE('webWidget', 'identify', visitor);
		zE('webWidget', 'prefill', {
			name: {value: visitor.name, readOnly: false},
			email: {value: visitor.email, readOnly: false}
		});
		// settings tags cover a fresh chat; re-add on start in case the widget booted without them
		zE('webWidget:on', 'chat:start', function () {
			zE('webWidget', 'chat:addTags', chatTags(id));
		});
	}

	function load() {
		if (!loading) {
			// sequential on purpose: Zendesk must boot with the identity already in place
			loading = fetchIdentity().then(function (id) {
				return injectScript(id).then(function () {
					setupWidget(id);
				});
			});
			// allow a retry after a failure (e.g. a blocker that gets disabled)
			loading.catch(function () {
				loading = null;
				var snippet = document.getElementById('ze-snippet');
				if (snippet && !window.zE) {
					snippet.parentNode.removeChild(snippet);
				}
			});
		}
		return loading;
	}

	function setState(container, state) {
		var button = container.querySelector('.mc-wc-support-chat-button');
		var label = container.querySelector('.mc-wc-support-chat-label');
		var error = container.querySelector('.mc-wc-support-chat-error');
		var text = state === 'loading' ? config.l10n.loading : label.getAttribute('data-default');

		button.disabled = state === 'loading';
		button.setAttribute('aria-busy', state === 'loading' ? 'true' : 'false');
		button.setAttribute('aria-label', text);
		container.classList.toggle('is-loading', state === 'loading');
		label.textContent = text;
		error.textContent = state === 'failed' ? config.l10n.failed : '';
		error.hidden = state !== 'failed';
		// Zendesk renders its own launcher in the same corner - hand off to it.
		container.hidden = state === 'ready';
	}

	function init() {
		var container = document.querySelector('.mc-wc-support-chat');
		if (!config || !container) {
			return;
		}
		var label = container.querySelector('.mc-wc-support-chat-label');
		label.setAttribute('data-default', label.textContent);

		container.querySelector('.mc-wc-support-chat-button').addEventListener('click', function (event) {
			event.preventDefault();
			setState(container, 'loading');
			load().then(function () {
				setState(container, 'ready');
				zE('webWidget', 'show');
				zE('webWidget', 'open');
			}).catch(function (error) {
				console.error('Mailchimp support chat:', error);
				setState(container, 'failed');
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
