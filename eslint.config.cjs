/**
 * ESLint flat config: WordPress defaults + externals provided by WordPress / Lite at runtime.
 */
let wpConfig = [];
try {
	wpConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );
} catch ( err ) {
	// Fallback if @wordpress/scripts is not installed.
	wpConfig = [
		{
			languageOptions: {
				ecmaVersion: 'latest',
				sourceType: 'module',
				parserOptions: {
					ecmaFeatures: {
						jsx: true,
					},
				},
			},
		},
	];
}

module.exports = [
	...( Array.isArray( wpConfig ) ? wpConfig : [ wpConfig ] ),
	{ ignores: [ 'assets/**', 'build/**', 'bin/**', 'node_modules/**', 'vendor/**', 'addons/**' ] },
	{
		rules: {
			'import/no-unresolved': 'off',
			'import/no-extraneous-dependencies': 'off',
			// Loading / empty / list render chains read clearly as ternaries in JSX.
			'no-nested-ternary': 'off',
		},
	},
];
