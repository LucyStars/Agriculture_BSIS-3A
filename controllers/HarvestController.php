<?php
// Harvest controller: list, add/edit form, details page and delete for harvest records.

class HarvestController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $search = trim($_GET['search'] ?? '');
        $grade = (int)($_GET['grade'] ?? 0);
        $remark = (int)($_GET['remark'] ?? 0);
        $sort = array_key_exists($_GET['sort'] ?? '', Harvest::SORTABLE) ? $_GET['sort'] : 'id';
        $dir = (($_GET['dir'] ?? '') === 'desc') ? 'desc' : 'asc';
        $perPage = 5;
        $page = max(1, (int)($_GET['page'] ?? 1));

        $harvests = [];
        $grades = [];
        $remarks = [];
        $totalRows = 0;
        $totalPages = 1;
        $error = '';

        try {
            $model = new Harvest();
            $grades = $model->grades();
            $remarks = $model->remarks();
            $result = $model->search($search, $grade, $remark, $sort, $dir, $page, $perPage);
            [$harvests, $totalRows, $totalPages, $page] = [$result['rows'], $result['totalRows'], $result['totalPages'], $result['page']];
        } catch (Throwable $e) {
            error_log('Harvests list error: ' . $e->getMessage());
            $error = 'Could not load the harvest records. Please try again later.';
        }

        $state = ['route' => 'harvests', 'search' => $search, 'grade' => $grade, 'remark' => $remark,
                  'sort' => $sort, 'dir' => $dir, 'page' => $page];
        $this->render('harvests/index', compact('harvests', 'grades', 'remarks', 'search', 'grade', 'remark', 'sort', 'dir',
                                                 'page', 'totalRows', 'totalPages', 'error', 'state') + ['pageTitle' => 'Harvest Records']);
    }

    public function form(): void
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $isEdit = $id > 0;
        $values = Harvest::blank();
        $errors = [];
        $plantings = $grades = $remarks = [];
        $plantingDates = [];

        try {
            $model = new Harvest();
            [$plantings, $plantingDates] = $model->plantingChoices();
            $grades = $model->grades();
            $remarks = $model->remarks();

            if ($isEdit && !$this->isPost()) {
                $values = $model->formValues($id);
                if ($values === null) {
                    flash_set('error', 'That harvest record was not found.');
                    $this->redirect('harvests');
                }
            }

            if ($this->isPost()) {
                $values = [
                    'planting_id'          => trim($_POST['planting_id'] ?? ''),
                    'harvest_date'         => trim($_POST['harvest_date'] ?? ''),
                    'quantity_kg'          => trim($_POST['quantity_kg'] ?? ''),
                    'selling_price_per_kg' => trim($_POST['selling_price_per_kg'] ?? ''),
                    'quality_grade_id'     => trim($_POST['quality_grade_id'] ?? ''),
                    'remarks_id'           => trim($_POST['remarks_id'] ?? ''),
                ];
                if (!csrf_check()) {
                    $errors[] = 'Your session expired. Please try again.';
                }
                $errors = array_merge($errors, $model->validate($values, $plantings, $plantingDates, $grades, $remarks));

                if (!$errors) {
                    $model->save($id, $values);
                    flash_set('success', $isEdit ? 'Harvest record updated.' : 'Harvest record added.');
                    $this->redirect('harvests');
                }
            }
        } catch (Throwable $e) {
            error_log('Harvest form error: ' . $e->getMessage());
            $errors[] = 'Could not save the harvest record. Please try again later.';
        }

        $this->render('harvests/form', compact('id', 'isEdit', 'values', 'errors', 'plantings', 'grades', 'remarks')
                      + ['pageTitle' => $isEdit ? 'Edit Harvest Record' : 'Add Harvest Record']);
    }

    public function view(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);

        try {
            $h = (new Harvest())->find($id);
            if (!$h) {
                flash_set('error', 'That harvest record was not found.');
                $this->redirect('harvests');
            }
        } catch (Throwable $e) {
            error_log('Harvest view error: ' . $e->getMessage());
            flash_set('error', 'Could not load the harvest record. Please try again later.');
            $this->redirect('harvests');
        }

        $this->render('harvests/view', compact('h') + ['pageTitle' => 'Harvest Details']);
    }

    public function delete(): void
    {
        $this->requireRole('Administrator');

        if (!$this->isPost()) {
            $this->redirect('harvests');
        }
        if (!csrf_check()) {
            flash_set('error', 'Your session expired. Please try again.');
            $this->redirect('harvests');
        }

        $id = (int)($_POST['id'] ?? 0);

        try {
            $deleted = (new Harvest())->delete($id);
            flash_set($deleted > 0 ? 'success' : 'error', $deleted > 0 ? 'Harvest record deleted.' : 'That harvest record was not found.');
        } catch (Throwable $e) {
            error_log('Harvest delete error: ' . $e->getMessage());
            flash_set('error', 'Could not delete the harvest record. Please try again later.');
        }

        $this->redirect('harvests');
    }
}
