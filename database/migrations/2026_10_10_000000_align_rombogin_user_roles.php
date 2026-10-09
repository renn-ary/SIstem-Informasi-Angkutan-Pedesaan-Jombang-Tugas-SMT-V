<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'masyarakat')->update(['role' => 'penumpang']);
        DB::table('users')->where('role', 'operator')->update(['role' => 'pengemudi']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('penumpang')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'penumpang')->update(['role' => 'masyarakat']);
        DB::table('users')->where('role', 'pengemudi')->update(['role' => 'operator']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('masyarakat')->change();
        });
    }
};
