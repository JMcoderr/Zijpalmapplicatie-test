<?php

namespace App\Console\Commands;

use App\Services\Migration\LegacySiteImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportLegacySite extends Command
{
    protected $signature = 'legacy:import {profile : football, charity of zijpalm}
                            {--source= : Naam van de aparte brondatabaseverbinding}
                            {--apply : Sla de gecontroleerde import op}
                            {--force : Bevestig de back-up voor niet-interactieve uitvoering}';

    protected $description = 'Migreer een oude website; zonder --apply wordt alles teruggedraaid';

    public function handle(LegacySiteImporter $importer): int
    {
        if ($this->option('apply') && ! $this->option('force')
            && ! $this->confirm('Is er een gecontroleerde back-up van de doeldatabase?')) {
            $this->warn('Geannuleerd. Er zijn geen gegevens gewijzigd.');

            return self::FAILURE;
        }

        try {
            $counts = $importer->run(
                (string) $this->argument('profile'),
                (string) $this->option('source'),
                (bool) $this->option('apply'),
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Migratie afgebroken. Alle wijzigingen in deze import zijn teruggedraaid.');
            $this->line('Controleer het afgeschermde applicatielog voor de oorzaak; deel geen persoonsgegevens of sleutels.');

            return self::FAILURE;
        }

        $this->table(['Tabel', 'Verwerkt'], collect($counts)->map(fn ($count, $table) => [$table, $count])->all());
        $this->info($this->option('apply')
            ? 'Import opgeslagen. Controleer aantallen, accounts en bijlagen voordat de oude website wordt uitgezet.'
            : 'Proefrun geslaagd. Er zijn geen gegevens opgeslagen.');

        return self::SUCCESS;
    }
}