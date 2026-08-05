<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.account.edit', ['user' => $request->user()]);
    }

    public function updateEmail(Request $request)
    {
        $user = $request->user();
        $data = $request->validateWithBag('emailUpdate', [
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'current_password' => ['required', 'current_password'],
        ]);
        $previous = $user->email;
        $user->update([
            'email' => strtolower($data['email']),
            'email_verified_at' => null,
        ]);
        DB::table('password_reset_tokens')->whereIn('email', [$previous, $user->email])->delete();

        return back()->with('success', 'Correo principal actualizado.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validateWithBag('passwordUpdate', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        $user = $request->user();
        $user->update(['password' => $data['password']]);
        $user->setRememberToken(null);
        $user->save();
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        $this->closeOtherSessions($request);
        $request->session()->regenerate();

        return back()->with('success', 'Contraseña actualizada. Las otras sesiones fueron cerradas.');
    }

    private function closeOtherSessions(Request $request): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }
        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }
}
