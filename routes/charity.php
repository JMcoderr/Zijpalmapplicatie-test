<?php
// Routes for the Lief en Leed module. This used to run on its own domain,
// but now lives under /lief-en-leed inside the Zijpalm website.

use App\Enums\Charity\EventCostStatus;
use App\Http\Controllers\Charity\CharityAdminController;
use App\Http\Controllers\Charity\BankAccountController;
use App\Http\Controllers\Charity\EventController;
use App\Http\Controllers\Charity\EventCostsController;
use App\Http\Controllers\Charity\MagicLoginController;
use App\Http\Controllers\Charity\MemberController;
use App\Http\Controllers\Charity\RapportController;
use App\Http\Controllers\Charity\SepaController;
use App\Http\Controllers\Charity\SupportRequestController;
use App\Models\Charity\EventCost;
use App\Models\Charity\Member;
use Illuminate\Support\Facades\Route;

Route::prefix('lief-en-leed')->name('charity.')->group(function () {

    // Logging in uses a magic link, so there is no password
    Route::middleware('magic.guest')->group(function () {
        Route::get('inloggen', [MagicLoginController::class, 'login'])->name('login');
        Route::post('inloggen', [MagicLoginController::class, 'sendLink'])
            ->name('magic-login.send-link')
            ->middleware('throttle:4,1');
        Route::get('magic-login', [MagicLoginController::class, 'verify'])
            ->name('magic-login.verify')
            ->middleware(['throttle:10,1', 'signed']);
    });

    Route::get('uitloggen', [MagicLoginController::class, 'logout'])
        ->middleware('magic.valid')
        ->name('logout');

    // The request form for employees
    Route::middleware('magic.valid')->group(function () {
        Route::post('bankrekening/controleren', BankAccountController::class)
            ->middleware('throttle:12,1')->name('bank-account.check');

        Route::get('aanvraag', function () {
            return view('charity.request', [
                'events' => EventCost::with('event')
                    ->withStatus(EventCostStatus::Active)
                    ->orderBy('event_id')
                    ->get(),
                    'members' => Member::query()->allowedMembers()->orderBy('full_name')->get(),
            ]);
        })->name('requests');

        Route::resource('aanvragen', SupportRequestController::class)
            ->parameters(['aanvragen' => 'requestModel'])
            ->only('store')
            ->names(['store' => 'requests.store']);
    });

    // Managing requests
    Route::name('admin.')->middleware(['magic.valid', 'magic.admin'])->group(function () {
        Route::resource('aanvragen', SupportRequestController::class)
            ->parameters(['aanvragen' => 'requestModel'])
            ->except('store')
            ->names([
                'index' => 'requests.index',
                'create' => 'requests.create',
                'show' => 'requests.show',
                'edit' => 'requests.edit',
                'update' => 'requests.update',
                'destroy' => 'requests.destroy',
            ]);

        // Only super admins may manage amounts, members and payouts
        Route::middleware('magic.super')->group(function () {
            Route::get('leden/export', [MemberController::class, 'export'])->name('members.export');

            Route::resource('gebeurtenissen', EventController::class)
                ->only(['index', 'store'])
                ->names(['index' => 'events.index', 'store' => 'events.store']);

            Route::resource('gebeurtenissen.kosten', EventCostsController::class)
                ->scoped(['gebeurtenissen' => 'id'])
                ->only(['store', 'update', 'destroy'])
                ->parameters(['gebeurtenissen' => 'event', 'kosten' => 'eventCost'])
                ->names([
                    'store' => 'events.costs.store',
                    'update' => 'events.costs.update',
                    'destroy' => 'events.costs.destroy',
                ]);

            Route::resource('beheerders', CharityAdminController::class)
                ->parameters(['beheerders' => 'admin'])
                ->names([
                    'index' => 'admins.index',
                    'create' => 'admins.create',
                    'store' => 'admins.store',
                    'show' => 'admins.show',
                    'edit' => 'admins.edit',
                    'update' => 'admins.update',
                    'destroy' => 'admins.destroy',
                ]);

            Route::resource('leden', MemberController::class)
                ->parameters(['leden' => 'member'])
                ->names([
                    'index' => 'members.index',
                    'create' => 'members.create',
                    'store' => 'members.store',
                    'show' => 'members.show',
                    'edit' => 'members.edit',
                    'update' => 'members.update',
                    'destroy' => 'members.destroy',
                ]);

            Route::name('download.')->prefix('download')->group(function () {
                Route::get('sepa', [SepaController::class, 'sepa'])->name('sepa');
                Route::get('rapport', [RapportController::class, 'rapport'])->name('rapport');
                Route::post('valideer-sepa', [SepaController::class, 'validateDebtor'])->name('validate-sepa');
                Route::post('valideer-rapport', [RapportController::class, 'validateRapportDate'])->name('validate-rapport');
            });
        });
    });
});
