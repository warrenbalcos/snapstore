<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('key_value_store', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->json('value');
            $table->timestamp('created_at')->nullable()->index();
            $table->timestamp('updated_at')->nullable();

            $table->index('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('key_value_store');
    }
};
