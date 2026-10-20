<?php

namespace Ssdev\Headless\Auth;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Response;
use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Contracts\PasswordConfirmedResponse as PasswordConfirmedResponseContract;
use Laravel\Fortify\Contracts\PasswordResetResponse as PasswordResetResponseContract;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Laravel\Fortify\Fortify;
use Ssdev\Headless\Auth\Actions\ResetUserPassword;
use Ssdev\Headless\Auth\Responses\LoginResponse;
use Ssdev\Headless\Auth\Responses\LogoutResponse;
use Ssdev\Headless\Auth\Responses\PasswordConfirmedResponse;
use Ssdev\Headless\Auth\Responses\PasswordResetLinkResponse;
use Ssdev\Headless\Auth\Responses\PasswordResetResponse;
use Ssdev\Headless\Auth\Responses\VerificationLinkSentResponse;
use Ssdev\Headless\Auth\Responses\VerifyEmailResponse;
use Ssdev\Headless\Http\Controllers\DashboardController;
use Ssdev\Headless\Http\Inertia\HeadlessPage;
use Ssdev\Headless\Support\Flash;

final class FortifyIntegration
{
    private const RESPONSES = [
        LoginResponseContract::class => LoginResponse::class,
        LogoutResponseContract::class => LogoutResponse::class,
        SuccessfulPasswordResetLinkRequestResponse::class => PasswordResetLinkResponse::class,
        PasswordResetResponseContract::class => PasswordResetResponse::class,
        VerifyEmailResponseContract::class => VerifyEmailResponse::class,
        EmailVerificationNotificationSentResponse::class => VerificationLinkSentResponse::class,
        PasswordConfirmedResponseContract::class => PasswordConfirmedResponse::class,
    ];

    public function __construct(
        private Application $app,
        private Repository $config,
    ) {}

    public function register(): void
    {
        // The package registers Fortify's controllers itself, with its own prefix and middleware.
        Fortify::ignoreRoutes();

        $this->config->set([
            'fortify.guard' => 'web',
            'fortify.passwords' => 'users',
            'fortify.username' => 'email',
            'fortify.email' => 'email',
            'fortify.lowercase_usernames' => true,
            'fortify.features' => [],
            // With a named limiter Fortify skips its own hard-coded login throttle.
            'fortify.limiters.login' => RateLimits::LOGIN,
            // Fortify sends already verified users here; built from the path because routes are not loaded yet.
            'fortify.home' => '/'.trim(trim((string) $this->config->get('headless.routes.prefix'), '/').'/'.DashboardController::PATH, '/'),
        ]);

        foreach (self::RESPONSES as $contract => $response) {
            $this->app->singleton($contract, $response);
        }

        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
    }

    public function boot(): void
    {
        Fortify::loginView(fn (Request $request): Response => HeadlessPage::render('Auth/Login', [
            'loginUrl' => route('login.store'),
            'passwordRequestUrl' => $this->routeUrl('password.request'),
            'registerUrl' => $this->config->get('headless.registration.enabled') ? $this->routeUrl('register') : null,
            'status' => $this->status($request),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request): Response => HeadlessPage::render('Auth/ForgotPassword', [
            'submitUrl' => route('password.email'),
            'loginUrl' => route('login'),
            'status' => $this->status($request),
        ]));

        Fortify::resetPasswordView(fn (Request $request): Response => HeadlessPage::render('Auth/ResetPassword', [
            'submitUrl' => route('password.update'),
            'token' => (string) $request->route('token'),
            'email' => (string) $request->query('email', ''),
        ]));

        Fortify::verifyEmailView(fn (Request $request): Response => HeadlessPage::render('Auth/VerifyEmail', [
            'resendUrl' => route('verification.send'),
            'profileUrl' => $this->routeUrl('headless.profile.edit'),
            'email' => $request->user()?->getEmailForVerification(),
            'status' => $this->status($request),
        ]));

        Fortify::confirmPasswordView(fn (): Response => HeadlessPage::render('Auth/ConfirmPassword', [
            'submitUrl' => route('password.confirm.store'),
        ]));
    }

    private function routeUrl(string $name): ?string
    {
        return Route::has($name) ? route($name) : null;
    }

    private function status(Request $request): ?string
    {
        return $request->session()->get(Flash::STATUS);
    }
}
