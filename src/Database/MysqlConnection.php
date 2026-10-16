<?php

declare(strict_types=1);

namespace Crowdmap\Database;

use PDO;

class MysqlConnection implements DatabaseConnection
{
    private PDO $pdo;

    /**
     * @param string $sslMode DISABLED, REQUIRED (TLS without certificate checks) or
     *                        VERIFY_CA (TLS, server certificate checked against $sslCa)
     */
    public function __construct(
        string $host,
        string $database,
        string $username,
        string $password,
        int $port = 3306,
        string $sslMode = 'DISABLED',
        ?string $sslCa = null,
    ) {
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        $this->pdo = new PDO($dsn, $username, $password, self::sslOptions($sslMode, $sslCa));
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec("SET time_zone = '+00:00'");
    }

    private static function sslOptions(string $sslMode, ?string $sslCa): array
    {
        $mode = strtoupper($sslMode);
        if ($mode === 'DISABLED') {
            return [];
        }

        $verify = $mode === 'VERIFY_CA' || $mode === 'VERIFY_IDENTITY';
        if (!$verify && !in_array($mode, ['REQUIRED', 'PREFERRED'], true)) {
            throw new \InvalidArgumentException("Unknown MYSQL_SSL_MODE: {$sslMode}");
        }

        // mysqlnd only negotiates TLS once a CA (or key/cert) is given. Without
        // verification the file is not consulted, so the system bundle will do.
        $ca = $sslCa ?? self::systemCaBundle();
        if ($ca === null) {
            throw new \RuntimeException('MYSQL_SSL_MODE requires TLS but no CA bundle was found; set MYSQL_SSL_CA');
        }

        return [
            self::attr('SSL_CA') => $ca,
            self::attr('SSL_VERIFY_SERVER_CERT') => $verify,
        ];
    }

    private static function systemCaBundle(): ?string
    {
        foreach (['/etc/ssl/certs/ca-certificates.crt', '/etc/pki/tls/certs/ca-bundle.crt', '/etc/ssl/cert.pem'] as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /** Pdo\Mysql::ATTR_* on PHP 8.4+, where the PDO::MYSQL_ATTR_* aliases are deprecated. */
    private static function attr(string $name): int
    {
        return defined("Pdo\\Mysql::ATTR_{$name}")
            ? constant("Pdo\\Mysql::ATTR_{$name}")
            : constant("PDO::MYSQL_ATTR_{$name}");
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}
