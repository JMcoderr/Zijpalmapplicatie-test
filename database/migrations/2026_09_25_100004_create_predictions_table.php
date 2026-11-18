<?php
// A participant's prediction for a single match, including the points they earned.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tournament_match_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('home_score');
            $table->unsignedTinyInteger('away_score');

            // For knockout matches the participant also picks who wins on penalties.
            $table->foreignId('penalty_winner_team_id')->nullable()->constrained('teams')->nullOnDelete();

            // Cached so the ranking does not have to recalculate everything every time.
            $table->unsignedSmallInteger('points')->default(0);
            $table->dateTime('scored_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'tournament_match_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
