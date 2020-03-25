/**
 * Internal dependencies
 */
const {
	TRANSLATION_FUNCTIONS,
	getTextContentFromNode,
	getTranslateFunctionName,
} = require( '../utils' );

const THREE_DOTS = '...';
const ELLIPSIS = '…';

function replaceThreeDotsWithEllipsis( string ) {
	return string.replace( /\.\.\./g, ELLIPSIS );
}

// see eslint-plugin-wpcalypso.
function makeFixerFunction( arg ) {
	return ( fixer ) => {
		switch ( arg.type ) {
			case 'TemplateLiteral':
				return arg.quasis.reduce( ( fixes, quasi ) => {
					if (
						'TemplateElement' === quasi.type &&
						quasi.value.raw.includes( THREE_DOTS )
					) {
						fixes.push(
							fixer.replaceTextRange(
								[ quasi.start, quasi.end ],
								replaceThreeDotsWithEllipsis( quasi.value.raw )
							)
						);
					}
					return fixes;
				}, [] );

			case 'Literal':
				return [
					fixer.replaceText(
						arg,
						replaceThreeDotsWithEllipsis( arg.raw )
					),
				];

			case 'BinaryExpression':
				return [
					...makeFixerFunction( arg.left )( fixer ),
					...makeFixerFunction( arg.right )( fixer ),
				];
		}
	};
}

module.exports = {
	meta: {
		type: 'problem',
		schema: [],
		messages: {
			foundThreeDots: 'Use ellipsis character (…) in place of three dots',
		},
		fixable: 'code',
	},
	create( context ) {
		return {
			CallExpression( node ) {
				const { callee, arguments: args } = node;

				const functionName = getTranslateFunctionName( callee );

				if ( ! TRANSLATION_FUNCTIONS.includes( functionName ) ) {
					return;
				}

				const functionArgs = [ args[ 0 ] ];

				if ( [ '_n', '_nx' ].includes( functionName ) ) {
					functionArgs.push( args[ 1 ] );
				}

				for ( const arg of functionArgs ) {
					const argumentString = getTextContentFromNode( arg );
					if (
						! argumentString ||
						! argumentString.includes( THREE_DOTS )
					) {
						continue;
					}

					context.report( {
						node,
						messageId: 'foundThreeDots',
						fix: makeFixerFunction( arg ),
					} );
				}
			},
		};
	},
};
