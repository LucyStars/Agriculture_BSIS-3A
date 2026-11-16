<?php
// Planting model: every database query and validation rule for the planting table.

class Planting extends Model
{
    public const SORTABLE = [
        'id'       => 'p.planting_id',
        'farm'     => 'f.farm_name',
        'crop'     => 'c.crop_name',
        'date'     => 'p.planting_date',
        'area'     => 'p.area_planted',
        'expected' => 'p.expected_harvest_date',
        'status'   => 'st.status_description',
    ];

    public static function blank(): array
    {
        return ['farm_id' => '', 'crop_id' => '', 'planting_date' => '', 'area_planted' => '',
                'seed_quantity' => '', 'expected_harvest_date' => '', 'status_id' => ''];
    }

    public function statuses(): array
    {
        return $this->lookup('planting_status', 'status_id', 'status_description');
    }

    public function farms(): array
    {
        return $this->pdo->query("SELECT f.farm_id, f.farm_name || ' (' || fr.last_name || ')'
                                  FROM farm f JOIN farmer fr ON fr.farmer_id = f.farmer_id
                                  ORDER BY f.farm_name")->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function crops(): array
    {
        return $this->pdo->query("SELECT crop_id, crop_name || COALESCE(' (' || variety || ')', '')
                                  FROM crop ORDER BY crop_name, variety")->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // One page of the planting list, filtered by search text and status.
    public function search(string $search, int $status, string $sort, string $dir, int $page, int $perPage): array
    {
        $conditions = [];
        $params = [];
        if ($search !== '') {
            $conditions[] = '(f.farm_name ILIKE :s1 OR c.crop_name ILIKE :s2 OR c.variety ILIKE :s3)';
            $like = '%' . $search . '%';
            $params = [':s1' => $like, ':s2' => $like, ':s3' => $like];
        }
        if ($status > 0) {
            $conditions[] = 'p.status_id = :status';
            $params[':status'] = $status;
        }
        return $this->paginate(
            'SELECT p.planting_id, f.farm_name, c.crop_name, c.variety, p.planting_date, p.area_planted,
                    p.expected_harvest_date, st.status_description',
            'FROM planting p
             JOIN farm f ON f.farm_id = p.farm_id
             JOIN crop c ON c.crop_id = p.crop_id
             JOIN planting_status st ON st.status_id = p.status_id',
            $conditions,
            $params,
            self::SORTABLE[$sort] . ' ' . $dir . ', p.planting_id ASC',
            $page,
            $perPage
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT p.planting_id, p.farm_id, f.farm_name, fr.first_name, fr.last_name, p.crop_id, c.crop_name, c.variety,
                    p.planting_date, p.area_planted, p.seed_quantity, p.expected_harvest_date,
                    st.status_description, p.created_at, p.updated_at
             FROM planting p
             JOIN farm f ON f.farm_id = p.farm_id
             JOIN farmer fr ON fr.farmer_id = f.farmer_id
             JOIN crop c ON c.crop_id = p.crop_id
             JOIN planting_status st ON st.status_id = p.status_id
             WHERE p.planting_id = :id',
            $id
        );
    }

    // The harvests of a planting (shown on the planting details page).
    public function harvests(int $id): array
    {
        return $this->fetchAllFor(
            'SELECT h.harvest_id, h.harvest_date, h.quantity_kg, h.selling_price_per_kg,
                    h.quantity_kg * h.selling_price_per_kg AS estimated_value,
                    g.quality_grade_description, r.remarks_description
             FROM harvest h
             JOIN harvest_quality_grade g ON g.quality_grade_id = h.quality_grade_id
             JOIN harvest_remark r ON r.remarks_id = h.remarks_id
             WHERE h.planting_id = :id
             ORDER BY h.harvest_date, h.harvest_id',
            $id
        );
    }

    public function formValues(int $id): ?array
    {
        $row = $this->fetchOne(
            'SELECT farm_id, crop_id, planting_date, area_planted, seed_quantity, expected_harvest_date, status_id
             FROM planting WHERE planting_id = :id',
            $id
        );
        if (!$row) {
            return null;
        }
        return [
            'farm_id'               => (string)$row['farm_id'],
            'crop_id'               => (string)$row['crop_id'],
            'planting_date'         => (string)$row['planting_date'],
            'area_planted'          => (string)($row['area_planted'] ?? ''),
            'seed_quantity'         => (string)($row['seed_quantity'] ?? ''),
            'expected_harvest_date' => (string)($row['expected_harvest_date'] ?? ''),
            'status_id'             => (string)$row['status_id'],
        ];
    }

    public function validate(array $values, array $farms, array $crops, array $statuses): array
    {
        $errors = [];
        if (!array_key_exists((int)$values['farm_id'], $farms)) {
            $errors[] = 'Please choose a farm.';
        }
        if (!array_key_exists((int)$values['crop_id'], $crops)) {
            $errors[] = 'Please choose a crop.';
        }
        $plantingOk = false;
        if ($values['planting_date'] === '') {
            $errors[] = 'Planting date is required.';
        } elseif (!valid_date($values['planting_date'])) {
            $errors[] = 'Planting date is not a valid date.';
        } else {
            $plantingOk = true;
        }
        if ($values['area_planted'] === '') {
            $errors[] = 'Area planted is required.';
        } elseif (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $values['area_planted']) || (float)$values['area_planted'] <= 0) {
            $errors[] = 'Area planted must be a number greater than 0 (up to 2 decimal places).';
        }
        if ($values['seed_quantity'] !== '' && !preg_match('/^\d{1,6}(\.\d{1,2})?$/', $values['seed_quantity'])) {
            $errors[] = 'Seed quantity must be 0 or more (up to 2 decimal places).';
        }
        if ($values['expected_harvest_date'] !== '') {
            if (!valid_date($values['expected_harvest_date'])) {
                $errors[] = 'Expected harvest date is not a valid date.';
            } elseif ($plantingOk && $values['expected_harvest_date'] < $values['planting_date']) {
                $errors[] = 'Expected harvest date cannot be before the planting date.';
            }
        }
        if (!array_key_exists((int)$values['status_id'], $statuses)) {
            $errors[] = 'Please choose a status.';
        }
        return $errors;
    }

    public function save(int $id, array $values): void
    {
        $data = [
            ':f' => (int)$values['farm_id'], ':c' => (int)$values['crop_id'], ':pd' => $values['planting_date'],
            ':a' => $values['area_planted'],
            ':sq' => $values['seed_quantity'] === '' ? null : $values['seed_quantity'],
            ':eh' => $values['expected_harvest_date'] === '' ? null : $values['expected_harvest_date'],
            ':st' => (int)$values['status_id'],
        ];
        if ($id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE planting SET farm_id = :f, crop_id = :c, planting_date = :pd, area_planted = :a,
                 seed_quantity = :sq, expected_harvest_date = :eh, status_id = :st WHERE planting_id = :id'
            );
            $stmt->execute($data + [':id' => $id]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO planting (farm_id, crop_id, planting_date, area_planted, seed_quantity, expected_harvest_date, status_id)
                 VALUES (:f, :c, :pd, :a, :sq, :eh, :st)'
            );
            $stmt->execute($data);
        }
    }

    public function harvestCount(int $id): int
    {
        return $this->countUses('harvest', 'planting_id', $id);
    }

    public function delete(int $id): int
    {
        return $this->deleteById('planting', 'planting_id', $id);
    }
}
