<?php

namespace Fiserv;

class RestClient
{

  private $_api_key;
  private $_api_secret;
  private $_base_url;
  private $debug = false;
  private $curl = false;

  function __construct($api_key = null, $api_secret = null, $base_url = null)
  {
    if(empty($api_key))
    {
      $api_key = getenv('FISERV_API_KEY');
    }

    if(empty($api_secret))
    {
      $api_secret = getenv('FISERV_SECRET');
    }

    if(empty($base_url))
    {
      $base_url = getenv('FISERV_API_URL');
    }

    $this->_api_key = $api_key;
    $this->_api_secret = $api_secret;
    $this->_base_url = $base_url;
  }

  function __toString()
  {
    return get_class($this);
  }

  function get($path, $payload = [], $custom_header = [])
  {
    $url = sprintf("%s%s", $this->_base_url, $path);

    if($payload)
    {
      $url = sprintf("%s?%s", $url, http_build_query($payload));
    }

    if($this->debug)
    {
      print("URL\n$url\n");
    }

    $time = microtime();
    list($usec, $sec) = explode(' ', $time);
    $msec = $usec * 1000;
    $msec = round(floatval($msec));
    $msec = sprintf('%03d', $msec);

    $epoch_timestamp = sprintf("%s%s", $sec, $msec);
    $epoch_timestamp = intval($epoch_timestamp);

    $method = 'GET';
    if($this->debug)
    {
      print("METHOD\n$method\n");
    }

    $client_request_id = \Ramsey\Uuid\Uuid::uuid4();

    $hmac_token = HmacUtil::generateHmac($this->_api_key, $this->_api_secret, $epoch_timestamp);

    $authorization = sprintf("HMAC %s", $hmac_token);

    $kvps = [
      "Content-Type" => "application/json",
      "Client-Request-Id" => $client_request_id,
      "Api-Key" => $this->_api_key,
      "Authorization" => $authorization,
      "Timestamp" => $epoch_timestamp
    ];

    foreach($custom_header as $key => $value){
      $kvps[$key] = $value;
    }

    $header = [];
    foreach($kvps as $key => $value){
      $header[] = sprintf("%s:%s", $key, $value);
    }

    $json = json_encode($header, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if($this->debug)
    {
      print("HEADER\n$json\n");
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if($this->debug)
    {
      curl_setopt($ch, CURLOPT_HEADER, true);
    }
    
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);

    $response = curl_exec($ch);

    $info = curl_getinfo($ch);

    $request_header = $info['request_header'];
    $request_header = trim($request_header);
    
    if($this->debug)
    {
      print("REQUEST\n$request_header\n");
    }

    if(curl_errno($ch)){
      $response = curl_error($ch);
    }

    $item = json_decode($response);
    $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    if($this->debug)
    {
      print("RESPONSE\n$json\n");
    }

    if($this->debug || $this->curl){
      $cmd = null;
      $cmd .= "curl -v \\\n";
      $cmd .= sprintf("-X '%s' \\\n", $method);
      foreach($header as $hitem){
        $cmd .= sprintf("-H '%s' \\\n", $hitem);
      }
      $cmd .= sprintf("%s\n", $url);

      print("CURL\n$cmd\n");
    }

    curl_close($ch);

    return $response;
  }
   
  function post($path, $payload = [], $custom_request = null, $custom_header = [])
  {
    $url = sprintf("%s%s", $this->_base_url, $path);

    if($this->debug)
    {
      print("URL\n$url\n");
    }

    $time = microtime();
    list($usec, $sec) = explode(' ', $time);
    $msec = $usec * 1000;
    $msec = round(floatval($msec));
    $msec = sprintf('%03d', $msec);

    $epoch_timestamp = sprintf("%s%s", $sec, $msec);
    $epoch_timestamp = intval($epoch_timestamp);

    $method = 'POST';

    if($custom_request)
    {
      if($this->debug){
        print("CUSTOM_REQUEST\n$custom_request\n");
      }
      $method = $custom_request;
    }
    
    if($this->debug)
    {
      print("METHOD\n$method\n");
    }

    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    if($this->debug)
    {
      print("PAYLOAD\n$json\n");
    }

    $payload = json_encode($payload, JSON_UNESCAPED_SLASHES);

    $uuid = \Ramsey\Uuid\Uuid::uuid4();
    $client_request_id = sprintf("%s", $uuid);

    $hmac_token = HmacUtil::generateHmac($this->_api_key, $this->_api_secret, $epoch_timestamp, $payload);

    $authorization = sprintf("HMAC %s", $hmac_token);

    $kvps = [
      "Content-Type" => "application/json",
      "Client-Request-Id" => $client_request_id,
      "Api-Key" => $this->_api_key,
      "Authorization" => $authorization,
      "Timestamp" => $epoch_timestamp
    ];

    foreach($custom_header as $key => $value){
      $kvps[$key] = $value;
    }

    $header = [];
    foreach($kvps as $key => $value){
      $header[] = sprintf("%s:%s", $key, $value);
    }

    $json = json_encode($header, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    if($this->debug)
    {
      print("HEADER\n$json\n");
    }

    $fields = $payload;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if($this->debug){
      curl_setopt($ch, CURLOPT_HEADER, true);
    }
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);

    if($custom_request)
    {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $custom_request);
    }

    $response = curl_exec($ch);

    $info = curl_getinfo($ch);

    $request_header = $info['request_header'];
    $request_header = trim($request_header);
    
    if($this->debug)
    {
      print("REQUEST\n$request_header\n");
    }

    if(curl_errno($ch)){
      $response = curl_error($ch);
    }

    $item = json_decode($response);
    $json = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    if($this->debug)
    {
      print("RESPONSE\n$json\n");
    }

    if($this->debug || $this->curl){
      $cmd = null;
      $cmd .= "curl -v \\\n";
      $cmd .= sprintf("-X '%s' \\\n", $method);
      foreach($header as $hitem){
        $cmd .= sprintf("-H '%s' \\\n", $hitem);
      }
      $cmd .= sprintf("-d '%s' \\\n", $payload);
      $cmd .= sprintf("%s\n", $url);

      print("CURL\n$cmd\n");
    }

    curl_close($ch);

    return $response;
  }

  function patch($path, $payload = [])
  {
    return $this->post($path, $payload, 'PATCH');
  }

  function put($path, $payload = [])
  {
    return $this->post($path, $payload, 'PUT');
  }

  function delete($path, $payload = [])
  {
    return $this->post($path, $payload, 'DELETE');
  }
}



