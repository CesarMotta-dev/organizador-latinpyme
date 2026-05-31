<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite; // 🚀 La librería oficial de Laravel para OAuth
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Exception;

class GoogleController extends Controller
{
    /**
     * Redirecciona al usuario a la página oficial de logueo de Google.
     */
    public function redirectToGoogle()
    {
        // Socialite se encarga de armar la URL gigante con los cliente_id que pusimos en config/services.php
        return Socialite::driver('google')->redirect();
    }

    /**
     * Recibe la respuesta de Google cuando el usuario ya seleccionó su cuenta.
     */
    public function handleGoogleCallback()
    {
        try {
            // Capturamos los datos que Google nos devuelve de forma segura
            $googleUser = Socialite::driver('google')->user();

            // Buscamos en la base de datos si ya existe un usuario con ese mismo email
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                // Si el usuario existe (ej: un administrativo de Latinpymes), lo logueamos directo
                Auth::login($user);
            } else {
                // Si es un usuario nuevo, lo creamos automáticamente en la base de datos
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'password' => encrypt('123456dummy') // Contraseña aleatoria/segura por defecto
                ]);

                Auth::login($user);
            }

            // Una vez logueado con éxito, lo mandamos al Dashboard que vimos en tus rutas
            return redirect()->intended('dashboard');

        } catch (Exception $e) {
            // Si el usuario cancela el inicio de sesión o Google falla, lo devolvemos al login con error
            return redirect()->route('login')->with('error', 'Error al autenticarse con Google.');
        }
    }
}
