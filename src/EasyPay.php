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

    public function __construct($o_name, $o_email, $t_value, $o_description = null, $o_obs = null, $o_mobile = null)
    {
        $this->o_name = $o_name;
        $this->o_email = $o_email;
        $this->t_value = $t_value;
        $this->o_description = $o_description;
        $this->o_obs = $o_obs;
        $this->o_mobile = $o_mobile;
        $this->code = config('easypay.code') ? config('easypay.code') : false;
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
        //$this->_add_uri_param('t_key', $this->key);
        $this->_add_uri_param('ep_language', config('easypay.language'));
        $this->_add_uri_param('ep_country', config('easypay.country'));
        $this->_add_uri_param('ep_ref_type', config('easypay.ref_type'));
        $this->_add_uri_param('o_name', $this->o_name);
        $this->_add_uri_param('o_description', $this->o_description);
        $this->_add_uri_param('o_obs', $this->o_obs);
        $this->_add_uri_param('o_mobile', $this->o_mobile);
        $this->_add_uri_param('o_email', $this->o_email);

        if( isset( $this -> code ) && $this -> code )
        {
            $this->_add_uri_param('s_code',  $this->code);
        }

        return $this->_xmlToArray( $this->_get_contents( $this->_get_uri( config('easypay.request_reference') ) ) );
    }

    /*
    |--------------------------------------------------------------------------
    | Process payment info (store ep_doc in database and return info from easypay)
    |--------------------------------------------------------------------------
    */
    public function processPaymentInfo($data)
    {
        //Insert notification into database
        DB::table('easypay_notifications')->insert([
            'ep_cin' => $data['ep_cin'],
            'ep_user' => $data['ep_user'],
            'ep_doc' => $data['ep_doc']
        ]);

        if(!$data['ep_doc'])
            throw new Exception("ep_doc is required for the communication");

        $this->_add_uri_param('ep_user', config('easypay.user'));
        $this->_add_uri_param('ep_cin', config('easypay.cin'));
        $this->_add_uri_param('ep_doc', $data['ep_doc']);

        if( isset( $this -> code ) && $this -> code )
        {
            $this->_add_uri_param('s_code',  $this->code);
        }

        $info = $this->clearArray($this->_xmlToArray( $this->_get_contents( $this->_get_uri( config('easypay.request_payment_data')) )));

        $this->updatePaymentInfo($info);

        $this->renderXML($info);
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
    | Clear data array
    |--------------------------------------------------------------------------
    */
    public function clearArray($array)
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