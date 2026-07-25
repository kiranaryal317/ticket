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
         Schema::create('tickets', function (Blueprint $table) {
        $table->id();
        $table->string('subject');
        $table->text('description');
        $table->string('status')->default('Open');
        $table->string('priority')->default('Medium');
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
    });
    }


    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
