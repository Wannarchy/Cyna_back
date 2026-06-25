<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Veuillez confirmer votre adresse email avant d\'effectuer cette action.',
                'email_verification_required' => true,
            ], 403);
        }

        return $next($request);
    }
}
