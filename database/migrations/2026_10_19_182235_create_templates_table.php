<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('kapso_template_id')->nullable()->unique();
            $table->string('name');
            $table->string('language')->default('en_US');
            $table->string('category')->nullable();
            $table->string('status')->default('draft');
            $table->json('components')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};