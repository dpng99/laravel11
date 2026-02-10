<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;

class GoogleDriveController extends Controller
{
    public function upload()
    {
        $client = new Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->refreshToken(config('services.google.refresh_token'));
        $client->setHttpClient(new \GuzzleHttp\Client([
            'verify' => false
        ]));

        $drive = new Google_Service_Drive($client);

        $fileMetadata = new Google_Service_Drive_DriveFile([
            'name' => 'contoh.txt'
        ]);

        $content = "Halo dari Laravel!";

        $file = $drive->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => 'text/plain',
            'uploadType' => 'multipart'
        ]);

        return "Upload sukses. File ID: " . $file->id;
    }
}
