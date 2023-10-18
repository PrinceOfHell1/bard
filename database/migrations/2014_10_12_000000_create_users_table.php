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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->text('photo')->default('storage/photo/default_p.jpg');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');

            // $table->string('device');
            $table->string('verified')->nullable();
            $table->string('login')->default('manual');
            $table->enum('authenticated', ['verified', 'unverified'])->default('unverified');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
