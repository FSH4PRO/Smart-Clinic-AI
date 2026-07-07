<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. التأكد من أن المستخدم مسجل الدخول
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // 2. استخراج قيمة الدور (Role)
        $userRole = $request->user()->role;
        
        // إذا كان الدور من نوع Enum، نأخذ قيمته النصية
        if ($userRole instanceof \BackedEnum) {
            $userRole = $userRole->value;
        }

        // 3. التحقق مما إذا كان دور المستخدم موجوداً في قائمة الأدوار المسموح بها
        if (!in_array($userRole, $roles)) {
             return response()->json([
                'success' => false,
                'message' => 'Unauthorized action. You do not have the required role.'
            ], 403);
        }

        return $next($request);
    }
}