<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive as GoogleDrive;
use GuzzleHttp\Client as HttpClient;

class GoogleService
{
    public function getClient(): Client
    {
        $client = new Client();
        $client->setClientId(config('google.client_id'));
        $client->setClientSecret(config('google.client_secret'));

        $client->refreshToken(config('google.refresh_token'));
        $client->addScope(GoogleDrive::DRIVE);

        $client->setHttpClient(new HttpClient);

        return $client;
    }
}
