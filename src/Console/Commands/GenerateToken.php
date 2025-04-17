<?php

namespace Funlifew\PushNotify\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GenerateToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push-token:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate an admin token for the push notification server';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $baseUrl = config('push-notify.push_base_url');
        
        if (!$baseUrl) {
            $this->error('PUSH_BASE_URL is not set in your .env file.');
            $this->line('Please add it to your .env file:');
            $this->line('PUSH_BASE_URL=https://your-push-server.com/api/push/');
            return 1;
        }
        
        // Make sure the URL ends with a slash
        if (!str_ends_with($baseUrl, '/')) {
            $baseUrl .= '/';
        }
        
        $this->info('Connecting to push notification server...');
        
        try {
            $response = Http::post($baseUrl . 'token/generate/');
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['token'])) {
                    $token = $data['token'];
                    $name = $data['name'] ?? 'Unknown';
                    
                    $this->newLine();
                    $this->info('Token generated successfully!');
                    $this->newLine();
                    $this->line('Token: ' . $token);
                    $this->line('Name: ' . $name);
                    $this->newLine();
                    $this->comment('Add this token to your .env file:');
                    $this->line('PUSH_TOKEN=' . $token);
                    $this->newLine();
                    
                    return 0;
                }
                
                $this->error('Unexpected response format from server.');
                $this->line('Response: ' . $response->body());
                return 1;
            }
            
            $this->error('Failed to generate token. Server returned status code: ' . $response->status());
            $this->line('Response: ' . $response->body());
            return 1;
            
        } catch (\Exception $e) {
            $this->error('Error connecting to push notification server: ' . $e->getMessage());
            
            if (strpos($e->getMessage(), 'Could not resolve host') !== false) {
                $this->line('Please check your PUSH_BASE_URL setting in .env file.');
            }
            
            return 1;
        }
    }
}