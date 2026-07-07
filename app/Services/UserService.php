<?php

namespace App\Services;

class UserService
{
    private const USERS_FILE = 'users.json';

    public function __construct(private JsonStorage $storage)
    {
        $this->ensureDefaultAdmin();
    }

    public function all(): array
    {
        return $this->storage->read(self::USERS_FILE);
    }

    public function findByUsername(string $username): ?array
    {
        $username = strtolower(trim($username));

        foreach ($this->all() as $user) {
            if (strtolower($user['username'] ?? '') === $username) {
                return $user;
            }
        }

        return null;
    }

    public function findById(string $id): ?array
    {
        foreach ($this->all() as $user) {
            if (($user['id'] ?? '') === $id) {
                return $user;
            }
        }

        return null;
    }

    public function verifyCredentials(string $username, string $password): ?array
    {
        $user = $this->findByUsername($username);

        if (! $user || ! password_verify($password, $user['password'] ?? '')) {
            return null;
        }

        return $this->publicUser($user);
    }

    public function register(string $name, string $username, string $password): array
    {
        if ($this->findByUsername($username)) {
            throw new \InvalidArgumentException('Username is already taken.');
        }

        $users = $this->all();

        $user = [
            'id' => $this->storage->generateId('user-'),
            'name' => $name,
            'username' => strtolower(trim($username)),
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'created_at' => $this->storage->now(),
            'updated_at' => $this->storage->now(),
        ];

        $users[] = $user;
        $this->storage->write(self::USERS_FILE, $users);

        return $this->publicUser($user);
    }

    public function publicUser(array $user): array
    {
        return [
            'id' => $user['id'],
            'name' => $user['name'],
            'username' => $user['username'],
        ];
    }

    private function ensureDefaultAdmin(): void
    {
        $seedPath = storage_path('app/data/'.self::USERS_FILE);
        $target = $this->storage->path(self::USERS_FILE);

        if (! file_exists($target) && file_exists($seedPath)) {
            copy($seedPath, $target);
        }

        if (! file_exists($target)) {
            $this->storage->write(self::USERS_FILE, [$this->defaultAdmin()]);
            return;
        }

        if (! $this->findByUsername('admin')) {
            $users = $this->all();
            $users[] = $this->defaultAdmin();
            $this->storage->write(self::USERS_FILE, $users);
        }
    }

    private function defaultAdmin(): array
    {
        return [
            'id' => 'user-admin',
            'name' => 'Admin',
            'username' => 'admin',
            'password' => password_hash('password', PASSWORD_BCRYPT),
            'created_at' => $this->storage->now(),
            'updated_at' => $this->storage->now(),
        ];
    }
}
