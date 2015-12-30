<?php namespace kanazaca\easypay;
 
use DB;
 
 
class EasyPay {

    /**
     * @var string
     */
    public $o_name;

    /**
     * @var string
     */
    public $o_email;

    /**
     * @var string
     */
    public $t_value;

    /**
     * @var string
     */
    public $o_description;

    /**
     * @var string
     */
    public $_obs;

    /**
     * @var string
     */
    public $o_mobile;

    /**
     * @var string
     */
    public $code;

    /**
     * @var string
     */
    public $t_key;

    /**
     * @var string
     */
    public $return_url;

    /**
     * @var array
     */
    private $uri = [];

    /**
     * @var boolean
     */
    public $live_mode;

    /**
     * @var string
     */
    public $status;

    /**
     * @var string
     */
    public $message;


    public function __construct($payment_info = [])
    {
        foreach($payment_info as $key => $info)
        {
            $this->$key = $info;
        }
    }


    /**
     * Creates a reference to pay with credit card or MB
     *
     * @return array
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

    /**
     * Process payment info sent from easypay
     *
     * @return string
     * @throws Exception
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
 
        return $this->renderXML($info);
    }

    /**
     * Save to database the key sent from easypay
     *
     * @return array|bool
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

    /**
     * @param $reference
     * @param $key
     * @param $value
     * @return array
     */
    public function requestPayment($reference, $key, $value)
    {
        $this->_add_uri_param('u', config('easypay.user'));
        $this->_add_uri_param('e', config('easypay.entity'));
        $this->_add_uri_param('r', $reference);
        $this->_add_uri_param('l', config('easypay.language'));
        $this->_add_uri_param('k', $key);
        $this->_add_uri_param('v', $value);
 
        return $this->_xmlToArray( $this->_get_contents( $this->_get_uri( config('easypay.request_payment') )));
    }

    /**
     * Return all payments
     *
     * @return array
     */
    public function fetchAllPayments()
    {
        $this->_add_uri_param('ep_cin', config('easypay.cin'));
        $this->_add_uri_param('ep_user', config('easypay.user'));
        $this->_add_uri_param('ep_entity', config('easypay.entity'));
 
        return $this->_xmlToArray( $this->_get_contents( $this->_get_uri( config('easypay.request_payment_list') )));
    }

    /**
     * Update information sent from easypay in database
     *
     * @param $data
     * @throws Exception
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

    /**
     * Build XML to easypay read
     *
     * @param $data
     * @return string
     */
    public function renderXML($data)
    {
        $content = '<?xml version="1.0" encoding="ISO-8859-1" ?>
               <getautomb_key>
                 <ep_status>'.$data['ep_status'].'</ep_status>
                 <ep_message>'.$data['ep_message'].'</ep_message>
                 <ep_cin>'.$data['ep_cin'].'</ep_cin>
                 <ep_user>'.$data['ep_user'].'</ep_user>
                 <ep_doc>'.$data['ep_doc'].'</ep_doc>
               </getautomb_key>';
 
        return $content;
    }

    /**
     * Read and convert XML to Array
     *
     * @param $string
     * @return bool|mixed
     */
    private function _xmlToArray($string )
    {
        try {
            $obj        = simplexml_load_string( $string );
            $data       = json_decode( json_encode( $obj ), true );
        } catch( Exception $e ) {
            $data = false;
        }
 
        return $data;
    }

    /**
     * Check if is production or live mode
     *
     * @return mixed
     */
    private function is_live()
    {
        if( $this->live_mode != null )
        {
            return $this->live_mode;
        }

        return config('easypay.live_mode');
    }

    /**
     * Add param to URI that will be sent to easypay API
     * @param $key
     * @param $value
     */
    private function _add_uri_param($key, $value )
    {
        $this->uri[ $key ] = $value;
    }

    /**
     * SECURITY : If any code is defined in config this will attach it to every request
     */
    private function _add_code_to_uri()
    {
        $this->code = config('easypay.code') ? config('easypay.code') : false;

        $this->_add_uri_param('s_code',  $this->code);
    }

    /**
     * Helper to clear array generated by xml to array converter
     *
     * @param $array
     * @return mixed
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

    /**
     * Build URI to send in requests to easypay
     *
     * @param $url
     * @return string
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

    /**
     * Make the call to easypay API
     *
     * @param $url
     * @param string $type
     * @return bool|mixed
     */
    private function _get_contents($url, $type = 'GET' )
    {
        try {
            $curl = curl_init();
            curl_setopt( $curl, CURLOPT_URL, $url );
            curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 20 );
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

    /**
     * Define if its production or dev mode using config file
     *
     * @param bool|false $live_mode
     */
    public function setLiveMode($live_mode = false)
    {
        $this->live_mode = $live_mode;
    }

    /**
     * Setter for return_url
     *
     * @param $return_url
     */
    public function seReturnUrl($return_url = null)
    {
        $this->return_url = $return_url ? $return_url : config('easypay.ep_rec_url');
    }

    /**
     * Getter for request reference link
     *
     * @return mixed
     */
    public function getRequestReferenceLink()
    {
        return config('easypay.request_reference');
    }

    /**
     * Getter for server link
     *
     * @return mixed
     */
    public function getServerLink()
    {
        return $this->is_live() ? config('easypay.production_server') : config('easypay.test_server');
    }
}
