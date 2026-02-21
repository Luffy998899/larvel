<?php

namespace App\Repositories;

use App\Models\SystemSetting;

class SystemSettingRepository
{
    public function getInt(string $key, int $default = 0): int
    {
        return (int) (SystemSetting::valueOf($key, (string) $default));
    }

    public function set(string $key, string $value): void
    {
        SystemSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public function allKeyValue(): array
    {
        return SystemSetting::query()->pluck('value', 'key')->toArray();
    }
}
