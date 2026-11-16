<?php
// Farmer model: every database query and validation rule for the farmer table.

class Farmer extends Model
{
    public const SORTABLE = [
        'id'      => 'farmer_id',
        'name'    => 'last_name',
        'contact' => 'contact_number',
        'address' => 'address',
        'date'    => 'date_registered',
    ];

    public static function blank(): array
    {
        return ['first_name' => '', 'last_name' => '', 'contact_number' => '', 'address' => ''];
    }

    // The distinct addresses for the filter dropdown.
    public function addresses(): array
    {
        return $this->pdo->query('SELECT DISTINCT address FROM farmer WHERE address IS NOT NULL ORDER BY address')
                         ->fetchAll(PDO::FETCH_COLUMN);
    }

    // One page of the farmer list, filtered by search text and address.
    public function search(string $search, string $address, string $sort, string $dir, int $page, int $perPage): array
    {
        $conditions = [];
        $params = [];
        if ($search !== '') {
            $conditions[] = '(first_name ILIKE :s1 OR last_name ILIKE :s2 OR contact_number ILIKE :s3 OR address ILIKE :s4)';
            $like = '%' . $search . '%';
            $params = [':s1' => $like, ':s2' => $like, ':s3' => $like, ':s4' => $like];
        }
        if ($address !== '') {
            $conditions[] = 'address = :address';
            $params[':address'] = $address;
        }
        return $this->paginate(
            'SELECT farmer_id, first_name, last_name, contact_number, address, date_registered',
            'FROM farmer',
            $conditions,
            $params,
            self::SORTABLE[$sort] . ' ' . $dir . ', farmer_id ASC',
            $page,
            $perPage
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT farmer_id, first_name, last_name, contact_number, address, date_registered, created_at, updated_at
             FROM farmer WHERE farmer_id = :id',
            $id
        );
    }

    // The farms owned by a farmer (shown on the farmer details page).
    public function farms(int $id): array
    {
        return $this->fetchAllFor(
            'SELECT f.farm_id, f.farm_name, f.location, f.area_hectares,
                    s.soil_type_description, i.irrigation_type_description
             FROM farm f
             JOIN farm_soil_type s ON s.soil_type_id = f.soil_type_id
             JOIN farm_irrigation_type i ON i.irrigation_type_id = f.irrigation_type_id
             WHERE f.farmer_id = :id
             ORDER BY f.farm_id',
            $id
        );
    }

    // The values of one farmer as the edit form needs them, or null when not found.
    public function formValues(int $id): ?array
    {
        $row = $this->fetchOne('SELECT first_name, last_name, contact_number, address FROM farmer WHERE farmer_id = :id', $id);
        if (!$row) {
            return null;
        }
        return [
            'first_name'     => $row['first_name'],
            'last_name'      => $row['last_name'],
            'contact_number' => $row['contact_number'] ?? '',
            'address'        => $row['address'] ?? '',
        ];
    }

    // Returns the list of error messages for the submitted values (empty when all are valid).
    public function validate(array $values): array
    {
        $errors = [];
        if ($values['first_name'] === '') {
            $errors[] = 'First name is required.';
        } elseif (mb_strlen($values['first_name']) > 50) {
            $errors[] = 'First name must be 50 characters or fewer.';
        }
        if ($values['last_name'] === '') {
            $errors[] = 'Last name is required.';
        } elseif (mb_strlen($values['last_name']) > 50) {
            $errors[] = 'Last name must be 50 characters or fewer.';
        }
        if ($values['contact_number'] !== '' && !preg_match('/^[0-9+\- ]{7,20}$/', $values['contact_number'])) {
            $errors[] = 'Contact number must be 7 to 20 characters: digits, spaces, + or - only.';
        }
        if (mb_strlen($values['address']) > 150) {
            $errors[] = 'Address must be 150 characters or fewer.';
        }
        return $errors;
    }

    // Inserts a new farmer (when $id is 0) or updates an existing one.
    public function save(int $id, array $values): void
    {
        $data = [
            ':f' => $values['first_name'],
            ':l' => $values['last_name'],
            ':c' => $values['contact_number'] === '' ? null : $values['contact_number'],
            ':a' => $values['address'] === '' ? null : $values['address'],
        ];
        if ($id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE farmer SET first_name = :f, last_name = :l, contact_number = :c, address = :a WHERE farmer_id = :id'
            );
            $stmt->execute($data + [':id' => $id]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO farmer (first_name, last_name, contact_number, address) VALUES (:f, :l, :c, :a)'
            );
            $stmt->execute($data);
        }
    }

    public function farmCount(int $id): int
    {
        return $this->countUses('farm', 'farmer_id', $id);
    }

    public function delete(int $id): int
    {
        return $this->deleteById('farmer', 'farmer_id', $id);
    }
}
