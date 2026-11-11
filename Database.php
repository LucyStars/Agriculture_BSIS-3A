<?php
class Database {
	private array $env;

	public function __construct() {
		$this->env = $this->loadEnv();
	}

	// Read the settings from the .env file (same folder, or the folder above)
	private function loadEnv(): array {
		$paths = [__DIR__ . "/.env", dirname(__DIR__) . "/.env"];

		foreach ($paths as $path) {
			if (!is_readable($path)) {
				continue;
			}

			$env = [];

			foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
				$line = trim($line);

				// skip comments and lines without an equal sign
				if ($line === "" || $line[0] === "#" || !str_contains($line, "=")) {
					continue;
				}

				[$key, $value] = explode("=", $line, 2);
				$value = trim($value);

				// remove quotes around the value, if there are any
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
			// the real error goes to the server log, not to the user
			error_log("Database connection failed: " . $e->getMessage());

			die($debug
				? "Database Error: " . htmlspecialchars($e->getMessage())
				: "Unable to connect to the database. Please try again later.");
		}
	}
}