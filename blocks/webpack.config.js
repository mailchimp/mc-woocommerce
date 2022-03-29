const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const DependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );

const wcDepMap = {
	'@woocommerce/blocks-checkout': [ 'wc', 'blocksCheckout' ],
	'@woocommerce/shared-hocs': [ 'wc', 'wcBlocksSharedHocs' ],
};

const wcHandleMap = {
	'@woocommerce/blocks-checkout': 'wc-blocks-checkout',
	'@woocommerce/shared-hocs': 'wc-blocks-shared-hocs',
};

const requestToExternal = ( request ) => {
	if ( wcDepMap[ request ] ) {
		return wcDepMap[ request ];
	}
};

const requestToHandle = ( request ) => {
	if ( wcHandleMap[ request ] ) {
		return wcHandleMap[ request ];
	}
};

module.exports = {
	...defaultConfig,
	entry: {
		'newsletter-block': path.resolve( process.cwd(), 'assets', 'js', 'checkout-newsletter-subscription-block', 'index.js' ),
		'newsletter-block-frontend': path.resolve( process.cwd(), 'assets', 'js', 'checkout-newsletter-subscription-block', 'frontend.js' ),
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin( {
			injectPolyfill: true,
			requestToExternal,
			requestToHandle,
		} ),
	],
};
