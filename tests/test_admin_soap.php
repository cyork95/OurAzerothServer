<?php

if (!defined('SOAP_RPC')) define('SOAP_RPC', 1);

$mockSoapState = 'success'; // can be 'connection_error', 'execution_error', 'success'

class SoapClient {
    public function __construct($wsdl, $options = []) {
        global $mockSoapState;
        if ($mockSoapState === 'connection_error') {
            throw new Exception("Connection refused");
        }
    }
    public function executeCommand($param) {
        global $mockSoapState;
        if ($mockSoapState === 'execution_error') {
            throw new Exception("Command execution failed");
        }
        return "Server is running fine.";
    }
}

class SoapParam {
    public function __construct($data, $name) {}
}

$_GET['action'] = 'dummy'; // skip AJAX
ob_start();
require_once __DIR__ . '/../scripts/admin_index.php';
ob_end_clean(); // clean any output from admin_index.php

$errors = 0;

function assertEqual($expected, $actual, $testName) {
    global $errors;
    if ($expected !== $actual) {
        echo "Test Failed: $testName\n";
        echo "Expected: " . json_encode($expected) . "\n";
        echo "Actual: " . json_encode($actual) . "\n";
        $errors++;
    } else {
        echo "Test Passed: $testName\n";
    }
}

// Test 1: Connection Error
$mockSoapState = 'connection_error';
$res1 = sendSoapCommand('server info');
assertEqual(false, $res1['success'], 'Connection Error - success flag');
assertEqual('Connection refused', $res1['output'], 'Connection Error - output message');

// Test 2: Execution Error
$mockSoapState = 'execution_error';
$res2 = sendSoapCommand('server info');
assertEqual(false, $res2['success'], 'Execution Error - success flag');
assertEqual('Command execution failed', $res2['output'], 'Execution Error - output message');

// Test 3: Success
$mockSoapState = 'success';
$res3 = sendSoapCommand('server info');
assertEqual(true, $res3['success'], 'Success - success flag');
assertEqual('Server is running fine.', $res3['output'], 'Success - output message');

if ($errors > 0) {
    exit(1);
} else {
    exit(0);
}
