<?php

namespace Fiserv\Resources\Recipient;

class RecipientInterface
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

  public function update($key, $data)
  {
    $path = "/ddp/v1/recipients/$key";
    return $this->_client->post($path, $data, 'PATCH');
  }

  public function get($key)
  {
    $path = "/ddp/v1/recipients/$key";
    return $this->_client->get($path);
  }

  public function create($data)
  {
    $path = "/ddp/v1/recipients";
    return $this->_client->post($path, $data);
  }

  public function delete($key)
  {
    $path = "/ddp/v1/recipients/$key";
    return $this->_client->delete($path);
  }

}
