<?php

namespace App\Services;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;


class FirebaseService
{
    protected $messaging;
    public function __construct()
    {
       $serviceAccountPath=storage_path('counsil-konnect-a34e7-firebase-adminsdk-h9ax4-37494d9504.json');
       $factory = (new Factory)->withServiceAccount($serviceAccountPath);
       $this->messaging = $factory->createMessaging();
    }

    public function sendNotification($token, $title, $body, $data =[])
    {
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(['title' => $title, 'body' => $body])
            ->withData($data);
        $this->messaging->send($message);
    }
}

