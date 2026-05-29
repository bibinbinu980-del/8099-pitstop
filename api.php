<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
try {
    if ($action === 'customer') {
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) throw new Exception('Invalid customer id');

        $stmt = $pdo->prepare('SELECT id, name, phone, email, address, created_at FROM users WHERE id = ? AND role = "CUSTOMER"');
        $stmt->execute([$id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$customer) throw new Exception('Customer not found');

        // vehicles for this customer
        $v = $pdo->prepare('SELECT vehicle_no, brand, model, category, fuel_type FROM vehicles WHERE customer_id = ?');
        $v->execute([$id]);
        $vehicles = $v->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['customer' => $customer, 'vehicles' => $vehicles]);
        exit;
    }

    if ($action === 'vehicle') {
        $reg = trim($_GET['reg'] ?? '');
        if ($reg === '') throw new Exception('Invalid registration');

        $stmt = $pdo->prepare('SELECT v.*, u.id AS customer_id, u.name AS customer_name, u.phone AS customer_phone, u.email AS customer_email FROM vehicles v LEFT JOIN users u ON v.customer_id = u.id WHERE v.vehicle_no = ?');
        $stmt->execute([$reg]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$vehicle) throw new Exception('Vehicle not found');

        // job history for this vehicle
        $h = $pdo->prepare('SELECT j.job_card_id, j.status, j.created_at, j.repair_notes, u.name AS mechanic_name FROM job_cards j LEFT JOIN users u ON j.mechanic_id = u.id WHERE j.vehicle_no = ? ORDER BY j.created_at DESC');
        $h->execute([$reg]);
        $history = $h->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['vehicle' => $vehicle, 'history' => $history]);
        exit;
    }

    throw new Exception('Unknown action');
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
