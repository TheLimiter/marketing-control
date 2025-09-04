<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TagihanKlien;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'tagihan:mark-overdue';
    protected $description = 'Tandai tagihan sebagai overdue jika jatuh tempo lewat dan belum lunas';

    public function handle(): int
    {
        $count = TagihanKlien::query()
            ->where('status','!=','paid')
            ->whereDate('jatuh_tempo','<', now()->toDateString())
            ->whereColumn('terbayar','<','total')
            ->update(['status' => 'overdue']);

        $this->info("Updated {$count} tagihan to overdue.");
        return self::SUCCESS;
    }
}
