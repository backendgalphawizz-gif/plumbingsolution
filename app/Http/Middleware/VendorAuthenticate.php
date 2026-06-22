<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendorAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if ($user->role !== UserRole::Vendor) {
            return response()->json(['success' => false, 'message' => 'Unauthorized vendor access.'], 403);
        }

        if ($user->is_blocked) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been blocked.',
                'reason' => $user->block_reason,
            ], 403);
        }

        return $next($request);
    }
}
