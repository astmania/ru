<?php

namespace App\Services;

use App\Models\DeployedProject;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class HealthCheckService
{
    /**
     * Выполнить проверку здоровья проекта
     */
    public function checkHealth(DeployedProject $project): array
    {
        $results = [
            'timestamp' => now()->toIso8601String(),
            'url' => $project->url,
            'checks' => [],
            'overall_status' => 'unknown',
        ];

        try {
            // Проверка доступности URL
            $urlCheck = $this->checkUrl($project->url);
            $results['checks']['url'] = $urlCheck;

            // Проверка API health endpoint (если есть)
            $apiCheck = $this->checkApiHealth($project);
            $results['checks']['api'] = $apiCheck;

            // Проверка лицензии (если есть)
            if ($project->license_key) {
                $licenseCheck = $this->checkLicense($project);
                $results['checks']['license'] = $licenseCheck;
            }

            // Определение общего статуса
            $results['overall_status'] = $this->determineOverallStatus($results['checks']);

            // Обновление проекта
            $project->update([
                'last_health_check' => now(),
                'health_status' => $results,
                'status' => $results['overall_status'] === 'healthy' ? 'active' : 'error',
            ]);

            return $results;

        } catch (\Exception $e) {
            Log::error("Health check failed for project {$project->id}: " . $e->getMessage());
            
            $results['overall_status'] = 'error';
            $results['error'] = $e->getMessage();

            $project->update([
                'last_health_check' => now(),
                'health_status' => $results,
                'status' => 'error',
            ]);

            return $results;
        }
    }

    /**
     * Проверка доступности URL
     */
    protected function checkUrl(string $url): array
    {
        try {
            $startTime = microtime(true);
            $response = Http::timeout(10)->get($url);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => $response->successful() ? 'ok' : 'error',
                'http_code' => $response->status(),
                'response_time_ms' => $responseTime,
                'message' => $response->successful() ? 'URL доступен' : 'URL недоступен',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Ошибка подключения: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Проверка API health endpoint
     */
    protected function checkApiHealth(DeployedProject $project): array
    {
        try {
            $healthUrl = rtrim($project->url, '/') . '/api/health';
            
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-API-Key' => $project->api_key,
                    'X-API-Secret' => $project->api_secret,
                ])
                ->get($healthUrl);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'status' => 'ok',
                    'message' => 'API доступен',
                    'data' => $data,
                ];
            }

            return [
                'status' => 'error',
                'message' => 'API недоступен',
                'http_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Ошибка проверки API: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Проверка лицензии
     */
    protected function checkLicense(DeployedProject $project): array
    {
        try {
            $licenseUrl = rtrim($project->url, '/') . '/api/license/check';
            
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-API-Key' => $project->api_key,
                    'X-API-Secret' => $project->api_secret,
                ])
                ->get($licenseUrl);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'status' => $data['valid'] ? 'ok' : 'error',
                    'message' => $data['valid'] ? 'Лицензия действительна' : 'Лицензия недействительна',
                    'data' => $data,
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Не удалось проверить лицензию',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Ошибка проверки лицензии: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Определение общего статуса
     */
    protected function determineOverallStatus(array $checks): string
    {
        $hasErrors = false;
        $hasWarnings = false;

        foreach ($checks as $check) {
            if (isset($check['status'])) {
                if ($check['status'] === 'error') {
                    $hasErrors = true;
                } elseif ($check['status'] === 'warning') {
                    $hasWarnings = true;
                }
            }
        }

        if ($hasErrors) {
            return 'error';
        } elseif ($hasWarnings) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Проверка всех проектов
     */
    public function checkAllProjects(): array
    {
        $projects = DeployedProject::where('status', '!=', 'inactive')->get();
        $results = [];

        foreach ($projects as $project) {
            $results[$project->id] = $this->checkHealth($project);
        }

        return $results;
    }

    /**
     * Получить статистику здоровья
     */
    public function getHealthStatistics(): array
    {
        $projects = DeployedProject::all();

        return [
            'total' => $projects->count(),
            'healthy' => $projects->where('status', 'active')->count(),
            'error' => $projects->where('status', 'error')->count(),
            'maintenance' => $projects->where('status', 'maintenance')->count(),
            'inactive' => $projects->where('status', 'inactive')->count(),
            'last_check' => $projects->max('last_health_check')?->toIso8601String(),
        ];
    }
}
