<?php
header("Content-Type: text/plain");

require_once "Database.php";

$pdo = (new Database())->connect();

$database = $pdo->query("SELECT current_database()")->fetchColumn();

$tables = $pdo->query("
	SELECT COUNT(*)
	FROM information_schema.tables
	WHERE table_schema = 'public'
	AND table_type = 'BASE TABLE'
")->fetchColumn();

$farmers = $pdo->query("SELECT COUNT(*) FROM farmer")->fetchColumn();
$farms   = $pdo->query("SELECT COUNT(*) FROM farm")->fetchColumn();

echo "Connection successful." . PHP_EOL;
echo "Database : " . $database . PHP_EOL;
echo "Tables   : " . $tables . " (expected 14)" . PHP_EOL;
echo "Farmers  : " . $farmers . " (expected 15)" . PHP_EOL;
echo "Farms    : " . $farms . " (expected 15)" . PHP_EOL;
