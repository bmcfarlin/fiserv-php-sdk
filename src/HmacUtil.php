<?php

namespace Fiserv;

class HmacUtil{
  static function generateHmac($api_Key, $api_secret, $epoch_timestamp, $payload = null){
    $message = $api_Key . ":" . $epoch_timestamp;
    if($payload){
      $payload = base64_encode(hash('sha256', $payload, true));
      $message = $message . ":" . $payload;
    }
    $result = base64_encode(hash_hmac('sha256', $message, $api_secret, true));
    return $result;
  }
}
