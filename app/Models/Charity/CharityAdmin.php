<?php
// Admin of the Lief en Leed module. Separate from the regular Zijpalm admins.

namespace App\Models\Charity;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharityAdmin extends Model
{
    use HasFactory;

    protected $table = 'admins';

    protected $fillable = [
        'employee',
        'super',
    ];

    protected $casts = [
        'super' => 'boolean',
    ];

    // The database stores a timestamp, but in code we work with a yes/no.
    public function setSuperAttribute($value): void
    {
        $this->attributes['super'] = $value ? Carbon::now() : null;
    }
}
