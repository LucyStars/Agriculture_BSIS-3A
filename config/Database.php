<?php
class Database {
	private array $env;

	public function __construct() {
		$this->env = $this->loadEnv();

		// On a host such as Render there is no .env file: the settings come from environment variables.
		foreach (["DB_HOST", "DB_PORT", "DB_NAME", "DB_USER", "DB_PASSWORD", "DB_SSLMODE", "APP_DEBUG"] as $key) {
			$value = getenv($key);

			if ($value !== false && !isset($this->env[$key])) {
				$this->env[$key] = $value;
			}
		}
	}

	private function loadEnv(): array {
		$paths = [__DIR__ . "/.env", dirname(__DIR__) . "/.env"];

		foreach ($paths as $path) {
			if (!is_readable($path)) {
				continue;
			}

			$env = [];

			foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
				$line = trim($line);

				if ($line === "" || $line[0] === "#" || !str_contains($line, "=")) {
					continue;
				}

				[$key, $value] = explode("=", $line, 2);
				$value = trim($value);

				if (strlen($value) >= 2 && $value[0] === $value[-1] && ($value[0] === '"' || $value[0] === "'")) {
					$value = substr($value, 1, -1);
				}

				$env[trim($key)] = $value;
			}

			return $env;
		}

		return [];
	}

	public function connect(): PDO {
		$host = $this->env["DB_HOST"] ?? "";
		$port = $this->env["DB_PORT"] ?? "5432";
		$name = $this->env["DB_NAME"] ?? "";
		$user = $this->env["DB_USER"] ?? "";
		$pass = $this->env["DB_PASSWORD"] ?? "";
		$ssl  = $this->env["DB_SSLMODE"] ?? "require";
		$debug = ($this->env["APP_DEBUG"] ?? "false") === "true";

		if ($host === "" || $name === "" || $user === "") {
			error_log("Database settings are missing. Check the .env file.");
			die($debug
				? "Database settings are missing. Check the .env file."
				: "Unable to connect to the database. Please try again later.");
		}

		try {
			$dsn = "pgsql:host=$host;port=$port;dbname=$name;sslmode=$ssl";
			$pdo = new PDO($dsn, $user, $pass);
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

			return $pdo;

		} catch (PDOException $e) {
			error_log("Database connection failed: " . $e->getMessage());

			die($debug
				? "Database Error: " . htmlspecialchars($e->getMessage())
				: "Unable to connect to the database. Please try again later.");
		}
	}
}