module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	settings: {
		// List of modules that are externals in our webpack config.
		// This helps the `import/no-extraneous-dependencies` and
		//`import/no-unresolved` rules account for them.
		'import/core-modules': [
			'@woocommerce/blocks-checkout',
			'@woocommerce/settings',
			'@woocommerce/shared-hocs',
			'react',
		],
		'import/resolver': {
			node: {},
			webpack: {},
		},
	},
};
