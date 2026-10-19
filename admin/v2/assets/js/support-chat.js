/**
 * Zendesk support chat, loaded on demand.
 *
 * Nothing from Zendesk is requested until the merchant clicks the support button.
 * The click fetches the store identity from admin-ajax first, hands it to Zendesk
 * before the snippet boots (settings + identify/prefill), then opens the chat.
 *
 * Every plugin tab is a full page load, so the widget has to be rebuilt on each one.
 * Once the merchant has opened the chat we keep a small record in localStorage - just
 * when, whether the window was open, and which WordPress user - and replay it on the
 * next page without a click. The identity itself never touches the browser's storage
 * (every script on the domain can read that): the click saves it server side for the
 * user, and PHP prints it into the page config (config.identity) while it lasts. So
 * there's no admin-ajax round trip (that one calls the Mailchimp API), and the window
 * comes back open if they left it open.
 * Zendesk restores the conversation itself from its own session. Ending the chat (or
 * 12 hours passing) forgets the record - minimising does not: Classic reports it as
 * "close", but the launcher stays in the corner, so it has to follow them too.
 *
 * Our button and the Zendesk launcher live in the same corner, so only one of them
 * is ever on screen: ours shows by default and steps aside once the real widget is up.
 *
 * Debugging: run mailchimpSupportChatDebug.on() in the console and reload. Every step
 * is logged, and kept in sessionStorage so mailchimpSupportChatDebug.trail() shows the
 * whole story across page loads. Contact details are never logged.
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

	// how long an opened chat keeps coming back on its own if it is never ended
	var STORAGE_KEY = 'mailchimp-woocommerce.support-chat';
	var REMEMBER_FOR = 12 * 60 * 60 * 1000;

	/* ---- debug logging (off unless switched on from the console) ---- */

	var DEBUG_KEY = STORAGE_KEY + '.debug';
	var TRAIL_KEY = STORAGE_KEY + '.trail';
	var TRAIL_SIZE = 300;
	var pageId = Math.random().toString(36).slice(2, 6);
	var pageName = (function () {
		var tab = new URLSearchParams(window.location.search).get('tab');
		return (tab || 'main') + '#' + pageId;
	})();

	function debugOn() {
		try {
			return window.localStorage.getItem(DEBUG_KEY) === '1';
		} catch (e) {
			return false;
		}
	}

	function log(event, data) {
		if (!debugOn()) {
			return;
		}
		var ms = Math.round(window.performance ? performance.now() : 0);
		console.log('%c[MC chat]%c ' + pageName + ' +' + ms + 'ms %c' + event, 'color:#734FA8;font-weight:bold', 'color:#888', 'font-weight:bold', data === undefined ? '' : data);
		try {
			var trail = JSON.parse(window.sessionStorage.getItem(TRAIL_KEY)) || [];
			trail.push({at: new Date().toISOString().slice(11, 23), page: pageName, ms: ms, event: event, data: data === undefined ? '' : JSON.stringify(data)});
			window.sessionStorage.setItem(TRAIL_KEY, JSON.stringify(trail.slice(-TRAIL_SIZE)));
		} catch (e) {}
	}

	function age(since) {
		var seconds = Math.round((Date.now() - since) / 1000);
		return Math.floor(seconds / 60) + 'm' + (seconds % 60) + 's';
	}

	// what we'd replay on the next page - without the owner's name or email
	function describeRecord() {
		try {
			var raw = window.localStorage.getItem(STORAGE_KEY);
			if (!raw) {
				return 'none';
			}
			var chat = JSON.parse(raw);
			return {
				age: chat.startedAt ? age(chat.startedAt) : '?',
				expired: !chat.startedAt || Date.now() - chat.startedAt >= REMEMBER_FOR,
				open: chat.open,
				user: chat.user,
				// a record from before identities moved server side - gets forgotten on sight
				legacyIdentity: !!chat.identity,
				pageUser: config ? config.userId : '?',
				serverIdentity: config && config.identity ? config.identity.woo.domain : 'none'
			};
		} catch (e) {
			return 'unreadable: ' + e.message;
		}
	}

	// Zendesk keeps its own session in storage - if these vanish between pages, the
	// conversation can't come back no matter what we replay
	function zendeskStorageKeys() {
		var keys = {local: [], session: []};
		try {
			[['local', window.localStorage], ['session', window.sessionStorage]].forEach(function (store) {
				for (var i = 0; i < store[1].length; i++) {
					var key = store[1].key(i);
					if (/zd|zendesk|zlc|zopim|__zl/i.test(key)) {
						keys[store[0]].push(key);
					}
				}
			});
		} catch (e) {}
		return keys;
	}

	function widgetGet(what) {
		if (!window.zE) {
			return 'zE not loaded';
		}
		try {
			return window.zE('webWidget:get', what);
		} catch (e) {
			return 'threw: ' + e.message;
		}
	}

	function snapshot() {
		var frames = [];
		document.querySelectorAll(ZENDESK_FRAMES).forEach(function (frame) {
			var rect = frame.getBoundingClientRect();
			frames.push((frame.id || frame.getAttribute('data-product') || 'iframe') + ' ' + Math.round(rect.width) + 'x' + Math.round(rect.height));
		});
		return {
			record: describeRecord(),
			zE: !!window.zE,
			display: widgetGet('display'),
			isChatting: widgetGet('chat:isChatting'),
			frames: frames,
			cornerOwner: zendeskOwnsCorner ? 'zendesk' : 'our button',
			loading: !!loading,
			zendeskStorage: zendeskStorageKeys()
		};
	}

	window.mailchimpSupportChatDebug = {
		on: function () {
			window.localStorage.setItem(DEBUG_KEY, '1');
			return 'support chat debug on - reload the page';
		},
		off: function () {
			window.localStorage.removeItem(DEBUG_KEY);
			return 'support chat debug off';
		},
		snapshot: snapshot,
		trail: function () {
			console.table(JSON.parse(window.sessionStorage.getItem(TRAIL_KEY)) || []);
		},
		clear: function () {
			window.sessionStorage.removeItem(TRAIL_KEY);
			return 'trail cleared';
		}
	};

	// storage can be off or throw (private windows, blocked site data) - the chat
	// still works then, it just starts over on the next page
	function rememberedChat() {
		var reason;
		try {
			var chat = JSON.parse(window.localStorage.getItem(STORAGE_KEY));
			if (!chat) {
				reason = null;
			} else if (chat.identity) {
				reason = 'old record with the identity in it';
			} else if (Date.now() - chat.startedAt >= REMEMBER_FOR) {
				reason = 'record expired after ' + age(chat.startedAt);
			} else if (!config || String(chat.user) !== String(config.userId)) {
				// someone else logged in on this browser - their chat isn't ours to restore
				reason = 'record belongs to another WordPress user';
			} else if (!config.identity) {
				reason = 'server no longer has the identity';
			} else {
				return chat;
			}
		} catch (e) {
			reason = 'storage unreadable: ' + e.message;
		}
		if (reason) {
			forgetChat(reason);
		}
		return null;
	}

	function rememberChat(changes) {
		try {
			var chat = rememberedChat() || {startedAt: Date.now()};
			for (var key in changes) {
				chat[key] = changes[key];
			}
			if (chat.user) {
				window.localStorage.setItem(STORAGE_KEY, JSON.stringify(chat));
				log('record saved', {changes: Object.keys(changes).join(', '), now: describeRecord()});
			} else {
				log('record NOT saved - no user yet', Object.keys(changes));
			}
		} catch (e) {
			log('record save failed', e.message);
		}
	}

	/**
	 * @param {string} reason only for the debug log
	 */
	function forgetChat(reason) {
		try {
			var had = window.localStorage.getItem(STORAGE_KEY) !== null;
			window.localStorage.removeItem(STORAGE_KEY);
			if (had) {
				log('record forgotten', typeof reason === 'string' ? reason : 'no reason given');
			}
		} catch (e) {
			log('record forget failed', e.message);
		}
	}

	// only while a chat is being remembered - a closed widget must not start one
	function rememberOpen(open) {
		if (rememberedChat()) {
			rememberChat({open: open});
		} else {
			log('open state ignored - no record', {open: open});
		}
	}

	function zE() {
		// getters are logged by their callers and listener binds are summarised - logging
		// every visibility poll and bind drowns the rest
		if (!/:(get|on)$/.test(arguments[0])) {
			log('zE call', [arguments[0], typeof arguments[1] === 'string' ? arguments[1] : ''].join(' '));
		}
		return window.zE.apply(null, arguments);
	}

	function fetchIdentity() {
		var body = new URLSearchParams();
		body.append('action', 'mailchimp_woocommerce_support_chat_identity');
		body.append('nonce', config.nonce);

		var started = Date.now();
		log('identity: admin-ajax request');
		return fetch(config.ajaxUrl, {method: 'POST', credentials: 'same-origin', body: body})
			.then(function (response) {
				log('identity: admin-ajax answered', {status: response.status, took: (Date.now() - started) + 'ms'});
				return response.json();
			})
			.then(function (json) {
				if (!json || !json.success) {
					log('identity: refused', json && json.data);
					throw new Error('support identity request failed');
				}
				log('identity: ok', {domain: json.data.woo.domain, sync_status: json.data.woo.sync_status});
				return json.data;
			});
	}

	function injectScript(id) {
		return new Promise(function (resolve, reject) {
			if (window.zE) {
				log('script: zE already on the page - not injecting');
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
			var started = Date.now();
			script.onload = function () {
				log('script: loaded', {took: (Date.now() - started) + 'ms', zE: !!window.zE});
				window.zE ? resolve() : reject(new Error('zE is missing after load'));
			};
			script.onerror = function () {
				log('script: FAILED to load (blocker? bad key?)', {took: (Date.now() - started) + 'ms', src: config.scriptUrl});
				reject(new Error('Failed to load ' + config.scriptUrl));
			};
			log('script: injecting', config.scriptUrl);
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
			log('event: chat:start');
			zE('webWidget', 'chat:addTags', chatTags(id));
		});
		// the conversation is over - stop bringing the widget back on every page
		zE('webWidget:on', 'chat:end', function () {
			log('event: chat:end');
			forgetChat('chat ended');
		});
	}

	/**
	 * @param {Object} [knownIdentity] replayed from an earlier page - skips admin-ajax
	 * @return {Promise<Object>} the identity Zendesk booted with
	 */
	function load(knownIdentity) {
		if (loading) {
			log('load: already in progress - reusing it');
		} else {
			log('load: start', knownIdentity ? 'identity from the page config (no admin-ajax)' : 'fetching identity from admin-ajax');
			var identity = knownIdentity ? Promise.resolve(knownIdentity) : fetchIdentity();
			// sequential on purpose: Zendesk must boot with the identity already in place
			loading = identity.then(function (id) {
				return injectScript(id).then(function () {
					setupWidget(id);
					return id;
				});
			});
			// allow a retry after a failure (e.g. a blocker that gets disabled)
			loading.catch(function (error) {
				log('load: FAILED', error && error.message);
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
			// Classic hasn't booted yet - fall through to the DOM check
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
		log('corner: ' + (active ? 'Zendesk takes over' : 'our button is back'), snapshot());
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
	 * Classic Web Widget only - the snippet key loads Classic, and Zendesk answers a
	 * call to a method it doesn't have (e.g. messenger:on) with a console error, not a
	 * throw, so a try/catch can't paper over binding the other flavour's events.
	 *
	 * The API has no "launcher is on screen" event, so every visibility change also
	 * re-checks the DOM (queueSync).
	 */
	function bindWidgetEvents() {
		if (eventsBound) {
			return;
		}
		eventsBound = true;
		log('events: listening for open/close' + (debugOn() ? ' + watch-only chat events' : ''));
		// carry the window's state to the next page. "close" is a minimise - the launcher
		// stays in the corner, so only the open flag changes; chat:end is what forgets.
		zE('webWidget:on', 'open', function () {
			log('event: open');
			rememberOpen(true);
			queueSync();
		});
		zE('webWidget:on', 'close', function () {
			log('event: close (minimised)', {isChatting: widgetGet('chat:isChatting')});
			rememberOpen(false);
			queueSync();
		});
		zE('webWidget:on', 'chat:connected', function () {
			log('event: chat:connected');
			queueSync();
		});
		if (debugOn()) {
			// watch-only: nothing acts on these, they just show what Zendesk is doing
			['chat:status', 'chat:unreadMessages', 'userEvent'].forEach(function (event) {
				zE('webWidget:on', event, function (detail) {
					log('event: ' + event, detail === undefined ? '' : detail);
				});
			});
		}
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
		var navigation = window.performance && performance.getEntriesByType ? performance.getEntriesByType('navigation')[0] : null;
		log('page: init', {
			url: window.location.pathname + window.location.search,
			navigation: navigation ? navigation.type : 'unknown',
			config: !!config,
			button: !!container,
			state: snapshot()
		});
		if (!config || !container) {
			log('page: nothing to do - ' + (!config ? 'no mailchimpSupportChat config (Tower gate off?)' : 'no button markup on this page'));
			return;
		}

		// the last word from a page before it goes - compare it with the next page's init
		window.addEventListener('pagehide', function (event) {
			log('page: leaving' + (event.persisted ? ' (into the back/forward cache)' : ''), snapshot());
		});
		// a back/forward cache restore doesn't re-run this script - the old page just wakes up
		window.addEventListener('pageshow', function (event) {
			if (event.persisted) {
				log('page: woke from the back/forward cache (no init)', snapshot());
			}
		});
		var label = container.querySelector('.mc-wc-support-chat-label');
		label.setAttribute('data-default', label.textContent);

		if (window.MutationObserver) {
			// Zendesk mounts and unmounts its frames as direct children of <body>
			new MutationObserver(queueSync).observe(document.body, {childList: true});
		}

		container.querySelector('.mc-wc-support-chat-button').addEventListener('click', function (event) {
			event.preventDefault();
			log('click: support button');
			setState('loading');
			load().then(function (id) {
				// the identity stays server side - the next page gets it from config.identity
				config.identity = id;
				rememberChat({user: String(config.userId), open: true});
				setState('ready');
				bindWidgetEvents();
				zE('webWidget', 'show');
				zE('webWidget', 'open');
				queueSync();
				log('click: chat is up', snapshot());
			}).catch(function (error) {
				console.error('Mailchimp support chat:', error);
				log('click: FAILED', error && error.message);
				setState('failed');
			});
		});

		// the merchant opened the chat on an earlier page - rebuild it without a click,
		// open or minimised the way they left it
		var remembered = window.zE ? null : rememberedChat();
		if (window.zE) {
			log('restore: skipped - Zendesk is already on this page');
		} else if (!remembered) {
			log('restore: nothing to restore - waiting for a click');
		}
		if (remembered) {
			log('restore: start', describeRecord());
			setState('loading');
			load(config.identity).then(function () {
				// before we touch it: does Zendesk bring back the window/conversation on its own?
				log('restore: Zendesk booted, before show/open', snapshot());
				setState('ready');
				bindWidgetEvents();
				zE('webWidget', 'show');
				if (remembered.open) {
					zE('webWidget', 'open');
				}
				queueSync();
				log('restore: done', {reopened: !!remembered.open});
				// the chat reconnects a moment after boot - watch isChatting settle
				[500, 2000, 5000].forEach(function (delay) {
					setTimeout(function () {
						log('restore: +' + delay + 'ms', snapshot());
					}, delay);
				});
			}).catch(function (error) {
				// quietly fall back to our button - an error they never asked for would just confuse
				console.error('Mailchimp support chat:', error);
				forgetChat('restore failed: ' + (error && error.message));
				setState('ready');
			});
		}

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
