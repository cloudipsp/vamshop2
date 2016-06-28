<?php
/* -----------------------------------------------------------------------------------------
   VamShop - http://vamshop.com
   -----------------------------------------------------------------------------------------
   Copyright (c) 2014 VamSoft Ltd.
   License - http://vamshop.com/license.html
   ---------------------------------------------------------------------------------------*/
App::uses('PaymentAppController', 'Payment.Controller');

class FondyController extends PaymentAppController  {

    public $uses = array('PaymentMethod', 'Order', 'Content');
    public $components = array('OrderBase','ModuleBase');
    public $module_name = 'Fondy';
    public $icon = 'fondy.png';

    public function settings ()
    {
        $this->set('data', $this->PaymentMethod->findByAlias($this->module_name));
    }

    public function install()
    {

        $default_alias = 'Fondy';
        $default_name = __('Fondy');
        $default_description = "{payment alias='Fondy'}";
        $this->ModuleBase->create_core_page($default_alias, $default_name, $default_description);

        $new_module = array();
        $new_module['PaymentMethod']['active'] = '1';
        $new_module['PaymentMethod']['default'] = '0';
        $new_module['PaymentMethod']['name'] = Inflector::humanize($this->module_name);
        $new_module['PaymentMethod']['icon'] = $this->icon;
        $new_module['PaymentMethod']['alias'] = $this->module_name;

        $new_module['PaymentMethodValue'][0]['fondy_merchant_id'] = $this->PaymentMethod->id;
        $new_module['PaymentMethodValue'][0]['key'] = 'fondy_merchant_id';
        $new_module['PaymentMethodValue'][0]['value'] = '';

        $new_module['PaymentMethodValue'][1]['fondy_secret_key'] = $this->PaymentMethod->id;
        $new_module['PaymentMethodValue'][1]['key'] = 'fondy_secret_key';
        $new_module['PaymentMethodValue'][1]['value'] = '';

        $new_module['PaymentMethodValue'][2]['fondy_lang_key'] = $this->PaymentMethod->id;
        $new_module['PaymentMethodValue'][2]['key'] = 'fondy_lang_key';
        $new_module['PaymentMethodValue'][2]['value'] = '';

        $new_module['PaymentMethodValue'][3]['fondy_cur_key'] = $this->PaymentMethod->id;
        $new_module['PaymentMethodValue'][3]['key'] = 'fondy_cur_key';
        $new_module['PaymentMethodValue'][3]['value'] = 'RUB';



        $this->PaymentMethod->saveAll($new_module);

        $this->Session->setFlash(__('Module Installed'));
        $this->redirect('/payment_methods/admin/');
    }

    public function uninstall()
    {
        $module_id = $this->PaymentMethod->findByAlias($this->module_name);

        $this->PaymentMethod->delete($module_id['PaymentMethod']['id'], true);

        $this->Session->setFlash(__('Module Uninstalled'));

        $core_page = $this->Content->find('first', array('conditions' => array('Content.parent_id' => '-1','alias' => 'Fondy')));
        $this->Content->delete($core_page['Content']['id'],true);

        $this->redirect('/payment_methods/admin/');
    }


    public function before_process ()
    {
        global $content, $config;

        if ($_SESSION['fondy_error'] == ''){
            if ($_SERVER['REQUEST_URI'] != '/page/Fondy'.$config['URL_EXTENSION']) {
                $order = $this->OrderBase->get_order();

                App::import('Model', 'PaymentMethod');
                $this->PaymentMethod = new PaymentMethod();
                $fondy_merchant_id = $this->PaymentMethod->PaymentMethodValue->find('first', array('conditions' => array('key' => 'fondy_merchant_id')));
                $merchant_id = $fondy_merchant_id['PaymentMethodValue']['value'];
                $fondy_secret_key = $this->PaymentMethod->PaymentMethodValue->find('first', array('conditions' => array('key' => 'fondy_secret_key')));
                $secret_key = $fondy_secret_key['PaymentMethodValue']['value'];
                $fondy_lang_key = $this->PaymentMethod->PaymentMethodValue->find('first', array('conditions' => array('key' => 'fondy_lang_key')));
                $lang_key = $fondy_lang_key['PaymentMethodValue']['value'];
                $fondy_cur_key = $this->PaymentMethod->PaymentMethodValue->find('first', array('conditions' => array('key' => 'fondy_cur_key')));
                $currency = $fondy_cur_key['PaymentMethodValue']['value'];
                if ($currency == '') {
                    $currency = $_SESSION['Customer']['currency_code'];
                }
                $desc = 'Order : ' . $order['Order']['id'];
                $result_url = 'http://' . $_SERVER['HTTP_HOST'] . BASE . '/orders/place_order/';

                $oplata_args = array('order_id' => $order['Order']['id'] . fondycsl::ORDER_SEPARATOR . time(),
                    'merchant_id' => $merchant_id,
                    'order_desc' => $desc,
                    'amount' => $order['Order']['total'],
                    'currency' => $currency,
                    'server_callback_url' => $result_url,
                    'response_url' => $result_url,
                    'lang' => $lang_key,
                    'sender_email' => $order['Order']['email']);

                $oplata_args['signature'] = fondycsl::getSignature($oplata_args, $secret_key);

                $content = '
        <script src="https://api.fondy.eu/static_common/v1/checkout/ipsp.js"></script>
<style>
#checkout_wrapper {
    top: -69px;
    text-align: left;
    position: relative;
    background: #FFF;
    width: auto;
    max-width: 2000px;
    margin: 9px auto;

}
</style>
<div style="display: none;">
<div id="checkout">
<div class="colorbox" id="checkout_wrapper">
</div>
</div>
</div>
<button class="btn btn-default" type="button" onclick="callpay();" value="{lang}Confirm Order{/lang}"><i class="fa fa-check"></i> {lang}Confirm Order{/lang}</button>
<script>
function checkoutInit(url) {
	$ipsp("checkout").scope(function() {
		this.setCheckoutWrapper("#checkout_wrapper");
		this.addCallback(__DEFAULTCALLBACK__);
		this.action("show", function(data) {
           $("#checkout_loader").remove();
            $("#checkout").show();
        });
		this.action("hide", function(data) {
            $("#checkout").hide();
        });

        this.width("100%");
        this.action("resize", function(data) {
        $("#checkout_wrapper").width(430).height(data.height);
            });


		this.loadUrl(url);
	});
    };
    var button = $ipsp.get("button");
    button.setMerchantId(' . $oplata_args[merchant_id] . ');
    button.setAmount(' . $oplata_args[amount] . ', "' . $oplata_args[currency] . '", true);
    button.setHost("api.fondy.eu");
    button.addParam("order_desc","' . $oplata_args[order_desc] . '");
	button.addParam("signature","' . $oplata_args[signature] . '");
    button.addParam("order_id","' . $oplata_args[order_id] . '");
    button.addParam("lang","' . $oplata_args[lang] . '");//button.addParam("delayed","N");
    button.addParam("server_callback_url","' . $oplata_args[server_callback_url] . '");
    button.addParam("sender_email","' . $oplata_args[sender_email] . '");
    button.setResponseUrl("' . $oplata_args[response_url] . '");
    checkoutInit(button.getUrl());

$(document).ready(function(){
 $.colorbox({
 inline:true,scrolling:false, innerWidth:480,innerHeight:700,
 href: "#checkout_wrapper",
 });
});
function callpay(){
$.colorbox({
 inline:true,scrolling:false, innerWidth:480,innerHeight:700,
 href: "#checkout_wrapper",
 });
}
    </script>


    ';
                return $content;
            }
            }else{
            $content='
            <p class="error">
            <strong> '.  __d("fondy","Oplata error").'  </strong></br>
       '.  __d("fondy","Order #").'  '.  $_SESSION["fondy_id"] .'</br>
       '.  __d("fondy","Error description").'  '.  $_SESSION["fondy_desc"] .'</br>
       '.  __d("fondy","Error code").'  '.  $_SESSION["fondy_error"] .'</br>
            </p>
            ';
            unset($_SESSION['fondy_id']);
            unset($_SESSION['fondy_desc']);
            unset($_SESSION['fondy_error']);
            return $content;
        }

    }

    public function after_process()
    {
        global $config;
        if (empty($_POST)) {
            $fap = json_decode(file_get_contents("php://input"));
            $_POST = array();
            foreach ($fap as $key => $val) {
                $_POST[$key] = $val;
            }
        }
        list($order_id,) = explode(fondycsl::ORDER_SEPARATOR, $_POST['order_id']);
        $payment_method = $this->PaymentMethod->find('first', array('conditions' => array('alias' => $this->module_name)));

        $order_data = $this->Order->find('first', array('conditions' => array('Order.id' => $order_id)));
        //print_r ($order_data);die;
        $fondy_merchant_id = $this->PaymentMethod->PaymentMethodValue->find('first', array('conditions' => array('key' => 'fondy_merchant_id')));
        $merchant_id = $fondy_merchant_id['PaymentMethodValue']['value'];
        $fondy_secret_key = $this->PaymentMethod->PaymentMethodValue->find('first', array('conditions' => array('key' => 'fondy_secret_key')));
        $secret_key = $fondy_secret_key['PaymentMethodValue']['value'];
        $options = array(
            'merchant' => $merchant_id,
            'secretkey' => $secret_key
        );
        $paymentInfo = fondycsl::isPaymentValid($options, $_POST);

        if ($order_data) {

            if ($paymentInfo === true && $_POST['order_status'] == fondycsl::ORDER_APPROVED) {
               // print_r ($paymentInfo); die;
                $order_data['Order']['order_status_id'] = $payment_method['PaymentMethod']['order_status_id'];
                $this->Order->save($order_data);
                //$this->Session->setFlash($_POST[order_status]);
            } else {
                //print_r($_POST);die;
                $_SESSION['fondy_id'] = $_POST[order_id];
                $_SESSION['fondy_desc'] = $_POST[response_description];
                $_SESSION['fondy_error'] = $_POST[response_code];

                    $this->redirect('/page/Fondy'.$config['URL_EXTENSION']);
                die();

            }

        }
    }




}
class fondycsl
{
    const RESPONCE_SUCCESS = 'success';
    const RESPONCE_FAIL = 'failure';
    const ORDER_SEPARATOR = '#';
    const SIGNATURE_SEPARATOR = '|';
    const ORDER_APPROVED = 'approved';
    const ORDER_DECLINED = 'declined';

    public static function getSignature($data, $password, $encoded = true)
    {
        $data = array_filter($data, function($var) {
            return $var !== '' && $var !== null;
        });
        ksort($data);
        $str = $password;
        foreach ($data as $k => $v) {
            $str .= self::SIGNATURE_SEPARATOR . $v;
        }
        if ($encoded) {
            return sha1($str);
        } else {
            return $str;
        }
    }
    public static function isPaymentValid($oplataSettings, $response)
    {
        if ($oplataSettings['merchant'] != $response['merchant_id']) {
            return 'An error has occurred during payment. Merchant data is incorrect.';
        }
   
		  $responseSignature = $response['signature'];
		if (isset($response['response_signature_string'])){
			unset($response['response_signature_string']);
		}
		if (isset($response['signature'])){
			unset($response['signature']);
		}
		if (self::getSignature($response, $oplataSettings['secretkey']) != $responseSignature) {
            return 'An error has occurred during payment. Signature is not valid.';
        }
        return true;
    }

}
?>