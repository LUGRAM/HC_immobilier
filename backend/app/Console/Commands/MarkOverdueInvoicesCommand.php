<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\MarkOverdueInvoices;


// ============================================
// COMMAND: app/Console/Commands/MarkOverdueInvoicesCommand.php
// ============================================
class MarkOverdueInvoicesCommand extends Command
{
    protected $signature = 'invoices:mark-overdue';
    protected $description = 'Mark pending invoices as overdue';

    public function handle(): int
    {
        $this->info('Marking overdue invoices...');
        
        MarkOverdueInvoices::dispatch();
        
        $this->info('Overdue invoices marking job dispatched!');
        
        return Command::SUCCESS;
    }
}
