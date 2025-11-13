<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

echo "University Networking Portal — Admin Seeder\n";
echo "-------------------------------------------\n";

$name = trim(readline('Full name: '));
$email = trim(readline('Institutional email: '));
$institutionalId = trim(readline('Institutional ID: '));
$password = trim(readline('Password (min 8 chars): '));

if ($name === '' || $email === '' || $institutionalId === '' || strlen($password) < 8) {
    fwrite(STDERR, "All fields are required and password must be at least 8 characters.\n");
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Invalid email address.\n");
    exit(1);
}

try {
    $pdo = Database::getConnection();

    $exists = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email OR institutional_id = :institutional_id');
    $exists->execute([
        'email' => $email,
        'institutional_id' => $institutionalId,
    ]);

    if ($exists->fetchColumn() > 0) {
        fwrite(STDERR, "An account already exists with that email or institutional ID.\n");
        exit(1);
    }

    $insert = $pdo->prepare('
        INSERT INTO users (name, email, institutional_id, password_hash, role)
        VALUES (:name, :email, :institutional_id, :password_hash, :role)
    ');
    $insert->execute([
        'name' => $name,
        'email' => $email,
        'institutional_id' => $institutionalId,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'admin',
    ]);

    echo "✅ Admin seeded successfully.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Failed to seed admin: {$e->getMessage()}\n");
    exit(1);
}

