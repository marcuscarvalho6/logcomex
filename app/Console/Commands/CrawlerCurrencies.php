<?php

namespace App\Console\Commands;

use App\Services\Currency\CurrencyCrawlerService;
use Illuminate\Console\Command;

class CrawlerCurrencies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:crawler';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Faz um crawler do site Wikipedia, onde as informações estão disponíveis';

    /**
     * Execute the console command.
     * @var CurrencyCrawlerService 
     */
    public function handle(CurrencyCrawlerService $currencyCrawlerService)
    {
        $currencyCrawlerService->execute(true);
    }
}
