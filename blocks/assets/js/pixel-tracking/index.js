/**
 * Mailchimp Pixel Tracking for WooCommerce Blocks
 *
 * Registers event handlers via @wordpress/hooks addAction() to track
 * e-commerce events on WooCommerce block-based pages (block checkout,
 * block cart, block product grids).
 *
 * Follows the same pattern as the WooCommerce Google Analytics block integration.
 *
 * @package MailChimp_WooCommerce
 * @since 1.0.0
 */

import { addAction } from '@wordpress/hooks';

const NAMESPACE = 'mailchimp-woocommerce';

/**
 * Pixel SDK readiness state.
 * Set to true once the SDK is confirmed available.
 */
let pixelReady = false;

// --- Pixel SDK Helpers ---

/**
 * Check if the Pixel SDK is available right now.
 *
 * @return {boolean}
 */
function isPixelSDKReady() {
	return (
		typeof window.$mcSite !== 'undefined' &&
		window.$mcSite.pixel &&
		typeof window.$mcSite.pixel.api !== 'undefined' &&
		typeof window.$mcSite.pixel.api.track === 'function'
	);
}

/**
 * Wait for Pixel SDK with exponential backoff.
 * Sets the module-level `pixelReady` flag when resolved.
 */
function waitForPixelSDK() {
	const initialDelayMs = 100;
	const maxDelayMs = 5000;
	const maxAttempts = 20;

	let attempt = 0;

	function check() {
		if ( isPixelSDKReady() ) {
			// Same remediation delay as the standard tracking JS
			setTimeout( function () {
				pixelReady = true;
			}, 1000 );
			return;
		}
		if ( attempt >= maxAttempts ) {
			return;
		}

		const delay = Math.min(
			initialDelayMs * Math.pow( 2, attempt ),
			maxDelayMs
		);
		attempt += 1;
		setTimeout( check, delay );
	}

	check();
}

/**
 * Get the cart ID from the PHP-injected window.mcPixel object.
 *
 * @return {string}
 */
function getCartId() {
	return window.mcPixel && window.mcPixel.cartId
		? window.mcPixel.cartId
		: '';
}

/**
 * Track an event via the Pixel SDK.
 *
 * @param {string} eventName Event name
 * @param {Object} eventData Event payload
 */
function trackPixelEvent( eventName, eventData ) {
	if ( ! pixelReady || ! isPixelSDKReady() ) {
		return;
	}

	window.$mcSite.pixel.api
		.track( eventName, eventData )
		.catch( function ( error ) {
			console.error(
				'Mailchimp Pixel Blocks: Error tracking ' + eventName,
				error
			);
		} );
}

// --- Data Formatters ---

/**
 * Format a WC Blocks ProductResponseItem into Pixel SDK product format.
 * Block products have prices as strings in minor units (cents).
 *
 * @param {Object} product ProductResponseItem from WC Blocks
 * @return {Object} Formatted product
 */
function formatBlockProduct( product ) {
	const prices = product.prices || {};
	const currencyMinorUnit = prices.currency_minor_unit || 2;
	const divisor = Math.pow( 10, currencyMinorUnit );
	const price = prices.price ? parseInt( prices.price, 10 ) / divisor : 0;

	return {
		id: String( product.id ),
		productId: String( product.id ),
		title: product.name || '',
		price: price,
		currency: ( prices.currency_code || '' ).toUpperCase(),
		sku: product.sku || '',
		imageUrl:
			product.images && product.images.length > 0
				? product.images[ 0 ].src
				: '',
		productUrl: product.permalink || '',
		vendor: '',
		categories: ( product.categories || [] ).map( function ( cat ) {
			return cat.name || '';
		} ),
	};
}

/**
 * Format a WC Blocks CartItem for use as a product in add/remove events.
 * CartItem has prices in a `prices` sub-object with minor unit strings.
 *
 * @param {Object} cartItem CartItem from WC Blocks
 * @return {Object} Formatted product
 */
function formatCartItem( cartItem ) {
	const prices = cartItem.prices || {};
	const currencyMinorUnit = prices.currency_minor_unit || 2;
	const divisor = Math.pow( 10, currencyMinorUnit );
	const price = prices.price ? parseInt( prices.price, 10 ) / divisor : 0;

	return {
		id: String( cartItem.id ),
		productId: String( cartItem.id ),
		title: cartItem.name || '',
		price: price,
		currency: ( prices.currency_code || '' ).toUpperCase(),
		sku: cartItem.sku || '',
		imageUrl:
			cartItem.images && cartItem.images.length > 0
				? cartItem.images[ 0 ].src
				: '',
		productUrl: cartItem.permalink || '',
		vendor: '',
		categories: [],
	};
}

/**
 * Format a cart item as a Pixel SDK line item (for checkout).
 *
 * @param {Object} cartItem CartItem from WC Blocks storeCart
 * @return {Object} Formatted line item
 */
function formatCartLineItem( cartItem ) {
	const prices = cartItem.prices || {};
	const currencyMinorUnit = prices.currency_minor_unit || 2;
	const divisor = Math.pow( 10, currencyMinorUnit );
	const lineTotal = prices.line_total
		? parseInt( prices.line_total, 10 ) / divisor
		: 0;

	return {
		item: formatCartItem( cartItem ),
		quantity: cartItem.quantity || 1,
		price: lineTotal,
		currency: ( prices.currency_code || '' ).toUpperCase(),
	};
}

/**
 * Format a full storeCart into Pixel SDK checkout data.
 *
 * @param {Object} storeCart The storeCart object from WC Blocks
 * @return {Object} Formatted checkout data
 */
function formatCheckoutFromStoreCart( storeCart ) {
	const totals = storeCart.cartTotals || {};
	const currencyMinorUnit = totals.currency_minor_unit || 2;
	const divisor = Math.pow( 10, currencyMinorUnit );

	const lineItems = ( storeCart.cartItems || [] ).map( formatCartLineItem );
	const cartId = getCartId();

	return {
		id: 'checkout_' + cartId,
		cartId: cartId,
		lineItems: lineItems,
		subtotalPrice: totals.total_items
			? parseInt( totals.total_items, 10 ) / divisor
			: 0,
		totalTax: totals.total_tax
			? parseInt( totals.total_tax, 10 ) / divisor
			: 0,
		totalShipping: totals.total_shipping
			? parseInt( totals.total_shipping, 10 ) / divisor
			: 0,
		totalPrice: totals.total_price
			? parseInt( totals.total_price, 10 ) / divisor
			: 0,
		currency: ( totals.currency_code || '' ).toUpperCase(),
	};
}

// --- Event Handlers ---

/**
 * Handle add-to-cart from block product grids.
 *
 * @param {Object} data { product, quantity }
 */
function onCartAddItem( data ) {
	if ( ! pixelReady ) {
		return;
	}
	if ( window.mcPixel && window.mcPixel._handled.addToCart ) {
		return;
	}

	const product = data.product;
	if ( ! product ) {
		return;
	}

	const formatted = formatBlockProduct( product );
	const quantity = data.quantity || 1;
	const cartId = getCartId();

	trackPixelEvent( 'PRODUCT_ADDED_TO_CART', {
		cartId: cartId,
		product: {
			item: {
				id: formatted.id,
				productId: formatted.productId,
				title: formatted.title,
				price: formatted.price,
				currency: formatted.currency,
				sku: formatted.sku,
			},
			quantity: quantity,
			price: formatted.price * quantity,
			currency: formatted.currency,
		},
	} );

	if ( window.mcPixel ) {
		window.mcPixel._handled.addToCart = true;
		// Reset flag after a short delay so future add-to-cart events can fire
		setTimeout( function () {
			window.mcPixel._handled.addToCart = false;
		}, 1000 );
	}
}

/**
 * Handle remove-from-cart from block cart.
 *
 * @param {Object} data { product, quantity }
 */
function onCartRemoveItem( data ) {
	if ( ! pixelReady ) {
		return;
	}
	if ( window.mcPixel && window.mcPixel._handled.removeFromCart ) {
		return;
	}

	const cartItem = data.product;
	if ( ! cartItem ) {
		return;
	}

	const formatted = formatCartItem( cartItem );
	const quantity = data.quantity || 1;
	const cartId = getCartId();

	trackPixelEvent( 'PRODUCT_REMOVED_FROM_CART', {
		cartId: cartId,
		product: {
			item: {
				id: formatted.id,
				productId: formatted.productId,
				title: formatted.title,
				price: formatted.price,
				currency: formatted.currency,
				sku: formatted.sku,
			},
			quantity: quantity,
			price: formatted.price * quantity,
			currency: formatted.currency,
		},
	} );

	if ( window.mcPixel ) {
		window.mcPixel._handled.removeFromCart = true;
		setTimeout( function () {
			window.mcPixel._handled.removeFromCart = false;
		}, 1000 );
	}
}

/**
 * Handle checkout form render from block checkout.
 *
 * @param {Object} data { storeCart }
 */
function onCheckoutRender( data ) {
	if ( ! pixelReady ) {
		return;
	}
	if ( window.mcPixel && window.mcPixel._handled.checkout ) {
		return;
	}

	const storeCart = data.storeCart;
	if ( ! storeCart || ! storeCart.cartItems || storeCart.cartItems.length === 0 ) {
		return;
	}

	const checkout = formatCheckoutFromStoreCart( storeCart );

	trackPixelEvent( 'CHECKOUT_STARTED', {
		checkout: checkout,
	} );

	if ( window.mcPixel ) {
		window.mcPixel._handled.checkout = true;
	}
}

/**
 * Handle product list render (category/shop pages with block product grids).
 */
function onProductListRender() {
	if ( ! pixelReady ) {
		return;
	}
	if ( window.mcPixel && window.mcPixel._handled.category ) {
		return;
	}

	// Category data is set by PHP via window.mcPixel.data
	if (
		! window.mcPixel ||
		! window.mcPixel.data ||
		! window.mcPixel.data.category
	) {
		return;
	}

	trackPixelEvent(
		'PRODUCT_CATEGORY_VIEWED',
		window.mcPixel.data.category
	);

	if ( window.mcPixel ) {
		window.mcPixel._handled.category = true;
	}
}

/**
 * Handle product search from block search.
 *
 * @param {Object} data { searchTerm }
 */
function onProductSearch( data ) {
	if ( ! pixelReady ) {
		return;
	}
	if ( window.mcPixel && window.mcPixel._handled.search ) {
		return;
	}

	const searchTerm = data.searchTerm || '';
	if ( ! searchTerm ) {
		return;
	}

	trackPixelEvent( 'SEARCH_SUBMITTED', {
		query: searchTerm,
	} );

	if ( window.mcPixel ) {
		window.mcPixel._handled.search = true;
	}
}

// --- Register WC Blocks Hooks ---

addAction(
	'experimental__woocommerce_blocks-cart-add-item',
	NAMESPACE,
	onCartAddItem
);

addAction(
	'experimental__woocommerce_blocks-cart-remove-item',
	NAMESPACE,
	onCartRemoveItem
);

addAction(
	'experimental__woocommerce_blocks-checkout-render-checkout-form',
	NAMESPACE,
	onCheckoutRender
);

addAction(
	'experimental__woocommerce_blocks-product-list-render',
	NAMESPACE,
	onProductListRender
);

addAction(
	'experimental__woocommerce_blocks-product-search',
	NAMESPACE,
	onProductSearch
);

// --- Initialize ---

waitForPixelSDK();
