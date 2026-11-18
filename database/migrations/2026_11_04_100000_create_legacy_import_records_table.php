<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_import_records', function (Blueprint $table) {
            $table->id();
            $table->string('source', 64);
            $table->string('source_table', 64);
            $table->string('source_id', 191);
            $table->string('target_table', 64);
            $table->unsignedBigInteger('target_id');
            $table->timestamp('created_at');
            $table->unique(['source', 'source_table', 'source_id'], 'legacy_source_record_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_import_records');
    }
};