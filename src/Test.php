<?php

  include_once(__DIR__ . '/../vendor/autoload.php');

  include_once(__DIR__ . '/Resources/Recipient/RecipientInterface.php');
  include_once(__DIR__ . '/Resources/Token/TokenInterface.php');
  include_once(__DIR__ . '/Resources/Account/AccountInterface.php');
  include_once(__DIR__ . '/Resources/Payment/PaymentInterface.php');

  include_once(__DIR__ . '/RestClient.php');
  include_once(__DIR__ . '/Client.php');
  include_once(__DIR__ . '/Settings.php');

  exec("clear; reset;");

  use \phpseclib3\Crypt\PublicKeyLoader;
  use \phpseclib3\Crypt\Common\PublicKey;
  use \phpseclib3\Crypt\Common\PrivateKey;
  use \phpseclib3\Crypt\RSA;

  $client = new \Fiserv\Client(FISERV_API_KEY, FISERV_SECRET, FISERV_BASE_URL);

  /************************
  *  Recipient
  ************************/

  // CREATE
  $dtm = new \DateTime('now');
  $timestamp = $dtm->getTimestamp();
  $timestamp = $timestamp;

  $first_name = 'user';
  $last_name = sprintf("%s", $timestamp);

  $email = sprintf("%s.%s@qeala.com", $first_name, $last_name);

  $recipient_type = 'Consumer';
  $address = 'Nhn Belton Stage Rd';
  $city = 'West Glacier';
  $state = 'MT';
  $zip = '59936';
  $country = 'USA';

  $uuid = \Ramsey\Uuid\Uuid::uuid4();
  $merchant_customer_id = $uuid;

  $recipeint_identifier_value = random_int(1000, 9999);

  $phone = sprintf("406330%s", $recipeint_identifier_value);

  $merchant = [
    'merchantCustomerId' => $merchant_customer_id
  ];

  $recipient = [
    'recipientType' => $recipient_type,
    'firstName' => $first_name,
    'lastName' => $last_name,
    'emailAddress' => [
      'type' => 'home',
      'value' => $email
    ],
    'recipientIdentifier' => 'SPECIAL_CODE',
    'recipientIdentifierValue' => $recipeint_identifier_value,
    'guest' => true,
    'address' => [
      'type' => 'home',
      'street' => $address,
      'city' => $city,
      'stateOrProvince' => $state,
      'postalCode' => $zip,
      'country' => $country
    ],
    'phoneNumber' => [
      'type' => 'home',
      'value' => $phone
    ]
  ];

  $data = [];
  $data['merchant'] = $merchant;
  $data['recipient'] = $recipient;

  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  print("RECEIPIENT_CREATE\n$json\n");

  $recipient_id = null;
  $json = $client->recipient->create($data);
  if($json){
    $item = json_decode($json);
    $recipient_id = $item->recipientId;
    $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    print("RESPONSE\n$json\n");
  }else{
    print("recipient->create json is null\n");
  }

  if(empty($recipient_id))
  {
    print("recipient_id is null\n");
    die;
  }

  // UPDATE
  $first_name = 'Donald';
  $recipient = [
    'firstName' => $first_name,
  ];

  $data = [];
  $data['recipient'] = $recipient;

  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  print("RECEIPIENT_UPDATE\n$json\n");

  $json = $client->recipient->update($merchant_customer_id, $data);
  if($json){
    $item = json_decode($json);
    $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    print("RESPONSE:\n$json\n");
  }else{
    print("recipient->update json is null\n");
  }

  $data = [
    'merchant_customer_id' => $merchant_customer_id
  ];
  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  print("RECEIPIENT_GET\n$json\n");

  // GET
  $json = $client->recipient->get($merchant_customer_id);
  if($json){
    $item = json_decode($json);
    $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    print("RESPONSE:\n$json\n");
  }else{
    print("recipient->get json is null\n");
  }

  /************************
  *  Token
  ************************/

  $token_id = null;
  $public_key = null;

  $token = [
    'fdCustomerId' => $merchant_customer_id,
  ];

  $data = [];
  $data['token'] = $token;
  $data['publicKeyRequired'] = true;

  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  print("TOKEN_CREATE\n$json\n");

  $json = $client->token->create($data);
  if($json){
    $item = json_decode($json);
    
    $token_id = $item->tokenId;
    $public_key = $item->publicKey;

    $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    print("RESPONSE\n$json\n");
  }else{
    print("token->create json is null\n");
  }

  if(empty($token_id))
  {
    print("token_id is null");
    die;
  }
  if(empty($public_key))
  {
    print("public_key is null");
    die;
  }

  /************************
  *  Nonce
  ************************/
  $key = RSA::load($public_key);
  $key = $key->withPadding(RSA::ENCRYPTION_PKCS1 | RSA::SIGNATURE_PKCS1);

  $card_number = '5102610000000077';
  $month = '12';
  $year = '25';

  $card_number = base64_encode($key->encrypt($card_number));
  $month = base64_encode($key->encrypt($month));
  $year = base64_encode($key->encrypt($year));

  $card_number = sprintf("ENC_[%s]", $card_number);
  $month = sprintf("ENC_[%s]", $month);
  $year = sprintf("ENC_[%s]", $year);

  $account = [
    'type' => 'CREDIT',
    'credit' => [
      'cardNumber' => $card_number,
      'expiryDate' => [
        'month' => $month,
        'year' => $year
      ]
    ]
  ];

  $reference_token = [
    'tokenType' => 'CLAIM_CHECK_NONCE'
  ];

  $data = [];
  $data['account'] = $account;
  $data['referenceToken'] = $reference_token;
  $data['fdCustomerId'] = $merchant_customer_id;

  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  print("TOKEN_NONCE\n$json\n");

  $nonce_token = null;
  $json = $client->token->nonce($token_id, $data);
  if($json){
    $item = json_decode($json);
    $nonce_token = $item->token;
    $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    print("RESPONSE\n$json\n");
  }else{
    print("token->nonce json is null\n");
  }

  if(empty($nonce_token))
  {
    print("nonce token is null\n");
    die;
  }

  /************************
  *  Vault
  ************************/
  $accounts = [
    'token' => [
      'tokenId' => $nonce_token->tokenId,
      'tokenProvider' => "SINGLE_USE_TOKEN"
    ]
  ];

  $data = [];
  $data['accounts'] = $accounts;

  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  print("TOKEN_VAULT\n$json\n");

  $vault_token = null;
  $json = $client->token->vault($token_id, $merchant_customer_id, $data);
  if($json){
    $item = json_decode($json);
    $accounts = $item->accounts;
    foreach($accounts as $account){
      $vault_token = $account->token;
      break;
    }
    $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    print("RESPONSE\n$json\n");
  }else{
    print("token->vault json is null\n");
  }

  if(empty($vault_token))
  {
    print("vault token is null\n");
    die;
  }

  /************************
  *  Account
  ************************/

  $data = [
    'recipient_id' => $recipient_id
  ];
  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  print("ACCOUNT_LIST\n$json\n");

  $account_token = null;
  $json = $client->account->list($recipient_id);
  if($json){
    $item = json_decode($json);
    $accounts = $item->accounts;
    foreach($accounts as $account){
      $card = $account->card;
      $account_token = $card->token;
      break;
    }
    $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    print("RESPONSE:\n$json\n");
  }else{
    print("account->list json is null\n");
  }

  if(empty($account_token))
  {
    print("account token is null\n");
    die;
  }

  /************************
  *  Payment
  ************************/

  $json = json_encode($nonce_token, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  print("NONCE_TOKEN:\n$json\n");

  $json = json_encode($vault_token, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  print("VAULT_TOKEN:\n$json\n");

  $json = json_encode($account_token, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  print("ACCOUNT_TOKEN:\n$json\n");


  $token_id = $vault_token->tokenId;
  $token_provider = $vault_token->tokenProvider;

  $amount = [
    'total' => 21.00,
    'currency' => 'USD'
  ];

  $uuid = \Ramsey\Uuid\Uuid::uuid4();
  $merchant_transaction_id = $uuid;

  $recipient = [
    [
      'recipientProfileInfo' => [
        'merchantCustomerId' => $merchant_customer_id
      ],
      'payments' => [
        'paymentType' => FISERV_PAYMENT_TYPE
      ],
      'description' => 'Compensate Payment',
      'source' => 'DEBIT',
      'card' => [
        'token' => [
          'tokenId' => $token_id,
          'tokenProvider' => $token_provider
        ]
      ]
    ]
  ];

  $data = [];
  $data['amount'] = $amount;
  $data['merchantTransactionId'] = $merchant_transaction_id;
  $data['recipient'] = $recipient;

  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  print("PAYMENT_CREATE\n$json\n");

  $json = $client->payment->create($data);
  if($json){
    $item = json_decode($json);
    $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    print("RESPONSE:\n$json\n");
  }else{
    print("payment->create json is null\n");
  }


