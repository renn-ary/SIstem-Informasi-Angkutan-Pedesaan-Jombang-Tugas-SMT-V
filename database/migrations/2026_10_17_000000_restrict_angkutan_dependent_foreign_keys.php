<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mengubah perilaku foreign key dari tabel rutes, jadwals, tarifs, dan verifikasis
 * terhadap angkutans dari CASCADE ke RESTRICT.
 *
 * Motivasi: UC-08 mewajibkan penghapusan angkutan yang masih memiliki data
 * terkait (rute, jadwal, tarif, verifikasi) ditolak—bukan dihapus bersama secara
 * otomatis. Perubahan ini membuat constraint database selaras dengan logika
 * proteksi di AngkutanController::destroy().
 *
 * Migration ini aman dijalankan pada data yang sudah ada karena hanya mengubah
 * aturan DELETE; tidak ada kolom atau baris yang diubah maupun dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        // rutes
        Schema::table('rutes', function (Blueprint $table) {
            $table->dropForeign(['angkutan_id']);
            $table->foreign('angkutan_id')
                ->references('id')->on('angkutans')
                ->restrictOnDelete();
        });

        // jadwals
        Schema::table('jadwals', function (Blueprint $table) {
            $table->dropForeign(['angkutan_id']);
            $table->foreign('angkutan_id')
                ->references('id')->on('angkutans')
                ->restrictOnDelete();
        });

        // tarifs
        Schema::table('tarifs', function (Blueprint $table) {
            $table->dropForeign(['angkutan_id']);
            $table->foreign('angkutan_id')
                ->references('id')->on('angkutans')
                ->restrictOnDelete();
        });

        // verifikasis
        Schema::table('verifikasis', function (Blueprint $table) {
            $table->dropForeign(['angkutan_id']);
            $table->foreign('angkutan_id')
                ->references('id')->on('angkutans')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Kembalikan ke CASCADE (perilaku awal sebelum migration ini)
        Schema::table('rutes', function (Blueprint $table) {
            $table->dropForeign(['angkutan_id']);
            $table->foreign('angkutan_id')
                ->references('id')->on('angkutans')
                ->cascadeOnDelete();
        });

        Schema::table('jadwals', function (Blueprint $table) {
            $table->dropForeign(['angkutan_id']);
            $table->foreign('angkutan_id')
                ->references('id')->on('angkutans')
                ->cascadeOnDelete();
        });

        Schema::table('tarifs', function (Blueprint $table) {
            $table->dropForeign(['angkutan_id']);
            $table->foreign('angkutan_id')
                ->references('id')->on('angkutans')
                ->cascadeOnDelete();
        });

        Schema::table('verifikasis', function (Blueprint $table) {
            $table->dropForeign(['angkutan_id']);
            $table->foreign('angkutan_id')
                ->references('id')->on('angkutans')
                ->cascadeOnDelete();
        });
    }
};
