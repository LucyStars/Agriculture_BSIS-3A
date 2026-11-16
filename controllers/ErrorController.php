<?php
// Error controller: the page shown for an address that does not exist.

class ErrorController extends Controller
{
    public function notFound(): void
    {
        http_response_code(404);
        $this->render('layout/not_found', ['pageTitle' => 'Page not found']);
    }
}
