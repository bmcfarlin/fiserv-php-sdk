<?php

namespace Fiserv\Resources\Payment;

class PaymentInterface
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
    $path = "/ddp/v1/payments";
    return $this->_client->post($path, $data);
  }

}
