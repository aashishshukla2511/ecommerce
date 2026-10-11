<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Request;
use App\Helpers\Flash;
use App\Helpers\Url;

class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            Flash::error("Please log in to access this page.");
            Url::redirect('/login');
            exit;
        }
    }
}
