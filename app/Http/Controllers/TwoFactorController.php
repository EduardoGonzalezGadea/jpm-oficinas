<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FALaravel\Support\Authenticator;
use PragmaRX\Google2FALaravel\Facade as Google2FA;

class TwoFactorController extends Controller
{
    /**
     * Muestra la vista para habilitar/gestionar 2FA.
     */
    public function index()
    {
        $user = Auth::user();
        // $google2fa = new Google2FA(); // Usar Facade
        $qrCodeUrl = null;
        $secret = null;

        if (!$user->two_factor_secret) {
            $secret = Google2FA::generateSecretKey();
            // No guardamos el secreto todavía en la BD, lo pasamos a la vista para confirmar
            // O podríamos guardarlo temporalmente.
            // Estrategia simple: Generar uno nuevo para mostrar, y guardarlo solo al confirmar.
            // PERO require persistencia temporal. 
            // Mejor estrategia: Si no está confirmado, generar uno y mostrarlo.

            // Para simplificar: Generamos y mostramos. El usuario debe enviar el secreto + código para confirmar.
            $qrCodeUrl = Google2FA::getQRCodeInline(
                config('app.name'),
                $user->email,
                $secret
            );
        }

        return view('auth.two-factor-manage', compact('user', 'qrCodeUrl', 'secret'));
    }

    /**
     * Confirma y habilita el 2FA.
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
            'secret' => 'required|string',
        ]);

        $user = Auth::user();
        // $google2fa = new Google2FA();

        $valid = Google2FA::verifyKey($request->secret, $request->code);

        if ($valid) {
            $user->two_factor_secret = encrypt($request->secret); // Encriptamos manualmente si no usamos cast en el modelo para este campo específico o dependemos del cast encrypted 
            // Nota: En User model pusimos hidden, pero no cast encrypted para secret, solo para recovery_codes.
            // Jetstream lo encripta. Vamos a encriptarlo.
            $user->two_factor_secret = encrypt($request->secret);
            $user->two_factor_confirmed_at = now();

            // Generar códigos de recuperación
            $recoveryCodes = [];
            for ($i = 0; $i < 8; $i++) {
                $recoveryCodes[] = \Illuminate\Support\Str::random(10) . '-' . \Illuminate\Support\Str::random(10);
            }
            $user->two_factor_recovery_codes = $recoveryCodes; // El cast encrypted:array lo manejará

            $user->save();

            return redirect()->route('two-factor.index')->with('success', 'Autenticación de dos factores habilitada correctamente.');
        }

        return back()->with('error', 'Código de verificación inválido.');
    }

    /**
     * Deshabilita el 2FA.
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = Auth::user();
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return redirect()->route('two-factor.index')->with('success', 'Autenticación de dos factores deshabilitada.');
    }

    /**
     * Regenera códigos de recuperación.
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $user = Auth::user();
        if (!$user->two_factor_confirmed_at) {
            return back()->with('error', '2FA no está habilitado.');
        }

        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $recoveryCodes[] = \Illuminate\Support\Str::random(10) . '-' . \Illuminate\Support\Str::random(10);
        }
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->save();

        return back()->with('success', 'Códigos de recuperación regenerados.');
    }

    /**
     * Muestra la vista de desafío de 2FA (Login).
     */
    public function showChallenge()
    {
        return view('auth.two-factor-challenge');
    }

    /**
     * Verifica el desafío de 2FA.
     */
    public function verifyChallenge(Request $request)
    {
        $request->validate([
            'code' => 'nullable|string',
            'recovery_code' => 'nullable|string',
        ]);

        $user = Auth::user();

        if (!$request->code && !$request->recovery_code) {
            return back()->with('error', 'Debe ingresar un código de autenticación o de recuperación.');
        }

        // Verificar código TOTP
        if ($request->code) {
            // $google2fa = new Google2FA();

            // Desencriptar secreto
            try {
                $secret = decrypt($user->two_factor_secret);
            } catch (\Exception $e) {
                $secret = $user->two_factor_secret;
            }

            if (Google2FA::verifyKey($secret, $request->code)) {
                $request->session()->put('auth.2fa.verified', true);
                return redirect()->intended('/panel');
            }
        }

        // Verificar código recuperación
        if ($request->recovery_code) {
            $codes = $user->two_factor_recovery_codes; // Array desencriptado

            if (($key = array_search($request->recovery_code, $codes)) !== false) {
                unset($codes[$key]);
                $user->two_factor_recovery_codes = array_values($codes); // Reindexar
                $user->save();

                $request->session()->put('auth.2fa.verified', true);
                return redirect()->intended('/panel');
            }
        }

        return back()->with('error', 'El código ingresado es incorrecto.');
    }
}
