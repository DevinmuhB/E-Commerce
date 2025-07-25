<?php
require_once '../vendor/autoload.php';

$client = new Google_Client();
$client->setClientId('YOUR CLIENT ID');
$client->setClientSecret('YOUR CLIENT SECRET');
$client->setRedirectUri('YOUR URL CALLBACK');
$client->addScope("email");
$client->addScope("profile");

// Bypass SSL untuk XAMPP
$httpClient = new GuzzleHttp\Client(['verify' => false]);
$client->setHttpClient($httpClient);
