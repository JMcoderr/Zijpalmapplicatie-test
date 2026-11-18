<?php

namespace App\Console\Commands;

use App\Models\Charity\Member;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;

class CheckMemberImport extends Command
{
    protected $signature = 'members:check-import
                            {--repair : Verwijder unieke indexen op alleen het e-mailadres}
                            {--force : Voer het herstel uit zonder bevestiging}';

    protected $description = 'Controleer de database voor ledenimports en herstel desgewenst de beperking op gedeelde e-mailadressen';

    public function handle(): int
    {
        $connection = (new Member)->getConnection();
        $schema = $connection->getSchemaBuilder();

        $this->line('Applicatiemap: '.base_path());
        $this->line('Databaseverbinding: '.$connection->getName());
        $this->line('Database: '.$connection->getDatabaseName());

        if (! $schema->hasTable('members')) {
            $this->error('De tabel members ontbreekt. Controleer of dit de juiste applicatie en database zijn.');

            return self::FAILURE;
        }

        $emailIndexes = array_filter(
            $schema->getIndexes('members'),
            fn (array $index) => $index['unique'] && $index['columns'] === ['email'],
        );

        if ($emailIndexes === []) {
            $this->info('OK: de database staat hetzelfde e-mailadres bij verschillende leden-ID\'s toe.');

            return self::SUCCESS;
        }

        foreach ($emailIndexes as $index) {
            $this->warn('Blokkerende unieke e-mailindex: '.$index['name']);
        }

        if (! $this->option('repair')) {
            $this->error('Maak eerst een databaseback-up. Voer daarna in deze applicatiemap php artisan members:check-import --repair uit.');

            return self::FAILURE;
        }

        $this->warn('Het herstel verwijdert alleen de unieke e-mailbeperking. Leden, ID\'s en aanvragen blijven behouden.');

        if (! $this->option('force') && ! $this->confirm('Heb je een databaseback-up en wil je deze database herstellen?')) {
            $this->warn('Herstel geannuleerd. De database is niet gewijzigd.');

            return self::FAILURE;
        }

        foreach ($emailIndexes as $index) {
            $schema->table('members', fn (Blueprint $table) => $table->dropUnique($index['name']));
        }

        Log::notice('Unieke e-mailindexen voor leden verwijderd via members:check-import.', [
            'connection' => $connection->getName(),
            'database' => $connection->getDatabaseName(),
            'indexes' => array_column($emailIndexes, 'name'),
        ]);

        if (array_filter(
            $schema->getIndexes('members'),
            fn (array $index) => $index['unique'] && $index['columns'] === ['email'],
        ) !== []) {
            $this->error('De unieke e-mailbeperking is nog aanwezig. Controleer de database voordat je opnieuw importeert.');

            return self::FAILURE;
        }

        $this->info('Herstel voltooid: hetzelfde e-mailadres is toegestaan bij verschillende leden-ID\'s. Importeer de volledige ledenlijst opnieuw.');

        return self::SUCCESS;
    }
}