<?php

namespace Funlifew\PushNotify\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Intervention\Image\ImageManagerStatic as Image;
use Intervention\Image\Geometry\Circle;


class InstallPush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:install {--force : Force publish assets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Push Notify package';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Installing Push Notify package...');

        // Ask if user wants to install recommended packages
        if ($this->confirm('Would you like to install recommended packages (intervention/image) and (guzzlehttp/guzzle)?', true)) {
            $this->comment('Installing additional packages via Composer...');
            
            $command = ['composer', 'require', 'intervention/image', "guzzlehttp/guzzle"];
            $process = new Process($command);
            $process->setTimeout(null); // No timeout
            
            try {
                $process->mustRun(function ($type, $buffer) {
                    $this->output->write($buffer);
                });
                $this->info('✓ Additional packages installed successfully');
            } catch (ProcessFailedException $exception) {
                $this->error('Failed to install additional packages.');
                $this->line($exception->getMessage());
            }
        }

        // Publish assets
        $this->comment('Publishing assets...');
        $force = $this->option('force') ? '--force' : '';
        Artisan::call("vendor:publish --tag=push-notify-assets {$force}");
        $this->info('✓ Assets published');

        // Publish config
        $this->comment('Publishing configuration...');
        Artisan::call("vendor:publish --tag=push-notify-config {$force}");
        $this->info('✓ Configuration published');

        // Run migrations
        $this->comment('Running migrations...');
        Artisan::call('migrate');
        $this->info('✓ Migrations complete');

        // Check if storage is linked
        if (!file_exists(public_path('storage'))) {
            $this->comment('Creating storage link...');
            Artisan::call('storage:link');
            $this->info('✓ Storage link created');
        }

        // Create directories
        $this->comment('Creating storage directories...');
        $iconDir = storage_path('app/public/icons');
        if (!File::exists($iconDir)) {
            File::makeDirectory($iconDir, 0755, true);
        }
        $this->info('✓ Directories created');

        // Add default icon
        $defaultIcon = public_path('default-icon.png');
        if (!File::exists($defaultIcon)) {
            $this->comment('Creating default icon...');

            if (class_exists('Intervention\Image\Laravel\Facades\Image')) {
                try {
                    // Try Intervention Image v3 approach
                    $img = \Intervention\Image\Laravel\Facades\Image::create(64, 64, '#ff5000');

                    // Create a white circle at the center
                    $circle = new Circle(50); // diameter
                    $circle->background('#ffffff')->position('center');
                    $img->draw($circle);

                    $img->save($defaultIcon);
                    $this->info('✓ Default icon created (v3)');
                } catch (\Throwable $e) {
                    // Fallback for Intervention Image v2
                    try {
                        $img = \Intervention\Image\Facades\Image::canvas(64, 64, '#ff5000');
                        $img->circle(50, 32, 32, function ($draw) {
                            $draw->background('#ffffff');
                        });
                        $img->save($defaultIcon);
                        $this->info('✓ Default icon created (v2)');
                    } catch (\Throwable $e2) {
                        $this->warn('⚠️ Error creating default icon: ' . $e2->getMessage());
                    }
                }
            } else {
                $this->warn('⚠️ Intervention Image not available - default icon not created');
            }
        }

        // Create offline page
        $offlinePage = public_path('offline.html');
        if (!File::exists($offlinePage)) {
            $this->comment('Creating offline page...');
            $offlineContent = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline</title>
    <style>
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        .container {
            max-width: 500px;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h1 {
            margin-top: 0;
            color: #ff5000;
        }
        p {
            margin-bottom: 30px;
            line-height: 1.5;
        }
        .icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📶</div>
        <h1>You're offline</h1>
        <p>It looks like you've lost your internet connection. Please check your connection and try again.</p>
        <button onclick="window.location.reload()">Try Again</button>
    </div>
</body>
</html>
HTML;
            File::put($offlinePage, $offlineContent);
            $this->info('✓ Offline page created');
        }

        // Successful installation message
        $this->newLine();
        $this->info('Push Notify has been installed successfully!');
        $this->newLine();
        $this->comment('Next steps:');
        $this->newLine();
        $this->line('1. Generate a VAPID key for your push server:');
        $this->line('   php artisan push-token:generate');
        $this->newLine();
        $this->line('2. Add the VAPID key and server URL to your .env file:');
        $this->line('   PUSH_BASE_URL=https://your-push-server.com/api/push/');
        $this->line('   PUSH_TOKEN=your_token_here');
        $this->newLine();
        $this->line('3. Add the subscription script to your layout:');
        $this->line('   {!! push_notify_subscription_script() !!}');
        $this->newLine();
        $this->line('4. Add a subscribe button to your page:');
        $this->line('   <button onclick="window.PushNotify.handleSubscription()">Subscribe to Notifications</button>');
        $this->newLine();
        $this->line('5. Set up the scheduler to run Laravel\'s scheduler for scheduled notifications:');
        $this->line('   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1');
        $this->newLine();

        return 0;
    }
}