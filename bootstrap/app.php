<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX
        );

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
        $middleware->statefulApi();
        $middleware->alias([
            'admin'      => \App\Http\Middleware\AdminMiddleware::class,
            'onboarding' => \App\Http\Middleware\EnsureOnboardingComplete::class,
            'suspended'  => \App\Http\Middleware\CheckSuspended::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (UniqueConstraintViolationException $e, Request $request) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'A duplicate entry was detected. Please try again.'], 409);
            }

            return back()->withErrors([
                'error' => 'A duplicate entry was detected. Please check your input and try again.',
            ]);
        });

        $exceptions->renderable(function (ModelNotFoundException $e, Request $request) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'The requested resource was not found.'], 404);
            }

            return Inertia::render('Error', ['status' => 404])
                ->toResponse($request)
                ->setStatusCode(404);
        });

        $exceptions->renderable(function (AuthorizationException $e, Request $request) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'You are not authorized to perform this action.'], 403);
            }

            return Inertia::render('Error', ['status' => 403])
                ->toResponse($request)
                ->setStatusCode(403);
        });

        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if ($request->wantsJson()) {
                return $response;
            }

            $status = $response->getStatusCode();

            if (in_array($status, [403, 404, 409, 419, 422, 429, 500, 503], true)) {
                return Inertia::render('Error', ['status' => $status])
                    ->toResponse($request)
                    ->setStatusCode($status);
            }

            return $response;
        });
    })->create();
