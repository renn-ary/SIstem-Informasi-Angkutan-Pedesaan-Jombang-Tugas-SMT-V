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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->string('booking_code')->unique();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('schedule_id')
                ->constrained('jadwals')
                ->cascadeOnDelete();

            $table->unsignedInteger('passenger_count');
            $table->decimal('total_fare', 12, 2);

            $table->string('booking_status')->default('menunggu_pembayaran');
            $table->string('payment_status')->default('menunggu_pembayaran');

            $table->timestamp('booked_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
