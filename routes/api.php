<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\ShejireController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\DeployedProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Публичные маршруты
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Публичные категории и теги
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/tags', [TagController::class, 'index']);

// Публичные статьи (только опубликованные)
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{idOrSlug}', [ArticleController::class, 'show']);

// Маршруты для лицензий
Route::prefix('license')->group(function () {
    // Публичные маршруты
    Route::post('/activate', [LicenseController::class, 'activate']);
    Route::get('/check', [LicenseController::class, 'check']);
    Route::get('/info', [LicenseController::class, 'info']);
    Route::get('/feature/{feature}', [LicenseController::class, 'checkFeature']);
    
    // Административные маршруты (требуют авторизации и прав администратора)
    Route::middleware(['auth:api', 'admin'])->group(function () {
        Route::get('/list', [LicenseController::class, 'index']);
        Route::get('/statistics', [LicenseController::class, 'statistics']);
        Route::post('/create', [LicenseController::class, 'create']);
        Route::get('/{id}', [LicenseController::class, 'show']);
        Route::put('/{id}', [LicenseController::class, 'update']);
        Route::delete('/{id}', [LicenseController::class, 'destroy']);
        Route::post('/{id}/toggle-status', [LicenseController::class, 'toggleStatus']);
        Route::put('/{id}/features', [LicenseController::class, 'updateFeatures']);
    });
});

// Управление статьями (только для администраторов)
Route::prefix('admin/articles')->middleware(['auth:api', 'admin'])->group(function () {
    Route::get('/', [ArticleController::class, 'adminIndex']);
    Route::post('/upload-image', [ArticleController::class, 'uploadImage']);
    Route::get('/{article}', [ArticleController::class, 'adminShow']);
    Route::post('/', [ArticleController::class, 'store']);
    Route::put('/{article}', [ArticleController::class, 'update']);
    Route::delete('/{article}', [ArticleController::class, 'destroy']);
});

// Управление тегами (админ)
Route::prefix('admin/tags')->middleware(['auth:api', 'admin'])->group(function () {
    Route::post('/', [TagController::class, 'store']);
    Route::delete('/{tag}', [TagController::class, 'destroy']);
});

// Шежіре (родословная)
Route::prefix('shejire')->group(function () {
    Route::get('/', [ShejireController::class, 'index'])->middleware('optional-auth');

    // Модерация (модератор) — до /{shejire}, чтобы не перехватывалось
    Route::middleware(['auth:api', 'moderator'])->prefix('moderation')->group(function () {
        Route::get('/', [ShejireController::class, 'moderationIndex']);
        Route::post('/{shejire}/approve', [ShejireController::class, 'approve']);
        Route::post('/{shejire}/reject', [ShejireController::class, 'reject']);
    });

    Route::get('/my/trees', [ShejireController::class, 'myTrees'])->middleware('auth:api');
    Route::get('/{shejire}', [ShejireController::class, 'show'])->middleware('optional-auth');

    Route::middleware('auth:api')->group(function () {
        Route::post('/', [ShejireController::class, 'store']);
        Route::put('/{shejire}', [ShejireController::class, 'update']);
        Route::delete('/{shejire}', [ShejireController::class, 'destroy']);
        Route::post('/{shejire}/nodes', [ShejireController::class, 'storeNode']);
        Route::put('/{shejire}/nodes/{node}', [ShejireController::class, 'updateNode']);
        Route::delete('/{shejire}/nodes/{node}', [ShejireController::class, 'destroyNode']);
    });
});

// Защищенные маршруты
Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    Route::get('/me', [UserController::class, 'me']);
    Route::put('/user/profile', [UserController::class, 'update']);
    Route::post('/user/upload-avatar', [UserController::class, 'uploadAvatar']);
    Route::put('/user/password', [UserController::class, 'changePassword']);
    Route::get('/protected-data', [UserController::class, 'protectedData']);
});

// Health check endpoint (публичный, для мониторинга)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'version' => config('app.version', '1.0.0'),
    ]);
});

// Регистрация развернутого проекта (публичный, но требует api_key)
Route::post('/projects/register', [DeployedProjectController::class, 'register']);

// Управление пользователями (только для супер-админов)
Route::prefix('admin/users')->middleware(['auth:api', 'super-admin'])->group(function () {
    Route::get('/', [AdminUserController::class, 'index']);
    Route::post('/', [AdminUserController::class, 'store']);
    Route::put('/{user}', [AdminUserController::class, 'update']);
    Route::delete('/{user}', [AdminUserController::class, 'destroy']);
    Route::put('/{user}/password', [AdminUserController::class, 'changePassword']);
});

// Управление развернутыми проектами (только для супер-админов)
Route::prefix('projects')->middleware(['auth:api', 'super-admin'])->group(function () {
    Route::get('/', [DeployedProjectController::class, 'index']);
    Route::get('/statistics', [DeployedProjectController::class, 'statistics']);
    Route::post('/', [DeployedProjectController::class, 'store']);
    Route::get('/{id}', [DeployedProjectController::class, 'show']);
    Route::put('/{id}', [DeployedProjectController::class, 'update']);
    Route::delete('/{id}', [DeployedProjectController::class, 'destroy']);
    Route::post('/{id}/check-health', [DeployedProjectController::class, 'checkHealth']);
    Route::post('/check-all-health', [DeployedProjectController::class, 'checkAllHealth']);
});
