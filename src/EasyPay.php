<?php namespace kanazaca\easypay;

use DB;


class EasyPay {

    // optional for multibanco, required for boleto, credit card and direct debit
    public $o_name;

    // optional for multibanco, required for boleto, credit card and direct debit
    public $o_email;

    // The value the client have to pay.
    public $t_value;

    // You can use this to store whatever you want..
    public $o_description;

    // You can use this to store whatever you want..
    public $_obs;

    // Your client mobile or phone number. It will be automatically filled on Credit Card gateway.
    public $o_mobile;

    //Code for API validation
    public $code;

    // ID from orders table
    public $t_key;

    // LInk to easypay redirect
    public $return_url;

    // Uri holder
    private $uri = [];

    // Sandbox true or false
    public $live_mode;

    // Status of response
    public $status;

    // Message of response
    public $message;

    public function __construct($payment_info = [])
    {
        foreach($payment_info as $key => $info)
        {
            $this->$key = $info;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Creates a New Reference
    |--------------------------------------------------------------------------
    */
    public function createReference()
    {
        $this->_add_uri_param('ep_user', config('easypay.user'));
        $this->_add_uri_param('ep_entity', config('easypay.entity'));
        $this->_add_uri_param('ep_cin', config('easypay.cin'));
        $this->_add_uri_param('t_value', $this->t_value);
        $this->_add_uri_param('t_key', $this->t_key);
        $this->_add_uri_param('ep_language', config('easypay.language'));
        $this->_add_uri_param('ep_country', config('easypay.country'));
        $this->_add_uri_param('ep_ref_type', config('easypay.ref_type'));
        $this->_add_uri_param('o_name', $this->o_name);
        $this->_add_uri_param('o_description', $this->o_description);
        $this->_add_uri_param('o_obs', $this->o_obs);
        $this->_add_uri_param('o_mobile', $this->o_mobile);
        $this->_add_uri_param('o_email', $this->o_email);

        return $this->_xmlToArray( $this->_get_contents( $this->_get_uri( config('easypay.request_reference') ) ) );
    }

    /*
    |--------------------------------------------------------------------------
    | Process payment info (store ep_doc in database and return info from easypay)
    |--------------------------------------------------------------------------
    */
    public function processPaymentInfo()
    {
        //Insert notification into database
        DB::table('easypay_notifications')->insert([
            'ep_cin' => $_GET['ep_cin'],
            'ep_user' => $_GET['ep_user'],
            'ep_doc' => $_GET['ep_doc']
        ]);

        if(!$_GET['ep_doc'])
            throw new Exception("ep_doc is required for the communication");

        $this->_add_uri_param('ep_user', config('easypay.user'));
        $this->_add_uri_param('ep_cin', config('easypay.cin'));
        $this->_add_uri_param('ep_doc', $_GET['ep_doc']);

        $info = $this->_clearArray($this->_xmlToArray( $this->_get_contents( $this->_get_uri( config('easypay.request_payment_data')) )));

        $this->updatePaymentInfo($info);

        $this->renderXML($info);
    }

    /*
    |--------------------------------------------------------------------------
    | Save creditcard authorization key if it is OK
    |--------------------------------------------------------------------------
    */
    public static function saveAuthorizationKey()
    {
        if($_GET['s'] == 'ok')
        {
            $authorization_key = DB::table(config('easypay.order_table_name'))
            ->where(config('easypay.order_table_id'), '=', $_GET['t_key'])
            ->update([config('easypay.order_table_key_field') => $_GET['k']]);

            return [
                'entity' => $_GET['e'],
                'reference' => $_GET['r'],
                'value' => $_GET['v'],
                'key' => $_GET['k'],
                't_key' => $_GET['t_key']
            ];
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Trigger credit-card payment in easypay
    |--------------------------------------------------------------------------
    */
    public function requestPayment( $reference, $key, $value)
    {
        $this->_add_uri_param('u', config('easypay.user'));
        $this->_add_uri_param('e', config('easypay.entity'));
        $this->_add_uri_param('r', $reference);
        $this->_add_uri_param('l', config('easypay.language'));
        $this->_add_uri_param('k', $key);
        $this->_add_uri_param('v', $value);

        return $this->_xmlToArray( $this->_get_contents( $this->_get_uri( config('easypay.request_payment') )));
    }

    /*
    |--------------------------------------------------------------------------
    | Fetch all payments
    |--------------------------------------------------------------------------
    */
    public function fetchAllPayments()
    {
        $this->_add_uri_param('ep_cin', config('easypay.cin'));
        $this->_add_uri_param('ep_user', config('easypay.user'));
        $this->_add_uri_param('ep_entity', config('easypay.entity'));

        return $this->_xmlToArray( $this->_get_contents( $this->_get_uri( config('easypay.request_payment_list') )));
    }

    /*
    |--------------------------------------------------------------------------
    | Update payment info
    |--------------------------------------------------------------------------
    */
    private function updatePaymentInfo($data)
    {
        if(!$data)
        {
            throw new Exception("Payment info fetch failed !");
        }

        DB::table('easypay_notifications')->where('ep_doc', '=', $data['ep_doc'])->update([
            'ep_status' => $data['ep_status'],
            'ep_entity' => $data['ep_entity'],
            'ep_reference' => $data['ep_reference'],
            'ep_value' => $data['ep_value'],
            'ep_date' => $data['ep_date'],
            'ep_payment_type' => $data['ep_payment_type'],
            'ep_value_fixed' => $data['ep_value_fixed'],
            'ep_value_var' => $data['ep_value_var'],
            'ep_value_tax' => $data['ep_value_tax'],
            'ep_value_transf' => $data['ep_value_transf'],
            'ep_date_transf' => $data['ep_date_transf'],
            't_key' => $data['t_key']
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Render XML
    |--------------------------------------------------------------------------
    */
    public function renderXML($data)
    {
        header( 'Content-type: text/xml' );

        echo '<?xml version="1.0" encoding="ISO-8859-1" ?>
                <getautoMB_key>
                  <ep_status>'.$data['ep_status'].'</ep_status>
                  <ep_message>'.$data['ep_message'].'</ep_message>
                  <ep_cin>'.$data['ep_cin'].'</ep_cin>
                  <ep_user>'.$data['ep_user'].'</ep_user>
                  <ep_doc>'.$data['ep_doc'].'</ep_doc>
                </getautoMB_key>';
    }

    /*
    |--------------------------------------------------------------------------
    | Sets live mode
    |--------------------------------------------------------------------------
    */
    public function setLiveMode($live_mode = false)
    {
        $this->live_mode = $live_mode;
    }

    /*
    |--------------------------------------------------------------------------
    | Set ep_rec_url - call if want a return url different for this request
    |--------------------------------------------------------------------------
    */
    public function seReturnUrl($return_url = null)
    {
        $this->return_url = $return_url ? $return_url : config('easypay.ep_rec_url');
    }

    /*
    |--------------------------------------------------------------------------
    | Convert XML to Array
    |--------------------------------------------------------------------------
    */
    private function _xmlToArray( $string )
    {
        try {
            $obj 	= simplexml_load_string( $string );
            $data 	= json_decode( json_encode( $obj ), true );
        } catch( Exception $e ) {
            $data = false;
        }

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Get request reference link
    |--------------------------------------------------------------------------
    */
    public function getRequestReferenceLink()
    {
        return config('easypay.request_reference');
    }

    /*
    |--------------------------------------------------------------------------
    | Get request payment link
    |--------------------------------------------------------------------------
    */
    public function getServerLink()
    {
        return $this->is_live() ? config('easypay.production_server') : config('easypay.test_server');
    }

    /*
    |--------------------------------------------------------------------------
    | Get production or sanbox mode
    |--------------------------------------------------------------------------
    */
    private function is_live()
    {
        if( $this->live_mode != null )
        {
            return $this->live_mode;
        }

        return config('easypay.live_mode');
    }

    /*
    |--------------------------------------------------------------------------
    | Return and clear URI
    |--------------------------------------------------------------------------
    */
    public function _get_uri($url)
    {
        $str = $this->getServerLink();

        $this->_add_code_to_uri(); // adds s_code to all requests

        $str .= $url;

        $tmp = str_replace(' ', '+', http_build_query( $this -> uri ) );
        $this -> uri = array();

        return $str . '?' . $tmp;
    }

    /*
    |--------------------------------------------------------------------------
    | Returns a string from a link via cUrl
    |--------------------------------------------------------------------------
    */
    private function _get_contents( $url, $type = 'GET' )
    {
        try {
            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, $url );
            curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 5 );
            if ( strtoupper( $type ) == 'GET' ) {
            } elseif ( strtoupper( $type ) == 'POST' ) {
                curl_setopt( $curl, CURLOPT_POST, TRUE );
            } else {
                throw new Exception('Communication Error, standart communication not selected, POST or GET required');
            }

            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, TRUE );
            $result = curl_exec( $curl );
            curl_close($curl);
        } catch( Exception $e ) {
            $result = false;
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | Adds a parameter to our URI
    |--------------------------------------------------------------------------
     */
    private function _add_uri_param( $key, $value )
    {
        $this->uri[ $key ] = $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Add s_code to URI
    |--------------------------------------------------------------------------
    */ 
    private function _add_code_to_uri()
    {
        $this->code = config('easypay.code') ? config('easypay.code') : false;

        $this->_add_uri_param('s_code',  $this->code);
    }

    /*
    |--------------------------------------------------------------------------
    | Clear data array
    |--------------------------------------------------------------------------
    */
    public function _clearArray($array)
    {
        foreach($array as $key => $arr)
        {
            if(is_array($arr))
            {
                $array[$key] = '';
            }
        }

        return $array;
    }
}