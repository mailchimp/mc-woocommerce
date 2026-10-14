/** Run with: node tests/regression/pixel-cart-identity.js */
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const vm = require( 'node:vm' );
const path = require( 'node:path' );
const events = [];
const hooks = {};
const context = vm.createContext( {
    window: { mcPixel: { _handled: {}, parentMap: {} }, $mcSite: { pixel: { api: {
        track: ( name, data ) => { events.push( { name, data } ); return Promise.resolve(); },
    } } } },
    addAction: ( name, namespace, callback ) => { hooks[ name ] = callback; },
    setTimeout: () => {},
    console,
} );
const source = fs.readFileSync( path.join( __dirname, '../../blocks/assets/js/pixel-tracking/index.js' ), 'utf8' )
    .replace( /import \{ addAction \} from '@wordpress\/hooks';/, '' );
vm.runInContext( source + '\npixelReady = true;', context );
const item = {
    id: 202, name: 'Blue / Small', quantity: 1, sku: 'BLUE-S',
    prices: { price: '1200', currency_code: 'USD', currency_minor_unit: 2 },
    extensions: { 'mailchimp-pixel': { product_id: '100' } },
};
context.item = item;
// No seeded map: this variation arrived after the page was rendered.
const formatted = vm.runInContext( 'formatCartItem(item)', context );
assert.equal( formatted.id, '202' );
assert.equal( formatted.productId, '100' );
assert.equal( formatted.sku, 'BLUE-S' );
assert.equal( formatted.price, 12 );
hooks[ 'experimental__woocommerce_blocks-cart-remove-item' ]( { product: item, quantity: 1 } );
assert.equal( events[0].data.product.item.productId, '100' );
hooks[ 'experimental__woocommerce_blocks-checkout-render-checkout-form' ]( {
    storeCart: { cartItems: [ item, { ...item, id: 203 } ], cartTotals: {} },
} );
assert.equal( events[1].data.checkout.lineItems[0].item.id, '202' );
assert.equal( events[1].data.checkout.lineItems[1].item.id, '203' );
assert.equal( events[1].data.checkout.lineItems[1].item.productId, '100' );
// Extension is authoritative over an old map; missing extension retains compatibility.
context.window.mcPixel.parentMap['202'] = '999';
assert.equal( vm.runInContext( 'formatCartItem(item).productId', context ), '100' );
context.item = { ...item, extensions: {} };
assert.equal( vm.runInContext( 'formatCartItem(item).productId', context ), '100' );
context.item = { ...item, id: 300, extensions: { 'mailchimp-pixel': { product_id: '300' } } };
assert.equal( vm.runInContext( 'formatCartItem(item).productId', context ), '300' );
assert.equal( vm.runInContext( 'formatCartItem(item).id', context ), '300' );
context.window.mcPixel._handled = {};
hooks[ 'experimental__woocommerce_blocks-cart-add-item' ]( { product: item, quantity: 2 } );
assert.equal( events.at(-1).data.product.item.id, '202' );
assert.equal( events.at(-1).data.product.item.productId, '100' );
context.item = { ...item, extensions: {}, parent: 100 };
assert.equal( vm.runInContext( 'formatBlockProduct(item).productId', context ), '100' );
// Exercise the shipped bundle's actual hook registration and emitted payloads too.
const builtHooks = {};
const builtEvents = [];
const builtWindow = {
    wp: { hooks: { addAction: ( name, namespace, callback ) => { builtHooks[name] = callback; } } },
    mcPixel: { _handled: {}, parentMap: {} },
    $mcSite: { pixel: { api: { track: ( name, data ) => {
        builtEvents.push( { name, data } ); return Promise.resolve();
    } } } },
};
vm.runInNewContext( fs.readFileSync( path.join( __dirname, '../../blocks/build/pixel-tracking.js' ), 'utf8' ), {
    window: builtWindow, setTimeout: ( callback ) => callback(), console,
} );
builtHooks['experimental__woocommerce_blocks-cart-remove-item']( { product: item, quantity: 1 } );
assert.equal( builtEvents[0].data.product.item.productId, '100' );
builtHooks['experimental__woocommerce_blocks-checkout-render-checkout-form']( {
    storeCart: { cartItems: [item], cartTotals: {} },
} );
assert.equal( builtEvents[1].data.checkout.lineItems[0].item.id, '202' );
assert.equal( builtEvents[1].data.checkout.lineItems[0].item.productId, '100' );
console.log( 'Pixel cart identity source and bundle regression checks passed.' );
