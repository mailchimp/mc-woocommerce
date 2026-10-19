/**
 * Mailchimp Pixel Tracking for WooCommerce
 *
 * Listens for WooCommerce events and sends them to the Mailchimp Pixel SDK.
 * Only tracks if window.$mcSite.pixel.api is available (MC.js loaded).
 *
 * @package MailChimp_WooCommerce
 * @since 1.0.0
 */

(function ($) {
	'use strict';

	/**
	 * Default config for waitForPixelSDK exponential backoff
	 */
	const PIXEL_SDK_WAIT_CONFIG = {
		initialDelayMs: 100,
		maxDelayMs: 5000,
		maxAttempts: 20
	};

	/**
	 * How long PRODUCT_VIEWED waits on a variable product for WooCommerce to resolve a
	 * default or deep-linked variation before settling for the parent.
	 */
	const VARIATION_RESOLVE_WAIT_MS = 1200;

	/**
	 * Settle time after a form interaction before re-reading the variation id input.
	 * The blocks form writes it through the Interactivity API on the next render.
	 */
	const VARIATION_INPUT_SETTLE_MS = 150;

	/**
	 * Mailchimp Pixel Tracking Handler
	 */
	const MailchimpPixelTracking = {
		// Debounce timers for cart fetch calls
		_atcTimer: null,
		_rfcTimer: null,
		_atcFetching: false,
		_rfcFetching: false,
		// PRODUCT_VIEWED state for a variable product being resolved to a variation
		_viewTimer: null,
		_viewedParent: null,
		_viewedKeys: null,
		_inputTimer: null,

		/**
		 * Initialize tracking.
		 * Waits for Pixel SDK with exponential backoff, then sends page events and attaches handlers.
		 */
		init: function () {
			const self = this;

			// Registered before the SDK wait on purpose: that wait runs for seconds, and an
			// integration that resolves its variation early must not race us.
			window.mcPixel = window.mcPixel || {};
			window.mcPixel.setVariation = function (variationId, product) {
				self.setVariation(variationId, product);
			};
			document.addEventListener('mailchimp_woocommerce_set_variation', function (event) {
				const detail = event.detail || {};
				self.setVariation(detail.variationId, detail.product);
			});

			this.waitForPixelSDK(PIXEL_SDK_WAIT_CONFIG)
				.then(function () {
					self.sendPageEvents();
					self.attachCartEventListeners();
					self.interceptStoreApiRequests();
					console.log('Mailchimp Pixel SDK loaded.');
				})
				.catch(function (e) {
					console.log('Mailchimp Pixel SDK not loaded within timeout. Tracking disabled.', e);
				});
		},

		/**
		 * Wait for Pixel SDK to become available using exponential backoff.
		 *
		 * @param {Object} options Optional config
		 * @param {number} options.initialDelayMs First delay in ms (default 100)
		 * @param {number} options.maxDelayMs Cap on delay in ms (default 5000)
		 * @param {number} options.maxAttempts Max number of attempts (default 20)
		 * @return {Promise<void>} Resolves when SDK is available, rejects after maxAttempts
		 */
		waitForPixelSDK: function (options) {
			const config = options || {};
			const initialDelayMs = config.initialDelayMs !== undefined ? config.initialDelayMs : PIXEL_SDK_WAIT_CONFIG.initialDelayMs;
			const maxDelayMs = config.maxDelayMs !== undefined ? config.maxDelayMs : PIXEL_SDK_WAIT_CONFIG.maxDelayMs;
			const maxAttempts = config.maxAttempts !== undefined ? config.maxAttempts : PIXEL_SDK_WAIT_CONFIG.maxAttempts;

			function isSDKReady() {
				return typeof window.$mcSite !== 'undefined' &&
					window.$mcSite.pixel &&
					typeof window.$mcSite.pixel.api !== 'undefined' &&
					typeof window.$mcSite.pixel.api.track === 'function' &&
					window.$mcSite.pixel.installed === true;
			}

			return new Promise(function (resolve, reject) {
				let attempt = 0;

				function scheduleCheck() {
					if (isSDKReady()) {
						// FIXME: temporary wait to accommodate for not being able to detect ready state
						console.warn('Pixel SDK - remediation for pixel ready issue')
						setTimeout(function () {
							resolve();
						}, 1000);
						return;
					}
					if (attempt >= maxAttempts) {
						reject(new Error('Pixel SDK not available'));
						return;
					}

					const delay = Math.min(
						initialDelayMs * Math.pow(2, attempt),
						maxDelayMs
					);
					attempt += 1;

					setTimeout(scheduleCheck, delay);
				}

				scheduleCheck();
			});
		},

		/**
		 * Synchronous check if Pixel SDK is available (e.g. before each track call).
		 *
		 * @return {boolean} True if SDK is available
		 */
		isPixelSDKReady: function () {
			return typeof window.$mcSite !== 'undefined' &&
				window.$mcSite.pixel &&
				typeof window.$mcSite.pixel.api !== 'undefined' &&
				typeof window.$mcSite.pixel.api.track === 'function';
		},

		/**
		 * Get cart ID from window.mcPixel
		 *
		 * @return {string} Cart ID
		 */
		getCartId: function () {
			return window.mcPixel && window.mcPixel.cartId ? window.mcPixel.cartId : '';
		},

		/**
		 * Get the REST API base URL from the localized config.
		 *
		 * @return {string} REST base URL
		 */
		getRestBase: function () {
			return (window.mcPixelConfig && window.mcPixelConfig.restBase) || '/wp-json/mailchimp-for-woocommerce/v1/';
		},

		/**
		 * Send page-level events based on pre-populated data
		 */
		sendPageEvents: function () {
			if (!window.mcPixel || !window.mcPixel.data) {
				return;
			}

			const data = window.mcPixel.data;
			const events = data.events || [];

			// Send events based on what was set by PHP
			events.forEach((eventType) => {
				//console.log('Mailchimp Pixel: Sending event', eventType, data);
				switch (eventType) {
					case 'PRODUCT_ADDED_TO_CART':
						if (data.added_to_cart) {
							var atcItems = Array.isArray(data.added_to_cart) ? data.added_to_cart : [data.added_to_cart];
							for (var ai = 0; ai < atcItems.length; ai++) {
								this.sendProductAddedToCart(atcItems[ai]);
							}
						}
						break;
					case 'PRODUCT_REMOVED_FROM_CART':
						if (data.removed_from_cart) {
							var rfcItems = Array.isArray(data.removed_from_cart) ? data.removed_from_cart : [data.removed_from_cart];
							for (var ri = 0; ri < rfcItems.length; ri++) {
								this.sendProductRemovedFromCart(rfcItems[ri]);
							}
						}
						break;
					case 'IDENTITY':
						if (data.identity && data.identity.email) {
							this.sendIdentityEvent(data.identity.email);
						}
						break;
					case 'PRODUCT_VIEWED':
						// Skip if an add-to-cart or remove-from-cart already fired for the same product.
						// The cart action implies the view, so firing both is redundant.
						if (data.product) {
							const viewedId = String(data.product.productId || data.product.id);
							const atcArr = Array.isArray(data.added_to_cart) ? data.added_to_cart : (data.added_to_cart ? [data.added_to_cart] : []);
							const rfcArr = Array.isArray(data.removed_from_cart) ? data.removed_from_cart : (data.removed_from_cart ? [data.removed_from_cart] : []);
							const atcMatch = atcArr.some(function (p) { return String(p.productId || p.id) === viewedId; });
							const rfcMatch = rfcArr.some(function (p) { return String(p.productId || p.id) === viewedId; });

							if (
								(events.includes('PRODUCT_ADDED_TO_CART') && atcMatch) ||
								(events.includes('PRODUCT_REMOVED_FROM_CART') && rfcMatch)
							) {
								//console.log('Mailchimp Pixel: Skipping PRODUCT_VIEWED (superseded by cart event for same product)');
								break;
							}
							this.trackProductViewed(data.product);
						}
						break;
					case 'CART_VIEWED':
						if (data.cart) {
							this.sendCartViewed(data.cart);
						}
						break;
					case 'CHECKOUT_STARTED':
						if (data.checkout) {
							this.sendCheckoutStarted(data.checkout);
							window.mcPixel._handled.checkout = true;
						}
						break;
					case 'PURCHASED':
						if (data.order) {
							this.sendPurchased(data.order);
						}
						break;
					case 'PRODUCT_CATEGORY_VIEWED':
						if (data.category) {
							this.sendCategoryViewed(data.category);
							window.mcPixel._handled.category = true;
						}
						break;
					case 'SEARCH_SUBMITTED':
						if (data.search) {
							this.sendSearchSubmitted(data.search);
							window.mcPixel._handled.search = true;
						}
						break;
				}
			});
		},

		/**
		 * PRODUCT_VIEWED for the single product page.
		 *
		 * A variable product renders before the customer has picked anything, so the
		 * server-side payload only knows the parent. WooCommerce announces the chosen
		 * variation on the .variations_form with found_variation, so we hold the event
		 * until that fires - or until it's clear nothing is going to resolve.
		 *
		 * @param {Object} product Parent product data from PHP
		 */
		trackProductViewed: function (product) {
			const self = this;
			this._viewedKeys = this._viewedKeys || {};

			// not a variable product - the server already knew everything there is to know
			if (!window.mcPixel.data.product_variations) {
				this.sendProductViewedOnce(product, product.id);
				return;
			}

			this._viewedParent = product;
			this.attachVariationListeners();

			const resolved = this.currentVariationId();
			if (resolved) {
				this.sendProductViewedOnce(this.resolveVariationPayload({ variation_id: resolved }), resolved);
				return;
			}

			// nothing picked yet, but a default variation or ?attribute_* deep link still
			// resolves a tick after the form initialises - give WooCommerce that tick
			// before settling for the parent
			this._viewTimer = setTimeout(function () {
				self.sendProductViewedOnce(product, product.id);
			}, VARIATION_RESOLVE_WAIT_MS);
		},

		/**
		 * One PRODUCT_VIEWED per thing actually viewed: the parent, then each variation
		 * the customer lands on. Switching back to an earlier pick sends nothing, so
		 * clicking through a dropdown doesn't turn into a burst of events.
		 *
		 * @param {Object} product Product data to send
		 * @param {string} key Dedup key - the variation id, or the parent id
		 */
		sendProductViewedOnce: function (product, key) {
			clearTimeout(this._viewTimer);
			if (this._viewedKeys[key]) {
				return;
			}
			// only claim the key once it actually went out - an integration can call
			// setVariation() before the SDK is up, and that must not count as sent
			if (this.sendProductViewed(product)) {
				this._viewedKeys[key] = true;
			}
		},

		/**
		 * Public API for themes, swatch plugins and custom templates.
		 *
		 * Detection covers what WooCommerce itself ships: the classic variations form,
		 * and the hidden variation_id input the block form maintains. Anything else - a
		 * custom swatch UI, a page builder widget, a headless front end - can announce
		 * the selection itself, either directly:
		 *
		 *     window.mcPixel.setVariation( 123 );
		 *
		 * or, when it cannot reach our global, by dispatching an event:
		 *
		 *     document.dispatchEvent( new CustomEvent(
		 *         'mailchimp_woocommerce_set_variation',
		 *         { detail: { variationId: 123 } }
		 *     ) );
		 *
		 * Pass a second argument to supply the whole product payload instead of letting
		 * us resolve it. Sending the same variation twice is a no-op either way.
		 *
		 * @param {number|string} variationId Variation being viewed
		 * @param {Object} [product] Optional product payload, sent as-is
		 */
		setVariation: function (variationId, product) {
			const id = String(variationId || '');

			if (!id || id === '0') {
				return;
			}
			// an integration may beat PRODUCT_VIEWED to it - nothing to dedup against yet
			this._viewedKeys = this._viewedKeys || {};
			this.sendProductViewedOnce(
				product || this.resolveVariationPayload({ variation_id: id }),
				id
			);
		},

		/**
		 * found_variation is WooCommerce's own signal that every attribute is chosen and
		 * a variation matched. Bound delegated off body so a form rendered late - blocks,
		 * lazy themes, quick-view modals - is still covered.
		 */
		attachVariationListeners: function () {
			const self = this;

			$(document.body).on('found_variation', '.variations_form', function (event, variation) {
				if (!variation || !variation.variation_id) {
					return;
				}
				self.sendProductViewedOnce(
					self.resolveVariationPayload(variation),
					String(variation.variation_id)
				);
			});

			// covers the block form, which fires no event at all
			this.watchVariationInput();
		},

		/**
		 * WooCommerce often resolves a variation before we boot - the SDK wait runs over a
		 * second, and the form fires found_variation during its own init. The form parks
		 * the answer in its variation_id input, so read that rather than assuming we were
		 * listening at the right moment.
		 *
		 * @return {string} Resolved variation id, or '' when nothing is selected
		 */
		currentVariationId: function () {
			// Classic puts this in .variations_form, blocks puts it in .single_variation_wrap
			// inside its own form element - so key off the input, not either wrapper.
			const inputs = document.querySelectorAll('form input[name="variation_id"], form input.variation_id');

			for (let i = 0; i < inputs.length; i++) {
				const id = parseInt(inputs[i].value, 10);
				if (id > 0) {
					return String(id);
				}
			}

			return '';
		},

		/**
		 * Catch variation changes on templates that announce nothing.
		 *
		 * The blocks "Add to Cart with Options" form runs on the Interactivity API and
		 * fires no jQuery event and no CustomEvent, so found_variation never arrives.
		 * What it does keep is the hidden variation_id input - WooCommerce renders it
		 * specifically so "extensions or Express Payment methods" can read the form
		 * state - so re-read that whenever the customer touches the form. Its value is
		 * set as a property by the Interactivity API, which a MutationObserver would
		 * never see, hence driving off the interaction instead.
		 */
		watchVariationInput: function () {
			const self = this;

			const recheck = function (event) {
				// blocks renders its attribute options as buttons, so change alone misses them
				if (!event.target || !event.target.closest || !event.target.closest('form')) {
					return;
				}
				clearTimeout(self._inputTimer);
				self._inputTimer = setTimeout(function () {
					const id = self.currentVariationId();
					if (id) {
						self.sendProductViewedOnce(self.resolveVariationPayload({ variation_id: id }), id);
					}
				}, VARIATION_INPUT_SETTLE_MS);
			};

			document.addEventListener('change', recheck, true);
			document.addEventListener('click', recheck, true);
		},

		/**
		 * Turn a resolved variation into the product shape the pixel expects.
		 *
		 * Prefer the server-rendered variation: it carries the sku, categories and
		 * parent-derived permalink that the event payload has no idea about, and it
		 * matches what add-to-cart sends for the same variation. Past the pre-render cap
		 * there is nothing to look up, so patch the parent with what the event does give
		 * us - the id being right matters more than the rest.
		 *
		 * @param {Object} variation found_variation payload (or just {variation_id})
		 * @return {Object} Product data for the pixel
		 */
		resolveVariationPayload: function (variation) {
			const parent = this._viewedParent || {};
			const id = String(variation.variation_id);
			const rendered = window.mcPixel.data.product_variations[id];

			if (rendered) {
				return rendered;
			}

			const patched = $.extend({}, parent, {
				id: id,
				productId: parent.productId || parent.id || id
			});
			if (variation.sku) {
				patched.sku = variation.sku;
			}
			if (variation.display_price !== undefined && variation.display_price !== null) {
				patched.price = parseFloat(variation.display_price);
			}
			if (variation.image && variation.image.full_src) {
				patched.imageUrl = variation.image.full_src;
			}

			return patched;
		},

		/**
		 * Send PRODUCT_VIEWED event
		 *
		 * @param {Object} product Product data
		 */
		sendProductViewed: function (product) {
			if (!this.isPixelSDKReady()) return false;

			window.$mcSite.pixel.api.track('PRODUCT_VIEWED', {
				product: product
			}).catch((error) => {
				console.error('Mailchimp Pixel: Error tracking PRODUCT_VIEWED', error);
			});

			return true;
		},

		/**
		 * Send PRODUCT_ADDED_TO_CART event
		 *
		 * @param {Object} product Product data
		 */
		sendProductAddedToCart: function (product) {
			if (!this.isPixelSDKReady()) return;

			const cartId = this.getCartId();
			const eventData = {
				cartId: cartId,
				product: {
					item: {
						id: product.id,
						productId: product.productId,
						title: product.title,
						price: product.price,
						currency: product.currency,
						sku: product.sku || ''
					},
					quantity: product.quantity || 1,
					price: product.price * (product.quantity || 1),
					currency: product.currency
				}
			};

			window.$mcSite.pixel.api.track('PRODUCT_ADDED_TO_CART', eventData).catch((error) => {
				console.error('Mailchimp Pixel: Error tracking PRODUCT_ADDED_TO_CART', error);
			});
		},

		/**
		 * Send PRODUCT_REMOVED_FROM_CART event
		 *
		 * @param {Object} product Product data
		 */
		sendProductRemovedFromCart: function (product) {
			if (!this.isPixelSDKReady()) return;

			const cartId = this.getCartId();
			const eventData = {
				cartId: cartId,
				product: {
					item: {
						id: product.id,
						productId: product.productId,
						title: product.title,
						price: product.price,
						currency: product.currency,
						sku: product.sku || ''
					},
					quantity: product.quantity || 1,
					price: product.price * (product.quantity || 1),
					currency: product.currency
				}
			};

			window.$mcSite.pixel.api.track('PRODUCT_REMOVED_FROM_CART', eventData).catch((error) => {
				console.error('Mailchimp Pixel: Error tracking PRODUCT_REMOVED_FROM_CART', error);
			});
		},

		/**
		 * Send CART_VIEWED event
		 *
		 * @param {Object} cart Cart data
		 */
		sendCartViewed: function (cart) {
			if (!this.isPixelSDKReady()) return;

			window.$mcSite.pixel.api.track('CART_VIEWED', {
				cart: cart
			}).catch((error) => {
				console.error('Mailchimp Pixel: Error tracking CART_VIEWED', error);
			});
		},

		/**
		 * Send CHECKOUT_STARTED event
		 *
		 * @param {Object} checkout Checkout data
		 */
		sendCheckoutStarted: function (checkout) {
			if (!this.isPixelSDKReady()) return;

			window.$mcSite.pixel.api.track('CHECKOUT_STARTED', {
				checkout: checkout
			}).catch((error) => {
				console.error('Mailchimp Pixel: Error tracking CHECKOUT_STARTED', error);
			});
		},

		sendIdentityEvent: function(email) {
			if (!this.isPixelSDKReady()) return;
			window.$mcSite.pixel.api.identify({
				type: 'EMAIL',
				value: email
			});
		},

		/**
		 * Send PURCHASED event
		 *
		 * @param {Object} order Order data
		 */
		sendPurchased: function (order) {
			if (!this.isPixelSDKReady()) return;

			window.$mcSite.pixel.api.track('PURCHASED', {
				order: order
			}).catch((error) => {
				console.error('Mailchimp Pixel: Error tracking PURCHASED', error);
			});
		},

		/**
		 * Send PRODUCT_CATEGORY_VIEWED event
		 *
		 * @param {Object} category Category data
		 */
		sendCategoryViewed: function (category) {
			if (!this.isPixelSDKReady()) return;

			window.$mcSite.pixel.api.track('PRODUCT_CATEGORY_VIEWED', category).catch((error) => {
				console.error('Mailchimp Pixel: Error tracking PRODUCT_CATEGORY_VIEWED', error);
			});
		},

		/**
		 * Send SEARCH_SUBMITTED event
		 *
		 * @param {Object} search Search data
		 */
		sendSearchSubmitted: function (search) {
			if (!this.isPixelSDKReady()) return;

			window.$mcSite.pixel.api.track('SEARCH_SUBMITTED', search).catch((error) => {
				console.error('Mailchimp Pixel: Error tracking SEARCH_SUBMITTED', error);
			});
		},

		/**
		 * Find product by ID from pre-loaded products
		 *
		 * @param {number|string} productId Product ID
		 * @return {Object|null} Product data or null
		 */
		findProductById: function (productId) {
			if (!window.mcPixel || !window.mcPixel.data || !window.mcPixel.data.products) {
				return null;
			}

			const products = window.mcPixel.data.products;
			const id = String(productId);

			for (let i = 0; i < products.length; i++) {
				if (String(products[i].id) === id) {
					return products[i];
				}
			}

			return null;
		},

		/**
		 * Debounced fetch for add-to-cart events.
		 * Each call resets the timer so rapid-fire triggers coalesce into one
		 * fetch that drains the entire PHP queue.
		 * If a fetch is already in flight, schedule another drain after it completes.
		 */
		fetchAndTrackAddToCart: function () {
			var self = this;
			if (!this.isPixelSDKReady()) return;

			clearTimeout(this._atcTimer);
			this._atcTimer = setTimeout(function () {
				self._drainAddToCartQueue();
			}, 600);
		},

		_drainAddToCartQueue: async function () {
			if (this._atcFetching) return;
			this._atcFetching = true;

			try {
				var res = await fetch(this.getRestBase() + 'pixel/atc', {
					method: 'GET',
					credentials: 'same-origin',
					headers: { 'Accept': 'application/json' },
				});

				if (!res.ok) return;

				var data = await res.json();
				if (!data) return;

				var items = Array.isArray(data) ? data : [data];
				for (var i = 0; i < items.length; i++) {
					this.sendProductAddedToCart(items[i]);
				}
			} catch (e) {
				// no-op
			} finally {
				this._atcFetching = false;
			}
		},

		/**
		 * Debounced fetch for remove-from-cart events.
		 * Same debounce pattern as add-to-cart.
		 */
		fetchAndTrackRemoveFromCart: function () {
			var self = this;
			if (!this.isPixelSDKReady()) return;

			clearTimeout(this._rfcTimer);
			this._rfcTimer = setTimeout(function () {
				self._drainRemoveFromCartQueue();
			}, 600);
		},

		_drainRemoveFromCartQueue: async function () {
			if (this._rfcFetching) return;
			this._rfcFetching = true;

			try {
				var res = await fetch(this.getRestBase() + 'pixel/rfc', {
					method: 'GET',
					credentials: 'same-origin',
					headers: { 'Accept': 'application/json' },
				});

				if (!res.ok) return;

				var data = await res.json();
				if (!data) return;

				var items = Array.isArray(data) ? data : [data];
				for (var i = 0; i < items.length; i++) {
					this.sendProductRemovedFromCart(items[i]);
				}
			} catch (e) {
				// no-op
			} finally {
				this._rfcFetching = false;
			}
		},

		/**
		 * Attach DOM and jQuery event listeners for cart add/remove.
		 * These serve as fallback triggers alongside the fetch interceptor.
		 */
		attachCartEventListeners: function () {
			const self = this;

			// Classic themes (jQuery events fired by WooCommerce's add-to-cart.js)
			$(document.body).on('added_to_cart', function () {
				self.fetchAndTrackAddToCart();
			});
			$(document.body).on('removed_from_cart', function () {
				self.fetchAndTrackRemoveFromCart();
			});

			// WC Blocks DOM CustomEvents
			document.body.addEventListener('wc-blocks_added_to_cart', function () {
				self.fetchAndTrackAddToCart();
			});
			document.body.addEventListener('wc-blocks_removed_from_cart', function () {
				self.fetchAndTrackRemoveFromCart();
			});
		},

		/**
		 * Intercept window.fetch to detect WooCommerce Store API cart mutations.
		 *
		 * This is the most reliable method for catching add-to-cart and remove-from-cart
		 * in block-based WooCommerce setups, where DOM events may not fire consistently.
		 * Watches for successful POST requests to the Store API cart endpoints.
		 */
		interceptStoreApiRequests: function () {
			const self = this;
			const originalFetch = window.fetch;

			window.fetch = function (input, init) {
				var promise = originalFetch.apply(this, arguments);

				promise.then(function (response) {
					try {
						if (!response.ok) return;

						var method = (init && init.method) ? init.method.toUpperCase() :
							(input instanceof Request ? input.method.toUpperCase() : 'GET');
						if (method !== 'POST') return;

						var url = typeof input === 'string' ? input :
							(input instanceof Request ? input.url : String(input));

						if (/wc\/store\/v1\/cart\/add-item/.test(url) || /wc\/store\/v1\/cart\/update-item/.test(url)) {
							self.fetchAndTrackAddToCart();
						} else if (/wc\/store\/v1\/cart\/remove-item/.test(url)) {
							self.fetchAndTrackRemoveFromCart();
						}
					} catch (e) {
						// Silently ignore interceptor errors
					}
				}).catch(function () {
					// Ignore - the original caller handles fetch errors
				});

				return promise;
			};
		},
	};

	// Initialize when DOM is ready
	$(document).ready(function () {
		MailchimpPixelTracking.init();
	});

})(jQuery);
