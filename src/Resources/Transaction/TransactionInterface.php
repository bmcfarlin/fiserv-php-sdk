<?php

namespace Fiserv\Resources\Transaction;

class TransactionInterface
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

  public function get($merchant_transaction_id)
  {
    $path = "/ddp/v1/transactions/$merchant_transaction_id";
    return $this->_client->get($path);
  }

}

