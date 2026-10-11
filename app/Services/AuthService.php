<?php

namespace App\Services;

use App\Core\Validator;
use App\Repositories\UserRepository;

class AuthService
{
    private UserRepository $userRepository;

    public function __construct(?UserRepository $userRepository = null)
    {
        $this->userRepository = $userRepository ?? new UserRepository();
    }

    public function authenticate(string $email, string $password): array
    {
        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
        ]);

        $isValid = $validator->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!$isValid) {
            return [
                'success' => false,
                'errors' => $validator->errors(),
            ];
        }

        $user = $this->userRepository->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.',
            ];
        }

        if (empty($user['is_active'])) {
            return [
                'success' => false,
                'message' => 'Your account is inactive. Please contact system support.',
            ];
        }

        return [
            'success' => true,
            'user' => $user,
        ];
    }

    public function getUserProfile(int $userId): ?array
    {
        return $this->userRepository->findById($userId);
    }
}
