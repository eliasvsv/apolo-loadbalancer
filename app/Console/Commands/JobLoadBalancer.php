<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Job;
use App\Models\Worker;
use Illuminate\Support\Facades\Log;
use Fiber;

class JobLoadBalancer extends Command
{
    protected $signature = 'job:load-balancer {totalJobs} {maxWorkers=5} {blockSize=2}';
    protected $description = 'Simula un sistema de Job Load Balancer con workers async';

    private $fibers = [];
    private $fiberStatus = [];

    public function handle()
    {
        $totalJobs = $this->argument('totalJobs');
        $maxWorkers = $this->argument('maxWorkers');
        $blockSize = $this->argument('blockSize');

        // Crear trabajos
        Job::factory()->count($totalJobs)->create(['status' => 'pending']);
        $this->info("Se han creado $totalJobs trabajos.");

        $activeWorkers = 0;

        while (Job::where('status', '!=', 'completed')->exists() || !empty($this->fibers)) {
            // Escalar workers
            if ($activeWorkers < $maxWorkers) {
                $workersToAdd = min($blockSize, $maxWorkers - $activeWorkers);
                echo $workersToAdd . "" . PHP_EOL;
                for ($i = 0; $i < $workersToAdd; $i++) {
                    $worker = Worker::create(['status' => 'idle']);
                    $activeWorkers++;
                    $this->info("Worker {$worker->id} creado.");
                }
            }

            // Asignar trabajos
            Worker::where('status', 'idle')->each(function ($worker) {
                $job = Job::where('status', 'pending')->first();

                if ($job) {
                    $worker->update(['status' => 'busy']);
                    $job->update(['status' => 'in-progress']);

                    Log::info("Worker {$worker->id} comenzó el trabajo {$job->id}.");
                    $this->info("Worker {$worker->id} comenzó el trabajo {$job->id}.");
                    // Crear un nuevo Fiber para procesar el trabajo
                    $fiber = new Fiber(function () use ($worker, $job) {
                        echo "aqui inicia".PHP_EOL;
                        $this->simulateJob($worker, $job);
                        echo "aqui finaliza".PHP_EOL;
                    });

                    $fiber->start();
                    $this->fibers[$worker->id] = $fiber;
                    $this->fiberStatus[$worker->id] = 'busy';
                }
            });

            // Ciclo de eventos para avanzar los Fibers
            foreach ($this->fibers as $workerId => $fiber) {
                if ($fiber->isSuspended()) {
                    $fiber->resume();
                }

                if ($fiber->isTerminated()) {
                    unset($this->fibers[$workerId]);
                    $this->fiberStatus[$workerId] = 'idle';
                }
            }

            sleep(1); // Simula tiempo de espera entre ciclos
        }

        $this->info('Todos los trabajos han sido completados.');
        Worker::truncate();
        Job::truncate();
    }

    private function simulateJob($worker, $job)
    {
        $duration = rand(2, 10);

        // Simular trabajo con Fiber suspendido
        sleep($duration);
        $job->update(['status' => 'completed']);
        $worker->update(['status' => 'idle']);
        Fiber::suspend();
        Log::info("Worker {$worker->id} completó el trabajo {$job->id} en {$duration} segundos.");
        $this->info("Worker {$worker->id} completó el trabajo {$job->id} en {$duration} segundos.");
    }
}
