<?php

use App\Exceptions\BusinessRuleException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Hosting platforms put the app behind a proxy / load balancer that handles HTTPS.
        // Trusting it lets Laravel see the real scheme and client IP, so generated links are
        // https://, secure cookies work and signed links (email verification) validate.
        $middleware->trustProxies(at: '*');

        // The React SPA is served from the same domain and logs in with Sanctum's
        // session cookie (with CSRF protection), not with tokens stored in the browser.
        $middleware->statefulApi();

        $middleware->alias([
            // role:admin,hr — the signed-in user must have one of these system roles.
            'role' => \App\Http\Middleware\EnsureRole::class,
            // The signed-in user must be a Head of Department.
            'head' => \App\Http\Middleware\EnsureDepartmentHead::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Every API error has the same JSON shape, so the frontend handles them in one place:
        // { "code": "validation_error", "message": "...", "errors": { "field": ["..."] } }
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null; // web pages keep Laravel's normal handling
            }

            [$status, $code, $message, $errors] = match (true) {
                $e instanceof ValidationException => [422, 'validation_error', 'Some fields need attention.', $e->errors()],
                $e instanceof BusinessRuleException => [422, $e->errorCode, $e->getMessage(), $e->errors],
                $e instanceof AuthenticationException => [401, 'unauthenticated', 'Please sign in to continue.', []],
                // A policy's own reason ("Only an Admin can…") is shown; otherwise a general message.
                $e instanceof AuthorizationException,
                $e instanceof AccessDeniedHttpException => [403, 'forbidden',
                    in_array($e->getMessage(), ['', 'This action is unauthorized.'], true) ? 'You do not have permission to do this.' : $e->getMessage(), []],
                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException => [404, 'not_found', 'That record could not be found.', []],
                $e instanceof TokenMismatchException => [419, 'session_expired', 'Your session expired. Please refresh and try again.', []],
                $e instanceof ThrottleRequestsException => [429, 'too_many_requests', 'Too many attempts. Please wait a moment and try again.', []],
                // abort(403) / abort(404) etc. arrive here as generic HTTP exceptions.
                $e instanceof HttpExceptionInterface => match ($e->getStatusCode()) {
                    403 => [403, 'forbidden', 'You do not have permission to do this.', []],
                    404 => [404, 'not_found', 'That record could not be found.', []],
                    default => [$e->getStatusCode(), 'http_error', $e->getMessage() ?: 'Request failed.', []],
                },
                default => [500, 'server_error', config('app.debug') ? $e->getMessage() : 'Something went wrong on our side. Please try again.', []],
            };

            return response()->json([
                'code' => $code,
                'message' => $message,
                'errors' => (object) $errors,
            ], $status);
        });
    })->create();
