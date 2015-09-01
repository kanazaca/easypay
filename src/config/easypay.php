<?php

return [
    // Define the Request reference API
    'request_reference' 					=> 'api_easypay_01BG.php',

    // Define the Request payment API
    'request_payment' 						=> 'api_easypay_05AG.php',

    // Define the Request Payment Data API
    'request_payment_data'  				=> 'api_easypay_03AG.php',

    // Define the Request Payment List Data
    'request_payment_list'  				=> 'api_easypay_040BG1.php',

    // Define the Request Recurring Payment
    //'request_payment_list'  				=> 'api_easypay_07BG.php',

    // Define the Request a Verification of a Transaction Key
    'request_transaction_key_verification' 	=> 'api_easypay_23AG.php',

    // Define the Payment Modifier API
    'modify_payment' 						=> 'api_easypay_00BG.php',

    // Define Test Environment
    'test_server' 							=> 'http://test.easypay.pt/_s/',

    // Define Production Environment
    'production_server' 					=> 'https://www.easypay.pt/_s/',

    // Define Country
    'country' 								=> 'PT',

    // Define Language
    'language' 								=> 'PT',

    // Define reference type
    'ref_type' 								=> 'auto',

    // Define user
    'user' 									=> 'your user',

    // Define CIN
    'cin' 									=> 'your cin',

    // Define Entity
    'entity' 								=> 'your entity',

    // Define code
    'code'								 	=> false,

    // Define mode to use, true to use live transactions
    'live_mode'								=> false,

    // Default URL for easypay redirect to
    'ep_rec_url'                            => 'http://yoursite.com/easypay/direct_debit_return.php',

    // Database table name which contains the orders/subscriptions to save de authorization key from credit card
    'order_table_name'                      => 'subscriptions',

    // Order identifier
    'order_table_id'                        => 'id',

    // Order authorization key
    'order_table_key_field'                 => 'key'

];