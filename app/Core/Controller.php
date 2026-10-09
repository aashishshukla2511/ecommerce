<?php

namespace App\Core;

abstract class Controller
{
    protected function render(string $view, array $data = [], ?string $layout = 'layouts/main'): Response
    {
        $content = View::render($view, $data, $layout);
        $response = new Response();
        $response->setContent($content);
        return $response;
    }

    protected function json(mixed $data, int $statusCode = 200): Response
    {
        $response = new Response();
        return $response->json($data, $statusCode);
    }

    protected function redirect(string $url): void
    {
        $response = new Response();
        $response->redirect($url);
    }
}
