<?php
session_start();

// Define the file to store the server data
$file = 'servers.json';

// Define a function to read existing servers from the file
function readServers($file) {
    if (file_exists($file)) {
        $data = file_get_contents($file);
        return json_decode($data, true);
    }
    return [];
}

// Define a function to save servers to the file
function saveServers($file, $servers) {
    file_put_contents($file, json_encode($servers, JSON_PRETTY_PRINT));
}

// Define a simple function to validate the token
function validateToken($token) {
    // Here, you can add your own token validation logic
    // For example, check against a predefined token or database
    return isset($_SESSION['auth_token']) && $_SESSION['auth_token'] === $token;
}

// Get the request method
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Handle token-based authentication
$headers = getallheaders();
$authToken = $headers['Authorization'] ?? null;

if ($authToken && preg_match('/Bearer\s(\S+)/', $authToken, $matches)) {
    $authToken = $matches[1];  // Extract the token
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!validateToken($authToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

if ($requestMethod === 'POST') {
    // Get the request body
    $body = file_get_contents('php://input');
    $serverData = json_decode($body, true);

    // Read existing servers
    $servers = readServers($file);

    // Add new server data directly
    $servers[] = $serverData;  // Append serverData directly

    // Save updated servers back to the file
    saveServers($file, $servers);

    // Respond with a success message
    echo json_encode(['message' => 'Server added successfully!']);
} elseif ($requestMethod === 'GET') {
    // Read existing servers and respond with the data
    $servers = readServers($file);
    echo json_encode($servers);
} elseif ($requestMethod === 'DELETE') {
    // Get the job ID from the URL
    $jobId = $_GET['jobId'] ?? null;

    if ($jobId) {
        // Read existing servers
        $servers = readServers($file);

        // Find and remove the server with the specified job ID
        $servers = array_filter($servers, function($server) use ($jobId) {
            return $server['JobId'] !== $jobId; // Adjust based on your actual key
        });

        // Save updated servers back to the file
        saveServers($file, $servers);

        // Respond with a success message
        echo json_encode(['message' => 'Server deleted successfully!']);
    } else {
        echo json_encode(['error' => 'Job ID not provided']);
    }
} else {
    // Handle other request methods
    echo json_encode(['error' => 'Invalid request method']);
}
?>
