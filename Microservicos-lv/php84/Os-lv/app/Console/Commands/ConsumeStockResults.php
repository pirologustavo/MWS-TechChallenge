<?php

namespace App\Console\Commands;

use App\Saga\Infrastructure\ConsumeSagaEvents;
use App\Saga\Infrastructure\RabbitMq;
use App\Saga\StockAdjustmentResultHandler;
use Illuminate\Console\Command;

class ConsumeStockResults extends Command
{
    protected $signature = 'saga:consume-stock-results';
    protected $description = 'Consume stock result events and complete or compensate OS Sagas';

    public function handle(ConsumeSagaEvents $consumer, RabbitMq $rabbit): int
    {
        $consumer->run('os.stock-results', fn (array $event) => app(StockAdjustmentResultHandler::class)->handle($event), $rabbit);
    }
}
