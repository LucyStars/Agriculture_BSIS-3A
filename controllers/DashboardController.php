<?php
// Dashboard controller: the home page with counts, totals, charts and recent records.

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        // Count cards: table => [label, route of the module].
        $tables = [
            'farmer'   => ['Farmers',   'farmers'],
            'farm'     => ['Farms',     'farms'],
            'crop'     => ['Crops',     'crops'],
            'planting' => ['Plantings', 'plantings'],
            'harvest'  => ['Harvests',  'harvests'],
        ];
        $counts = [];
        $totalKg = 0;
        $totalValue = 0;
        $byStatus = [];
        $byMonth = [];
        $kgByCrop = [];
        $valueByCrop = [];
        $recentPlantings = [];
        $recentHarvests = [];
        $error = '';

        try {
            $model = new Dashboard();
            $counts = $model->counts();
            [$totalKg, $totalValue] = $model->totals();
            $byStatus = $model->plantingsByStatus();
            $byMonth = $model->kgByMonth();
            $kgByCrop = $model->kgByCrop();
            $valueByCrop = $model->valueByCrop();
            $recentPlantings = $model->recentPlantings();
            $recentHarvests = $model->recentHarvests();
        } catch (Throwable $e) {
            error_log('Dashboard error: ' . $e->getMessage());
            $error = 'Could not load the dashboard numbers. Please try again later.';
        }

        $monthLabels = array_map(fn($ym) => date('M y', strtotime($ym . '-01')), array_column($byMonth, 'ym'));

        $this->render('dashboard/index', compact('tables', 'counts', 'totalKg', 'totalValue', 'byStatus', 'byMonth', 'kgByCrop',
                                                  'valueByCrop', 'recentPlantings', 'recentHarvests', 'error', 'monthLabels')
                      + ['pageTitle' => 'Dashboard']);
    }
}
