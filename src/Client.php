<?php

namespace Fiserv;

use Fiserv\Resources\Recipient\RecipientInterface;
use Fiserv\Resources\Token\TokenInterface;
use Fiserv\Resources\Account\AccountInterface;
use Fiserv\Resources\Payment\PaymentInterface;
use Fiserv\Resources\Transaction\TransactionInterface;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Client
{

  private $_client;
  private $_recipient;
  private $_token;
  private $_account;
  private $_payment;
  private $_transaction;

  function __construct($api_key, $api_secret, $base_url, $logger = null)
  {
    $this->_client = new RestClient($api_key, $api_secret, $base_url, $logger);
  }

  function __toString()
  {
    return get_class($this);
  }

  public function __get($name)
  {
    $name = ucfirst($name);
    $method = sprintf("get%s", $name);
    if(method_exists($this, $method))
    {
      return $this->$method();
    }
    throw new \Exception('Unknown resource ' . $name);
  }

  function getRecipient()
  {
    if(empty($this->_recipient))
    {
      $this->_recipient = new RecipientInterface($this->_client);
    }
    return $this->_recipient;
  }

  function getToken()
  {
    if(empty($this->_token))
    {
      $this->_token = new TokenInterface($this->_client);
    }
    return $this->_token;
  }

  function getAccount()
  {
    if(empty($this->_account))
    {
      $this->_account = new AccountInterface($this->_client);
    }
    return $this->_account;
  }

  function getPayment()
  {
    if(empty($this->_payment))
    {
      $this->_payment = new PaymentInterface($this->_client);
    }
    return $this->_payment;
  }

  function getTransaction()
  {
    if(empty($this->_transaction))
    {
      $this->_transaction = new TransactionInterface($this->_client);
    }
    return $this->_transaction;
  }
}
