<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /**
         * Personalización del email de verificación
         */
        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
          
            $verifyUrl = $url;

            return (new MailMessage)
                ->subject('Verifica tu correo – Método Rebirth')
                ->greeting('¡Hola ' . ($notifiable->firstName ?? '👋') . '!')
                ->line('Gracias por registrarte en Método Rebirth. Para activar tu cuenta, por favor confirma tu correo electrónico.')
                ->action('Verificar mi email', $verifyUrl)
                ->line('Si no te registraste, puedes ignorar este mensaje.')
                ->salutation('— Equipo Método Rebirth');
        });
    }
}
