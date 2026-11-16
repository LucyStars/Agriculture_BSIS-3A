<?php
// Base class for every model: the database connection and the queries all models share.

abstract class Model
{
    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = (new Database())->connect();
    }

    // Dropdown choices from a lookup table as [id => label].
    public function lookup(string $table, string $idColumn, string $labelColumn): array
    {
        return $this->pdo->query("SELECT $idColumn, $labelColumn FROM $table ORDER BY $idColumn")
                         ->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // Runs a list query with search conditions, sorting and paging.
    // Returns the rows, the total row count, the page count and the page actually shown.
    protected function paginate(string $select, string $from, array $conditions, array $params, string $orderBy, int $page, int $perPage): array
    {
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $count = $this->pdo->prepare("SELECT COUNT(*) $from $where");
        $count->execute($params);
        $totalRows = (int)$count->fetchColumn();
        $totalPages = max(1, (int)ceil($totalRows / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare("$select $from $where ORDER BY $orderBy LIMIT :limit OFFSET :offset");
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(), 'totalRows' => $totalRows, 'totalPages' => $totalPages, 'page' => $page];
    }

    // How many rows of $table point at the record $id (used by the delete rules).
    protected function countUses(string $table, string $column, int $id): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM $table WHERE $column = :id");
        $stmt->execute([':id' => $id]);
        return (int)$stmt->fetchColumn();
    }

    // Deletes one record and returns how many rows were removed (0 when it does not exist).
    protected function deleteById(string $table, string $column, int $id): int
    {
        $stmt = $this->pdo->prepare("DELETE FROM $table WHERE $column = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }

    // One row by id, or null when it does not exist.
    protected function fetchOne(string $sql, int $id): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    protected function fetchAllFor(string $sql, int $id): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll();
    }
}
