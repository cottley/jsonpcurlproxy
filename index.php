<?php
/*
 * Author - Christopher Ottley
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 */

function startsWith($haystack, $needle)
{
    return $needle === "" || strpos($haystack, $needle) === 0;
}

function getHost($Address) { 
   $parseUrl = parse_url(trim($Address)); 
   return trim($parseUrl['host'] ? $parseUrl['host'] : array_shift(explode('/', $parseUrl['path'], 2))); 
} 

session_start();
ob_start();

/* config settings */
$base = $_GET["url"]; 
$ckfile = '/tmp/jsonpcurlproxy-cookie-'.session_id(); 
$proxy = null;
if (startsWith($base, "http://")) {
  // Only proxy http connections
  $proxy = '127.0.0.1:8089';
}

$cookiedomain = getHost($base);
$url = $base; 


// Open the cURL session
$curlSession = curl_init();

curl_setopt ($curlSession, CURLOPT_URL, $url);
curl_setopt ($curlSession, CURLOPT_HEADER, 0);
curl_setopt($curlSession, CURLINFO_HEADER_OUT, 1); // enable tracking
if (startsWith($base, "http://")) {
  curl_setopt ($curlSession, CURLOPT_PROXY, $proxy);
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  curl_setopt ($curlSession, CURLOPT_POST, 1);

  foreach($_POST as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
  rtrim($fields_string, '&');

  curl_setopt ($curlSession, CURLOPT_POSTFIELDS, $fields_string);
}

curl_setopt($curlSession, CURLOPT_RETURNTRANSFER,1);
curl_setopt($curlSession, CURLOPT_TIMEOUT,120);
curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($curlSession, CURLOPT_COOKIEJAR, $ckfile); 
curl_setopt($curlSession, CURLOPT_COOKIEFILE, $ckfile);
curl_setopt($curlSession, CURLOPT_HTTPHEADER, array( 'Expect:' ) );
curl_setopt($curlSession, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

$response = curl_exec ($curlSession);

if (curl_error($curlSession)){
  print curl_error($curlSession);
} else {
  $requestinfo = curl_getinfo($curlSession, CURLINFO_HEADER_OUT ); // request headers

  $jsonpresponse = '';
  if ($_GET['callback']) {
    $jsonpresponse .= $_GET['callback']."(";
  }
  $jsonpresponse .= $response;
  if ($_GET['callback']) {
    $jsonpresponse .= ")";
  }
  header("Content-Type: application/json");
  header("Content-Length: ".strlen($jsonpresponse));
  header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
  header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
  header("Access-Control-Allow-Origin: *");
  print($jsonpresponse);

/*
$file = 'debug.txt';
file_put_contents($file, $jsonpresponse);
*/
}

curl_close ($curlSession);
ob_end_flush();
?>