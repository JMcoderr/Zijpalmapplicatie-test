<?php
// Prefixes for the identifiers inside the SEPA export file.

namespace App\Enums\Charity;

enum ExportPrefix: string
{
    case MSG = 'MSG';
    case PMNTINFO = 'PMT';
    case ENDTOEND = 'E2E';
}
