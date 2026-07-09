<?php

declare(strict_types=1);

namespace App\Platform\Auth;

use App\Platform\Storage\JsonStore;

final class AuthService
{
    private JsonStore $store;

    public function __construct()
    {
        $this->store = new JsonStore();
    }

    public function attempt(string $email, string $password): bool
    {
        foreach ($this->store->all('users') as $user) {
            if ($user['email'] === $email && $user['status'] === 'active' && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                return true;
            }
        }

        return false;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function user(): ?array
    {
        $id = $_SESSION['user_id'] ?? null;
        return $id ? $this->store->find('users', (int) $id) : null;
    }

    public function hasRole(string $role): bool
    {
        $user = $this->user();
        return $user !== null && $user['role'] === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        $user = $this->user();
        return $user !== null && in_array((string) ($user['role'] ?? ''), $roles, true);
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}
