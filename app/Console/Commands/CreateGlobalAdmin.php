<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('app:create-global-admin')]
#[Description('Crea una cuenta de administrador global')]
class CreateGlobalAdmin extends Command
{
    public function handle(): int
    {
        if (User::where('global_role', 'ADMIN_GLOBAL')->exists()) {
            $this->error('Ya existe un administrador global.');

            return self::FAILURE;
        }

        $name = $this->ask('Nombre');
        $email = $this->ask('Correo electrónico');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Correo electrónico inválido.');

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('Ya existe un usuario con ese correo.');

            return self::FAILURE;
        }

        $password = $this->secret('Contraseña');
        $confirmation = $this->secret('Confirma la contraseña');

        if (! $password || $password !== $confirmation || strlen($password) < 12) {
            $this->error('Contraseña inválida, menor a 12 caracteres o sin coincidencia.');

            return self::FAILURE;
        }

        $user = new User;
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->global_role = 'ADMIN_GLOBAL';
        $user->email_verified_at = now();
        $user->save();

        $this->info('Administrador global creado correctamente.');

        return self::SUCCESS;
    }
}
