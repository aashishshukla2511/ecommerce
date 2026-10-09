<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDOException;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $dbStatus = 'Disconnected';
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->query("SELECT DATABASE()");
            $dbName = $stmt->fetchColumn();
            $dbStatus = "Connected to database '{$dbName}'";
        } catch (PDOException $e) {
            $dbStatus = "Database Error: " . $e->getMessage();
        }

        return $this->render('home/index', [
            'title' => 'Home',
            'dbStatus' => $dbStatus,
        ]);
    }
}
