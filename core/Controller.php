<?php
// Base class for every controller: showing a view, redirecting, and access control.

abstract class Controller
{
    // Shows a view inside the page layout (header and footer). $data becomes the variables of the view.
    protected function render(string $view, array $data = []): void
    {
        $route = current_route();
        $pageTitle = $data['pageTitle'] ?? 'Agriculture Information System';
        extract($data);
        require BASE_PATH . '/views/layout/header.php';
        require BASE_PATH . '/views/' . $view . '.php';
        require BASE_PATH . '/views/layout/footer.php';
    }

    protected function redirect(string $route, array $params = []): never
    {
        header('Location: ' . route_path($route, $params));
        exit;
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function requireLogin(): void
    {
        if (!is_logged_in()) {
            $this->redirect('login');
        }
    }

    protected function requireRole(string $role): void
    {
        $this->requireLogin();
        if ($_SESSION['role_name'] !== $role) {
            http_response_code(403);
            $this->render('layout/denied', ['pageTitle' => 'Access denied']);
            exit;
        }
    }
}
