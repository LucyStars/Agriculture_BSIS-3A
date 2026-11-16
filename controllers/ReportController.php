<?php
// Report controller: the reports menu and the six reports, each with an optional date range.

class ReportController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        // Report title => [route, description].
        $reports = [
            'Farmers and their farms'  => ['report_farmers',  'Every farmer with their farms. Filter by date registered.'],
            'Crops planted per farm'   => ['report_crops',    'How many plantings and how much area of each crop, per farm. Filter by planting date.'],
            'Plantings by status'      => ['report_status',   'Number of plantings and area for each status. Filter by planting date.'],
            'Harvest records'          => ['report_harvests', 'Every harvest with quantity, price and value. Filter by harvest date.'],
            'Total harvested quantity' => ['report_quantity', 'Kilograms harvested per crop. Filter by harvest date.'],
            'Estimated harvest value'  => ['report_value',    'Quantity x selling price per crop. Filter by harvest date.'],
        ];

        $this->render('reports/index', compact('reports') + ['pageTitle' => 'Reports']);
    }

    public function farmers(): void
    {
        [$from, $to, $errors, $rows, $error] = $this->load('farmers', 'Report farmers error: ');
        $totalArea = array_sum(array_map(fn($r) => (float)$r['area_hectares'], $rows));
        $this->render('reports/farmers', compact('from', 'to', 'errors', 'rows', 'error', 'totalArea')
                      + ['pageTitle' => 'Report: Farmers and Their Farms']);
    }

    public function crops(): void
    {
        [$from, $to, $errors, $rows, $error] = $this->load('cropsPerFarm', 'Report crops error: ');
        $totalPlantings = array_sum(array_map(fn($r) => (int)$r['plantings'], $rows));
        $totalArea = array_sum(array_map(fn($r) => (float)$r['area'], $rows));
        $this->render('reports/crops', compact('from', 'to', 'errors', 'rows', 'error', 'totalPlantings', 'totalArea')
                      + ['pageTitle' => 'Report: Crops Planted per Farm']);
    }

    public function status(): void
    {
        [$from, $to, $errors, $rows, $error] = $this->load('byStatus', 'Report status error: ');
        $totalPlantings = array_sum(array_map(fn($r) => (int)$r['plantings'], $rows));
        $totalArea = array_sum(array_map(fn($r) => (float)$r['area'], $rows));
        $this->render('reports/status', compact('from', 'to', 'errors', 'rows', 'error', 'totalPlantings', 'totalArea')
                      + ['pageTitle' => 'Report: Plantings by Status']);
    }

    public function harvests(): void
    {
        [$from, $to, $errors, $rows, $error] = $this->load('harvests', 'Report harvests error: ');
        $totalKg = array_sum(array_map(fn($r) => (float)$r['quantity_kg'], $rows));
        $totalValue = array_sum(array_map(fn($r) => (float)$r['value'], $rows));
        $this->render('reports/harvests', compact('from', 'to', 'errors', 'rows', 'error', 'totalKg', 'totalValue')
                      + ['pageTitle' => 'Report: Harvest Records']);
    }

    public function quantity(): void
    {
        [$from, $to, $errors, $rows, $error] = $this->load('quantity', 'Report quantity error: ');
        $totalHarvests = array_sum(array_map(fn($r) => (int)$r['harvests'], $rows));
        $totalKg = array_sum(array_map(fn($r) => (float)$r['total_kg'], $rows));
        $this->render('reports/quantity', compact('from', 'to', 'errors', 'rows', 'error', 'totalHarvests', 'totalKg')
                      + ['pageTitle' => 'Report: Total Harvested Quantity']);
    }

    public function value(): void
    {
        [$from, $to, $errors, $rows, $error] = $this->load('value', 'Report value error: ');
        $totalKg = array_sum(array_map(fn($r) => (float)$r['total_kg'], $rows));
        $totalValue = array_sum(array_map(fn($r) => (float)$r['total_value'], $rows));
        $this->render('reports/value', compact('from', 'to', 'errors', 'rows', 'error', 'totalKg', 'totalValue')
                      + ['pageTitle' => 'Report: Estimated Harvest Value']);
    }

    // Checks the date range, then runs the named Report query. Returns [from, to, errors, rows, error].
    private function load(string $method, string $logPrefix): array
    {
        $this->requireLogin();
        [$from, $to, $errors] = $this->range();
        $rows = [];
        $error = '';

        if (!$errors) {
            try {
                $rows = (new Report())->$method($from, $to);
            } catch (Throwable $e) {
                error_log($logPrefix . $e->getMessage());
                $error = 'Could not load the report. Please try again later.';
            }
        }
        return [$from, $to, $errors, $rows, $error];
    }

    // Reads and validates the from/to dates from the address.
    private function range(): array
    {
        $from = trim($_GET['from'] ?? '');
        $to = trim($_GET['to'] ?? '');
        $errors = [];

        if ($from !== '' && !valid_date($from)) {
            $errors[] = 'The "from" date is not a valid date.';
        }
        if ($to !== '' && !valid_date($to)) {
            $errors[] = 'The "to" date is not a valid date.';
        }
        if (!$errors && $from !== '' && $to !== '' && $from > $to) {
            $errors[] = 'The "from" date cannot be after the "to" date.';
        }
        return [$from, $to, $errors];
    }
}
