<?php
declare(strict_types=1);
function db(): PDO {
    static $connection;
    if ($connection instanceof PDO) return $connection;
    $config = require dirname(__DIR__) . '/config/database.php';
    $dsn = $config['socket'] ? 'mysql:unix_socket=' . $config['socket'] : 'mysql:host=' . $config['host'] . ';port=' . $config['port'];
    $connection = new PDO($dsn . ';dbname=' . $config['name'] . ';charset=utf8mb4', $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    $connection->exec("SET time_zone = '+07:00'");
    return $connection;
}
function query(string $sql, array $parameters = []): PDOStatement {
    $statement = db()->prepare($sql); $statement->execute($parameters); return $statement;
}
function categories(): array { return query('SELECT * FROM categories ORDER BY id')->fetchAll(); }
function services(bool $includeInactive = false): array {
    return query('SELECT s.*, c.name AS category FROM services s JOIN categories c ON c.id=s.category_id' . ($includeInactive ? '' : ' WHERE s.is_active=1') . ' ORDER BY s.id')->fetchAll();
}
function find_service($id, bool $includeInactive = false): ?array {
    if ((!is_string($id) && !is_int($id)) || !preg_match('/^[1-9][0-9]{0,9}$/', (string) $id)) return null;
    return query('SELECT s.*,c.name AS category FROM services s JOIN categories c ON c.id=s.category_id WHERE s.id=?' . ($includeInactive ? '' : ' AND s.is_active=1'), [(int) $id])->fetch() ?: null;
}
function audit(string $action, string $entity, ?int $entityId, string $description): void {
    query('INSERT INTO audit_logs (user_id,action,entity,entity_id,description) VALUES (?,?,?,?,?)', [$_SESSION['user_id'] ?? null, $action, $entity, $entityId, $description]);
}
