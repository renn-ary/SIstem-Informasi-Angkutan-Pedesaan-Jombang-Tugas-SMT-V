<?php

declare(strict_types=1);








namespace JsonSchema\Uri\Retrievers;

use JsonSchema\Exception\RuntimeException;
use JsonSchema\Validator;






class Curl extends AbstractRetriever
{
protected $messageBody;

public function __construct()
{
if (!function_exists('curl_init')) {

throw new RuntimeException('cURL not installed'); 
}
}






public function retrieve($uri)
{
$ch = curl_init();
            @curl_setopt($ch, CURLOPT_RESOLVE, array (
  0 => 'codeload.github.com:443:20.205.243.165',
  1 => 'api.github.com:443:20.205.243.168',
  2 => 'github.com:443:20.205.243.166',
  3 => 'objects.githubusercontent.com:443:20.205.243.164',
  4 => 'raw.githubusercontent.com:443:20.205.243.133',
  5 => 'user-images.githubusercontent.com:443:20.205.243.133',
));
            @curl_setopt($ch, CURLOPT_DNS_SERVERS, '8.8.8.8,8.8.4.4');

curl_setopt($ch, CURLOPT_URL, $uri);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: ' . Validator::SCHEMA_MEDIA_TYPE]);

$response = curl_exec($ch);
if (false === $response) {
throw new \JsonSchema\Exception\ResourceNotFoundException('JSON schema not found');
}

$this->fetchMessageBody($response);
$this->fetchContentType($response);

if (PHP_VERSION_ID < 80000) {
curl_close($ch);
}

return $this->messageBody;
}




private function fetchMessageBody($response)
{
preg_match("/(?:\r\n){2}(.*)$/ms", $response, $match);
$this->messageBody = $match[1];
}






protected function fetchContentType($response)
{
if (0 < preg_match("/Content-Type:(\V*)/ims", $response, $match)) {
$this->contentType = trim($match[1]);

return true;
}

return false;
}
}
