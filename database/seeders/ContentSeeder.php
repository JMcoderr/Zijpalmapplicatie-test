<?php

namespace Database\Seeders;

use App\FileType;
use App\Models\Content;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seedContent = static function (array $attributes): Content {
            return Content::updateOrCreate(['name' => $attributes['name']], $attributes);
        };
        $email = config('mail.bestuur.address');
        $name = config('mail.bestuur.name');

        // Default
        // $seedContent([
        //     'type' => 'text',
        //     'name' => 'home',
        //     'title' => 'Home',
        //     'text' => 'Welkom',
        //     'filePath' => null,
        //     'fileType' => null,
        // ]);

        // Content for the homepage
        $seedContent([
            'type' => 'text',
            'name' => 'homepage-banner',
            'title' => 'Personeelsvereniging Zijpalm',
            'text' => '{"time":1746621250787,"blocks":[{"id":"pCtcQsZTRb","type":"header","data":{"text":"Personeelsvereniging Zijpalm","level":4}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'text',
            'name' => 'homepage-info',
            'title' => 'Wie zijn wij?',
            'text' => '{"time":1746621406186,"blocks":[{"id":"ZBzNB6msuM","type":"paragraph","data":{"text":"De personeelsvereniging ‘Zijpalm’ is opgericht in 1979 en is een actieve vereniging die regelmatig activiteiten organiseert voor haar leden. Voorbeelden zijn diverse (wekelijkse) sportactiviteiten, culturele voorstellingen, creatieve workshops, diverse clinic’s, stedentrips, activiteiten in de natuur, etc."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        // In order to grab the email provided in the env, it has to be concatenated here first.
        $activityIdeaText = '{"time":1746621785856,"blocks":[{"id":"EZ9CHsp0HM","type":"paragraph","data":{"text":"Dien je idee in!"}}],"version":"2.31.0-rc.7"}';

        $seedContent([
            'type' => 'text',
            'name' => 'homepage-activity-idea',
            'title' => 'Heb jij nou een goed idee voor een nieuwe activiteit?',
            'text' => $activityIdeaText,
            'filePath' => null,
            'fileType' => null,
        ]);

        // Content for the "Lid Worden" page
        $seedContent([
            'type' => 'text',
            'name' => 'lid-worden-banner',
            'title' => 'Lid Worden',
            'text' => null,
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'text',
            'name' => 'lid-worden-info',
            'title' => 'Kom mee',
            'text' => '{"time":1746775105514,"blocks":[{"id":"sPqs5ONqKY","type":"paragraph","data":{"text":"Als werknemer kan je lid worden van de personeelsvereniging."}},{"id":"q9FixML_t0","type":"paragraph","data":{"text":"De contributie wordt bepaald door de organisatie."}},{"id":"8zb0XWjxH-","type":"paragraph","data":{"text":"Je lidmaatschap volgt de afspraken van de organisatie."}},{"id":"iQi0bNpDWu","type":"paragraph","data":{"text":"Je aanmelden of afmelden gaat via het HR-portaal van je organisatie.\n"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'text',
            'name' => 'lid-worden-aanmelden',
            'title' => 'Aanmelden inhuur / stagiaire / pensionado',
            'text' => '{"time":1764775101848,"blocks":[{"id":"qA8r0sKHHd","type":"paragraph","data":{"text":"Wanneer u zich aanmeldt om lid te worden van Zijpalm, wordt de eerste betaling bepaald door het aantal resterende maanden tot 1 januari van het volgende jaar."}},{"id":"oipAo3IXeQ","type":"paragraph","data":{"text":"De jaarlijkse contributie bedraagt €24,-"}},{"id":"DxZeKTX9eR","type":"paragraph","data":{"text":"Vanaf de maand van aanmelding ontvangt u een factuur tot het einde van het jaar."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        // Content for the "Lief en leed" page
        $seedContent([
            'type' => 'text',
            'name' => 'lief-en-leed-banner',
            'title' => 'Lief & Leed',
            'text' => null,
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'text',
            'name' => 'lief-en-leed-info',
            'title' => 'Over Lief en Leed',
            'text' => '{"time":1764773823533,"blocks":[{"id":"nU2vJNGD8P","type":"paragraph","data":{"text":"Stichting Lief en Leed valt onder de personeelsvereniging Zijpalm en is bedoeld voor medewerkers van de aangesloten organisatie. Een eventuele bijdrage wordt bepaald door de afspraken van die organisatie."}},{"id":"Hm8Q8yVN_c","type":"paragraph","data":{"text":"Lief en Leed betaalt bij diverse gebeurtenissen een bedrag uit aan degene die voor een collega een attentie verzorgt. Je kunt je via deze pagina aanmelden of afmelden als deelnemer."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'text',
            'name' => 'lief-en-leed-bijdragen',
            'title' => 'Welke bijdragen worden uit de lief en leedpot betaald?',
            'text' => '{"time":1747048748651,"blocks":[{"id":"baY04kfA3G","type":"list","data":{"style":"unordered","meta":{},"items":[{"content":"Voor geboorte € 25,00 ","meta":{},"items":[]},{"content":"Voor ziekte (na de eerste drie weken of bij acute ziekenhuisopname) € 25,00 ","meta":{},"items":[]},{"content":"Bij langdurige ziekte herhaling na iedere drie maanden € 25,00 (bij 100% ziekte!) ","meta":{},"items":[]},{"content":"Voor een huwelijk of geregistreerd partnerschap € 40,00 ","meta":{},"items":[]},{"content":"Bij einde van het dienstverband, na de afgesproken lidmaatschapsperiode, € 25,00 ","meta":{},"items":[]},{"content":"Voor dertigste verjaardag € 25,00 (nieuw vanaf 14-04-2023) ","meta":{},"items":[]},{"content":"Voor veertigste verjaardag € 25,00 (nieuw vanaf 14-04-2023) ","meta":{},"items":[]},{"content":"Voor vijftigste verjaardag € 25,00 ","meta":{},"items":[]},{"content":"Voor zestigste verjaardag € 25,00 (nieuw vanaf 19-04-2024) ","meta":{},"items":[]},{"content":"Voor vijfenzestigste verjaardag € 25,00 ","meta":{},"items":[]},{"content":"Voor 12½- of 25-jarig ambtsjubileum € 25,00 ","meta":{},"items":[]},{"content":"Voor 12½- of 25-jarig huwelijk of geregistreerd partnerschap € 25,00 ","meta":{},"items":[]},{"content":"Voor veertigjarig huwelijk of geregistreerd partnerschap € 40,00 ","meta":{},"items":[]},{"content":"Voor veertigjarig ambtsjubileum € 40,00 ","meta":{},"items":[]},{"content":"Bij overlijden personeelslid of huisgenoot € 50,00","meta":{},"items":[]}]}},{"id":"wUPVzVOrus","type":"paragraph","data":{"text":"Wil je weten of iemand meedoet ga dan naar de pagina met deelnemende medewerkers Lief en Leed om het op te zoeken. Contactpersonen: Is er een collega die in aanmerking komt voor een bijdrage, neem dan binnen drie maanden contact op met de beheerder van je organisatie."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'text',
            'name' => 'lief-en-leed-deelnemers',
            'title' => 'Deelnemers',
            'text' => '{"time":1747051730877,"blocks":[{"id":"giXP94K37_","type":"paragraph","data":{"text":"Wil je weten of iemand meedoet kijk dan op deze pagina en zoek de link naar deelnemende medewerkers Lief en Leed."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'text',
            'name' => 'lief-en-leed-contact',
            'title' => 'Aanvraagformulier',
            'text' => '{"time":1764774008671,"blocks":[{"id":"v8y7Ks3ib0","type":"paragraph","data":{"text":"Medewerkers van de aangesloten organisatie kunnen een aanvraag doen voor een lid van de Lief & Leed. Klik hieronder om de aanvraag te starten."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        // Content for the "Info" page
        $seedContent([
            'type' => 'text',
            'name' => 'info-banner',
            'title' => 'Over ons',
            'text' => null,
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'text',
            'name' => 'info-over',
            'title' => 'Over Zijpalm',
            'text' => '{"time":1746626262753,"blocks":[{"id":"cRr8wBrFBN","type":"paragraph","data":{"text":"Welkom op de website van de Personeelsvereniging Zijpalm."}},{"id":"gCmJgCYgJu","type":"paragraph","data":{"text":"                        De personeelsvereniging ‘Zijpalm’ is opgericht in 1979 en is een dynamische en actieve vereniging die regelmatig activiteiten organiseert voor haar leden, waarbij meestal één introducé is toegestaan. Voorbeelden van activiteiten die de personeelsvereniging organiseert zijn: diverse wekelijkse sportactiviteiten, culturele voorstellingen, creatieve workshops, diverse clinic’s, bezoek kerstmarkt, een buitenlandtrip, het Sinterklaasfeest en nog veel meer!"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'text',
            'name' => 'info-bijzonder-verlof',
            'title' => 'Buitengewoon verlof',
            'text' => '{"time":1746626248363,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Voor 1 daagse activiteiten heb je recht op 1 dag buitengewoon/bijzonder verlof. Vergeet niet daar gebruik van te maken. Klik hieronder voor de komende activiteiten."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'text',
            'name' => 'afmelden-zijpalm',
            'title' => 'Afmelden Zijpalm',
            'text' => '{"time":1748008383911,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Om af te melden voor de Zijpalm, druk op onderstaande knop"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'text',
            'name' => 'afmelden-zijpalm-medewerker',
            'title' => 'Afmelden Zijpalm',
            'text' => '{"time":1748003619977,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Afmelden kan via het HR-portaal van je organisatie."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        // Bestuur
        $seedContent([
            'type' => 'bestuurslid',
            'name' => 'bestuurslid-0',
            'title' => 'Example Board Member 1',
            'text' => 'Penningmeester',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'bestuurslid',
            'name' => 'bestuurslid-1',
            'title' => 'Example Board Member 2',
            'text' => 'Bestuurslid',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'bestuurslid',
            'name' => 'bestuurslid-2',
            'title' => 'Example Board Member 3',
            'text' => 'Voorzitter',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'bestuurslid',
            'name' => 'bestuurslid-3',
            'title' => 'Example Board Member 4',
            'text' => 'Bestuurslid',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'bestuurslid',
            'name' => 'bestuurslid-4',
            'title' => 'Example Board Member 5',
            'text' => 'Secretaris',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'bestuurslid',
            'name' => 'bestuurslid-5',
            'title' => 'Example Board Member 6',
            'text' => 'Bestuurslid',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'bestuurslid',
            'name' => 'bestuurslid-6',
            'title' => 'Example Board Member 7',
            'text' => 'Bestuurslid',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'bestuurslid',
            'name' => 'bestuurslid-7',
            'title' => 'Example Board Member 8',
            'text' => 'Bestuurslid',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'bestuurslid',
            'name' => 'bestuurslid-8',
            'title' => 'Example Board Member 9',
            'text' => 'Bestuurslid',
            'filePath' => null,
            'fileType' => null,
        ]);

        // Files
        $seedContent([
            'type' => 'file',
            'name' => 'background',
            'title' => null,
            'text' => null,
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'file',
            'name' => 'huishoudelijk-reglement',
            'title' => 'Huishoudelijk reglement',
            'text' => null,
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'file',
            'name' => 'privacy',
            'title' => 'Privacy',
            'text' => null,
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'file',
            'name' => 'statuten',
            'title' => 'Statuten',
            'text' => null,
            'filePath' => null,
            'fileType' => null,
        ]);

        // Jaarverslagen
        $seedContent([
            'type' => 'year-report',
            'name' => 'jaarverslag-2023',
            'title' => 'Jaarverslag 2023',
            'text' => '2023',
            'filePath' => null,
            'fileType' => null,
        ]);

        // Emails
        $seedContent([
            'type' => 'email',
            'name' => 'email-nieuw-lid',
            'title' => 'Welkom bij Zijpalm!',
            'text' => '{"time":1750075440441,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Leuk dat jij lid bent geworden van Zijpalm."}},{"id":"UIZfWpOyZC","type":"paragraph","data":{"text":"U kunt uw wachtwoord instellen door op de onderstaande knop te klikken:"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'email',
            'name' => 'email-bestelling-betaald',
            'title' => 'Je bestelling is betaald!',
            'text' => '{"time":1750075440441,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Je bestelling is betaald!"}},{"id":"UIZfWpOyZC","type":"paragraph","data":{"text":"Hieronder vind je de details van je bestelling:"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::updateOrCreate([
            'name' => 'email-betaling-mislukt',
        ], [
            'type' => 'email',
            'title' => 'Betaling mislukt',
            'text' => '{"time":1763000000000,"blocks":[{"type":"paragraph","data":{"text":"Uw betaling is helaas mislukt."}},{"type":"paragraph","data":{"text":"Probeer het alstublieft opnieuw via de betaallink."}}],"version":"2.31.0"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'email',
            'name' => 'email-activiteit-aangemeld',
            'title' => 'Bevestiging aanmelding',
            'text' => '{"time":1750075440441,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Leuk dat jij je hebt aangemeld. Hierbij bevestigen wij dat jouw aanmelding is binnengekomen."}},{"id":"UIZfWpOyZC","type":"paragraph","data":{"text":"Zie de details van uw aanmelding hieronder:"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'email',
            'name' => 'email-activiteit-aangemeld-reserve',
            'title' => null,
            'text' => '{"time":1750667926653,"blocks":[{"id":"suHhoLsGwp","type":"paragraph","data":{"text":"U bent op de <mark class=\"cdx-marker\">reservelijst </mark>geplaatst! Als er een plek voor u vrij komt krijgt u een email met een betaallink die binnen 2 werkdagen betaalt dient te worden om mee te doen aan de activiteit"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'email',
            'name' => 'email-activiteit-afgemeld',
            'title' => 'U bent succesvol afgemeld voor: ',
            'text' => '{"time":1750075440441,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Wat jammer dat jij je hebt afgemeld. Hierbij bevestigen wij dat je succesvol bent afgemeld."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'email',
            'name' => 'email-reserve-upgrade',
            'title' => 'U mag meedoen met:',
            'text' => '{"time":1750425193847,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Joepie! U mag mee met de activiteit."}},{"id":"PYmkk9GUu1","type":"paragraph","data":{"text":"Betaal het bedrag binnen 2 werkdagen om mee te mogen. Als u niet binnen 2 werkdagen betaalt vervalt de reservering"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'email',
            'name' => 'email-bestuur-nieuwe-leden',
            'title' => 'Nieuwe Zijpalm leden',
            'text' => '{"time":1750425193847,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"De volgende nieuwe leden hebben zich aangemeld voor Zijpalm:"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'email',
            'name' => 'email-bestuur-nieuwe-bestelling',
            'title' => 'Nieuwe Bestelling',
            'text' => '{"time":1750075440441,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Er is een nieuwe bestelling aangemaakt."}},{"id":"UIZfWpOyZC","type":"paragraph","data":{"text":"Hieronder vindt u de details van de bestelling:"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'email',
            'name' => 'email-bestuur-activiteit-aanmeldingen',
            'title' => 'Aanmeldingen voor activiteit:',
            'text' => '{"time":1750075440441,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Hierbij alle aanmeldingen voor de activiteit"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'email',
            'name' => 'email-nieuwe-activiteit',
            'title' => 'Er is een nieuwe activiteit aangemaakt!',
            'text' => '',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'email',
            'name' => 'email-herinnering-activiteit-deelnemers',
            'title' => 'Herinnering: Een activiteit start binnenkort!',
            'text' => '',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'email',
            'name' => 'email-herinnering-activiteit-niet-deelnemers',
            'title' => 'Herinnering: Meld u aan!',
            'text' => '{"time":1750075440441,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Herinnering: schrijf u nu in voor de volgende activiteit!"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        $seedContent([
            'type' => 'email',
            'name' => 'email-reset-wachtwoord',
            'title' => 'Hallo',
            'text' => '{"time":1769171792368,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Je ontvangt deze e-mail omdat we een wachtwoordresetaanvraag voor je account hebben ontvangen."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::updateOrCreate([
            'name' => 'email-toekomstige-activiteiten',
        ], [
            'type' => 'email',
            'title' => 'Email Toekomstige Activiteiten',
            'text' => '{"time":1775600000000,"blocks":[{"type":"paragraph","data":{"text":"Beste leden,"}},{"type":"paragraph","data":{"text":"Hieronder vinden jullie de komende activiteiten van Zijpalm."}}],"version":"2.31.0"}',
            'filePath' => null,
            'fileType' => null,
        ]);
    }
}
