<?php
// Farm model: every database query and validation rule for the farm table.

class Farm extends Model
{
    public const SORTABLE = [
        'id'         => 'f.farm_id',
        'name'       => 'f.farm_name',
        'farmer'     => 'fr.last_name',
        'location'   => 'f.location',
        'area'       => 'f.area_hectares',
        'soil'       => 's.soil_type_description',
        'irrigation' => 'i.irrigation_type_description',
    ];

    public static function blank(): array
    {
        return ['farmer_id' => '', 'farm_name' => '', 'location' => '', 'area_hectares' => '',
                'soil_type_id' => '', 'irrigation_type_id' => ''];
    }

    // Dropdown choices.
    public function irrigations(): array
    {
        return $this->lookup('farm_irrigation_type', 'irrigation_type_id', 'irrigation_type_description');
    }

    public function soils(): array
    {
        return $this->lookup('farm_soil_type', 'soil_type_id', 'soil_type_description');
    }

    public function farmers(): array
    {
        return $this->pdo->query("SELECT farmer_id, last_name || ', ' || first_name FROM farmer ORDER BY last_name, first_name")
                         ->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // One page of the farm list, filtered by search text, irrigation type and soil type.
    public function search(string $search, int $irrigation, int $soil, string $sort, string $dir, int $page, int $perPage): array
    {
        $conditions = [];
        $params = [];
        if ($search !== '') {
            $conditions[] = '(f.farm_name ILIKE :s1 OR f.location ILIKE :s2 OR fr.first_name ILIKE :s3 OR fr.last_name ILIKE :s4)';
            $like = '%' . $search . '%';
            $params = [':s1' => $like, ':s2' => $like, ':s3' => $like, ':s4' => $like];
        }
        if ($irrigation > 0) {
            $conditions[] = 'f.irrigation_type_id = :irrigation';
            $params[':irrigation'] = $irrigation;
        }
        if ($soil > 0) {
            $conditions[] = 'f.soil_type_id = :soil';
            $params[':soil'] = $soil;
        }
        return $this->paginate(
            'SELECT f.farm_id, f.farm_name, fr.first_name, fr.last_name, f.location, f.area_hectares,
                    s.soil_type_description, i.irrigation_type_description',
            'FROM farm f
             JOIN farmer fr ON fr.farmer_id = f.farmer_id
             JOIN farm_soil_type s ON s.soil_type_id = f.soil_type_id
             JOIN farm_irrigation_type i ON i.irrigation_type_id = f.irrigation_type_id',
            $conditions,
            $params,
            self::SORTABLE[$sort] . ' ' . $dir . ', f.farm_id ASC',
            $page,
            $perPage
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT f.farm_id, f.farm_name, f.farmer_id, fr.first_name, fr.last_name, f.location, f.area_hectares,
                    s.soil_type_description, i.irrigation_type_description, f.created_at, f.updated_at
             FROM farm f
             JOIN farmer fr ON fr.farmer_id = f.farmer_id
             JOIN farm_soil_type s ON s.soil_type_id = f.soil_type_id
             JOIN farm_irrigation_type i ON i.irrigation_type_id = f.irrigation_type_id
             WHERE f.farm_id = :id',
            $id
        );
    }

    // The plantings done on a farm (shown on the farm details page).
    public function plantings(int $id): array
    {
        return $this->fetchAllFor(
            'SELECT p.planting_id, c.crop_name, c.variety, p.planting_date, p.area_planted, st.status_description
             FROM planting p
             JOIN crop c ON c.crop_id = p.crop_id
             JOIN planting_status st ON st.status_id = p.status_id
             WHERE p.farm_id = :id
             ORDER BY p.planting_date, p.planting_id',
            $id
        );
    }

    public function formValues(int $id): ?array
    {
        $row = $this->fetchOne(
            'SELECT farmer_id, farm_name, location, area_hectares, soil_type_id, irrigation_type_id FROM farm WHERE farm_id = :id',
            $id
        );
        if (!$row) {
            return null;
        }
        return [
            'farmer_id'          => (string)$row['farmer_id'],
            'farm_name'          => $row['farm_name'],
            'location'           => $row['location'],
            'area_hectares'      => (string)($row['area_hectares'] ?? ''),
            'soil_type_id'       => (string)$row['soil_type_id'],
            'irrigation_type_id' => (string)$row['irrigation_type_id'],
        ];
    }

    // Returns the list of error messages; the dropdown choices are passed in to check the chosen ids exist.
    public function validate(array $values, array $farmers, array $soils, array $irrigations): array
    {
        $errors = [];
        if (!array_key_exists((int)$values['farmer_id'], $farmers)) {
            $errors[] = 'Please choose a farmer.';
        }
        if ($values['farm_name'] === '') {
            $errors[] = 'Farm name is required.';
        } elseif (mb_strlen($values['farm_name']) > 100) {
            $errors[] = 'Farm name must be 100 characters or fewer.';
        }
        if ($values['location'] === '') {
            $errors[] = 'Location is required.';
        } elseif (mb_strlen($values['location']) > 150) {
            $errors[] = 'Location must be 150 characters or fewer.';
        }
        if ($values['area_hectares'] === '') {
            $errors[] = 'Area is required.';
        } elseif (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $values['area_hectares']) || (float)$values['area_hectares'] <= 0) {
            $errors[] = 'Area must be a number greater than 0 (up to 2 decimal places).';
        }
        if (!array_key_exists((int)$values['soil_type_id'], $soils)) {
            $errors[] = 'Please choose a soil type.';
        }
        if (!array_key_exists((int)$values['irrigation_type_id'], $irrigations)) {
            $errors[] = 'Please choose an irrigation type.';
        }
        return $errors;
    }

    public function save(int $id, array $values): void
    {
        $data = [':fr' => (int)$values['farmer_id'], ':n' => $values['farm_name'], ':l' => $values['location'],
                 ':a' => $values['area_hectares'], ':s' => (int)$values['soil_type_id'],
                 ':i' => (int)$values['irrigation_type_id']];
        if ($id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE farm SET farmer_id = :fr, farm_name = :n, location = :l, area_hectares = :a,
                 soil_type_id = :s, irrigation_type_id = :i WHERE farm_id = :id'
            );
            $stmt->execute($data + [':id' => $id]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO farm (farmer_id, farm_name, location, area_hectares, soil_type_id, irrigation_type_id)
                 VALUES (:fr, :n, :l, :a, :s, :i)'
            );
            $stmt->execute($data);
        }
    }

    public function plantingCount(int $id): int
    {
        return $this->countUses('planting', 'farm_id', $id);
    }

    public function delete(int $id): int
    {
        return $this->deleteById('farm', 'farm_id', $id);
    }
}
