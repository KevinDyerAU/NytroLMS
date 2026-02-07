<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestEmail extends Command
{
    protected $signature = 'email:send-test';

    protected $description = 'Send a test email every 3 minutes';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Mail::raw('This is a test email sent every 3 minutes.', function ($message) {
            $message->to('mohsin.adeel@live.com')
                ->subject('Test Email');
        });

        \Log::info('Test email sent at '.now());
    }
}
