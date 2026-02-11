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
		/**
		 * Initialize tracking.
		 * Waits for Pixel SDK with exponential backoff, then sends page events and attaches handlers.
		 */
		init: function () {
			const self = this;

			this.waitForPixelSDK(PIXEL_SDK_WAIT_CONFIG)
				.then(function () {
					self.sendPageEvents();
					self.attachAjaxAddToCartHandler();
					self.attachCartRemoveHandler();
				})
				.catch(function () {
					console.log('Mailchimp Pixel SDK not loaded within timeout. Tracking disabled.');
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
						// FIXME: temporary wait to accomodate for not being able to detect ready state
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
				switch (eventType) {
					case 'PRODUCT_VIEWED':
						if (data.product) {
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
		 * Attach AJAX add to cart handler
		 *
		 * Listens for the 'added_to_cart' event triggered by WooCommerce
		 */
		attachAjaxAddToCartHandler: function () {
			const self = this;

			// Listen for WooCommerce's added_to_cart event (AJAX add to cart)
			$(document.body).on('added_to_cart', function (event, fragments, cart_hash, $button) {
				if (!self.isPixelSDKReady()) return;

				// Try to get product ID from button
				let productId = null;

				if ($button && $button.length > 0) {
					productId = $button.data('product_id') || $button.attr('data-product_id');
				}

				if (!productId) {
					console.warn('Mailchimp Pixel: Could not determine product ID from added_to_cart event');
					return;
				}

				// Look up product from pre-loaded data
				const product = self.findProductById(productId);

				if (product) {
					// Get quantity from button if available
					const quantity = $button && $button.data('quantity') ? parseInt($button.data('quantity'), 10) : 1;
					product.quantity = quantity;

					self.sendProductAddedToCart(product);
					window.mcPixel._handled.addToCart = true;
					setTimeout(function () { window.mcPixel._handled.addToCart = false; }, 1000);
				} else {
					console.warn('Mailchimp Pixel: Product not found in pre-loaded data', productId);
				}
			});

			// Also handle non-AJAX add to cart from PHP data
			if (window.mcPixel && window.mcPixel.data && window.mcPixel.data.added_to_cart) {
				const product = window.mcPixel.data.added_to_cart;
				this.sendProductAddedToCart(product);
				window.mcPixel._addToCartTracked = true;
				setTimeout(function () { window.mcPixel._addToCartTracked = false; }, 1000);
			}
		},

		/**
		 * Attach cart remove handler
		 *
		 * Listens for cart item removal events
		 */
		attachCartRemoveHandler: function () {
			const self = this;

			// Listen for clicks on remove links in cart
			$(document.body).on('click', '.woocommerce-cart-form .remove', function (e) {
				if (!self.isPixelSDKReady()) return;

				const $removeLink = $(this);
				const productId = $removeLink.data('product_id');

				if (!productId) {
					return;
				}

				// Look up product from pre-loaded data or current cart
				const product = self.findProductById(productId);

				if (product) {
					// Delay slightly to let WooCommerce process the removal
					setTimeout(function () {
						self.sendProductRemovedFromCart(product);
						window.mcPixel._handled.removeFromCart = true;
						setTimeout(function () { window.mcPixel._handled.removeFromCart = false; }, 1000);
					}, 100);
				}
			});

			// Listen for WooCommerce's removed_from_cart event
			$(document.body).on('removed_from_cart', function (event, fragments, cart_hash, $button) {
				if (!self.isPixelSDKReady()) return;

				if ($button && $button.length > 0) {
					const productId = $button.data('product_id');
					if (productId) {
						const product = self.findProductById(productId);
						if (product) {
							self.sendProductRemovedFromCart(product);
							window.mcPixel._removeFromCartTracked = true;
							setTimeout(function () { window.mcPixel._removeFromCartTracked = false; }, 1000);
						}
					}
				}
			});
		}
	};

	// Initialize when DOM is ready
	$(document).ready(function () {
		MailchimpPixelTracking.init();
	});

})(jQuery);
