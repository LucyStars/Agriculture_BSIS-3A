<?php
// Crop controller: list, add/edit form, details page and delete for crops.

class CropController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $search = trim($_GET['search'] ?? '');
        $category = (int)($_GET['category'] ?? 0);
        $season = (int)($_GET['season'] ?? 0);
        $sort = array_key_exists($_GET['sort'] ?? '', Crop::SORTABLE) ? $_GET['sort'] : 'id';
        $dir = (($_GET['dir'] ?? '') === 'desc') ? 'desc' : 'asc';
        $perPage = 5;
        $page = max(1, (int)($_GET['page'] ?? 1));

        $crops = [];
        $categories = [];
        $seasons = [];
        $totalRows = 0;
        $totalPages = 1;
        $error = '';

        try {
            $model = new Crop();
            $categories = $model->categories();
            $seasons = $model->seasons();
            $result = $model->search($search, $category, $season, $sort, $dir, $page, $perPage);
            [$crops, $totalRows, $totalPages, $page] = [$result['rows'], $result['totalRows'], $result['totalPages'], $result['page']];
        } catch (Throwable $e) {
            error_log('Crops list error: ' . $e->getMessage());
            $error = 'Could not load the crops. Please try again later.';
        }

        $state = ['route' => 'crops', 'search' => $search, 'category' => $category, 'season' => $season,
                  'sort' => $sort, 'dir' => $dir, 'page' => $page];
        $this->render('crops/index', compact('crops', 'categories', 'seasons', 'search', 'category', 'season', 'sort', 'dir',
                                              'page', 'totalRows', 'totalPages', 'error', 'state') + ['pageTitle' => 'Crops']);
    }

    public function form(): void
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $isEdit = $id > 0;
        $values = Crop::blank();
        $errors = [];
        $categories = $seasons = [];

        try {
            $model = new Crop();
            $categories = $model->categories();
            $seasons = $model->seasons();

            if ($isEdit && !$this->isPost()) {
                $values = $model->formValues($id);
                if ($values === null) {
                    flash_set('error', 'That crop was not found.');
                    $this->redirect('crops');
                }
            }

            if ($this->isPost()) {
                $values = [
                    'crop_name'             => trim($_POST['crop_name'] ?? ''),
                    'variety'               => trim($_POST['variety'] ?? ''),
                    'category_id'           => trim($_POST['category_id'] ?? ''),
                    'growing_period_days'   => trim($_POST['growing_period_days'] ?? ''),
                    'recommended_season_id' => trim($_POST['recommended_season_id'] ?? ''),
                ];
                if (!csrf_check()) {
                    $errors[] = 'Your session expired. Please try again.';
                }
                $errors = array_merge($errors, $model->validate($values, $categories, $seasons));

                if (!$errors) {
                    $model->save($id, $values);
                    flash_set('success', $isEdit ? 'Crop updated.' : 'Crop added.');
                    $this->redirect('crops');
                }
            }
        } catch (Throwable $e) {
            if (is_duplicate_error($e)) {
                $errors[] = 'That crop and variety already exist.';
            } else {
                error_log('Crop form error: ' . $e->getMessage());
                $errors[] = 'Could not save the crop. Please try again later.';
            }
        }

        $this->render('crops/form', compact('id', 'isEdit', 'values', 'errors', 'categories', 'seasons')
                      + ['pageTitle' => $isEdit ? 'Edit Crop' : 'Add Crop']);
    }

    public function view(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);

        try {
            $model = new Crop();
            $crop = $model->find($id);
            if (!$crop) {
                flash_set('error', 'That crop was not found.');
                $this->redirect('crops');
            }
            $plantings = $model->plantings($id);
        } catch (Throwable $e) {
            error_log('Crop view error: ' . $e->getMessage());
            flash_set('error', 'Could not load the crop. Please try again later.');
            $this->redirect('crops');
        }

        $this->render('crops/view', compact('crop', 'plantings') + ['pageTitle' => 'Crop Details']);
    }

    public function delete(): void
    {
        $this->requireRole('Administrator');

        if (!$this->isPost()) {
            $this->redirect('crops');
        }
        if (!csrf_check()) {
            flash_set('error', 'Your session expired. Please try again.');
            $this->redirect('crops');
        }

        $id = (int)($_POST['id'] ?? 0);

        try {
            $model = new Crop();
            $plantings = $model->plantingCount($id);

            if ($plantings > 0) {
                flash_set('error', "Cannot delete: this crop is used in $plantings planting record(s). Delete those plantings first.");
            } else {
                $deleted = $model->delete($id);
                flash_set($deleted > 0 ? 'success' : 'error', $deleted > 0 ? 'Crop deleted.' : 'That crop was not found.');
            }
        } catch (Throwable $e) {
            error_log('Crop delete error: ' . $e->getMessage());
            flash_set('error', 'Could not delete the crop. Please try again later.');
        }

        $this->redirect('crops');
    }
}
