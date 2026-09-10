<?php

function runAdminAction($action, $postData) {
    $code = '
        if (!defined("SOAP_RPC")) define("SOAP_RPC", 1);
        class SoapClient {
            public function __construct($wsdl, $options = []) {}
            public function executeCommand($param) { return "Command executed."; }
        }
        class SoapParam {
            public function __construct($data, $name) {}
        }
        $_GET["action"] = ' . var_export($action, true) . ';
        $_POST = ' . var_export($postData, true) . ';
        ob_start();
        require __DIR__ . "/scripts/admin_index.php";
        $out = ob_get_clean();
        echo $out;
    ';

    $descriptorSpec = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"]
    ];
    $process = proc_open('php', $descriptorSpec, $pipes, __DIR__ . '/..');
    if (is_resource($process)) {
        fwrite($pipes[0], "<?php " . $code);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        return json_decode($output, true);
    }
    return null;
}

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

// Test 1: Bot add injection
$res1 = runAdminAction('execute_bot_command', ['command' => 'add', 'class' => 'mage; account create hacker password']);
assertEqual(false, $res1['success'], 'Bot add injection - success flag');
assertEqual('Invalid class format.', $res1['output'], 'Bot add injection - output');

// Test 2: Console semicolon injection
$res2 = runAdminAction('console', ['command' => 'server info; account create hacker password']);
assertEqual(false, $res2['success'], 'Console semicolon injection - success flag');
assertEqual('Invalid command format.', $res2['output'], 'Console semicolon injection - output');

// Test 3: Console newline injection
$res3 = runAdminAction('console', ['command' => "server info\naccount set gmlevel hacker 3"]);
assertEqual(false, $res3['success'], 'Console newline injection - success flag');
assertEqual('Invalid command format.', $res3['output'], 'Console newline injection - output');

// Test 4: Console null byte injection
$res4 = runAdminAction('console', ['command' => "server info\0account create hacker password"]);
assertEqual(false, $res4['success'], 'Console null byte injection - success flag');
assertEqual('Invalid command format.', $res4['output'], 'Console null byte injection - output');

// Test 5: Valid console command
$res5 = runAdminAction('console', ['command' => 'server info']);
assertEqual(true, $res5['success'], 'Valid console command - success flag');
assertEqual('Command executed.', $res5['output'], 'Valid console command - output');

if ($errors > 0) {
    exit(1);
} else {
    exit(0);
}
