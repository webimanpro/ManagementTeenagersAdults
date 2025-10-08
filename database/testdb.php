<?php
// Include database configuration
require_once __DIR__ . '/config/database.php';

// Set content type to JSON
header('Content-Type: application/json; charset=utf-8');

$response = [
    'status' => 'success',
    'data' => []
];

try {
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE 'Teen'");
    if ($result->num_rows === 0) {
        throw new Exception("Table 'Teen' does not exist in the database.");
    }
    
    // Get table structure
    $columns = [];
    $result = $conn->query("DESCRIBE Teen");
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row;
    }
    
    // Get sample data (first 5 records)
    $sampleData = [];
    $result = $conn->query("SELECT * FROM Teen LIMIT 5");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $sampleData[] = $row;
        }
    }
    
    $response['data'] = [
        'table_structure' => $columns,
        'sample_data' => $sampleData,
        'total_records' => $conn->query("SELECT COUNT(*) as count FROM Teen")->fetch_assoc()['count']
    ];
    
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Close connection
if (isset($conn)) {
    $conn->close();
}
?>
