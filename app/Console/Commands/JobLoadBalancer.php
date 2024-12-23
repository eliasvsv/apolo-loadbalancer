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

    private $tasks = [];

    public function handle()
    {
        $totalJobs = $this->argument('totalJobs');
        $maxWorkers = $this->argument('maxWorkers');
        $blockSize = $this->argument('blockSize');

        // Crear trabajos
        Worker::truncate();
        Job::truncate();
        Job::factory()->count($totalJobs)->create(['status' => 'pending']);
        $this->info("Se han creado $totalJobs trabajos.");
        Log::info("Se han creado $totalJobs trabajos.");

        $activeWorkers = 0;

        while (Job::where('status', '!=', 'completed')->exists() || !empty($this->tasks)) {
            // Escalar workers
            if ($activeWorkers < $maxWorkers) {
                $workersToAdd = min($blockSize, $maxWorkers - $activeWorkers);
                for ($i = 0; $i < $workersToAdd; $i++) {
                    $worker = Worker::create(['status' => 'idle']);
                    $activeWorkers++;
                    $this->info("Worker {$worker->id} creado.");
                    Log::info("Worker {$worker->id} creado.");
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

                    // Crear un nuevo runtime para ejecutar el trabajo
                    $runtime = new Runtime();
                    $this->tasks[$worker->id] = $runtime->run(function ($workerId, $jobId) {
                        // Cargar el framework Laravel en el hilo
                        require_once __DIR__ . '/../../../vendor/autoload.php';

                        // Configurar Laravel para la ejecución
                        $app = require_once __DIR__ . '/../../../bootstrap/app.php';
                        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

                        $duration = rand(2, 10);
                        sleep($duration);

                        // Actualizar los estados del trabajo y del worker
                        $job = \App\Models\Job::find($jobId);
                        $worker = \App\Models\Worker::find($workerId);

                        $job->update(['status' => 'completed']);
                        $worker->update(['status' => 'idle']);
                        Log::info("Worker {$workerId} completó el trabajo {$jobId} en {$duration} segundos.");
                        echo "Worker {$workerId} completó el trabajo {$jobId} en {$duration} segundos.".PHP_EOL;
                        return "Worker {$workerId} completó el trabajo {$jobId} en {$duration} segundos.";
            
                    }, [$worker->id, $job->id]);
                }
            });

            // Verificar finalización de tareas
            foreach ($this->tasks as $workerId => $task) {
                if (strlen( $task->value())>0) {
                   // $this->info($task->value());
                    unset($this->tasks[$workerId]);
                }
            }

            sleep(1); // Simula tiempo de espera entre ciclos
        }

        Log::info('Todos los trabajos han sido completados.');
        $this->info('Todos los trabajos han sido completados.');
    }
}
