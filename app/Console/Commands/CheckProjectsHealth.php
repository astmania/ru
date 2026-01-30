<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HealthCheckService;
use App\Models\DeployedProject;

class CheckProjectsHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:check-health {--project-id= : ID конкретного проекта}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Проверка работоспособности развернутых проектов';

    protected $healthCheckService;

    public function __construct(HealthCheckService $healthCheckService)
    {
        parent::__construct();
        $this->healthCheckService = $healthCheckService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $projectId = $this->option('project-id');

        if ($projectId) {
            // Проверка конкретного проекта
            $project = DeployedProject::find($projectId);
            
            if (!$project) {
                $this->error("Проект с ID {$projectId} не найден");
                return 1;
            }

            $this->info("Проверка проекта: {$project->name} ({$project->url})");
            $result = $this->healthCheckService->checkHealth($project);
            
            $this->displayHealthResult($result);
            
            return $result['overall_status'] === 'healthy' ? 0 : 1;
        }

        // Проверка всех проектов
        $this->info('Проверка всех развернутых проектов...');
        $results = $this->healthCheckService->checkAllProjects();
        
        $healthy = 0;
        $errors = 0;

        foreach ($results as $projectId => $result) {
            $project = DeployedProject::find($projectId);
            $this->line("Проект: {$project->name} ({$project->url})");
            $this->displayHealthResult($result);
            
            if ($result['overall_status'] === 'healthy') {
                $healthy++;
            } else {
                $errors++;
            }
            $this->line('');
        }

        $stats = $this->healthCheckService->getHealthStatistics();
        $this->info("Статистика: Всего: {$stats['total']}, Здоровых: {$stats['healthy']}, Ошибок: {$stats['error']}");

        return $errors > 0 ? 1 : 0;
    }

    protected function displayHealthResult(array $result)
    {
        $status = $result['overall_status'];
        $statusColor = $status === 'healthy' ? 'green' : ($status === 'warning' ? 'yellow' : 'red');
        
        $this->line("Статус: <fg={$statusColor}>{$status}</>");
        
        foreach ($result['checks'] ?? [] as $checkName => $check) {
            $checkStatus = $check['status'] ?? 'unknown';
            $checkColor = $checkStatus === 'ok' ? 'green' : 'red';
            $message = $check['message'] ?? '';
            
            $this->line("  {$checkName}: <fg={$checkColor}>{$checkStatus}</> - {$message}");
            
            if (isset($check['response_time_ms'])) {
                $this->line("    Время ответа: {$check['response_time_ms']}ms");
            }
        }
    }
}
