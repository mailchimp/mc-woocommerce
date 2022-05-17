import {Header} from "@wordpress/components/build/box-control/styles/box-control-styles";
import {__} from "@wordpress/i18n";

{gdpr.length &&
<div>
    <Header>
        {__(gdprHeadline, 'mailchimp-for-woocommerce')}
    </Header>
    {
        gdpr.forEach((gdprItem) => {
            <CheckboxControl
                id={'gdpr_'+gdprItem['marketing_permission_id']}
                checked={ gdprValues[gdprItem['marketing_permission_id']] }
                onChange={ () => {
                    gdprValues[gdprItem['marketing_permission_id']] = !gdprValues[gdprItem['marketing_permission_id']]
                }}
            >
                <span dangerouslySetInnerHTML={ {__html: gdprItem['text']} }/>
            </CheckboxControl>
        })
    }
</div>
}