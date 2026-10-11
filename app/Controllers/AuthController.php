<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Flash;
use App\Helpers\Url;
use App\Services\AuthService;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(?AuthService $authService = null)
    {
        $this->authService = $authService ?? new AuthService();
    }

    public function showLogin(Request $request): Response
    {
        if (Auth::check()) {
            Url::redirect(Auth::isAdmin() ? '/admin/products' : '/account');
            exit;
        }

        return $this->render('auth/login', [
            'title' => 'Login',
        ]);
    }

    public function login(Request $request): Response
    {
        $email = $request->post('email', '');
        $password = $request->post('password', '');

        $result = $this->authService->authenticate($email, $password);

        if (!$result['success']) {
            Flash::setOld(['email' => $email]);
            $errorMessage = $result['message'] ?? 'Please fix validation errors.';
            Flash::error($errorMessage);
            Url::redirect('/login');
            exit;
        }

        $user = $result['user'];
        Auth::login($user);
        Flash::clearOld();
        Flash::success("Welcome back, {$user['name']}!");

        if (Auth::isAdmin()) {
            Url::redirect('/admin/products');
        } else {
            Url::redirect('/my-orders');
        }
        exit;
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        Flash::success("You have been logged out.");
        Url::redirect('/login');
        exit;
    }

    public function account(Request $request): Response
    {
        $userId = Auth::id();
        $user = $this->authService->getUserProfile($userId);

        return $this->render('auth/account', [
            'title' => 'My Account Profile',
            'user' => $user,
        ]);
    }
}
