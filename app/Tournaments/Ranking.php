<?php
// Builds the ranking of a tournament. Only the display name is public,
// the real name is never part of the result.

namespace App\Tournaments;

use App\Models\Tournament\Tournament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Ranking
{
    /**
     * @param  bool  $includeEmail  Only the board version may include email addresses.
     * @return Collection<int, object{user_id:int, display_name:string, points:int, email:?string}>
     */
    public function for(Tournament $tournament, bool $includeEmail = false): Collection
    {
        $columns = [
            'users.id as user_id',
            'users.display_name',
            DB::raw('COALESCE(SUM(predictions.points), 0) AS points'),
        ];

        if ($includeEmail) {
            $columns[] = 'users.email';
        }

        return DB::table('tournament_participants')
            ->join('users', 'users.id', '=', 'tournament_participants.user_id')
            ->leftJoin('predictions', function ($join) use ($tournament) {
                $join->on('predictions.user_id', '=', 'users.id')
                    ->whereIn('predictions.tournament_match_id', function ($query) use ($tournament) {
                        $query->select('id')
                            ->from('tournament_matches')
                            ->where('tournament_id', $tournament->id);
                    });
            })
            ->where('tournament_participants.tournament_id', $tournament->id)
            ->whereNull('users.deleted_at')
            ->groupBy('users.id', 'users.display_name', ...($includeEmail ? ['users.email'] : []))
            ->orderByDesc('points')
            ->orderBy('users.display_name')
            ->select($columns)
            ->get();
    }
}
