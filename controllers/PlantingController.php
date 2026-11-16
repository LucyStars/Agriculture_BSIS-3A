<?php
// Planting controller: list, add/edit form, details page and delete for planting records.

class PlantingController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $search = trim($_GET['search'] ?? '');
        $status = (int)($_GET['status'] ?? 0);
        $sort = array_key_exists($_GET['sort'] ?? '', Planting::SORTABLE) ? $_GET['sort'] : 'id';
        $dir = (($_GET['dir'] ?? '') === 'desc') ? 'desc' : 'asc';
        $perPage = 5;
        $page = max(1, (int)($_GET['page'] ?? 1));

        $plantings = [];
        $statuses = [];
        $totalRows = 0;
        $totalPages = 1;
        $error = '';

        try {
            $model = new Planting();
            $statuses = $model->statuses();
            $result = $model->search($search, $status, $sort, $dir, $page, $perPage);
            [$plantings, $totalRows, $totalPages, $page] = [$result['rows'], $result['totalRows'], $result['totalPages'], $result['page']];
        } catch (Throwable $e) {
            error_log('Plantings list error: ' . $e->getMessage());
            $error = 'Could not load the planting records. Please try again later.';
        }

        $state = ['route' => 'plantings', 'search' => $search, 'status' => $status, 'sort' => $sort, 'dir' => $dir, 'page' => $page];
        $this->render('plantings/index', compact('plantings', 'statuses', 'search', 'status', 'sort', 'dir', 'page',
                                                  'totalRows', 'totalPages', 'error', 'state') + ['pageTitle' => 'Planting Records']);
    }

    public function form(): void
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $isEdit = $id > 0;
        $values = Planting::blank();
        $errors = [];
        $farms = $crops = $statuses = [];

        try {
            $model = new Planting();
            $farms = $model->farms();
            $crops = $model->crops();
            $statuses = $model->statuses();

            if ($isEdit && !$this->isPost()) {
                $values = $model->formValues($id);
                if ($values === null) {
                    flash_set('error', 'That planting record was not found.');
                    $this->redirect('plantings');
                }
            }

            if ($this->isPost()) {
                $values = [
                    'farm_id'               => trim($_POST['farm_id'] ?? ''),
                    'crop_id'               => trim($_POST['crop_id'] ?? ''),
                    'planting_date'         => trim($_POST['planting_date'] ?? ''),
                    'area_planted'          => trim($_POST['area_planted'] ?? ''),
                    'seed_quantity'         => trim($_POST['seed_quantity'] ?? ''),
                    'expected_harvest_date' => trim($_POST['expected_harvest_date'] ?? ''),
                    'status_id'             => trim($_POST['status_id'] ?? ''),
                ];
                if (!csrf_check()) {
                    $errors[] = 'Your session expired. Please try again.';
                }
                $errors = array_merge($errors, $model->validate($values, $farms, $crops, $statuses));

                if (!$errors) {
                    $model->save($id, $values);
                    flash_set('success', $isEdit ? 'Planting record updated.' : 'Planting record added.');
                    $this->redirect('plantings');
                }
            }
        } catch (Throwable $e) {
            error_log('Planting form error: ' . $e->getMessage());
            $errors[] = 'Could not save the planting record. Please try again later.';
        }

        $this->render('plantings/form', compact('id', 'isEdit', 'values', 'errors', 'farms', 'crops', 'statuses')
                      + ['pageTitle' => $isEdit ? 'Edit Planting Record' : 'Add Planting Record']);
    }

    public function view(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);

        try {
            $model = new Planting();
            $planting = $model->find($id);
            if (!$planting) {
                flash_set('error', 'That planting record was not found.');
                $this->redirect('plantings');
            }
            $harvests = $model->harvests($id);
        } catch (Throwable $e) {
            error_log('Planting view error: ' . $e->getMessage());
            flash_set('error', 'Could not load the planting record. Please try again later.');
            $this->redirect('plantings');
        }

        $this->render('plantings/view', compact('planting', 'harvests') + ['pageTitle' => 'Planting Details']);
    }

    public function delete(): void
    {
        $this->requireRole('Administrator');

        if (!$this->isPost()) {
            $this->redirect('plantings');
        }
        if (!csrf_check()) {
            flash_set('error', 'Your session expired. Please try again.');
            $this->redirect('plantings');
        }

        $id = (int)($_POST['id'] ?? 0);

        try {
            $model = new Planting();
            $harvests = $model->harvestCount($id);

            if ($harvests > 0) {
                flash_set('error', "Cannot delete: this planting has $harvests harvest record(s). Delete those harvests first.");
            } else {
                $deleted = $model->delete($id);
                flash_set($deleted > 0 ? 'success' : 'error', $deleted > 0 ? 'Planting record deleted.' : 'That planting record was not found.');
            }
        } catch (Throwable $e) {
            error_log('Planting delete error: ' . $e->getMessage());
            flash_set('error', 'Could not delete the planting record. Please try again later.');
        }

        $this->redirect('plantings');
    }
}
