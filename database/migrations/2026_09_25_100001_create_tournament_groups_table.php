<?php
// The groups (poules) inside a tournament, named A, B, C and so on.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->string('name', 10);
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->unique(['tournament_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_groups');
    }
};
