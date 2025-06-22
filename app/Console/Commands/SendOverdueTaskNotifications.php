<?php

namespace App\Console\Commands;

use App\Application\Services\NotificationService;
use Illuminate\Console\Command;

class SendOverdueTaskNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-overdue-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for overdue tasks';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $this->info('Sending overdue task notifications...');

        try {
            $notificationService->sendOverdueTaskNotifications();

            $this->info('Overdue task notifications sent successfully!');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to send overdue task notifications: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
