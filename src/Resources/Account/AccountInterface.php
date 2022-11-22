<?php

namespace Fiserv\Resources\Account;

class AccountInterface
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

  public function list($recipient_id)
  {
    $path = "/ddp/v1/recipients/$recipient_id/accounts";
    return $this->_client->get($path);
  }

}
