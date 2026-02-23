<?php
/**
 * Dynamic search endpoint for quiz questions
 * Returns JSON results matching the search query
 */

// Set response header
header('Content-Type: application/json');

try {
    // Get search query from GET parameter
    $searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    // Return empty results if search is empty
    if (empty($searchQuery)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    
    // Database configuration
    $host = '127.0.0.1';
    $port = 4306;
    $user = 'root';
    $password = '';
    $database = 'middestgar';
    
    // Connect to database
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Prepare search term
    $searchTerm = '%' . $searchQuery . '%';
    
    // Query questions that match the search
    $sql = "SELECT q.id, q.question, q.createdAt,
                   GROUP_CONCAT(DISTINCT r.option SEPARATOR ', ') as options,
                   GROUP_CONCAT(DISTINCT r.tag SEPARATOR ', ') as tags
            FROM questions q
            LEFT JOIN reponses r ON q.id = r.question
            WHERE q.question LIKE :search 
                  OR r.option LIKE :search 
                  OR r.tag LIKE :search
            GROUP BY q.id
            ORDER BY q.createdAt DESC
            LIMIT 50";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return results as JSON
    echo json_encode([
        'success' => true,
        'data' => $results,
        'count' => count($results)
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
