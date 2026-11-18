<?php
// A tournament holds the whole competition: teams, group stage and knockout bracket.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();

            // Key of the sport rules used, so other sports can be added later.
            $table->string('sport', 50)->default('football');

            $table->string('status', 20)->default('draft');

            $table->unsignedSmallInteger('team_count');
            $table->unsignedSmallInteger('group_count');
            $table->unsignedSmallInteger('group_size');
            $table->unsignedSmallInteger('qualifiers_per_group');
            $table->unsignedSmallInteger('knockout_size');

            // Registration and predictions close the moment the tournament starts.
            $table->dateTime('starts_at');
            $table->dateTime('scoring_started_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
