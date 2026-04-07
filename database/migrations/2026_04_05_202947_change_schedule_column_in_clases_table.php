<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clases', function (Blueprint $table) {
            $table->text('schedule')->nullable()->change();
        });

        // Limpiar datos existentes que no son JSON válido
        \DB::table('clases')->update(['schedule' => null]);
    }

    public function down(): void
    {
        Schema::table('clases', function (Blueprint $table) {
            $table->string('schedule', 100)->nullable()->change();
        });
    }
};
