<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;
use App\Rules\ValidIban;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->merge(['iban' => ValidIban::normalize($request->input('iban'))]);
        $request->validate([
            'iban' => ['required', 'string', 'max:34', new ValidIban],
        ], [
            'iban.required' => 'Vul een IBAN in om het te controleren.',
            'iban.string' => 'Vul het IBAN in als tekst.',
            'iban.max' => 'Een IBAN bevat maximaal 34 tekens zonder spaties.',
        ]);

        return response()->json([
            'format_valid' => true,
            'account_verified' => false,
            'holder_verified' => false,
            'message' => 'Formaat en controlegetal zijn geldig. Het bestaan van de rekening en de naam van de rekeninghouder zijn niet geverifieerd.',
        ])->header('Cache-Control', 'no-store, private');
    }
}