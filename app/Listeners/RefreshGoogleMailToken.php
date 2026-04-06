<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RefreshGoogleMailToken
{
    /**
     * Handle the event.
     */
    public function handle(MessageSending $event): void
    {
        if (config('mail.default') !== 'smtp') {
            return;
        }

        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $refreshToken = env('GOOGLE_MAIL_REFRESH_TOKEN');

        if (!$clientId || !$clientSecret || !$refreshToken) {
            return; // Fallback to standard SMTP logic if tokens aren't present
        }

        try {
            // Use cache to prevent hammering Google's API on mass mailings (access tokens are good for an hour)
            $accessToken = Cache::remember('google_mail_access_token', 3000, function () use ($clientId, $clientSecret, $refreshToken) {
                $client = new \Google\Client();
                $client->setClientId($clientId);
                $client->setClientSecret($clientSecret);
                $client->refreshToken($refreshToken);
                
                $tokenArr = $client->getAccessToken();
                if (!$tokenArr || isset($tokenArr['error'])) {
                    throw new \Exception('Could not refresh token: ' . json_encode($tokenArr));
                }

                return $tokenArr['access_token'];
            });

            // Reconfigure the SMTP transport forcefully with the fresh Access Token
            $transport = $event->message->getHeaders();
            // In Symfony we need to use xoauth2 auth mode
            config(['mail.mailers.smtp.password' => $accessToken]);
            config(['mail.mailers.smtp.username' => env('MAIL_USERNAME', 'smartspacelibrary@gmail.com')]);
            config(['mail.mailers.smtp.host' => 'smtp.gmail.com']);
            config(['mail.mailers.smtp.port' => 465]);
            
            // To ensure the actual active transport gets updated before Send, Laravel rebuilds transport if we purge it, but unfortunately MessageSending is fired AFTER the transport connects sometimes.
            // Actually, in Symfony Mailer, the Authenticator is determined inside the SMTP Transport. 
            // We just ensure the config is set:
            app('mailer')->getSymfonyTransport()->setPassword($accessToken);

        } catch (\Exception $e) {
            Log::error('Failed to refresh Google Mail Access Token', ['error' => $e->getMessage()]);
        }
    }
}
