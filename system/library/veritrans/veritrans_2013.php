<?php 

namespace Veritrans;

require_once 'lib/hash_generator.php';
require_once 'lib/Pest.php';
require_once 'lib/PestJSON.php';
require_once 'veritrans_utility.php';

class Veritrans2013 {

  private $veritrans;

  public function __construct($veritrans)
  {
    $this->veritrans = $veritrans;
  }

  public function charge($options)
  {
    $data = array(
      'token_id' => $this->veritrans->token_id,
      'order_id' => $this->veritrans->order_id,
      'email' => $this->veritrans->email
      );
    if ($this->veritrans->required_shipping_address)
    {
      if ($this->veritrans->billing_different_with_shipping)
      {
        $data['shipping_address'] = array(
          'first_name' => $this->veritrans->shipping_first_name,
          'last_name' => $this->veritrans->shipping_last_name,
          'address1' => $this->veritrans->shipping_address1,
          'address2' => $this->veritrans->shipping_address2,
          'city' => $this->veritrans->shipping_city,
          'postal_code' => $this->veritrans->shipping_postal_code,
          'phone' => $this->veritrans->shipping_phone
          );
      } else
      {
        $data['shipping_address'] = array(
          'first_name' => $this->veritrans->first_name,
          'last_name' => $this->veritrans->last_name,
          'address1' => $this->veritrans->address1,
          'address2' => $this->veritrans->address2,
          'city' => $this->veritrans->city,
          'postal_code' => $this->veritrans->postal_code,
          'phone' => $this->veritrans->phone
          );
      }
    }
    $data['billing_address'] = array(
      'first_name' => $this->veritrans->first_name,
      'last_name' => $this->veritrans->last_name,
      'address1' => $this->veritrans->address1,
      'address2' => $this->veritrans->address2,
      'city' => $this->veritrans->city,
      'postal_code' => $this->veritrans->postal_code,
      'phone' => $this->veritrans->phone
      );
    $items = array();
    foreach ($this->veritrans->items as $item) {
      $new_item = array(
        'id' => $item['item_id'],
        'price' => $item['price'],
        'qty' => $item['quantity'],
        'name' => $item['item_name1']
        );
      $items[] = $new_item;
    }
    $data['order_items'] = $items;
    $subtotal = 0;
    foreach ($data['order_items'] as $item) {
      $subtotal += $item['price'] * $item['qty'];
    }
    $data['gross_amount'] = $subtotal;
    return Utility::remoteCall('https://payments.veritrans.co.id/vtdirect/v1/charges', $this->veritrans->server_key, $data);
  }

  public function getTokens($options)
  {
    // Generate merchant hash code
    $hash = \HashGenerator::generate($this->veritrans->merchant_id, $this->veritrans->merchant_hash_key, $this->veritrans->order_id);

    // populate parameters for the post request
    $data = array(
      'version'                     => $this->veritrans->version,
      'merchant_id'                 => $this->veritrans->merchant_id,
      'merchanthash'                => $hash,
    
      'order_id'                        => $this->veritrans->order_id,
      'billing_different_with_shipping' => $this->veritrans->billing_different_with_shipping,
      'required_shipping_address'       => $this->veritrans->required_shipping_address,
    
      'shipping_first_name'         => $this->veritrans->shipping_first_name,
      'shipping_last_name'          => $this->veritrans->shipping_last_name,
      'shipping_address1'           => $this->veritrans->shipping_address1,
      'shipping_address2'           => $this->veritrans->shipping_address2,
      'shipping_city'               => $this->veritrans->shipping_city,
      'shipping_country_code'       => $this->veritrans->shipping_country_code,
      'shipping_postal_code'        => $this->veritrans->shipping_postal_code,
      'shipping_phone'              => $this->veritrans->shipping_phone,

      'email'                       => $this->veritrans->email, 
      
      'first_name'                  => $this->veritrans->first_name,
      'last_name'                   => $this->veritrans->last_name,
      'postal_code'                 => $this->veritrans->postal_code,
      'address1'                    => $this->veritrans->address1,
      'address2'                    => $this->veritrans->address2,
      'city'                        => $this->veritrans->city,
      'country_code'                => $this->veritrans->country_code,
      'phone'                       => $this->veritrans->phone,      
      
      'finish_payment_return_url'   => $this->veritrans->finish_payment_return_url,
      'unfinish_payment_return_url' => $this->veritrans->unfinish_payment_return_url,
      'error_payment_return_url'    => $this->veritrans->error_payment_return_url,

      // 'enable_3d_secure'            => $this->veritrans->enable_3d_secure, 
      // 'bank'                        => $this->veritrans->bank,
      // 'installment_banks'           => $this->veritrans->installment_banks, //array ["bni", "cimb"]
      // 'promo_bins'                  => $this->veritrans->promo_bins,
      // 'point_banks'                 => $this->veritrans->point_banks,
      // 'payment_methods'             => $this->veritrans->payment_methods, //array ["credit_card", "mandiri_clickpay"]
      // 'installment_terms'           => $this->veritrans->installment_terms
      );

    $optional_features =  array(
      'enable_3d_secure',
      'bank',
      'installment_terms',
      'promo_bins',
      'point_banks',
      'payment_methods',
      'installment_banks'
      );

    foreach ($optional_features as $feature) {
      if (!is_null($this->veritrans->{$feature}))
        $data[$feature] = $this->veritrans->{$feature};
    }

    // Populate items
    $data['repeat_line'] = 0;
    foreach ($this->veritrans->items as $item) {
      $item_id[]    = $item['item_id'];
      $item_name1[] = $item['item_name1'];
      $item_name2[] = $item['item_name2'];
      $price[]      = $item['price'];
      $quantity[]   = $item['quantity'];
      
      $data['repeat_line'] ++;
    }

    $data['item_id']    = $item_id;
    $data['item_name1'] = $item_name1;
    $data['item_name2'] = $item_name2;
    $data['price']      = $price;
    $data['quantity']   = $quantity;

    // Call Veritrans API
    try {
      $pest = new \PestJSON('');
      $result = $pest->post(\Veritrans::REQUEST_KEY_URL, $data);
    } catch (Exception $e) {
      throw $e;
    }

    // Check result
    if(!empty($result['token_merchant'])) {
      // OK
      return $result;
    }
    else {
      // Veritrans doesn't return tokens
      $this->veritrans->errors = $result['errors'];
      return false;
    }
  }
}