<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class Register extends Component
{
    // public string $name = '';
    public string $firstName = '';
    public string $lastName = '';

    // Shown on the public ranking instead of the real name.
    public string $display_name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            // 'name' => ['required', 'string', 'max:255'],
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'display_name' => ['required', 'string', 'min:3', 'max:50', 'unique:'.User::class],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ], [
            'display_name.unique' => 'Deze schermnaam is al in gebruik, kies een andere.',
            'display_name.required' => 'Vul een schermnaam in, die wordt op de ranglijst getoond.',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));

        Auth::login($user);

        // Redirect on succesful register
        $this->redirect(route('home', absolute: false), navigate: true);
    }
}
