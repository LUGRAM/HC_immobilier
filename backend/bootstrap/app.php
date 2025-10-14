<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ============================================
        // ALIAS DE MIDDLEWARE PERSONNALISÉS
        // ============================================
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // ============================================
        // MIDDLEWARE GLOBAL API
        // ============================================
        $middleware->api(prepend: [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // ============================================
        // MIDDLEWARE GLOBAL WEB
        // ============================================
        $middleware->web(append: [
            // Vos middlewares web ici si nécessaire
        ]);

        // ============================================
        // RATE LIMITING (Throttle)
        // ============================================
        $middleware->throttleApi();

        // ============================================
        // TRUST PROXIES (si derrière proxy/load balancer)
        // ============================================
        // $middleware->trustProxies(at: [
        //     '*', // ou spécifier les IPs
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // ============================================
        // GESTION DES EXCEPTIONS PERSONNALISÉES
        // ============================================
        
        // Exemple: Logger toutes les exceptions
        $exceptions->report(function (Throwable $e) {
            if (app()->environment('production')) {
                // Envoyer à Sentry, etc.
                // \Sentry\captureException($e);
            }
        });

        // Rendu personnalisé pour les API
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié',
                ], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé',
                ], 403);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ressource non trouvée',
                ], 404);
            }
        });
    })->create();