<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('CUM_customers')->restrictOnDelete();
            $table->string('name');
            addCommonColumns($table);
        });

        Schema::create('epics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->string('name');
            addCommonColumns($table);
        });

        Schema::create('epic_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('epic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epic_comments');
        Schema::dropIfExists('epics');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('users');
    }
};
