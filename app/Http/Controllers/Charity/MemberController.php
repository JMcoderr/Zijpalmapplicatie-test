<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;
use App\Exports\Charity\MemberExport;
use App\Imports\Charity\MemberImport;
use App\Models\Charity\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return view('charity.admin.members', [
            'members' => $this->filteredMembers($request)
                ->with(['requests.eventCost.event'])
                ->paginate(10)
                ->onEachSide(1)
                ->withQueryString(),
            'filters' => $request->only(['search', 'deleted']),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'deleted' => ['nullable', 'string', 'in:0,1,2,3'],
        ]);

        return Excel::download(
            new MemberExport($this->filteredMembers($request)),
            'ledenlijst_'.now()->format('Ymd_His').'.xlsx',
            ExcelFormat::XLSX,
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:2048'
        ], [
            'file.required' => 'Kies een Excelbestand om te importeren.',
            'file.mimes' => 'Gebruik een Excelbestand (.xlsx of .xls). Sla een .ods- of .csv-bestand eerst op als .xlsx.',
            'file.max' => 'Het Excelbestand mag maximaal 2 MB groot zijn.',
        ]);

        $connection = (new Member)->getConnection();

        try {
            $connection->transaction(fn () => Excel::import(new MemberImport, $request->file('file')));
        } catch (UniqueConstraintViolationException $exception) {
            if (! str_contains($exception->getMessage(), 'members_email_unique')
                && ! str_contains($exception->getMessage(), 'UNIQUE constraint failed: members.email')) {
                throw $exception;
            }

            Log::warning('Ledenimport afgebroken: de database blokkeert nog gedeelde e-mailadressen.', [
                'connection' => $connection->getName(),
                'database' => $connection->getDatabaseName(),
            ]);

            throw ValidationException::withMessages([
                'file' => 'De ledenlijst is niet geimporteerd omdat de database dubbele e-mailadressen nog blokkeert. Laat de beheerder op de server "php artisan members:check-import --repair" uitvoeren. Er zijn geen leden gewijzigd.',
            ]);
        }

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Leden succesvol opgeslagen'
        ]);
    }

    private function filteredMembers(Request $request): Builder
    {
        $search = $request->input('search');

        $members = match ($request->input('deleted')) {
            '1' => Member::query(),
            '2' => Member::query()->onlyTrashed(),
            '3' => Member::query()->withTrashed(),
            default => Member::query()->allowedMembers(),
        };

        return $members
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', '=', $search)
                        ->orWhere('email', 'like', "%$search%");
                });
            })
            ->orderBy('id');
    }

    /**
     * Display the specified resource.
     */
    public function show(Member $member)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Member $member)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Member $member)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Member $member)
    {
        //
    }
}
