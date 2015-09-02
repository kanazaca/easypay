# Easypay for Laravel 5
Laravel Package to work with easypay API

## Feature Overview
- Support MB and credit-card (VISA, MASTERCARD, AMERICAN-EXPRESS)
- Create reference
- Get payment notification in real time
- Get all payments
- and will be more ...

## Installation
Add this to your composer.json file, in the require object:

```javascript
"kanazaca/easypay": "1.0.*"
```
After that, run composer install to install the package.
Add the service provider to `config/app.php`, within the `providers` array.

```php
'providers' => array(
	// ...
	kanazaca\easypay\EasypayServiceProvider::class,
)
```
Publish the config file.
```
php artisan vendor:publish
```
After this you might want change config file located at `config/easypay.php` with your credentials, etc

Lastly, run migrate to create `easypay_notifications` table
```
php artisan migrate
```

## Usage

### Create a reference
This code will ask easypay for a new reference which can be payed using MB or credit-card
```php
$payment_info = [
            'o_name' => "Your name",
            'o_email' => 'Your email',
            't_value' => '29.00',
            'o_description' => 'Here is your description',
            'o_obs' => 'Here is your observations',
            'o_mobile' => 'Here is your mobile',
            't_key' => 'Here is the ID of your order'
    ];
    
$easypay = new EasyPay($payment_info);

$reference = $easypay->createReference();
```
This will return an array with the following : 
```

Array
(
    [ep_status] => ok0
    [ep_message] => ep_country and ep_entity and ep_user and ep_cin ok and validation by code;code ok - new reference generated - NEW REFERENCE - 
    [ep_cin] => your CIN
    [ep_user] => your USER
    [ep_entity] => your ENTITY
    [ep_reference] => generated REFERENCE
    [ep_value] => Asked value
    [t_key] => Order ID sent
    [ep_link] => Link to pay using credit-card
)
```
Now you can do what you want with this information, maybe you might want save to database to build user history payments.

### Method to receive notifications in real time
When easypay get his payment they will call the URL that you provided to them which will execute a similiar method like below, for more details see (https://docs.easypay.pt/workflow/payment-notification)
```php
$easypay = new EasyPay($payment_info);

$notification = $easypay->processPaymentInfo();
```
This block of code will store into database the document number of the payment received from easypay and update with more info sent from them.

### Get all payments
This will return an array with all of your payments from easypay
```php
$easypay = new EasyPay();

$all_payments = $easypay->fetchAllPayments();
```

and thats it ....

### Credits
Hugo Neto
