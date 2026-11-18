<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Charity\MagicRequest;
use App\Mail\Charity\MagicLoginMail;
use App\Models\Charity\CharityAdmin as Admin;
use App\Services\MagicLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class MagicLoginController extends Controller
{
    public function __construct(protected MagicLinkService $magic)
    {
    }

    public function login()
    {
        return view('charity.auth.login');
    }

    /**
     * @param MagicRequest $request
     * @return RedirectResponse
     * @author Brighton van Rouendal, Wim van Ginkel
     */
    public function sendLink(MagicRequest $request)
    {
        $token = $this->magic->generateToken($request->email);
        $url = $this->magic->createSignedUrl($request->email, $token);

        Mail::to($request->email)->send(new MagicLoginMail($url));

        return back()->with('status', 'Controleer je werkmail en eventueel de map met ongewenste e-mail.');
    }

    public function verify(MagicRequest $request)
    {
        $request->validate(['token' => ['required', 'string', 'size:32']]);

        if (!$this->magic->isValidToken($request->email, $request->token))
            abort(403, 'Invalid or expired login token.');

        $this->magic->invalidateToken($request->email);
        $request->session()->regenerate();

        $admin = Admin::query()->where('employee', $request->email)->first();

        // Store session for 24 hours
        session([
            'magic' => [
                'email' => strtolower($request->email),
                'expires_at' => now()->addHours(24),
                'admin' => [
                    'isAdmin' => (bool)$admin,
                    'isSuper' => $admin && (boolean)$admin->super,
                ],
            ]
        ]);

        return redirect()->route($admin ? 'charity.admin.requests.index' : 'charity.requests');
    }

    public function logout()
    {
        session()->forget(['magic', 'debtor_data']);
        session()->regenerate();
        session()->regenerateToken();
        return redirect()->route('charity.login');
    }
}
