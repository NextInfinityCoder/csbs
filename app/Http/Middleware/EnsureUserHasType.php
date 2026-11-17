<?php

namespace App\Http\Middleware;

use App\UserType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasType
{
    /**
     * Only let users of the given types through, e.g. "user.type:admin" or "user.type:admin,encoder".
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $allowedTypes = array_map(fn (string $type): UserType => UserType::from($type), $types);

        abort_unless(in_array($request->user()?->type, $allowedTypes, true), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
