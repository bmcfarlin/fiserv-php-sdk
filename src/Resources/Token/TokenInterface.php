<?php

namespace Fiserv\Resources\Token;

class TokenInterface
{

  private $_client;

  function __construct($client)
  {
    $this->_client = $client;
  }

  function __toString()
  {
    return get_class($this);
  }

  public function create($data)
  {
    $path = "/ucom/v1/tokens";
    return $this->_client->post($path, $data);
  }

  public function nonce($token_id, $data)
  {
    $path = "/ucom/v1/account-tokens";
    $authorization = sprintf("Bearer %s", $token_id);
    $custom_header = ["Authorization" => $authorization];
    return $this->_client->post($path, $data, null, $custom_header);
  }

  public function vault($token_id, $merchant_customer_id, $data)
  {
    $path = "/ddp/v1/recipients/$merchant_customer_id/accounts";
    $custom_header = ["access_token" => $token_id];
    return $this->_client->post($path, $data, null, $custom_header);
  }
}
