<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('angkutans', function (Blueprint $table) {
            $table->foreignId('operator_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('vehicle_number')->nullable()->unique();
            $table->string('license_plate')->nullable()->unique();
            $table->unsignedInteger('capacity')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('angkutans', function (Blueprint $table) {
            $table->dropForeign(['operator_id']);
            $table->dropUnique('angkutans_vehicle_number_unique');
            $table->dropUnique('angkutans_license_plate_unique');
            $table->dropColumn(['operator_id', 'vehicle_number', 'license_plate', 'capacity']);
        });
    }
};
