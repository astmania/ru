<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\License;
use App\Services\LicenseService;
use Carbon\Carbon;

class LicenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $licenseService = app(LicenseService::class);

        // Пробная лицензия
        $trialLicense = $licenseService->createLicense([
            'license_key' => 'TRIAL-0000-0000-0000',
            'type' => 'trial',
            'expires_at' => Carbon::now()->addDays(30),
            'max_users' => 1,
            'customer_email' => 'trial@example.com',
            'customer_name' => 'Пробный пользователь',
            'feature_list' => [
                'basic_auth' => ['enabled' => true],
            ],
        ]);

        // Базовая лицензия
        $basicLicense = $licenseService->createLicense([
            'license_key' => 'BASIC-0000-0000-0000',
            'type' => 'basic',
            'expires_at' => Carbon::now()->addYear(),
            'max_users' => 10,
            'customer_email' => 'basic@example.com',
            'customer_name' => 'Базовый клиент',
            'feature_list' => [
                'basic_auth' => ['enabled' => true],
                'user_management' => ['enabled' => true],
            ],
        ]);

        // Премиум лицензия
        $premiumLicense = $licenseService->createLicense([
            'license_key' => 'PREMIUM-0000-0000-0000',
            'type' => 'premium',
            'expires_at' => Carbon::now()->addYear(),
            'max_users' => 100,
            'max_requests_per_month' => 100000,
            'customer_email' => 'premium@example.com',
            'customer_name' => 'Премиум клиент',
            'feature_list' => [
                'basic_auth' => ['enabled' => true],
                'user_management' => ['enabled' => true],
                'api_access' => ['enabled' => true],
                'export_data' => ['enabled' => true],
                'advanced_reports' => ['enabled' => true],
            ],
        ]);

        // Корпоративная лицензия
        $enterpriseLicense = $licenseService->createLicense([
            'license_key' => 'ENTERPRISE-0000-0000-0000',
            'type' => 'enterprise',
            'expires_at' => null, // Без ограничений по времени
            'max_users' => null, // Без ограничений
            'max_requests_per_month' => null, // Без ограничений
            'customer_email' => 'enterprise@example.com',
            'customer_name' => 'Корпоративный клиент',
            'feature_list' => [
                'basic_auth' => ['enabled' => true],
                'user_management' => ['enabled' => true],
                'api_access' => ['enabled' => true],
                'export_data' => ['enabled' => true],
                'import_data' => ['enabled' => true],
                'advanced_reports' => ['enabled' => true],
                'custom_branding' => ['enabled' => true],
                'backup_restore' => ['enabled' => true],
                'multi_tenant' => ['enabled' => true],
                'advanced_analytics' => ['enabled' => true],
            ],
        ]);

        $this->command->info('Лицензии созданы:');
        $this->command->info("Trial: {$trialLicense->license_key}");
        $this->command->info("Basic: {$basicLicense->license_key}");
        $this->command->info("Premium: {$premiumLicense->license_key}");
        $this->command->info("Enterprise: {$enterpriseLicense->license_key}");
    }
}
