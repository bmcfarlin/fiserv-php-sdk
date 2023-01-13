<?php
  
  // version 1.0.9

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

  use Monolog\Logger;
  use Monolog\Handler\StreamHandler;

  $update = false;

  $file_path = '/var/log/logger/fiserv.log';
  if(!is_writable($file_path)){
    print("file $file_path is not writable\n");
    die;
  }

  $logger = new Logger('fiserv');
  $logger->pushHandler(new StreamHandler($file_path, Logger::DEBUG));

  $client = new \Fiserv\Client(FISERV_API_KEY, FISERV_SECRET, FISERV_BASE_URL, $logger);

  $nonce_token = null;
  $vault_token = null;
  $account_token = null;
  $recipient_id = null;
  $token_id = null;
  $public_key = null;

  /************************
  *  Create Recipient
  ************************/
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

  $merchant_customer_id = \Ramsey\Uuid\Uuid::uuid4();
  $recipeint_identifier_value = random_int(1000, 9999);
  $phone = sprintf("406330%s", $recipeint_identifier_value);

  if($ben){
    foreach($ben as $key => $value){
      $$key = $value;
    }
  }

  if(empty($ben)){

    /************************
    *  Create Recipient
    ************************/
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
    print("RECEIPIENT_CREATE\n");
    print("=================\n");
    print("REQUEST\n$json\n");

    $json = $client->recipient->create($data);
    if($json){
      $item = json_decode($json);
      if($item){
        if(isset($item->recipientId)){
          $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
          print("RESPONSE\n$json\n");
        }else{
          $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
          print("item->recipientId not set\n$json\n");
          die;
        }
      }else{
        print("INVALID JSON\n$json\n");
        die;
      }
    }else{
      print("recipient->create json is null\n");
    }

    if(empty($recipient_id))
    {
      print("recipient_id is null\n");
      die;
    }
  }

  if($update){
    /************************
    *  Update Recipient
    ************************/
    $first_name = 'Ben';
    $recipient = [
      'firstName' => $first_name,
    ];

    $data = [];
    $data['recipient'] = $recipient;

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    print("RECEIPIENT_UPDATE\n");
    print("=================\n");
    print("REQUEST\n$json\n");

    $json = $client->recipient->update($merchant_customer_id, $data);
    if($json){
      $item = json_decode($json);
      if($item){
        $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        print("RESPONSE:\n$json\n");
      }else{
        print("INVALID JSON\n$json\n");
        die;
      }
    }else{
      print("recipient->update json is null\n");
    }
  }

  /************************
  *  Get Recipient
  ************************/
  $data = [
    'merchant_customer_id' => $merchant_customer_id
  ];
  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  print("RECEIPIENT_GET\n");
  print("==============\n");
  print("REQUEST\n$json\n");

  $json = $client->recipient->get($merchant_customer_id);
  if($json){
    $item = json_decode($json);
    if($item){
      $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
      print("RESPONSE:\n$json\n");
      if(isset($item->recipient)){
        if(isset($item->recipient->recipientId)){
          $recipient_id = $item->recipient->recipientId;
        }else{
          $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
          print("item->recipient->recipientId not set\n$json\n");
          die;
        }
      }else{
        $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        print("item->recipient not set\n$json\n");
        die;
      }
    }else{
      print("INVALID JSON\n$json\n");
      die;
    }
  }else{
    print("recipient->get json is null\n");
  }

  if(empty($recipient_id))
  {
    print("recipient_id is null");
    die;
  }

  if(empty($ben)){

    /************************
    *  Token
    ************************/

    $token = [
      'fdCustomerId' => $merchant_customer_id,
    ];

    $data = [];
    $data['token'] = $token;
    $data['publicKeyRequired'] = true;

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    print("TOKEN_CREATE\n");
    print("============\n");
    print("REQUEST\n$json\n");

    $json = $client->token->create($data);
    if($json){
      $item = json_decode($json);
      if($item){
        if(isset($item->tokenId)){
          $token_id = $item->tokenId;
          if(isset($item->publicKey)){
            $public_key = $item->publicKey;
            $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            print("RESPONSE\n$json\n");
          }else{
            $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            print("item->tokenId not set\n$json\n");
            die;
          }
        }else{
          $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
          print("item->publicKey not set\n$json\n");
          die;
        }
      }else{
        print("INVALID JSON\n$json\n");
        die;
      }
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
    print("TOKEN_NONCE\n");
    print("===========\n");
    print("REQUEST\n$json\n");

    $json = $client->token->nonce($token_id, $data);
    if($json){
      $item = json_decode($json);
      if($item){
        if(isset($item->token)){
          $nonce_token = $item->token;
          $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
          print("RESPONSE\n$json\n");
        }else{
          $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
          print("item->token not set\n$json\n");
          die;
        }
      }else{
        print("INVALID JSON\n$json\n");
        die;
      }
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
    print("TOKEN_VAULT\n");
    print("===========\n");
    print("REQUEST\n$json\n");

    $json = $client->token->vault($token_id, $merchant_customer_id, $data);
    if($json){
      $item = json_decode($json); 
      if($item){
        $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        print("RESPONSE:\n$json\n");
        if(isset($item->accounts)){
          $accounts = $item->accounts;
          foreach($accounts as $account){
            $vault_token = $account->token;
            break;
          }
        }else{
          if(isset($item->message)){
            $message = $item->message;
            if($message == "Duplicate Account"){
              $vault_token = $item;
            }else{
              $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
              print("item->message\n$json\n");
              die;
            }
          }else{
            $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            print("item->message not set\n$json\n");
            die;
          }
        }
      }else{
        print("INVALID JSON\n$json\n");
        die;
      }
    }else{
      print("token->vault json is null\n");
    }

    if(empty($vault_token))
    {
      print("vault token is null\n");
      die;
    }
  }

  /************************
  *  Get Account
  ************************/
  $data = [
    'recipient_id' => $recipient_id
  ];
  $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  print("ACCOUNT_LIST\n");
  print("============\n");
  print("REQUEST\n$json\n");

  $json = $client->account->list($recipient_id);
  if($json){
    $item = json_decode($json);
    if($item){
      $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
      print("RESPONSE:\n$json\n");
      if(isset($item->accounts)){
        $accounts = $item->accounts;
        foreach($accounts as $account){
          $source = $account->source;
          $account_token = $account->card->token;
          break;
        }
      }else{
        $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        print("item->accounts not set\n$json\n");
        die;
      }
    }else{
      print("INVALID JSON\n$json\n");
      die;
    }
  }else{
    $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    print("account->list json not set\n");
  }

  if(empty($account_token))
  {
    print("account token is null\n");
    die;
  }

  if($nonce_token)
  {
    $json = json_encode($nonce_token, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    print("NONCE_TOKEN:\n$json\n");
  }

  if($vault_token)
  {
    $json = json_encode($vault_token, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    print("VAULT_TOKEN:\n$json\n");
  }

  if($account_token)
  {
    $json = json_encode($account_token, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    print("ACCOUNT_TOKEN:\n$json\n");
  }


  /************************
  *  Create Payment
  ************************/
  $token_id = $account_token->tokenId;
  $token_provider = $account_token->tokenProvider;

  $amount = [
    'total' => $total,
    'currency' => 'USD'
  ];

  $merchant_transaction_id = \Ramsey\Uuid\Uuid::uuid4();

  $recipient = [
    [
      'recipientProfileInfo' => [
        'merchantCustomerId' => $merchant_customer_id
      ],
      'payments' => [
        'paymentType' => FISERV_PAYMENT_TYPE
      ],
      'description' => 'Compensate Payment',
      'source' => $source,
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
  print("PAYMENT_CREATE\n");
  print("==============\n");
  print("REQUEST\n$json\n");

  $json = $client->payment->create($data);
  if($json){
    $item = json_decode($json);
    if($item){
      $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
      print("RESPONSE:\n$json\n");
    }else{
      print("INVALID JSON\n$json\n");
      die;
    }
  }else{
    print("payment->create json is null\n");
  }

  function show_globals(){
    $items = $GLOBALS;
    foreach($items as $key => $value){
      if(preg_match('/^_ENV|_REQUEST|_GET|_POST|_COOKIE|_FILES|argv|argc|_SERVER|GLOBALS|client|__composer_autoload_files$/', $key)){
        // do nothing
      }else{
        if(!is_scalar($value)){
          $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        print("$key:$value\n");
      }
    }
  }
