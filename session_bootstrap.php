<?php
/**
 * SDS — Démarrage de session compatible serverless (Vercel)
 *
 * Sur Vercel, chaque requête peut atterrir sur une instance différente et le
 * disque local n'est pas persistant : le handler de session par défaut de PHP
 * (fichiers dans /tmp) ne survit pas entre deux requêtes. On stocke donc les
 * sessions dans la table MySQL `php_sessions` via un SessionHandlerInterface.
 */

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/admin/includes/db.php';

final class SdsDbSessionHandler implements SessionHandlerInterface {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function open($savePath, $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string|false {
        $stmt = $this->pdo->prepare(
            'SELECT data FROM php_sessions WHERE id = :id AND last_activity > :expiry LIMIT 1'
        );
        $stmt->execute([
            ':id'     => $id,
            ':expiry' => time() - (int) ini_get('session.gc_maxlifetime'),
        ]);
        $row = $stmt->fetch();
        return $row ? $row['data'] : '';
    }

    public function write($id, $data): bool {
        $stmt = $this->pdo->prepare(
            'INSERT INTO php_sessions (id, data, last_activity) VALUES (:id, :data, :time)
             ON DUPLICATE KEY UPDATE data = :data2, last_activity = :time2'
        );
        return $stmt->execute([
            ':id'     => $id,
            ':data'   => $data,
            ':time'   => time(),
            ':data2'  => $data,
            ':time2'  => time(),
        ]);
    }

    public function destroy($id): bool {
        $stmt = $this->pdo->prepare('DELETE FROM php_sessions WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function gc($max_lifetime): int|false {
        $stmt = $this->pdo->prepare('DELETE FROM php_sessions WHERE last_activity <= :expiry');
        $stmt->execute([':expiry' => time() - $max_lifetime]);
        return $stmt->rowCount();
    }
}

function sds_session_start(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime'  => 0,
        'path'      => '/',
        'domain'    => '',
        'secure'    => $isSecure,
        'httponly'  => true,
        'samesite'  => 'Lax',
    ]);

    session_set_save_handler(new SdsDbSessionHandler(getDB()), true);
    session_start();
}
