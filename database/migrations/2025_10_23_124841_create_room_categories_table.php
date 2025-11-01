<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Standard Twin, Deluxe Double, Suite, etc.
            $table->string('type'); // single, double, twin, suite
            $table->text('description');
            $table->integer('max_guests');
            $table->decimal('price_per_night', 10, 2);
            $table->integer('total_rooms')->default(0);
            $table->decimal('size', 8, 2)->nullable(); // in m2
            $table->json('images')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_categories');
    }
};
