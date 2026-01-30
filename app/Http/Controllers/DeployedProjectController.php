<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DeployedProject;
use App\Services\HealthCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeployedProjectController extends Controller
{
    protected $healthCheckService;

    public function __construct(HealthCheckService $healthCheckService)
    {
        $this->healthCheckService = $healthCheckService;
    }

    /**
     * Список всех развернутых проектов
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $search = $request->get('search');
        $status = $request->get('status');

        $query = DeployedProject::with('license');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%")
                  ->orWhere('contact_email', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $projects = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($projects);
    }

    /**
     * Получить один проект
     */
    public function show($id)
    {
        $project = DeployedProject::with('license')->findOrFail($id);
        return response()->json(['project' => $project]);
    }

    /**
     * Создание нового проекта
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'license_key' => 'nullable|string',
            'license_id' => 'nullable|exists:licenses,id',
            'server_ip' => 'nullable|ip',
            'server_user' => 'nullable|string',
            'ssh_key' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'contact_name' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $project = DeployedProject::create([
            'name' => $request->name,
            'url' => $request->url,
            'api_key' => DeployedProject::generateApiKey(),
            'api_secret' => DeployedProject::generateApiSecret(),
            'license_key' => $request->license_key,
            'license_id' => $request->license_id,
            'server_ip' => $request->server_ip,
            'server_user' => $request->server_user,
            'ssh_key' => $request->ssh_key,
            'contact_email' => $request->contact_email,
            'contact_name' => $request->contact_name,
            'notes' => $request->notes,
            'status' => 'active',
        ]);

        // Первая проверка здоровья
        $this->healthCheckService->checkHealth($project);

        return response()->json([
            'success' => true,
            'message' => 'Проект успешно создан',
            'project' => $project->fresh(['license']),
        ], 201);
    }

    /**
     * Обновление проекта
     */
    public function update(Request $request, $id)
    {
        $project = DeployedProject::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url',
            'license_key' => 'nullable|string',
            'license_id' => 'nullable|exists:licenses,id',
            'server_ip' => 'nullable|ip',
            'server_user' => 'nullable|string',
            'ssh_key' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive,error,maintenance',
            'contact_email' => 'nullable|email',
            'contact_name' => 'nullable|string',
            'notes' => 'nullable|string',
            'notifications_enabled' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $project->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Проект обновлен',
            'project' => $project->fresh(['license']),
        ]);
    }

    /**
     * Удаление проекта
     */
    public function destroy($id)
    {
        $project = DeployedProject::findOrFail($id);
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Проект удален',
        ]);
    }

    /**
     * Проверка здоровья проекта
     */
    public function checkHealth($id)
    {
        $project = DeployedProject::findOrFail($id);
        $result = $this->healthCheckService->checkHealth($project);

        return response()->json([
            'success' => true,
            'health' => $result,
            'project' => $project->fresh(['license']),
        ]);
    }

    /**
     * Проверка здоровья всех проектов
     */
    public function checkAllHealth()
    {
        $results = $this->healthCheckService->checkAllProjects();

        return response()->json([
            'success' => true,
            'results' => $results,
            'statistics' => $this->healthCheckService->getHealthStatistics(),
        ]);
    }

    /**
     * Статистика проектов
     */
    public function statistics()
    {
        $stats = $this->healthCheckService->getHealthStatistics();

        return response()->json($stats);
    }

    /**
     * Регистрация проекта (вызывается самим развернутым проектом)
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'license_key' => 'required|string',
            'api_key' => 'required|string',
            'api_secret' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Проверка, что проект с таким api_key уже существует
        $project = DeployedProject::where('api_key', $request->api_key)->first();

        if ($project) {
            // Обновление существующего проекта
            $project->update([
                'name' => $request->name,
                'url' => $request->url,
                'license_key' => $request->license_key,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Проект обновлен',
                'project' => $project,
            ]);
        }

        // Создание нового проекта
        $project = DeployedProject::create([
            'name' => $request->name,
            'url' => $request->url,
            'api_key' => $request->api_key,
            'api_secret' => $request->api_secret,
            'license_key' => $request->license_key,
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Проект зарегистрирован',
            'project' => $project,
        ], 201);
    }
}
