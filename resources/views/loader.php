<?php
require 'vendor/autoload.php';

$client = new Google_Client();
$client->setClientId('844260534767-5s3qbvdvniq07hnlrutpn5q94si6d9sv.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost:8000/oauth2callback');
$client->addScope(Google_Service_Drive::DRIVE_FILE);
$client->setAccessType('offline');
$client->setPrompt('consent');

if (!isset($_GET['code'])) {
    $authUrl = $client->createAuthUrl();
    echo "<a href='$authUrl'>Login Google</a>";
    exit;
} else {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    echo "<pre>";
    print_r($token);
}
