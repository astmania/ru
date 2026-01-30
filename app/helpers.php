<?php

if (!function_exists('has_license_feature')) {
    /**
     * Проверка доступности функции лицензии
     *
     * @param string $featureKey
     * @return bool
     */
    function has_license_feature(string $featureKey): bool
    {
        return app(\App\Services\LicenseService::class)->hasFeature($featureKey);
    }
}

if (!function_exists('check_license')) {
    /**
     * Проверка лицензии
     *
     * @param string|null $domain
     * @return bool
     */
    function check_license(?string $domain = null): bool
    {
        return app(\App\Services\LicenseService::class)->checkLicense($domain);
    }
}

if (!function_exists('get_current_license')) {
    /**
     * Получить текущую лицензию
     *
     * @return \App\Models\License|null
     */
    function get_current_license()
    {
        return app(\App\Services\LicenseService::class)->getCurrentLicense();
    }
}
