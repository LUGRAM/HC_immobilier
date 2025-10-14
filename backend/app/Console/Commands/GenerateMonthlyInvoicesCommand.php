<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\GenerateMonthlyInvoices;
use SYMfony\Component\Console\Command\Command as SymfonyCommand; 

// ============================================
// COMMAND: app/Console/Commands/GenerateMonthlyInvoicesCommand.php
// ============================================
class GenerateMonthlyInvoicesCommand extends Command
{
    protected $signature = 'invoices:generate-monthly';
    protected $description = 'Generate monthly rent invoices for all active leases';

    public function handle(): int
    {
        $this->info('Generating monthly invoices...');
        
        GenerateMonthlyInvoices::dispatch();
        
        $this->info('Monthly invoices generation job dispatched!');
        
        return Command::SUCCESS;
    }
}
