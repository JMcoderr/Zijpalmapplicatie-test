<?php

namespace App\Services\Migration;

use App\Models\Tournament\Tournament;
use App\Tournaments\ScoreCalculator;
use Illuminate\Database\Connection;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LegacySiteImporter
{
    private Connection $source;

    private Connection $target;

    private string $identity;

    private array $counts = [];

    public function run(string $profile, string $connection, bool $apply = false): array
    {
        if (! in_array($profile, ['football', 'charity', 'zijpalm'], true) || $connection === ''
            || ! config('database.connections.'.$connection)) {
            throw new RuntimeException('Ongeldig migratieprofiel of ontbrekende bronverbinding.');
        }

        $this->source = DB::connection($connection);
        $this->target = DB::connection();
        $this->counts = [];

        if ($this->source->getPdo() === $this->target->getPdo()
            || ($this->source->getDatabaseName() === $this->target->getDatabaseName()
                && $this->source->getConfig('host') === $this->target->getConfig('host')
                && $this->source->getDatabaseName() !== ':memory:')) {
            throw new RuntimeException('Bron en doel moeten verschillende databases zijn.');
        }

        $this->identity = hash('sha256', json_encode([
            $profile, $this->source->getDriverName(), $this->source->getConfig('host'),
            $this->source->getConfig('port'), $this->source->getDatabaseName(),
        ], JSON_THROW_ON_ERROR));

        if ($this->target->transactionLevel() === 0 && $this->target->getDriverName() === 'mysql') {
            foreach ($this->target->select('SHOW TABLE STATUS') as $table) {
                if ($table->Engine !== null && strtoupper($table->Engine) !== 'INNODB') {
                    throw new RuntimeException('Gebruik InnoDB-tabellen voor transactionele migratie.');
                }
            }
        }

        $this->target->beginTransaction();

        try {
            if ($profile === 'football') {
                $this->football();
            } else {
                $this->copySite($profile);
            }

            if ($apply) {
                $this->target->commit();
            } else {
                $this->target->rollBack();
            }
        } catch (\Throwable $exception) {
            $this->target->rollBack();
            throw $exception;
        }

        return $this->counts;
    }

    private function football(): void
    {
        foreach (['users', 'poules', 'matches', 'predictions'] as $table) {
            if (! $this->source->getSchemaBuilder()->hasTable($table)) {
                throw new RuntimeException('Een vereiste voetbalpouletabel ontbreekt: '.$table);
            }
        }

        foreach ($this->source->table('users')->orderBy('id')->cursor() as $user) {
            $this->record('users', $user->id, 'users', function () use ($user) {
                $email = strtolower(trim($user->email));
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Ongeldig e-mailadres bij bronaccount '.$user->id);
                }

                $existing = $this->target->table('users')->whereRaw('LOWER(email) = ?', [$email])->first();
                if ($existing) {
                    if ($existing->deleted_at !== null) {
                        throw new RuntimeException('Bronaccount verwijst naar een verwijderd doelaccount.');
                    }

                    return $existing->id;
                }

                if (! password_get_info($user->password_hash)['algo']) {
                    throw new RuntimeException('Onbekend wachtwoordformaat bij bronaccount '.$user->id);
                }

                return $this->insert('users', [
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
                    'email' => $email,
                    'password' => $user->password_hash,
                    'is_admin' => false,
                    'created_at' => $user->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            });
        }

        foreach ($this->source->table('poules')->orderBy('id')->cursor() as $poule) {
            $this->record('poules', $poule->id, 'tournaments', function () use ($poule) {
                $matches = $this->source->table('matches')->where('poule_id', $poule->id)->get();
                $teamCount = $matches->pluck('home_team')->merge($matches->pluck('away_team'))->unique()->count();
                $startsAt = $poule->starts_at ?? $matches->min('kickoff');
                if (! $startsAt || $teamCount < 2) {
                    throw new RuntimeException('Bronpoule heeft geen bruikbare wedstrijden of startdatum.');
                }

                return $this->insert('tournaments', [
                    'name' => $poule->name, 'description' => $poule->description ?? null,
                    'sport' => 'legacy_football', 'status' => 'open', 'team_count' => $teamCount,
                    'group_count' => 1, 'group_size' => $teamCount,
                    'qualifiers_per_group' => 0, 'knockout_size' => 0,
                    'starts_at' => $startsAt, 'created_at' => now(), 'updated_at' => now(),
                ]);
            });
            $tournamentId = $this->mapped('poules', $poule->id);
            $this->record('groups', $poule->id, 'tournament_groups', fn () => $this->insert('tournament_groups', [
                'tournament_id' => $tournamentId, 'name' => 'A', 'position' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }

        foreach ($this->source->table('matches')->orderBy('id')->cursor() as $match) {
            $this->record('matches', $match->id, 'tournament_matches', function () use ($match) {
                $tournamentId = $this->mapped('poules', $match->poule_id);
                $groupId = $this->mapped('groups', $match->poule_id);
                $teams = [];
                foreach (['home_team', 'away_team'] as $field) {
                    $name = trim($match->$field);
                    if ($name === '') {
                        throw new RuntimeException('Lege teamnaam bij bronwedstrijd '.$match->id);
                    }
                    $sourceId = $match->poule_id.':'.hash('sha256', $name);
                    $teams[$field] = $this->record('teams', $sourceId, 'teams', fn () => $this->insert('teams', [
                        'tournament_id' => $tournamentId, 'tournament_group_id' => $groupId,
                        'name' => $name, 'created_at' => now(), 'updated_at' => now(),
                    ]));
                }

                return $this->insert('tournament_matches', [
                    'tournament_id' => $tournamentId, 'tournament_group_id' => $groupId,
                    'round' => 'group', 'home_team_id' => $teams['home_team'], 'away_team_id' => $teams['away_team'],
                    'kickoff' => $match->kickoff, 'home_score' => $match->home_score, 'away_score' => $match->away_score,
                    'result_entered_at' => $match->result_entered_at,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            });
        }

        foreach ($this->source->table('predictions')->orderBy('id')->cursor() as $prediction) {
            $userId = $this->mapped('users', $prediction->user_id);
            $matchId = $this->mapped('matches', $prediction->match_id);
            $tournamentId = $this->target->table('tournament_matches')->where('id', $matchId)->value('tournament_id');
            $this->record('participants', $tournamentId.':'.$userId, 'tournament_participants', fn () =>
                $this->target->table('tournament_participants')->where('tournament_id', $tournamentId)->where('user_id', $userId)->value('id')
                ?? $this->insert('tournament_participants', [
                    'tournament_id' => $tournamentId, 'user_id' => $userId,
                    'created_at' => now(), 'updated_at' => now(),
                ]));
            $this->record('predictions', $prediction->id, 'predictions', fn () => $this->insert('predictions', [
                'user_id' => $userId, 'tournament_match_id' => $matchId,
                'home_score' => $prediction->home_score, 'away_score' => $prediction->away_score,
                'created_at' => $prediction->created_at ?? now(), 'updated_at' => $prediction->updated_at ?? now(),
            ]));
        }

        foreach ($this->target->table('legacy_import_records')->where([
            'source' => $this->identity, 'source_table' => 'poules',
        ])->pluck('target_id') as $id) {
            $tournament = Tournament::findOrFail($id);
            if ($tournament->matches()->whereNotNull('result_entered_at')->exists()) {
                $tournament->update(['scoring_started_at' => $tournament->scoring_started_at ?? now()]);
                app(ScoreCalculator::class)->recalculateTournament($tournament);
            }
        }
    }

    private function insert(string $table, array $values): int
    {
        return $this->target->table($table)->insertGetId($values);
    }

    private function copySite(string $profile): void
    {
        $tables = $profile === 'charity'
            ? ['events', 'event_costs', 'members', 'admins', 'requests']
            : ['users', 'contents', 'activities', 'products', 'questions', 'question_selects',
                'applications', 'payments', 'orders', 'order_products', 'guests', 'reports',
                'answers', 'mail_settings', 'documents'];
        $ignored = ['migrations', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs',
            'sessions', 'password_reset_tokens'];
        if ($profile === 'charity') {
            $ignored[] = 'users';
        }

        $sourceSchema = $this->source->getSchemaBuilder();
        $targetSchema = $this->target->getSchemaBuilder();
        foreach ($sourceSchema->getTableListing(schemaQualified: false) as $table) {
            if (! in_array($table, [...$tables, ...$ignored], true)
                && $this->source->table($table)->exists()) {
                throw new RuntimeException('Onbekende gevulde brontabel; controleer de mapping: '.$table);
            }
        }

        $deferred = [];
        foreach ($tables as $table) {
            if (! $sourceSchema->hasTable($table)) {
                if ($profile === 'charity' || $table === 'users') {
                    throw new RuntimeException('Vereiste brontabel ontbreekt: '.$table);
                }

                continue;
            }

            if (! $targetSchema->hasTable($table)) {
                throw new RuntimeException('Voer eerst de doelmigraties uit: '.$table);
            }

            $sourceColumns = $sourceSchema->getColumnListing($table);
            $targetColumns = $targetSchema->getColumnListing($table);
            if (array_diff($sourceColumns, $targetColumns) !== []) {
                throw new RuntimeException('Bronkolommen hebben nog geen doelmapping: '.$table);
            }

            $previous = $this->target->table('legacy_import_records')->where([
                'source' => $this->identity, 'source_table' => $table,
            ])->pluck('target_id');
            if ($this->target->table($table)->whereNotIn('id', $previous)->exists()) {
                throw new RuntimeException('Doeltabel is niet leeg of bevat andere gegevens: '.$table);
            }

            $nullable = collect($targetSchema->getColumns($table))
                ->where('nullable', true)->pluck('name')->all();
            $foreignKeys = $targetSchema->getForeignKeys($table);
            foreach ($this->source->table($table)->orderBy('id')->cursor() as $row) {
                $values = (array) $row;
                $relations = [];
                foreach ($foreignKeys as $foreignKey) {
                    foreach ($foreignKey['columns'] as $column) {
                        if (($values[$column] ?? null) !== null && in_array($column, $nullable, true)) {
                            $relations[] = [$table, $row->id, $column, $values[$column]];
                            $values[$column] = null;
                        }
                    }
                }

                if ($table === 'requests' && ! empty($values['iban'])) {
                    $key = config('legacy.charity_key');
                    if (! is_string($key) || $key === '') {
                        throw new RuntimeException('Stel LEGACY_CHARITY_KEY lokaal in voor versleutelde bankgegevens.');
                    }
                    if (str_starts_with($key, 'base64:')) {
                        $key = base64_decode(substr($key, 7), true);
                    }
                    $encrypter = new Encrypter($key, config('legacy.charity_cipher', 'AES-256-CBC'));
                    $values['iban'] = Crypt::encryptString($encrypter->decryptString($values['iban']));
                }

                $this->record($table, $row->id, $table, function () use ($table, $values, $row, $relations, &$deferred) {
                    $this->target->table($table)->insert($values);
                    array_push($deferred, ...$relations);

                    return $row->id;
                });
            }
        }

        foreach ($deferred as [$table, $id, $column, $value]) {
            $this->target->table($table)->where('id', $id)->update([$column => $value]);
        }

        if ($profile === 'charity') {
            $missingRecipient = $this->target->table('requests')
                ->leftJoin('members', 'members.id', '=', 'requests.employee_recipient')
                ->whereNull('members.id')->exists();
            if ($missingRecipient) {
                throw new RuntimeException('Een Lief & Leed-aanvraag verwijst naar een ontbrekend personeelslid.');
            }
        }
    }

    private function record(string $sourceTable, string|int $sourceId, string $targetTable, callable $create): int
    {
        $existing = $this->target->table('legacy_import_records')->where([
            'source' => $this->identity, 'source_table' => $sourceTable, 'source_id' => (string) $sourceId,
        ])->first();

        if ($existing) {
            if (! $this->target->table($existing->target_table)->where('id', $existing->target_id)->exists()) {
                throw new RuntimeException('Een eerder gemigreerd doelrecord ontbreekt.');
            }

            return $existing->target_id;
        }

        $targetId = $create();
        $this->target->table('legacy_import_records')->insert([
            'source' => $this->identity, 'source_table' => $sourceTable, 'source_id' => (string) $sourceId,
            'target_table' => $targetTable, 'target_id' => $targetId, 'created_at' => now(),
        ]);
        $this->counts[$targetTable] = ($this->counts[$targetTable] ?? 0) + 1;

        return $targetId;
    }

    private function mapped(string $table, string|int $sourceId): int
    {
        $id = $this->target->table('legacy_import_records')->where([
            'source' => $this->identity, 'source_table' => $table, 'source_id' => (string) $sourceId,
        ])->value('target_id');

        if ($id === null) {
            throw new RuntimeException('Ontbrekende bronrelatie: '.$table.' #'.$sourceId);
        }

        return $id;
    }
}