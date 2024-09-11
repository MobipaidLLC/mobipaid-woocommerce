const { decodeEntities } = window.wp.htmlEntities
const { registerPaymentMethod } = window.wc.wcBlocksRegistry
const { getSetting } = window.wc.wcSettings

const settings = getSetting( 'mobipaid_data', {} )

const label = decodeEntities( settings.title ) || 'Mobipaid'

const content = () => {
	return decodeEntities( settings.description || '' )
}

const finalLabel = window.wp.element.createElement(
    "span",
    {
		class: "wc-block-components-payment-method-label",
		style: { width: '100%' }
	},
	window.wp.element.createElement(
		"img", {
			src: settings.icon,
			alt: label,
			style: { float: 'right' }
		}
	),
	label
);

registerPaymentMethod( {
	name: "mobipaid",
	label: finalLabel,
	content: Object( window.wp.element.createElement )( content, null ),
	edit: Object( window.wp.element.createElement )( content, null ),
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	}
} )