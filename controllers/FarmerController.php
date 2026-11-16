<?php
// Farmer controller: list, add/edit form, details page and delete for farmers.

class FarmerController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $search = trim($_GET['search'] ?? '');
        $address = trim($_GET['address'] ?? '');
        $sort = array_key_exists($_GET['sort'] ?? '', Farmer::SORTABLE) ? $_GET['sort'] : 'id';
        $dir = (($_GET['dir'] ?? '') === 'desc') ? 'desc' : 'asc';
        $perPage = 5;
        $page = max(1, (int)($_GET['page'] ?? 1));

        $farmers = [];
        $addresses = [];
        $totalRows = 0;
        $totalPages = 1;
        $error = '';

        try {
            $model = new Farmer();
            $addresses = $model->addresses();
            $result = $model->search($search, $address, $sort, $dir, $page, $perPage);
            [$farmers, $totalRows, $totalPages, $page] = [$result['rows'], $result['totalRows'], $result['totalPages'], $result['page']];
        } catch (Throwable $e) {
            error_log('Farmers list error: ' . $e->getMessage());
            $error = 'Could not load the farmers. Please try again later.';
        }

        $state = ['route' => 'farmers', 'search' => $search, 'address' => $address, 'sort' => $sort, 'dir' => $dir, 'page' => $page];
        $this->render('farmers/index', compact('farmers', 'addresses', 'search', 'address', 'sort', 'dir', 'page',
                                                'totalRows', 'totalPages', 'error', 'state') + ['pageTitle' => 'Farmers']);
    }

    public function form(): void
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $isEdit = $id > 0;
        $values = Farmer::blank();
        $errors = [];

        try {
            $model = new Farmer();

            if ($isEdit && !$this->isPost()) {
                $values = $model->formValues($id);
                if ($values === null) {
                    flash_set('error', 'That farmer was not found.');
                    $this->redirect('farmers');
                }
            }

            if ($this->isPost()) {
                $values = [
                    'first_name'     => trim($_POST['first_name'] ?? ''),
                    'last_name'      => trim($_POST['last_name'] ?? ''),
                    'contact_number' => trim($_POST['contact_number'] ?? ''),
                    'address'        => trim($_POST['address'] ?? ''),
                ];
                if (!csrf_check()) {
                    $errors[] = 'Your session expired. Please try again.';
                }
                $errors = array_merge($errors, $model->validate($values));

                if (!$errors) {
                    $model->save($id, $values);
                    flash_set('success', $isEdit ? 'Farmer updated.' : 'Farmer added.');
                    $this->redirect('farmers');
                }
            }
        } catch (Throwable $e) {
            error_log('Farmer form error: ' . $e->getMessage());
            $errors[] = 'Could not save the farmer. Please try again later.';
        }

        $this->render('farmers/form', compact('id', 'isEdit', 'values', 'errors')
                      + ['pageTitle' => $isEdit ? 'Edit Farmer' : 'Add Farmer']);
    }

    public function view(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);

        try {
            $model = new Farmer();
            $farmer = $model->find($id);
            if (!$farmer) {
                flash_set('error', 'That farmer was not found.');
                $this->redirect('farmers');
            }
            $farms = $model->farms($id);
        } catch (Throwable $e) {
            error_log('Farmer view error: ' . $e->getMessage());
            flash_set('error', 'Could not load the farmer. Please try again later.');
            $this->redirect('farmers');
        }

        $this->render('farmers/view', compact('farmer', 'farms') + ['pageTitle' => 'Farmer Details']);
    }

    public function delete(): void
    {
        $this->requireRole('Administrator');

        if (!$this->isPost()) {
            $this->redirect('farmers');
        }
        if (!csrf_check()) {
            flash_set('error', 'Your session expired. Please try again.');
            $this->redirect('farmers');
        }

        $id = (int)($_POST['id'] ?? 0);

        try {
            $model = new Farmer();
            $farms = $model->farmCount($id);

            if ($farms > 0) {
                flash_set('error', "Cannot delete: this farmer still has $farms farm(s). Delete or reassign the farms first.");
            } else {
                $deleted = $model->delete($id);
                flash_set($deleted > 0 ? 'success' : 'error', $deleted > 0 ? 'Farmer deleted.' : 'That farmer was not found.');
            }
        } catch (Throwable $e) {
            error_log('Farmer delete error: ' . $e->getMessage());
            flash_set('error', 'Could not delete the farmer. Please try again later.');
        }

        $this->redirect('farmers');
    }
}
