<?php

declare(strict_types=1);

namespace App\Platform\Auth;

use App\Platform\View\View;

final class AuthController
{
    public function showLogin(): void
    {
        View::render('auth/login', ['error' => $_SESSION['login_error'] ?? null], 'auth');
        unset($_SESSION['login_error']);
    }

    public function login(): void
    {
        verify_csrf();

        $auth = new AuthService();
        if ($auth->attempt(trim($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''))) {
            redirect('dashboard');
        }

        $_SESSION['login_error'] = 'ログインIDまたはパスワードが正しくありません。';
        redirect('login');
    }

    public function logout(): void
    {
        (new AuthService())->logout();
        redirect('login');
    }
}
