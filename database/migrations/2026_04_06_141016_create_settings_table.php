<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('value')->nullable();
            $table->timestamps();
        });

        DB::table('settings')->insert([
            ['key' => 'price_8h',   'value' => '120', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'price_12h',  'value' => '150', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'price_16h',  'value' => '170', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'price_full1', 'value' => '190', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'price_full2', 'value' => '210', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
