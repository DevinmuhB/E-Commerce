<?php
require_once '../vendor/autoload.php';

$client = new Google_Client();
$client->setClientId('287918867373-e1tfb1vkp0g9fsvir6mump4jgrc88nnu.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost/skripsi/auth/google-callback.php');
$client->addScope("email");
$client->addScope("profile");

// Bypass SSL untuk XAMPP
$httpClient = new GuzzleHttp\Client(['verify' => false]);
$client->setHttpClient($httpClient);
