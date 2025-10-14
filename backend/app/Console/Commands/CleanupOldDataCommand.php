<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;


// ============================================
// COMMAND: app/Console/Commands/CleanupOldDataCommand.php
// ============================================
class CleanupOldDataCommand extends Command
{
    protected $signature = 'cleanup:old-data';
    protected $description = 'Cleanup old notifications and expired device tokens';

    public function handle(): int
    {
        $this->info('Cleaning up old data...');
        
        \App\Jobs\CleanupOldNotifications::dispatch();
        \App\Jobs\CleanupExpiredDeviceTokens::dispatch();
        
        $this->info('Cleanup jobs dispatched!');
        
        return Command::SUCCESS;
    }
}
