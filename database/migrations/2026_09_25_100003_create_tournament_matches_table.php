<?php
// All matches of a tournament, both group stage and knockout.
// Knockout matches start without teams and get filled once the previous stage is done.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tournament_group_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('round', 20);
            $table->unsignedSmallInteger('bracket_position')->nullable();

            $table->foreignId('home_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('away_team_id')->nullable()->constrained('teams')->nullOnDelete();

            // Shown before the teams are known, for example "Winnaar groep A".
            $table->string('home_source', 100)->nullable();
            $table->string('away_source', 100)->nullable();

            $table->dateTime('kickoff');

            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();
            $table->foreignId('penalty_winner_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->dateTime('result_entered_at')->nullable();

            // Where the winner of this match goes next.
            $table->foreignId('next_match_id')->nullable()->constrained('tournament_matches')->nullOnDelete();
            $table->string('next_slot', 10)->nullable();

            $table->timestamps();

            $table->index(['tournament_id', 'round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
