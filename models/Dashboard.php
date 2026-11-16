<?php
// Dashboard model: the counts, totals and chart data shown on the dashboard.

class Dashboard extends Model
{
    // The five main tables that get a count card.
    public const TABLES = ['farmer', 'farm', 'crop', 'planting', 'harvest'];

    public function counts(): array
    {
        $counts = [];
        foreach (self::TABLES as $table) {
            $counts[$table] = (int)$this->pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        }
        return $counts;
    }

    // Total kilograms harvested and their estimated value: [kg, value].
    public function totals(): array
    {
        $totals = $this->pdo->query(
            'SELECT COALESCE(SUM(quantity_kg), 0) AS kg, COALESCE(SUM(quantity_kg * selling_price_per_kg), 0) AS value FROM harvest'
        )->fetch();
        return [(float)$totals['kg'], (float)$totals['value']];
    }

    public function plantingsByStatus(): array
    {
        return $this->pdo->query(
            'SELECT st.status_description, COUNT(p.planting_id) AS total
             FROM planting_status st
             LEFT JOIN planting p ON p.status_id = st.status_id
             GROUP BY st.status_id, st.status_description
             ORDER BY st.status_id'
        )->fetchAll();
    }

    // Kilograms harvested in each of the last 12 months that have harvests.
    public function kgByMonth(): array
    {
        $rows = $this->pdo->query(
            "SELECT to_char(harvest_date, 'YYYY-MM') AS ym, SUM(quantity_kg) AS kg
             FROM harvest
             GROUP BY ym
             ORDER BY ym"
        )->fetchAll();
        return array_slice($rows, -12);
    }

    public function kgByCrop(): array
    {
        return $this->pdo->query(
            "SELECT c.crop_name || COALESCE(' (' || c.variety || ')', '') AS label, SUM(h.quantity_kg) AS value
             FROM harvest h
             JOIN planting p ON p.planting_id = h.planting_id
             JOIN crop c ON c.crop_id = p.crop_id
             GROUP BY c.crop_id
             ORDER BY value DESC, label
             LIMIT 8"
        )->fetchAll();
    }

    public function valueByCrop(): array
    {
        return $this->pdo->query(
            "SELECT c.crop_name || COALESCE(' (' || c.variety || ')', '') AS label,
                    SUM(h.quantity_kg * h.selling_price_per_kg) AS value
             FROM harvest h
             JOIN planting p ON p.planting_id = h.planting_id
             JOIN crop c ON c.crop_id = p.crop_id
             GROUP BY c.crop_id
             ORDER BY value DESC, label
             LIMIT 8"
        )->fetchAll();
    }

    public function recentPlantings(): array
    {
        return $this->pdo->query(
            'SELECT p.planting_id, f.farm_name, c.crop_name, p.planting_date, st.status_description
             FROM planting p
             JOIN farm f ON f.farm_id = p.farm_id
             JOIN crop c ON c.crop_id = p.crop_id
             JOIN planting_status st ON st.status_id = p.status_id
             ORDER BY p.planting_date DESC, p.planting_id DESC
             LIMIT 5'
        )->fetchAll();
    }

    public function recentHarvests(): array
    {
        return $this->pdo->query(
            'SELECT h.harvest_id, f.farm_name, c.crop_name, h.harvest_date, h.quantity_kg
             FROM harvest h
             JOIN planting p ON p.planting_id = h.planting_id
             JOIN farm f ON f.farm_id = p.farm_id
             JOIN crop c ON c.crop_id = p.crop_id
             ORDER BY h.harvest_date DESC, h.harvest_id DESC
             LIMIT 5'
        )->fetchAll();
    }
}
