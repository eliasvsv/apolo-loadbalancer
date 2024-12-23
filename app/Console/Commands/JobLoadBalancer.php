<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Job;
use App\Models\Worker;
use Illuminate\Support\Facades\Log;
use Parallel\Runtime;

class JobLoadBalancer extends Command
{
    protected $signature = 'job:load-balancer {totalJobs} {maxWorkers=5} {blockSize=2}';
    protected $description = 'Simula un sistema de Job Load Balancer con Parallel';

    private $runtimes = [];
    private $futures = [];
    private $workers = [];

    public function handle()
    {
        $totalJobs = $this->argument('totalJobs');
        $maxWorkers = $this->argument('maxWorkers');
        $blockSize = $this->argument('blockSize');

        // Crear trabajos
        Worker::truncate();//limpia base de datos 
        Job::truncate();
        Job::factory()->count($totalJobs)->create(['status' => 'pending']);
        $this->info("Se han creado $totalJobs trabajos.");

        // Crear workers
        $activeWorkers = 0;
        while ($activeWorkers < $maxWorkers) {
            $workersToAdd = min($blockSize, $maxWorkers - $activeWorkers);
            for ($i = 0; $i < $workersToAdd; $i++) {
                $worker = Worker::create(['status' => 'idle']);
                $this->workers[$worker->id] = $worker;
                $this->startWorker($worker->id);
                $activeWorkers++;
                $this->info("Worker {$worker->id} creado.");
            }
            sleep(1); // Dar tiempo para que los workers se inicialicen y tomen trabajos
        }

        // Monitorear workers
        while (Job::where('status', '!=', 'completed')->exists() || !empty($this->futures)) {
            foreach ($this->futures as $workerId => $future) {
                if ($future->done()) {
                    unset($this->futures[$workerId]); // Remover futuros completados
                }
            }
            sleep(1); // Evitar un bucle de alta carga
        }

        $this->info('Todos los trabajos han sido completados.');
    }

    private function startWorker($workerId)
    {
        $runtime = new Runtime();
        $this->runtimes[$workerId] = $runtime;

        $this->futures[$workerId] = $runtime->run(function ($workerId) {
            require_once __DIR__ . '/../../../vendor/autoload.php';

            $app = require_once __DIR__ . '/../../../bootstrap/app.php';
            $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

            while (true) {
                $job = \App\Models\Job::where('status', 'pending')->first();
                if (!$job) {
                    break; // No hay más trabajos pendientes
                }

                // Marcar el worker como ocupado
                $worker = \App\Models\Worker::find($workerId);
                $worker->update(['status' => 'busy']);
                $job->update(['status' => 'in-progress']);
                echo "Worker {$workerId} empezo el trabajo {$job->id}.".PHP_EOL;
                // Simular el procesamiento del trabajo
                $duration = rand(2, 10);
                sleep($duration);

                // Completar el trabajo y liberar el worker
                $job->update(['status' => 'completed']);
                $worker->update(['status' => 'idle']);
                echo "Worker {$workerId} completó el trabajo {$job->id} en {$duration} segundos.".PHP_EOL;
                Log::info("Worker {$workerId} completó el trabajo {$job->id} en {$duration} segundos.");
            }
        }, [$workerId]);
    }
}

