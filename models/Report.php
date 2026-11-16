<?php
// Report model: the queries behind the six reports. Each takes an optional from/to date range.

class Report extends Model
{
    // Builds the date conditions for a column and fills in the query parameters.
    private function dateConditions(string $column, string $from, string $to, array &$params): array
    {
        $parts = [];
        if ($from !== '') {
            $parts[] = "$column >= :from";
            $params[':from'] = $from;
        }
        if ($to !== '') {
            $parts[] = "$column <= :to";
            $params[':to'] = $to;
        }
        return $parts;
    }

    private function run(string $sql, array $params): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Every farmer with their farms, filtered by date registered.
    public function farmers(string $from, string $to): array
    {
        $params = [];
        $conditions = $this->dateConditions('fr.date_registered', $from, $to, $params);
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return $this->run(
            "SELECT fr.last_name, fr.first_name, fr.contact_number, fr.date_registered,
                    f.farm_name, f.location, f.area_hectares,
                    s.soil_type_description, i.irrigation_type_description
             FROM farmer fr
             LEFT JOIN farm f ON f.farmer_id = fr.farmer_id
             LEFT JOIN farm_soil_type s ON s.soil_type_id = f.soil_type_id
             LEFT JOIN farm_irrigation_type i ON i.irrigation_type_id = f.irrigation_type_id
             $where
             ORDER BY fr.last_name, fr.first_name, f.farm_name",
            $params
        );
    }

    // Plantings and area of each crop per farm, filtered by planting date.
    public function cropsPerFarm(string $from, string $to): array
    {
        $params = [];
        $conditions = $this->dateConditions('p.planting_date', $from, $to, $params);
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return $this->run(
            "SELECT f.farm_name, fr.last_name, fr.first_name, c.crop_name, c.variety,
                    COUNT(*) AS plantings, SUM(p.area_planted) AS area
             FROM planting p
             JOIN farm f ON f.farm_id = p.farm_id
             JOIN farmer fr ON fr.farmer_id = f.farmer_id
             JOIN crop c ON c.crop_id = p.crop_id
             $where
             GROUP BY f.farm_id, fr.farmer_id, c.crop_id
             ORDER BY f.farm_name, c.crop_name, c.variety",
            $params
        );
    }

    // Number of plantings and area for each status, filtered by planting date.
    public function byStatus(string $from, string $to): array
    {
        $params = [];
        $conditions = $this->dateConditions('p.planting_date', $from, $to, $params);
        $on = $conditions ? ' AND ' . implode(' AND ', $conditions) : '';
        return $this->run(
            "SELECT st.status_description, COUNT(p.planting_id) AS plantings,
                    COALESCE(SUM(p.area_planted), 0) AS area
             FROM planting_status st
             LEFT JOIN planting p ON p.status_id = st.status_id $on
             GROUP BY st.status_id, st.status_description
             ORDER BY st.status_id",
            $params
        );
    }

    // Every harvest with quantity, price and value, filtered by harvest date.
    public function harvests(string $from, string $to): array
    {
        $params = [];
        $conditions = $this->dateConditions('h.harvest_date', $from, $to, $params);
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return $this->run(
            "SELECT h.harvest_id, h.harvest_date, f.farm_name, c.crop_name, h.quantity_kg,
                    h.selling_price_per_kg, (h.quantity_kg * h.selling_price_per_kg) AS value,
                    g.quality_grade_description, r.remarks_description
             FROM harvest h
             JOIN planting p ON p.planting_id = h.planting_id
             JOIN farm f ON f.farm_id = p.farm_id
             JOIN crop c ON c.crop_id = p.crop_id
             JOIN harvest_quality_grade g ON g.quality_grade_id = h.quality_grade_id
             JOIN harvest_remark r ON r.remarks_id = h.remarks_id
             $where
             ORDER BY h.harvest_date, h.harvest_id",
            $params
        );
    }

    // Kilograms harvested per crop, filtered by harvest date.
    public function quantity(string $from, string $to): array
    {
        $params = [];
        $conditions = $this->dateConditions('h.harvest_date', $from, $to, $params);
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return $this->run(
            "SELECT c.crop_name, c.variety, COUNT(h.harvest_id) AS harvests, SUM(h.quantity_kg) AS total_kg
             FROM harvest h
             JOIN planting p ON p.planting_id = h.planting_id
             JOIN crop c ON c.crop_id = p.crop_id
             $where
             GROUP BY c.crop_id
             ORDER BY total_kg DESC, c.crop_name",
            $params
        );
    }

    // Quantity multiplied by selling price per crop, filtered by harvest date.
    public function value(string $from, string $to): array
    {
        $params = [];
        $conditions = $this->dateConditions('h.harvest_date', $from, $to, $params);
        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return $this->run(
            "SELECT c.crop_name, c.variety, SUM(h.quantity_kg) AS total_kg,
                    SUM(h.quantity_kg * h.selling_price_per_kg) AS total_value
             FROM harvest h
             JOIN planting p ON p.planting_id = h.planting_id
             JOIN crop c ON c.crop_id = p.crop_id
             $where
             GROUP BY c.crop_id
             ORDER BY total_value DESC, c.crop_name",
            $params
        );
    }
}
