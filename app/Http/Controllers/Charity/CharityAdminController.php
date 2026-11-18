<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;

use App\Models\Charity\CharityAdmin as Admin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CharityAdminController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return view('charity.admin.admins', [
            'admins' => Admin::all()
        ]);
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
        $validated = $request->validate([
            'employee' => ['required', 'email', new \App\Rules\ValidDomainEmail, 'unique:admins,employee'],
        ]);

        Admin::create(['employee' => strtolower($validated['employee'])]);

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Admin succesvol toegevoegd.'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Admin $admin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Admin $admin)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Admin $admin)
    {
        //
        $validated = $request->validate([
            'super' => 'required|boolean',
        ]);

        if ($admin->super && !$validated['super'])
            throw ValidationException::withMessages(['super_delete' => 'Een superadmin kan niet worden teruggezet naar admin.']);

        $admin->update($validated);

        if ($admin->employee === $request->session()->get('magic.email') && !$admin->super) {
            session()->flush();
            session()->regenerate();
            session()->regenerateToken();
            session()->invalidate();
        }
//        if ($admin->)

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Admin succesvol aangepast.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Admin $admin)
    {
        $currentEmail = strtolower((string) $request->session()->get('magic.email'));

        if (strtolower($admin->employee) === $currentEmail) {
            throw ValidationException::withMessages(['super_delete' => 'Je kunt je eigen adminaccount niet verwijderen.']);
        }

        if ($admin->super) {
            throw ValidationException::withMessages(['super_delete' => 'Superadmins kunnen niet worden verwijderd.']);
        }

        $admin->delete();

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Admin succesvol verwijderd.'
        ]);
    }
}
