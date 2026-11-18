<?php
// Routes for the tournament module (the old voetbalpoule website).

use App\Http\Controllers\Tournament\ParticipantController;
use App\Http\Controllers\Tournament\PredictionController;
use App\Http\Controllers\Tournament\RankingController;
use App\Http\Controllers\Tournament\ResultController;
use App\Http\Controllers\Tournament\TournamentAdminController;
use App\Http\Controllers\Tournament\TournamentController;
use Illuminate\Support\Facades\Route;

Route::prefix('voetbalpoule')->name('tournament.')->group(function () {

    Route::get('/', [TournamentController::class, 'index'])->name('index');

    // Admin: creating tournaments, checking the schedule, entering results
    Route::middleware('admin')->prefix('beheer')->name('admin.')->group(function () {
        Route::get('/', [TournamentAdminController::class, 'index'])->name('index');
        Route::post('/', [TournamentAdminController::class, 'store'])->name('store');

        Route::get('{tournament}/wedstrijden', [TournamentAdminController::class, 'matches'])->name('matches');
        Route::put('wedstrijd/{match}', [TournamentAdminController::class, 'updateMatch'])->name('updateMatch');
        Route::put('{tournament}/team', [TournamentAdminController::class, 'updateTeam'])->name('updateTeam');
        Route::delete('{tournament}', [TournamentAdminController::class, 'destroy'])->name('destroy');

        Route::get('{tournament}/uitslagen', [ResultController::class, 'index'])->name('results');
        Route::post('wedstrijd/{match}/uitslag', [ResultController::class, 'store'])->name('storeResult');

        Route::get('{tournament}/deelnemers', [ParticipantController::class, 'index'])->name('participants');
        Route::put('{tournament}/deelnemer/{user}/betaald', [ParticipantController::class, 'togglePaid'])->name('togglePaid');
        Route::delete('{tournament}/deelnemer/{user}', [ParticipantController::class, 'destroy'])->name('removeParticipant');
    });

    // Participants
    Route::middleware('auth')->group(function () {
        Route::get('mijn-voorspellingen', [PredictionController::class, 'index'])->name('predictions');
        Route::post('wedstrijd/{match}/voorspelling', [PredictionController::class, 'store'])->name('prediction.store');
        Route::post('{tournament}/inschrijven', [ParticipantController::class, 'join'])->name('join');
    });

    // Public pages
    Route::get('{tournament}/stand', [RankingController::class, 'index'])->name('ranking');
    Route::get('{tournament}/stand/excel', [RankingController::class, 'excel'])->name('ranking.excel');
    Route::get('{tournament}/stand/pdf', [RankingController::class, 'pdf'])->name('ranking.pdf');
    Route::get('{tournament}/knockout', [TournamentController::class, 'bracket'])->name('bracket');
    Route::get('{tournament}', [TournamentController::class, 'show'])->name('show');
});
