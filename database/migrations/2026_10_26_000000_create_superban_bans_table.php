<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('superban_bans', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('type')->index();
            $table->string('value')->index();
            $table->string('scope')->nullable();
            $table->string('route')->nullable();
            $table->string('reason')->nullable();
            $table->unsignedInteger('strikes')->default(1);
            $table->timestamp('banned_at');
            $table->timestamp('banned_until')->index();
            $table->timestamp('lifted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('superban_bans');
    }
};
