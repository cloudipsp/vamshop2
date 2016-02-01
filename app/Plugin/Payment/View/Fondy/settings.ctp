<?php
/* -----------------------------------------------------------------------------------------
   VamShop - http://vamshop.com
   -----------------------------------------------------------------------------------------
   Copyright (c) 2014 VamSoft Ltd.
   License - http://vamshop.com/license.html
   ---------------------------------------------------------------------------------------*/
	
echo $this->Form->input('fondy.fondy_merchant_id', array(
	'label' => __d('fondy','Merchant ID'),
	'type' => 'text',
	'value' => $data['PaymentMethodValue'][0]['value']
));
echo $this->Form->input('fondy.fondy_secret_key', array(
	'label' => __d('fondy','Secret Key'),
	'type' => 'text',
	'value' => $data['PaymentMethodValue'][1]['value']
	));
echo $this->Form->input('fondy.fondy_lang_key', array(
    	'label' => __d('fondy','Language'),
    	'type' => 'text',
    	'value' => $data['PaymentMethodValue'][2]['value']
    	));
 echo $this->Form->input('fondy.fondy_cur_key', array(
            	'label' => __d('fondy','Currency'),
            	'type' => 'text',
            	'value' => $data['PaymentMethodValue'][3]['value']
            	));
?>