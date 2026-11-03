<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        // Auto-seed initial data on fresh install
        if (Schema::hasTable('users') && User::count() === 0) {
            Artisan::call('db:seed', ['--force' => true]);
        }

        Window::open('main')
            ->title('Sistem Presensi Siswa RFID')
            ->width(1366)
            ->height(860)
            ->minWidth(1024)
            ->minHeight(700)
            ->rememberState()
            ->hideMenu();
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
            'memory_limit' => '512M',
            'upload_max_filesize' => '32M',
            'post_max_size' => '32M',
            'max_execution_time' => '120',
        ];
    }
}
