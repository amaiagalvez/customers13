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

        Schema::create('PRO_projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('CUM_customers')->restrictOnDelete();
            $table->string('name');
            addCommonColumns($table);
        });

        Schema::create('PRO_epics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('PRO_projects')->restrictOnDelete();
            $table->string('name');
            addCommonColumns($table);
        });

        Schema::create('PRO_epic_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('epic_id')->constrained('PRO_epics');
            $table->foreignId('user_id')->constrained('users');
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PRO_epic_comments');
        Schema::dropIfExists('PRO_epics');
        Schema::dropIfExists('PRO_projects');
        Schema::dropIfExists('users');
    }
};
