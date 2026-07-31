<?php
if (!defined('SOAP_RPC')) define('SOAP_RPC', 1);

class SoapClient {
    public function __construct($wsdl, $options = []) {}
    public function executeCommand($param) {
        return "Command executed.";
    }
}
class SoapParam {
    public function __construct($data, $name) {}
}

$_GET['action'] = 'execute_bot_command';
$_POST['command'] = 'add';
$_POST['class'] = 'mage; account create hacker password';

ob_start();
require_once __DIR__ . '/../scripts/admin_index.php';
$output = ob_get_clean();

$result = json_decode($output, true);
if ($result['success'] === false && $result['output'] === 'Invalid class format.') {
    echo "Test Passed: Command injection prevented.\n";
    exit(0);
} else {
    echo "Test Failed: Command injection not prevented.\n";
    echo "Output: " . $output . "\n";
    exit(1);
}
