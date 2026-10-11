<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Request;

class RoleMiddleware
{
    private string $requiredRole;

    public function __construct(string $role = 'admin')
    {
        $this->requiredRole = $role;
    }

    public function handle(Request $request): void
    {
        if (!Auth::check() || Auth::role() !== $this->requiredRole) {
            throw new HttpException(403, "Access denied. Required role: " . ucfirst($this->requiredRole));
        }
    }
}
