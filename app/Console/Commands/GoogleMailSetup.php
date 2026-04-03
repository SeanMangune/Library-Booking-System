<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GoogleMailSetup extends Command
{
    protected $signature = 'google:mail-setup';
    protected $description = 'Setup Google OAuth for SMTP Mail functionality';

    public function handle()
    {
        $this->info('Starting Google Mail OAuth Setup...');

        $clientId = env('GOOGLE_CLIENT_ID');
        $clientSecret = env('GOOGLE_CLIENT_SECRET');

        if (!$clientId || !$clientSecret) {
            $this->error('GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET must be set in .env');
            return 1;
        }

        $client = new \Google\Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri('http://127.0.0.1:8000');
        $client->addScope('https://mail.google.com/');
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        $authUrl = $client->createAuthUrl();

        $this->info("1. First, make sure you added http://127.0.0.1:8000 to Authorized Redirect URIs in your Google Cloud Console.");
        $this->info("2. Open the following URL in your browser:");
        $this->line("");
        $this->line($authUrl);
        $this->line("");
        $this->info("3. Login with your LibrarySmartSpace@gmail.com account and authorize the application.");
        $this->info("4. Important: The browser will redirect you to a broken page or your dashboard with ?code= in the URL link (e.g. http://127.0.0.1:8000/?code=4/xxxx...)");
        $this->info("5. Copy ONLY the long text code after '?code=' and paste it below:");

        $authCode = $this->ask('Enter authorization code');

        if (!$authCode) {
            $this->error('No code provided. Aborting.');
            return 1;
        }

        try {
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

            if (array_key_exists('error', $accessToken)) {
                throw new \Exception(join(', ', $accessToken));
            }

            if (!isset($accessToken['refresh_token'])) {
                $this->error('Failed to get Refresh Token! The app might have already been authorized. Please go to your Google Account Settings -> Security -> Manage Third Party Access, remove this app, and try again so it issues a fresh Refresh Token.');
                return 1;
            }

            $refreshToken = $accessToken['refresh_token'];

            $this->updateEnv(['GOOGLE_MAIL_REFRESH_TOKEN' => $refreshToken]);

            $this->info('Successfully fetched and saved the Refresh Token to .env!');
            $this->info('You can now send automated emails using Google OAuth.');

        } catch (\Exception $e) {
            $this->error('Error exchanging code: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function updateEnv(array $data): void
    {
        $path = base_path('.env');

        if (file_exists($path)) {
            $env = file_get_contents($path);

            foreach ($data as $key => $value) {
                // Remove existing
                $env = preg_replace("/^{$key}=.*/m", '', $env);
            }
            
            // Clean up multiple empty lines
            $env = preg_replace("/\n{3,}/", "\n\n", $env);

            $append = "";
            foreach ($data as $key => $value) {
                $append .= "{$key}={$value}\n";
            }

            file_put_contents($path, trim($env) . "\n\n" . $append);
        }
    }
}
