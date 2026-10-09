<?php

namespace App\Core;

use Dotenv\Dotenv;
use Throwable;

class App
{
    private Router $router;

    public function __construct()
    {
        $this->router = new Router();
    }

    public function init(): void
    {
        // Load environment variables
        $rootDir = dirname(__DIR__, 2);
        if (file_exists($rootDir . '/.env')) {
            $dotenv = Dotenv::createImmutable($rootDir);
            $dotenv->safeLoad();
        }

        // Initialize Session
        Auth::initSession();
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function run(): void
    {
        try {
            $this->init();
            
            // Load routes
            $router = $this->router;
            require __DIR__ . '/../../routes/web.php';

            $request = new Request();
            $response = $this->router->dispatch($request);
            $response->send();
        } catch (HttpException $e) {
            $this->handleHttpException($e);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    private function handleHttpException(HttpException $e): void
    {
        $code = $e->getStatusCode();
        $message = $e->getMessage();

        http_response_code($code);

        $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

        try {
            echo View::render('errors/error', [
                'code' => $code,
                'message' => $message,
                'debug' => $debug,
                'exception' => $debug ? $e : null,
            ], 'layouts/main');
        } catch (Throwable $renderEx) {
            echo "<h1>Error {$code}</h1><p>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</p>";
        }
    }

    private function handleException(Throwable $e): void
    {
        // Log technical error server-side
        error_log("Unhandled Exception: " . $e->getMessage() . "\nTrace:\n" . $e->getTraceAsString());

        $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
        http_response_code(500);

        try {
            echo View::render('errors/error', [
                'code' => 500,
                'message' => $debug ? $e->getMessage() : "An unexpected server error occurred. Please try again later.",
                'debug' => $debug,
                'exception' => $debug ? $e : null,
            ], 'layouts/main');
        } catch (Throwable $renderEx) {
            echo "<h1>500 Internal Server Error</h1>";
            if ($debug) {
                echo "<pre>" . htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') . "</pre>";
            } else {
                echo "<p>An unexpected server error occurred.</p>";
            }
        }
    }
}
