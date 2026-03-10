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
	 * Mailchimp Pixel Tracking Handler
	 */
	const MailchimpPixelTracking = {
		// Debounce timers for cart fetch calls
		_atcTimer: null,
		_rfcTimer: null,
		_atcFetching: false,
		_rfcFetching: false,

		/**
		 * Initialize tracking.
		 * Waits for Pixel SDK with exponential backoff, then sends page events and attaches handlers.
		 */
		init: function () {
			const self = this;
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
							this.sendProductViewed(data.product);
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
		 * Send PRODUCT_VIEWED event
		 *
		 * @param {Object} product Product data
		 */
		sendProductViewed: function (product) {
			if (!this.isPixelSDKReady()) return;

			window.$mcSite.pixel.api.track('PRODUCT_VIEWED', {
				product: product
			}).catch((error) => {
				console.error('Mailchimp Pixel: Error tracking PRODUCT_VIEWED', error);
			});
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
