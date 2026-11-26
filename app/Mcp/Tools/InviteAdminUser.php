<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\User;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Facades\Filament;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Title;

/**
 * Gives someone access to the admin without anybody handling a password: the
 * account gets a random password that is never shown, and the person receives
 * the admin's "set your password" email link. If the link expires (60 min),
 * "Forgot password?" on the login page or inviting again sends a new one.
 */
#[Title('Invite admin user')]
class InviteAdminUser extends BaseTool
{
    public function description(): string
    {
        return 'Create an admin (Filament) account and email the person a link to set their own password. '
            .'For an existing account it only emails a fresh link (name and password stay). '
            .'No password is returned or needed.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Full name.')->required(),
            'email' => $schema->string()->description('Email address the set-password link goes to.')->required(),
        ];
    }

    protected function run(array $arguments): Response
    {
        $validator = Validator::make($arguments, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->first());
        }

        // Inviting an existing account again just sends a fresh link.
        $user = User::where('email', $arguments['email'])->first();
        $created = ! $user;
        $user ??= User::create([
            'name' => $arguments['name'],
            'email' => $arguments['email'],
            'password' => Str::password(48),
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $token = Password::broker(Filament::getAuthPasswordBroker())->createToken($user);
        $notification = app(ResetPasswordNotification::class, ['token' => $token]);
        $notification->url = Filament::getResetPasswordUrl($token, $user);
        $user->notify($notification);

        return Response::json([
            'invited' => true,
            'created' => $created,
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'login_url' => url('/admin/login'),
            'note' => 'A link to set the password was emailed; it is valid for 60 minutes. Afterwards use "Forgot password?" on the login page.',
        ]);
    }
}
