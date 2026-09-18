/**
 * Zendesk support chat, loaded on demand.
 *
 * Nothing from Zendesk is requested until the merchant clicks the support button.
 * The click fetches the store identity from admin-ajax first, hands it to Zendesk
 * before the snippet boots (settings + identify/prefill), then opens the chat.
 *
 * Our button and the Zendesk launcher live in the same corner, so only one of them
 * is ever on screen: ours shows by default and steps aside once the real widget is up.
 */
(function () {
	'use strict';

	var config = window.mailchimpSupportChat;
	var loading = null;
	var container = null;
	var eventsBound = false;
	var zendeskOwnsCorner = false;
	var observedFrames = [];
	var syncQueued = false;

	// the frames Zendesk mounts on <body>: Classic launcher/widget, messaging, and the
	// newer data-product tagged ones. Any of them being on screen means Zendesk is up.
	var ZENDESK_FRAMES = 'iframe#launcher, iframe#webWidget, iframe#webMessenger, iframe[data-product="web_widget"]';

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
				syncRemoteSupportToggle(id);
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
				queueSync();
			});
		}
		return loading;
	}

	/**
	 * Opening the chat turns remote diagnostics on server side. The Advanced tab renders
	 * on this same page, so reflect it in the checkbox instead of letting it lie until a
	 * reload. Assigning .checked fires no change event, so the tab's own handler - which
	 * would post the toggle a second time - stays out of it.
	 */
	function syncRemoteSupportToggle(id) {
		var toggle = document.getElementById('tower_box_switch');
		if (toggle && id.remote_support) {
			toggle.checked = true;
		}
	}

	/**
	 * Is a Zendesk frame actually on screen? Zendesk swaps between the launcher and the
	 * open widget, so we only care that one of them has a real box.
	 */
	function widgetMounted() {
		var frames = document.querySelectorAll(ZENDESK_FRAMES);
		var visible = false;
		for (var i = 0; i < frames.length; i++) {
			observeFrame(frames[i]);
			var rect = frames[i].getBoundingClientRect();
			if (rect.width > 0 && rect.height > 0) {
				visible = true;
			}
		}
		return visible;
	}

	function widgetActive() {
		if (!window.zE) {
			return false;
		}
		try {
			// what Classic reports after a hide() - the corner is ours again
			if (zE('webWidget:get', 'display') === 'hidden') {
				return false;
			}
		} catch (e) {
			// messaging widget, or Classic hasn't booted yet - fall through to the DOM check
		}
		return widgetMounted();
	}

	function syncVisibility() {
		syncQueued = false;
		if (!container) {
			return;
		}
		var active = widgetActive();
		if (active === zendeskOwnsCorner) {
			return;
		}
		zendeskOwnsCorner = active;
		container.hidden = active;
	}

	// signals arrive in bursts (a widget event plus the frame mutations behind it),
	// so collapse them into a single check on the next tick
	function queueSync() {
		if (syncQueued) {
			return;
		}
		syncQueued = true;
		setTimeout(syncVisibility, 0);
	}

	function observeFrame(frame) {
		if (!window.MutationObserver || observedFrames.indexOf(frame) !== -1) {
			return;
		}
		observedFrames.push(frame);
		// Zendesk shows/hides its frames by restyling them in place, which fires no widget event
		new MutationObserver(queueSync).observe(frame, {attributes: true, attributeFilter: ['style', 'class', 'hidden']});
	}

	/**
	 * The widget API has no "launcher is on screen" event, so we hook every event that
	 * marks a visibility change in either widget flavour and re-check the DOM from there.
	 */
	function bindWidgetEvents() {
		if (eventsBound) {
			return;
		}
		eventsBound = true;
		[
			['webWidget:on', 'open'],
			['webWidget:on', 'close'],
			['webWidget:on', 'chat:connected'],
			['messenger:on', 'open'],
			['messenger:on', 'close']
		].forEach(function (event) {
			try {
				zE(event[0], event[1], queueSync);
			} catch (e) {
				// the other widget flavour doesn't know this event - ignore it
			}
		});
	}

	function setState(state) {
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
	}

	function init() {
		container = document.querySelector('.mc-wc-support-chat');
		if (!config || !container) {
			return;
		}
		var label = container.querySelector('.mc-wc-support-chat-label');
		label.setAttribute('data-default', label.textContent);

		if (window.MutationObserver) {
			// Zendesk mounts and unmounts its frames as direct children of <body>
			new MutationObserver(queueSync).observe(document.body, {childList: true});
		}

		container.querySelector('.mc-wc-support-chat-button').addEventListener('click', function (event) {
			event.preventDefault();
			setState('loading');
			load().then(function () {
				setState('ready');
				bindWidgetEvents();
				zE('webWidget', 'show');
				zE('webWidget', 'open');
				queueSync();
			}).catch(function (error) {
				console.error('Mailchimp support chat:', error);
				setState('failed');
			});
		});

		// covers a Zendesk widget that is already up when we load (another plugin, or a
		// page load while the merchant's chat session is still open)
		if (window.zE) {
			bindWidgetEvents();
		}
		queueSync();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
