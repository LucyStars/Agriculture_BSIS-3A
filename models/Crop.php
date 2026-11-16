<?php
// Crop model: every database query and validation rule for the crop table.

class Crop extends Model
{
    public const SORTABLE = [
        'id'       => 'c.crop_id',
        'name'     => 'c.crop_name',
        'variety'  => 'c.variety',
        'category' => 'cc.category_description',
        'days'     => 'c.growing_period_days',
        'season'   => 's.recommended_season_description',
    ];

    public static function blank(): array
    {
        return ['crop_name' => '', 'variety' => '', 'category_id' => '', 'growing_period_days' => '', 'recommended_season_id' => ''];
    }

    public function categories(): array
    {
        return $this->lookup('crop_category', 'category_id', 'category_description');
    }

    public function seasons(): array
    {
        return $this->lookup('crop_recommended_season', 'recommended_season_id', 'recommended_season_description');
    }

    // One page of the crop list, filtered by search text, category and season.
    public function search(string $search, int $category, int $season, string $sort, string $dir, int $page, int $perPage): array
    {
        $conditions = [];
        $params = [];
        if ($search !== '') {
            $conditions[] = '(c.crop_name ILIKE :s1 OR c.variety ILIKE :s2)';
            $params = [':s1' => '%' . $search . '%', ':s2' => '%' . $search . '%'];
        }
        if ($category > 0) {
            $conditions[] = 'c.category_id = :category';
            $params[':category'] = $category;
        }
        if ($season > 0) {
            $conditions[] = 'c.recommended_season_id = :season';
            $params[':season'] = $season;
        }
        return $this->paginate(
            'SELECT c.crop_id, c.crop_name, c.variety, cc.category_description,
                    c.growing_period_days, s.recommended_season_description',
            'FROM crop c
             JOIN crop_category cc ON cc.category_id = c.category_id
             JOIN crop_recommended_season s ON s.recommended_season_id = c.recommended_season_id',
            $conditions,
            $params,
            self::SORTABLE[$sort] . ' ' . $dir . ', c.crop_id ASC',
            $page,
            $perPage
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT c.crop_id, c.crop_name, c.variety, cc.category_description, c.growing_period_days,
                    s.recommended_season_description, c.created_at, c.updated_at
             FROM crop c
             JOIN crop_category cc ON cc.category_id = c.category_id
             JOIN crop_recommended_season s ON s.recommended_season_id = c.recommended_season_id
             WHERE c.crop_id = :id',
            $id
        );
    }

    // The plantings of a crop (shown on the crop details page).
    public function plantings(int $id): array
    {
        return $this->fetchAllFor(
            'SELECT p.planting_id, f.farm_name, p.planting_date, p.area_planted, st.status_description
             FROM planting p
             JOIN farm f ON f.farm_id = p.farm_id
             JOIN planting_status st ON st.status_id = p.status_id
             WHERE p.crop_id = :id
             ORDER BY p.planting_id',
            $id
        );
    }

    public function formValues(int $id): ?array
    {
        $row = $this->fetchOne(
            'SELECT crop_name, variety, category_id, growing_period_days, recommended_season_id FROM crop WHERE crop_id = :id',
            $id
        );
        if (!$row) {
            return null;
        }
        return [
            'crop_name'             => $row['crop_name'],
            'variety'               => $row['variety'] ?? '',
            'category_id'           => (string)$row['category_id'],
            'growing_period_days'   => (string)($row['growing_period_days'] ?? ''),
            'recommended_season_id' => (string)$row['recommended_season_id'],
        ];
    }

    public function validate(array $values, array $categories, array $seasons): array
    {
        $errors = [];
        if ($values['crop_name'] === '') {
            $errors[] = 'Crop name is required.';
        } elseif (mb_strlen($values['crop_name']) > 80) {
            $errors[] = 'Crop name must be 80 characters or fewer.';
        }
        if (mb_strlen($values['variety']) > 80) {
            $errors[] = 'Variety must be 80 characters or fewer.';
        }
        if (!array_key_exists((int)$values['category_id'], $categories)) {
            $errors[] = 'Please choose a category.';
        }
        if ($values['growing_period_days'] !== '' &&
            (!ctype_digit($values['growing_period_days']) || (int)$values['growing_period_days'] < 1 || (int)$values['growing_period_days'] > 3650)) {
            $errors[] = 'Growing period must be a whole number of days from 1 to 3650.';
        }
        if (!array_key_exists((int)$values['recommended_season_id'], $seasons)) {
            $errors[] = 'Please choose a recommended season.';
        }
        return $errors;
    }

    public function save(int $id, array $values): void
    {
        $data = [
            ':n' => $values['crop_name'],
            ':v' => $values['variety'] === '' ? null : $values['variety'],
            ':c' => (int)$values['category_id'],
            ':d' => $values['growing_period_days'] === '' ? null : (int)$values['growing_period_days'],
            ':s' => (int)$values['recommended_season_id'],
        ];
        if ($id > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE crop SET crop_name = :n, variety = :v, category_id = :c,
                 growing_period_days = :d, recommended_season_id = :s WHERE crop_id = :id'
            );
            $stmt->execute($data + [':id' => $id]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO crop (crop_name, variety, category_id, growing_period_days, recommended_season_id)
                 VALUES (:n, :v, :c, :d, :s)'
            );
            $stmt->execute($data);
        }
    }

    public function plantingCount(int $id): int
    {
        return $this->countUses('planting', 'crop_id', $id);
    }

    public function delete(int $id): int
    {
        return $this->deleteById('crop', 'crop_id', $id);
    }
}
