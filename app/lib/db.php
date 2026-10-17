<?php
class Database {
	private static ?PDO $pdo = null;

	public static function getConfig(): array {
		static $config = null;
		if ($config === null) {
			$config = require __DIR__ . '/../config.php';
		}
		return $config;
	}

	public static function pdo(): PDO {
		if (self::$pdo !== null) {
			try {
				self::$pdo->query('SELECT 1');
				return self::$pdo;
			} catch (PDOException $e) {
				self::$pdo = null;
			}
		}

		$config = self::getConfig();
		$host = $config['db_host'];
		$port = $config['db_port'];
		$db   = $config['db_name'];
		$user = $config['db_user'];
		$pass = $config['db_pass'];

		$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
		$options = [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false,
			PDO::ATTR_PERSISTENT         => false,
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION wait_timeout=600, interactive_timeout=600, net_read_timeout=600, net_write_timeout=600",
		];

		try {
			self::$pdo = new PDO($dsn, $user, $pass, $options);
		} catch (PDOException $e) {
			$dsnNoDb = "mysql:host={$host};port={$port};charset=utf8mb4";
			$tempPdo = new PDO($dsnNoDb, $user, $pass, $options);
			$tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
			self::$pdo = new PDO($dsn, $user, $pass, $options);
		}

		return self::$pdo;
	}
}