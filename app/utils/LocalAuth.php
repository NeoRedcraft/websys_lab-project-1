<?php

namespace App\Utils;

class LocalAuth
{
    private $pdo;

    public function __construct()
    {
        $dir = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'database';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $dbFile = $dir . DIRECTORY_SEPARATOR . 'local_auth.sqlite';
        $needInit = !file_exists($dbFile);
        $this->pdo = new \PDO('sqlite:' . $dbFile);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        if ($needInit) {
            $this->initSchema();
        }
    }

    private function initSchema()
    {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id TEXT PRIMARY KEY,
            email TEXT UNIQUE NOT NULL,
            full_name TEXT,
            password TEXT NOT NULL,
            role TEXT,
            org_id TEXT,
            is_active INTEGER DEFAULT 1,
            created_at TEXT
        );";

        $this->pdo->exec($sql);
    }

    public function signUp($email, $password, $metadata = [])
    {
        $email = strtolower(trim($email));
        if ($this->getByEmail($email)) {
            return ['success' => false, 'error' => 'Email already registered'];
        }

        $id = bin2hex(random_bytes(8));
        $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $name = $metadata['name'] ?? $metadata['full_name'] ?? null;
        $role = $metadata['role'] ?? 'organizer';
        $now = date('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare('INSERT INTO users (id,email,full_name,password,role,org_id,is_active,created_at) VALUES (:id,:email,:full_name,:password,:role,:org_id,1,:created_at)');
        $stmt->execute([
            ':id' => $id,
            ':email' => $email,
            ':full_name' => $name,
            ':password' => $hashed,
            ':role' => $role,
            ':org_id' => null,
            ':created_at' => $now,
        ]);

        return ['success' => true, 'data' => ['user_id' => $id]];
    }

    public function signIn($email, $password)
    {
        $email = strtolower(trim($email));
        $user = $this->getByEmail($email);
        if (!$user) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // emulate tokens
        $access = bin2hex(random_bytes(16));
        $refresh = bin2hex(random_bytes(16));

        return ['success' => true, 'data' => [
            'access_token' => $access,
            'refresh_token' => $refresh,
            'expires_in' => 3600,
            'user' => $user,
        ]];
    }

    public function getByEmail($email)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => strtolower(trim($email))]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
