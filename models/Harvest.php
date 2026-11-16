<?php
// Harvest model: every database query and validation rule for the harvest table.

class Harvest extends Model
{
    public const SORTABLE = [
        'id'      => 'h.harvest_id',
        'farm'    => 'f.farm_name',
        'crop'    => 'c.crop_name',
        'date'    => 'h.harvest_date',
        'qty'     => 'h.quantity_kg',
        'price'   => 'h.selling_price_per_kg',
        'value'   => '(h.quantity_kg * h.selling_price_per_kg)',
        'grade'   => 'g.quality_grade_description',
        'remarks' => 'r.remarks_description',
    ];

    public static function blank(): array
    {
        return ['planting_id' => '', 'harvest_date' => '', 'quantity_kg' => '', 'selling_price_per_kg' => '',
                'quality_grade_id' => '', 'remarks_id' => ''];
    }

    public function grades(): array
    {
        return $this->lookup('harvest_quality_grade', 'quality_grade_id', 'quality_grade_description');
    }

    public function remarks(): array
    {
        return $this->lookup('harvest_remark', 'remarks_id', 'remarks_description');
    }

    // The plantings a harvest can belong to: [labels, planting dates], both keyed by planting id.
    public function plantingChoices(): array
    {
        $rows = $this->pdo->query("SELECT p.planting_id, p.planting_date, f.farm_name, c.crop_name
                                   FROM planting p
                                   JOIN farm f ON f.farm_id = p.farm_id
                                   JOIN crop c ON c.crop_id = p.crop_id
                                   ORDER BY p.planting_id")->fetchAll();
        $labels = [];
        $dates = [];
        foreach ($rows as $r) {
            $labels[$r['planting_id']] = '#' . $r['planting_id'] . ' ' . $r['farm_name'] . ' - ' . $r['crop_name']
                                       . ' (planted ' . $r['planting_date'] . ')';
            $dates[$r['planting_id']] = $r['planting_date'];
        }
        return [$labels, $dates];
    }

    // One page of the harvest list, filtered by search text, quality grade and remarks.
    public function search(string $search, int $grade, int $remark, string $sort, string $dir, int $page, int $perPage): array
    {
        $conditions = [];
        $params = [];
        if ($search !== '') {
            $conditions[] = '(f.farm_name ILIKE :s1 OR c.crop_name ILIKE :s2 OR r.remarks_description ILIKE :s3)';
            $like = '%' . $search . '%';
            $params = [':s1' => $like, ':s2' => $like, ':s3' => $like];
        }
        if ($grade > 0) {
            $conditions[] = 'h.quality_grade_id = :grade';
            $params[':grade'] = $grade;
        }
        if ($remark > 0) {
            $conditions[] = 'h.remarks_id = :remark';
            $params[':remark'] = $remark;
        }
        return $this->paginate(
            'SELECT h.harvest_id, f.farm_name, c.crop_name, h.harvest_date, h.quantity_kg, h.selling_price_per_kg,
                    (h.quantity_kg * h.selling_price_per_kg) AS estimated_value,
                    g.quality_grade_description, r.remarks_description',
            'FROM harvest h
             JOIN planting p ON p.planting_id = h.planting_id
             JOIN farm f ON f.farm_id = p.farm_id
             JOIN crop c ON c.crop_id = p.crop_id
             JOIN harvest_quality_grade g ON g.quality_grade_id = h.quality_grade_id
             JOIN harvest_remark r ON r.remarks_id = h.remarks_id',
            $conditions,
            $params,
            self::SORTABLE[$sort] . ' ' . $dir . ', h.harvest_id ASC',
            $page,
            $perPage
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT h.harvest_id, h.planting_id, p.planting_date, f.farm_id, f.farm_name, fr.first_name, fr.last_name,
                    c.crop_id, c.crop_name, c.variety, h.harvest_date, h.quantity_kg, h.selling_price_per_kg,
                    (h.quantity_kg * h.selling_price_per_kg) AS estimated_value,
                    g.quality_grade_description, r.remarks_description, h.created_at, h.updated_at
             FROM harvest h
             JOIN planting p ON p.planting_id = h.planting_id
             JOIN farm f ON f.farm_id = p.farm_id
             JOIN farmer fr ON fr.farmer_id = f.farmer_id
             JOIN crop c ON c.crop_id = p.crop_id
             JOIN harvest_quality_grade g ON g.quality_grade_id = h.quality_grade_id
             JOIN harvest_remark r ON r.remarks_id = h.remarks_id
             WHERE h.harvest_id = :id',
            $id
        );
    }

    public function formValues(int $id): ?array
    {
        $row = $this->fetchOne(
            'SELECT planting_id, harvest_date, quantity_kg, selling_price_per_kg, quality_grade_id, remarks_id
             FROM harvest WHERE harvest_id = :id',
            $id
        );
        if (!$row) {
            return null;
        }
        return [
            'planting_id'          => (string)$row['planting_id'],
            'harvest_date'         => (string)$row['harvest_date'],
            'quantity_kg'          => (string)($row['quantity_kg'] ?? ''),
            'selling_price_per_kg' => (string)($row['selling_price_per_kg'] ?? ''),
            'quality_grade_id'     => (string)$row['quality_grade_id'],
            'remarks_id'           => (string)$row['remarks_id'],
        ];
    }

    public function validate(array $values, array $plantings, array $plantingDates, array $grades, array $remarks): array
    {
        $errors = [];
        $plantingOk = array_key_exists((int)$values['planting_id'], $plantings);
        if (!$plantingOk) {
            $errors[] = 'Please choose a planting record.';
        }
        if ($values['harvest_date'] === '') {
            $errors[] = 'Harvest date is required.';
        } elseif (!valid_date($values['harvest_date'])) {
            $errors[] = 'Harvest date is not a valid date.';
        } elseif ($plantingOk && $values['harvest_date'] < $plantingDates[(int)$values['planting_id']]) {
            $errors[] = 'Harvest date cannot be before the planting date (' . $plantingDates[(int)$values['planting_id']] . ').';
        }
        if ($values['quantity_kg'] === '') {
            $errors[] = 'Quantity is required.';
        } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $values['quantity_kg'])) {
            $errors[] = 'Quantity must be 0 or more (up to 2 decimal places).';
        }
        if ($values['selling_price_per_kg'] === '') {
            $errors[] = 'Selling price is required.';
        } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $values['selling_price_per_kg'])) {
            $errors[] = 'Selling price must be 0 or more (up to 2 decimal places).';
        }
        if (!array_key_exists((int)$values['quality_grade_id'], $grades)) {
            $errors[] = 'Please choose a quality grade.';
        }
        if (!array_key_exists((int)$values['remarks_id'], $remarks)) {
            $errors[] = 'Please choose the remarks.';
        }
        return $errors;
    }

    public function save(int $id, array $values): void
    {
        $data = [':p' => (int)$values['planting_id'], ':d' => $values['harvest_date'], ':q' => $values['quantity_kg'],
                 ':pr' => $values['selling_price_per_kg'], ':g' => (int)$values['quality_grade_id'],
                 ':r' => (int)$values['remarks_id']];
        if ($id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE harvest SET planting_id = :p, harvest_date = :d, quantity_kg = :q,
                 selling_price_per_kg = :pr, quality_grade_id = :g, remarks_id = :r WHERE harvest_id = :id'
            );
            $stmt->execute($data + [':id' => $id]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO harvest (planting_id, harvest_date, quantity_kg, selling_price_per_kg, quality_grade_id, remarks_id)
                 VALUES (:p, :d, :q, :pr, :g, :r)'
            );
            $stmt->execute($data);
        }
    }

    public function delete(int $id): int
    {
        return $this->deleteById('harvest', 'harvest_id', $id);
    }
}
