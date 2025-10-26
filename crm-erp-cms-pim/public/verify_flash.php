<?php
/**
 * Flash Message Verification Script
 * This script verifies the flash message implementation is working correctly
 */

require_once __DIR__ . '/../core/bootstrap.php';

use Core\Session\SessionManager;

// Start session
SessionManager::start();

// Set some test flash messages
SessionManager::flash('success', 'This is a SUCCESS message - Implementation working!');
SessionManager::flash('error', 'This is an ERROR message - Implementation working!');
SessionManager::flash('warning', 'This is a WARNING message - Implementation working!');
SessionManager::flash('info', 'This is an INFO message - Implementation working!');

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flash Message Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .debug-box {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            font-family: monospace;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-left: 10px;
        }
        .status-ok { background: #d4edda; color: #155724; }
        .status-fail { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">🔍 Flash Message Implementation Verification</h1>
        
        <div class="alert alert-info">
            <strong>Test Scenario:</strong> This page sets 4 flash messages (success, error, warning, info) and displays them below.
            After refresh, messages should disappear (flash expiry).
        </div>
        
        <hr>
        
        <h3>Flash Messages Display:</h3>
        
        <?php
        // Display flash messages like in the guest layout
        $flashTypes = ['success', 'error', 'warning', 'info'];
        $messagesFound = 0;
        
        foreach ($flashTypes as $type) {
            $message = $_SESSION['flash'][$type] ?? null;
            if ($message) {
                $messagesFound++;
                $alertClass = $type === 'error' ? 'danger' : $type;
                echo '<div class="alert alert-' . $alertClass . ' alert-dismissible fade show" role="alert">';
                echo htmlspecialchars($message);
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                echo '</div>';
            }
        }
        
        if ($messagesFound === 0) {
            echo '<div class="alert alert-secondary">No flash messages to display. Try refreshing the page.</div>';
        }
        ?>
        
        <hr>
        
        <h3>Debug Information:</h3>
        
        <div class="debug-box">
            <h5>Session Flash Array Status:</h5>
            <pre><?php 
            if (isset($_SESSION['flash'])) {
                echo "✓ \$_SESSION['flash'] exists\n";
                echo "Content:\n";
                print_r($_SESSION['flash']);
            } else {
                echo "✗ \$_SESSION['flash'] does not exist\n";
            }
            ?></pre>
        </div>
        
        <div class="debug-box">
            <h5>Flash Keys Tracking:</h5>
            <pre><?php 
            $flashKeys = SessionManager::get('_flash_keys', []);
            if (!empty($flashKeys)) {
                echo "✓ Flash keys tracked: " . count($flashKeys) . " keys\n";
                print_r($flashKeys);
            } else {
                echo "✗ No flash keys tracked\n";
            }
            ?></pre>
        </div>
        
        <div class="debug-box">
            <h5>Implementation Status:</h5>
            <p>
                Flash Storage Path: 
                <code>$_SESSION['flash'][$key]</code>
                <?php if (isset($_SESSION['flash'])) : ?>
                    <span class="status-badge status-ok">✓ Correct</span>
                <?php else : ?>
                    <span class="status-badge status-fail">✗ Not Found</span>
                <?php endif; ?>
            </p>
            <p>
                Messages Found: 
                <strong><?= $messagesFound ?> / 4</strong>
                <?php if ($messagesFound === 4) : ?>
                    <span class="status-badge status-ok">✓ All Messages Display</span>
                <?php elseif ($messagesFound > 0) : ?>
                    <span class="status-badge status-fail">⚠ Partial Display</span>
                <?php else : ?>
                    <span class="status-badge status-fail">✗ No Messages</span>
                <?php endif; ?>
            </p>
        </div>
        
        <hr>
        
        <div class="d-flex gap-2">
            <a href="verify_flash.php" class="btn btn-primary">🔄 Refresh Page (Test Flash Expiry)</a>
            <a href="/login" class="btn btn-secondary">Go to Login Page</a>
            <a href="test_flash_messages.html" class="btn btn-info">Test with Real Login</a>
        </div>
        
        <div class="mt-4 alert alert-warning">
            <strong>Expected Behavior:</strong>
            <ul class="mb-0">
                <li>First load: All 4 colored alert messages should appear</li>
                <li>After refresh: All messages should disappear</li>
                <li>Flash storage should use <code>$_SESSION['flash'][$key]</code> path</li>
            </ul>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
