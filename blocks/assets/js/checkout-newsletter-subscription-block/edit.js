/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { CheckboxControl } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import './style.scss';

export const Edit = ( { attributes, setAttributes } ) => {
	const { text, gdprHeadline, gdpr } = attributes;
	const blockProps = useBlockProps();


	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Block options', 'mailchimp-for-woocommerce' ) }>
					Options for the block go here.
				</PanelBody>
			</InspectorControls>
			<div style={{display: 'flex'}}>
				<CheckboxControl
					id="newsletter-text"
					checked={ false }
					disabled={ true }
				/>
				<RichText
					value={ text }
					help={__( 'Set the newsletter confirmation text.', 'mailchimp-for-woocommerce' )}
					onChange={ ( value ) => setAttributes( { text: value } ) }
				/>
			</div>
			{
				gdpr && gdpr.length &&
				(
					<>
						<div style={{display: 'flex', marginTop: '2rem'}}>
							<RichText
								value={ gdprHeadline }
								help={__( 'Set the GDPR headline.', 'mailchimp-for-woocommerce' )}
								onChange={ ( value ) => setAttributes( { gdprHeadline: value } ) }
							/>
						</div>
						{gdpr.map((gdprItem, index) => {
							return (
								<div style={{display: 'flex', marginTop: '1rem'}}>
									<CheckboxControl
										id={'gdpr_'+gdprItem.marketing_permission_id}
										checked={ gdpr[index].enabled }
										onChange={ () => {
											gdpr[index].enabled = !gdpr[index].enabled;
											setAttributes({gdpr: gdpr});
										}}
									>
										<span dangerouslySetInnerHTML={ {__html: gdprItem.text} }/>
									</CheckboxControl>
								</div>
							)
						})}
					</>
				)
			}
		</div>
	);
};

// not sure
export const Save = () => {
	return <div { ...useBlockProps.save() } />;
};