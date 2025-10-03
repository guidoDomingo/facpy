<?php

namespace App\Console\Commands;

use App\Services\SifenEventService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessSifenEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sifen:process-events 
                           {--limit=15 : Número máximo de eventos a procesar}
                           {--retry : Reintentar eventos fallidos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa eventos SIFEN pendientes en lotes';

    private $eventService;

    /**
     * Create a new command instance.
     */
    public function __construct(SifenEventService $eventService)
    {
        parent::__construct();
        $this->eventService = $eventService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando procesamiento de eventos SIFEN...');
        
        try {
            $limit = $this->option('limit');
            $retry = $this->option('retry');
            
            if ($retry) {
                $this->info('Reintentando eventos fallidos...');
                // Aquí se podría implementar lógica de reintento
            }
            
            $result = $this->eventService->processEventBatch();
            
            if ($result['success']) {
                $this->info('✅ ' . $result['message']);
                if (isset($result['protocol'])) {
                    $this->info('Protocolo SIFEN: ' . $result['protocol']);
                }
            } else {
                $this->error('❌ ' . $result['message']);
                return Command::FAILURE;
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error procesando eventos: ' . $e->getMessage());
            Log::error('Command ProcessSifenEvents failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
