<?php
// src/config/log.php

function logMessage($message, $level = 'INFO', $logFile = '/path/to/your/logfile.log') {
    $date = date('Y-m-d H:i:s');
    $formattedMessage = "[$date] [$level] $message" . PHP_EOL;
    
    // Ensure the directory exists
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }

    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}