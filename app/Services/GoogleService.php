<?php
namespace App\Services;
use Google\Client;
use Google\Service\Drive as GoogleDrive;

class GoogleService
{
    public function getClient()
    {
        $client = new Client();
        $client->setClientId(config('google.client_id'));
        $client->setClientSecret(config('google.client_secret'));
        
        //untuk otomatis anti exp
        $client->refreshToken(config('google.refresh_token'));
        //ijin baca tulis gdrive
        $client->addScope(GoogleDrive::DRIVE);

        //biar ga error bae
        $guzzleClient = new \GuzzleHttp\Client(['verify' => false]);
        $client->setHttpClient($guzzleClient);
        return $client;
    }
}