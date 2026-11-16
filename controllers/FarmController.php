<?php
// Farm controller: list, add/edit form, details page and delete for farms.

class FarmController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $search = trim($_GET['search'] ?? '');
        $irrigation = (int)($_GET['irrigation'] ?? 0);
        $soil = (int)($_GET['soil'] ?? 0);
        $sort = array_key_exists($_GET['sort'] ?? '', Farm::SORTABLE) ? $_GET['sort'] : 'id';
        $dir = (($_GET['dir'] ?? '') === 'desc') ? 'desc' : 'asc';
        $perPage = 5;
        $page = max(1, (int)($_GET['page'] ?? 1));

        $farms = [];
        $irrigations = [];
        $soils = [];
        $totalRows = 0;
        $totalPages = 1;
        $error = '';

        try {
            $model = new Farm();
            $irrigations = $model->irrigations();
            $soils = $model->soils();
            $result = $model->search($search, $irrigation, $soil, $sort, $dir, $page, $perPage);
            [$farms, $totalRows, $totalPages, $page] = [$result['rows'], $result['totalRows'], $result['totalPages'], $result['page']];
        } catch (Throwable $e) {
            error_log('Farms list error: ' . $e->getMessage());
            $error = 'Could not load the farms. Please try again later.';
        }

        $state = ['route' => 'farms', 'search' => $search, 'irrigation' => $irrigation, 'soil' => $soil,
                  'sort' => $sort, 'dir' => $dir, 'page' => $page];
        $this->render('farms/index', compact('farms', 'irrigations', 'soils', 'search', 'irrigation', 'soil', 'sort', 'dir',
                                              'page', 'totalRows', 'totalPages', 'error', 'state') + ['pageTitle' => 'Farms']);
    }

    public function form(): void
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $isEdit = $id > 0;
        $values = Farm::blank();
        $errors = [];
        $farmers = $soils = $irrigations = [];

        try {
            $model = new Farm();
            $farmers = $model->farmers();
            $soils = $model->soils();
            $irrigations = $model->irrigations();

            if ($isEdit && !$this->isPost()) {
                $values = $model->formValues($id);
                if ($values === null) {
                    flash_set('error', 'That farm was not found.');
                    $this->redirect('farms');
                }
            }

            if ($this->isPost()) {
                $values = [
                    'farmer_id'          => trim($_POST['farmer_id'] ?? ''),
                    'farm_name'          => trim($_POST['farm_name'] ?? ''),
                    'location'           => trim($_POST['location'] ?? ''),
                    'area_hectares'      => trim($_POST['area_hectares'] ?? ''),
                    'soil_type_id'       => trim($_POST['soil_type_id'] ?? ''),
                    'irrigation_type_id' => trim($_POST['irrigation_type_id'] ?? ''),
                ];
                if (!csrf_check()) {
                    $errors[] = 'Your session expired. Please try again.';
                }
                $errors = array_merge($errors, $model->validate($values, $farmers, $soils, $irrigations));

                if (!$errors) {
                    $model->save($id, $values);
                    flash_set('success', $isEdit ? 'Farm updated.' : 'Farm added.');
                    $this->redirect('farms');
                }
            }
        } catch (Throwable $e) {
            if (is_duplicate_error($e)) {
                $errors[] = 'This farmer already has a farm with that name.';
            } else {
                error_log('Farm form error: ' . $e->getMessage());
                $errors[] = 'Could not save the farm. Please try again later.';
            }
        }

        $this->render('farms/form', compact('id', 'isEdit', 'values', 'errors', 'farmers', 'soils', 'irrigations')
                      + ['pageTitle' => $isEdit ? 'Edit Farm' : 'Add Farm']);
    }

    public function view(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);

        try {
            $model = new Farm();
            $farm = $model->find($id);
            if (!$farm) {
                flash_set('error', 'That farm was not found.');
                $this->redirect('farms');
            }
            $plantings = $model->plantings($id);
        } catch (Throwable $e) {
            error_log('Farm view error: ' . $e->getMessage());
            flash_set('error', 'Could not load the farm. Please try again later.');
            $this->redirect('farms');
        }

        $this->render('farms/view', compact('farm', 'plantings') + ['pageTitle' => 'Farm Details']);
    }

    public function delete(): void
    {
        $this->requireRole('Administrator');

        if (!$this->isPost()) {
            $this->redirect('farms');
        }
        if (!csrf_check()) {
            flash_set('error', 'Your session expired. Please try again.');
            $this->redirect('farms');
        }

        $id = (int)($_POST['id'] ?? 0);

        try {
            $model = new Farm();
            $plantings = $model->plantingCount($id);

            if ($plantings > 0) {
                flash_set('error', "Cannot delete: this farm has $plantings planting record(s). Delete those plantings first.");
            } else {
                $deleted = $model->delete($id);
                flash_set($deleted > 0 ? 'success' : 'error', $deleted > 0 ? 'Farm deleted.' : 'That farm was not found.');
            }
        } catch (Throwable $e) {
            error_log('Farm delete error: ' . $e->getMessage());
            flash_set('error', 'Could not delete the farm. Please try again later.');
        }

        $this->redirect('farms');
    }
}
