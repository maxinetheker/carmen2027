<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\PasswordResetNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_primary_email_and_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('ClaveActual1!'),
            'role' => 'ceo',
        ]);

        $this->actingAs($user)->get(route('admin.account.edit'))
            ->assertOk()->assertSee('Correo principal')->assertSee('Cambiar contraseña');
        $this->actingAs($user)->put(route('admin.account.email'), [
            'email' => 'principal@carmenmestanza.com',
            'current_password' => 'ClaveActual1!',
        ])->assertSessionHas('success');
        $this->assertSame('principal@carmenmestanza.com', $user->fresh()->email);

        $this->actingAs($user)->put(route('admin.account.password'), [
            'current_password' => 'ClaveActual1!',
            'password' => 'NuevaClave2!Segura',
            'password_confirmation' => 'NuevaClave2!Segura',
        ])->assertSessionHas('success');
        $this->assertTrue(Hash::check('NuevaClave2!Segura', $user->fresh()->password));
    }

    public function test_password_can_be_recovered_by_primary_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'principal@carmenmestanza.com']);

        $this->get(route('login'))->assertOk()
            ->assertSee(route('password.request'), false);
        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');
        Notification::assertSentTo($user, PasswordResetNotification::class);

        $token = Password::createToken($user);
        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()->assertSee($user->email);
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Recuperada3!Segura',
            'password_confirmation' => 'Recuperada3!Segura',
        ])->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('Recuperada3!Segura', $user->fresh()->password));
    }

    public function test_reseeding_does_not_overwrite_account_or_edited_content(): void
    {
        $this->seed();
        $user = User::where('role', 'ceo')->firstOrFail();
        $user->update([
            'email' => 'nuevo@carmenmestanza.com',
            'password' => 'Personal4!Segura',
        ]);
        SiteSetting::where('key', 'hero_title')->update(['value' => 'Título personalizado']);

        $this->seed();

        $user->refresh();
        $this->assertSame('nuevo@carmenmestanza.com', $user->email);
        $this->assertTrue(Hash::check('Personal4!Segura', $user->password));
        $this->assertSame(
            'Título personalizado', SiteSetting::where('key', 'hero_title')->value('value')
        );
    }
}
