<?php
// AzerothCore Admin Control Panel
// Remote Access via SOAP & MariaDB

define('SOAP_USER', 'admin');
define('SOAP_PASS', 'admin');
define('SOAP_URL', 'http://127.0.0.1:7878/');
define('CONFIG_PATH', '/home/coyofroyo/azeroth-server/etc/modules/individualProgression.conf');

// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'acore');
define('DB_PASS', 'acore');
define('DB_WORLD', 'acore_world');

// Helper to send SOAP Remote Access Command
function sendSoapCommand($command) {
    $oldTimeout = ini_get('default_socket_timeout');
    try {
        ini_set('default_socket_timeout', 3);
        $client = new SoapClient(NULL, array(
            'location' => SOAP_URL,
            'uri' => 'urn:AC',
            'style' => SOAP_RPC,
            'login' => SOAP_USER,
            'password' => SOAP_PASS,
            'connection_timeout' => 3,
            'trace' => 1
        ));
        $result = $client->executeCommand(new SoapParam($command, 'command'));
        ini_set('default_socket_timeout', $oldTimeout);
        return array('success' => true, 'output' => trim($result));
    } catch (Exception $e) {
        ini_set('default_socket_timeout', $oldTimeout);
        return array('success' => false, 'output' => $e->getMessage());
    }
}

// Handle AJAX actions
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    if ($action === 'console') {
        $cmd = $_POST['command'] ?? '';
        if (empty($cmd)) {
            echo json_encode(array('success' => false, 'output' => 'Command cannot be empty.'));
            exit;
        }
        $res = sendSoapCommand($cmd);
        echo json_encode($res);
        exit;
    }

    if ($action === 'toggle_event') {
        $eventId = intval($_POST['event_id'] ?? 0);
        $active = intval($_POST['active'] ?? 0);
        if ($eventId <= 0) {
            echo json_encode(array('success' => false, 'output' => 'Invalid event ID.'));
            exit;
        }
        $cmd = $active ? "event stop $eventId" : "event start $eventId";
        $res = sendSoapCommand($cmd);
        echo json_encode($res);
        exit;
    }

    if ($action === 'set_progression') {
        $limit = intval($_POST['limit'] ?? 0);
        if ($limit < 0 || $limit > 18) {
            echo json_encode(array('success' => false, 'output' => 'Invalid progression limit.'));
            exit;
        }
        
        if (!file_exists(CONFIG_PATH)) {
            echo json_encode(array('success' => false, 'output' => 'Configuration file not found.'));
            exit;
        }
        if (!is_writable(CONFIG_PATH)) {
            echo json_encode(array('success' => false, 'output' => 'Configuration file is not writeable.'));
            exit;
        }

        $content = file_get_contents(CONFIG_PATH);
        $content = preg_replace(
            '/^IndividualProgression\.ProgressionLimit\s*=\s*\d+/m',
            "IndividualProgression.ProgressionLimit = $limit",
            $content
        );
        file_put_contents(CONFIG_PATH, $content);

        $res = sendSoapCommand('reload config');
        if ($res['success']) {
            $res['output'] = "Progression limit updated to $limit and config reloaded successfully!";
        }
        echo json_encode($res);
        exit;
    }

    if ($action === 'reset_player') {
        $player = trim($_POST['player'] ?? '');
        $type = $_POST['type'] ?? 'all';
        if (empty($player)) {
            echo json_encode(array('success' => false, 'output' => 'Player name is required.'));
            exit;
        }

        $outputs = [];
        $success = true;

        if ($type === 'dungeons' || $type === 'all') {
            $res = sendSoapCommand("reset dungeons $player");
            $outputs[] = "Dungeons: " . $res['output'];
            if (!$res['success']) $success = false;
        }
        if ($type === 'raids' || $type === 'all') {
            $res = sendSoapCommand("reset raids $player");
            $outputs[] = "Raids: " . $res['output'];
            if (!$res['success']) $success = false;
        }

        echo json_encode(array('success' => $success, 'output' => implode("\n", $outputs)));
        exit;
    }

    if ($action === 'create_account') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $gmlevel = intval($_POST['gmlevel'] ?? 0);

        if (empty($username) || empty($password)) {
            echo json_encode(array('success' => false, 'output' => 'Username and Password are required.'));
            exit;
        }

        $res = sendSoapCommand("account create $username $password");
        if (!$res['success']) {
            echo json_encode($res);
            exit;
        }

        if ($gmlevel > 0) {
            $gmRes = sendSoapCommand("account set gmlevel $username $gmlevel -1");
            if (!$gmRes['success']) {
                echo json_encode(array('success' => true, 'output' => $res['output'] . "\nAccount created but failed to set GM level: " . $gmRes['output']));
                exit;
            }
        }

        echo json_encode(array('success' => true, 'output' => "Account '$username' successfully created!"));
        exit;
    }

    if ($action === 'delete_account') {
        $username = trim($_POST['username'] ?? '');
        if (empty($username)) {
            echo json_encode(array('success' => false, 'output' => 'Username is required.'));
            exit;
        }

        $res = sendSoapCommand("account delete $username");
        echo json_encode($res);
        exit;
    }

    if ($action === 'restart_server') {
        // Run safe restart watchdog in background
        exec('nohup /home/coyofroyo/safe_restart.sh > /dev/null 2>&1 &');
        echo json_encode(array('success' => true, 'output' => "Safe 30-second shutdown watchdog initiated.\n- Announcements sent in-game.\n- Saving player progress...\n- Watchdog timeout protection active (force kills after 40s if hung).\n- The server will reboot automatically."));
        exit;
    }

    if ($action === 'shutdown_server') {
        // Run safe shutdown watchdog in background
        exec('nohup /home/coyofroyo/safe_shutdown.sh > /dev/null 2>&1 &');
        echo json_encode(array('success' => true, 'output' => "Safe 30-second shutdown initiated.\n- Announcements sent in-game.\n- Saving player progress...\n- Tmux container will be terminated."));
        exit;
    }

    if ($action === 'start_server') {
        // Start services in background
        exec('nohup /home/coyofroyo/start.sh > /dev/null 2>&1 &');
        echo json_encode(array('success' => true, 'output' => "Start command executed.\n- Services starting in tmux session 'azeroth'."));
        exit;
    }


    if ($action === 'search_logs') {
        $charName = trim($_POST['name'] ?? '');
        $keyword = trim($_POST['keyword'] ?? '');
        $type = trim($_POST['type'] ?? 'ALL');
        
        $charDsn = "mysql:host=" . DB_HOST . ";dbname=acore_characters;charset=utf8mb4";
        try {
            $charPdo = new PDO($charDsn, DB_USER, DB_PASS, array(
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));
            
            $results = [];
            
            // 1. Chat Logs Search
            if ($type === 'ALL' || $type === 'CHAT_ALL' || in_array($type, ['SAY', 'YELL', 'WHISPER', 'PARTY', 'GUILD'])) {
                $chatQuery = "SELECT timestamp, sender_name AS name, chat_type AS type, CONCAT(IF(receiver_name IS NOT NULL, CONCAT('To ', receiver_name, ': '), ''), message) AS details FROM custom_chat_log";
                $chatWhere = [];
                $chatParams = [];
                
                if ($charName !== '') {
                    $chatWhere[] = "sender_name LIKE ?";
                    $chatParams[] = "%$charName%";
                }
                if ($keyword !== '') {
                    $chatWhere[] = "message LIKE ?";
                    $chatParams[] = "%$keyword%";
                }
                if ($type !== 'ALL' && $type !== 'CHAT_ALL') {
                    $chatWhere[] = "chat_type = ?";
                    $chatParams[] = $type;
                }
                
                if (!empty($chatWhere)) {
                    $chatQuery .= " WHERE " . implode(" AND ", $chatWhere);
                }
                $chatQuery .= " ORDER BY timestamp DESC LIMIT 100";
                
                $stmt = $charPdo->prepare($chatQuery);
                $stmt->execute($chatParams);
                $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            
            // 2. Autobiography Events Search
            if ($type === 'ALL' || $type === 'EVENTS_ALL' || in_array($type, ['LEVEL_UP', 'QUEST_COMPLETE', 'BOSS_KILL', 'DEATH', 'PVP_KILL', 'LOGIN', 'LOGOUT', 'AUCTION_POST', 'AUCTION_SOLD', 'AUCTION_BUY'])) {
                $evtQuery = "SELECT a.timestamp, c.name, a.event_type AS type, a.event_details AS details FROM custom_autobiography a JOIN characters c ON a.character_guid = c.guid";
                $evtWhere = [];
                $evtParams = [];
                
                if ($charName !== '') {
                    $evtWhere[] = "c.name LIKE ?";
                    $evtParams[] = "%$charName%";
                }
                if ($keyword !== '') {
                    $evtWhere[] = "a.event_details LIKE ?";
                    $evtParams[] = "%$keyword%";
                }
                if ($type !== 'ALL' && $type !== 'EVENTS_ALL') {
                    if ($type === 'LOGIN') {
                        $evtWhere[] = "a.event_type IN ('LOGIN', 'LOGOUT')";
                    } else {
                        $evtWhere[] = "a.event_type = ?";
                        $evtParams[] = $type;
                    }
                }
                
                if (!empty($evtWhere)) {
                    $evtQuery .= " WHERE " . implode(" AND ", $evtWhere);
                }
                $evtQuery .= " ORDER BY timestamp DESC LIMIT 100";
                
                $stmt = $charPdo->prepare($evtQuery);
                $stmt->execute($evtParams);
                $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            
            // Sort combined results by timestamp DESC
            usort($results, function($a, $b) {
                return strcmp($b['timestamp'], $a['timestamp']);
            });
            
            // Return top 100
            $results = array_slice($results, 0, 100);
            
            echo json_encode(array('success' => true, 'logs' => $results));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'output' => $e->getMessage()));
        }
        exit;
    }

    if ($action === 'spawn_rare_near_player') {
        $playerName = $_POST['player_name'] ?? '';
        $creatureEntry = intval($_POST['creature_entry'] ?? 0);
        
        if (empty($playerName) || $creatureEntry <= 0) {
            echo json_encode(array('success' => false, 'output' => 'Invalid parameters.'));
            exit;
        }
        
        try {
            $charDsn = "mysql:host=" . DB_HOST . ";dbname=acore_characters;charset=utf8mb4";
            $charPdo = new PDO($charDsn, DB_USER, DB_PASS, array(
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));
            
            // Check if player is online
            $stmt = $charPdo->prepare("SELECT online FROM characters WHERE name = ?");
            $stmt->execute([$playerName]);
            $char = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$char) {
                echo json_encode(array('success' => false, 'output' => "Player '$playerName' does not exist."));
                exit;
            }
            if (intval($char['online']) !== 1) {
                echo json_encode(array('success' => false, 'output' => "Player '$playerName' must be online to summon a rare near them."));
                exit;
            }
            
            // Insert spawn request
            $stmtInsert = $charPdo->prepare("INSERT INTO custom_spawn_requests (player_name, creature_entry) VALUES (?, ?)");
            $stmtInsert->execute([$playerName, $creatureEntry]);
            
            echo json_encode(array('success' => true));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'output' => $e->getMessage()));
        }
        exit;
    }

    if ($action === 'get_system_stats') {
        $memPercent = 0;
        $totalMemGB = 0;
        $usedMemGB = 0;
        if (file_exists("/proc/meminfo")) {
            $memInfo = file_get_contents("/proc/meminfo");
            preg_match('/MemTotal:\s+(\d+) kB/', $memInfo, $matchesTotal);
            preg_match('/MemAvailable:\s+(\d+) kB/', $memInfo, $matchesAvail);
            if (!empty($matchesTotal) && !empty($matchesAvail)) {
                $totalMem = intval($matchesTotal[1]) * 1024;
                $availMem = intval($matchesAvail[1]) * 1024;
                $usedMem = $totalMem - $availMem;
                $memPercent = round(($usedMem / $totalMem) * 100, 1);
                $totalMemGB = round($totalMem / (1024*1024*1024), 1);
                $usedMemGB = round($usedMem / (1024*1024*1024), 1);
            }
        }
        
        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        $diskUsed = $diskTotal - $diskFree;
        $diskPercent = round(($diskUsed / $diskTotal) * 100, 1);
        $diskTotalGB = round($diskTotal / (1024*1024*1024), 1);
        $diskUsedGB = round($diskUsed / (1024*1024*1024), 1);
        
        $load = sys_getloadavg();
        $cpuLoad = $load ? round($load[0], 2) : 0.0;
        $cpuPercent = round($cpuLoad * 100 / 8, 1);
        if ($cpuPercent > 100) $cpuPercent = 100;
        
        $uptimeStr = "Unknown";
        if (file_exists('/proc/uptime')) {
            $uptimeSeconds = floatval(explode(' ', file_get_contents('/proc/uptime'))[0]);
            $days = floor($uptimeSeconds / 86400);
            $hours = floor(($uptimeSeconds % 86400) / 3600);
            $minutes = floor(($uptimeSeconds % 3600) / 60);
            $parts = [];
            if ($days > 0) $parts[] = "$days days";
            if ($hours > 0) $parts[] = "$hours hours";
            $parts[] = "$minutes mins";
            $uptimeStr = implode(", ", $parts);
        }
        
        $cpuTemp = "N/A";
        exec("sensors | grep -i 'Package id 0' | awk '{print $4}'", $tempOutput);
        if (!empty($tempOutput) && isset($tempOutput[0])) {
            $cpuTemp = trim($tempOutput[0]);
        } else {
            if (file_exists('/sys/class/thermal/thermal_zone1/temp')) {
                $rawTemp = intval(file_get_contents('/sys/class/thermal/thermal_zone1/temp'));
                if ($rawTemp > 0) {
                    $cpuTemp = "+" . round($rawTemp / 1000, 1) . "°C";
                }
            }
        }

        $worldRunning = false;
        exec("pgrep -x worldserver", $pidsWorld);
        if (!empty($pidsWorld)) {
            $worldRunning = true;
        }
        
        $authRunning = false;
        exec("pgrep -x authserver", $pidsAuth);
        if (!empty($pidsAuth)) {
            $authRunning = true;
        }

        $soapRes = sendSoapCommand('server info');
        $soapOnlineVal = $soapRes['success'];
        
        echo json_encode(array(
            'success' => true,
            'cpu' => $cpuPercent,
            'cpu_load' => $cpuLoad,
            'ram_percent' => $memPercent,
            'ram_total' => $totalMemGB,
            'ram_used' => $usedMemGB,
            'disk_percent' => $diskPercent,
            'disk_total' => $diskTotalGB,
            'disk_used' => $diskUsedGB,
            'uptime' => $uptimeStr,
            'cpu_temp' => $cpuTemp,
            'auth_running' => $authRunning,
            'world_running' => $worldRunning,
            'soap_online' => $soapOnlineVal
        ));
        exit;
    }

    if ($action === 'set_bot_config') {
        $enabled = intval($_POST['enabled'] ?? 1);
        $minBots = intval($_POST['min_bots'] ?? 50);
        $maxBots = intval($_POST['max_bots'] ?? 50);
        
        $confPath = '/home/coyofroyo/azeroth-server/etc/modules/playerbots.conf';
        if (!file_exists($confPath)) {
            echo json_encode(array('success' => false, 'output' => 'playerbots.conf not found.'));
            exit;
        }
        
        $content = file_get_contents($confPath);
        $content = preg_replace('/^AiPlayerbot\.Enabled\s*=\s*\d+/m', "AiPlayerbot.Enabled = $enabled", $content);
        $content = preg_replace('/^AiPlayerbot\.MinRandomBots\s*=\s*\d+/m', "AiPlayerbot.MinRandomBots = $minBots", $content);
        $content = preg_replace('/^AiPlayerbot\.MaxRandomBots\s*=\s*\d+/m', "AiPlayerbot.MaxRandomBots = $maxBots", $content);
        file_put_contents($confPath, $content);
        
        $res = sendSoapCommand('reload config');
        if ($res['success']) {
            $res['output'] = "Playerbots config updated successfully! Range: $minBots-$maxBots.";
        }
        echo json_encode($res);
        exit;
    }

    if ($action === 'set_challenge_config') {
        $enabled = intval($_POST['enabled'] ?? 0);
        $hardcore = intval($_POST['hardcore'] ?? 0);
        $semihardcore = intval($_POST['semihardcore'] ?? 0);
        $selfcrafted = intval($_POST['selfcrafted'] ?? 0);
        $itemquality = intval($_POST['itemquality'] ?? 0);
        $slowxp = intval($_POST['slowxp'] ?? 0);
        $veryslowxp = intval($_POST['veryslowxp'] ?? 0);
        $questxp = intval($_POST['questxp'] ?? 0);
        $ironman = intval($_POST['ironman'] ?? 0);

        $confPath = '/home/coyofroyo/azeroth-server/etc/modules/challenge_modes.conf';
        if (!file_exists($confPath)) {
            echo json_encode(array('success' => false, 'output' => 'challenge_modes.conf not found.'));
            exit;
        }

        $content = file_get_contents($confPath);
        $content = preg_replace('/^ChallengeModes\.Enable\s*=\s*\d+/m', "ChallengeModes.Enable = $enabled", $content);
        $content = preg_replace('/^Hardcore\.Enable\s*=\s*\d+/m', "Hardcore.Enable = $hardcore", $content);
        $content = preg_replace('/^SemiHardcore\.Enable\s*=\s*\d+/m', "SemiHardcore.Enable = $semihardcore", $content);
        $content = preg_replace('/^SelfCrafted\.Enable\s*=\s*\d+/m', "SelfCrafted.Enable = $selfcrafted", $content);
        $content = preg_replace('/^ItemQualityLevel\.Enable\s*=\s*\d+/m', "ItemQualityLevel.Enable = $itemquality", $content);
        $content = preg_replace('/^SlowXpGain\.Enable\s*=\s*\d+/m', "SlowXpGain.Enable = $slowxp", $content);
        $content = preg_replace('/^VerySlowXpGain\.Enable\s*=\s*\d+/m', "VerySlowXpGain.Enable = $veryslowxp", $content);
        $content = preg_replace('/^QuestXpOnly\.Enable\s*=\s*\d+/m', "QuestXpOnly.Enable = $questxp", $content);
        $content = preg_replace('/^IronMan\.Enable\s*=\s*\d+/m', "IronMan.Enable = $ironman", $content);
        
        file_put_contents($confPath, $content);

        $res = sendSoapCommand('reload config');
        if ($res['success']) {
            $res['output'] = "Challenge Modes config updated and reloaded successfully!";
        }
        echo json_encode($res);
        exit;
    }

    if ($action === 'set_features_config') {
        $transmog = intval($_POST['transmog'] ?? 0);
        $enchants = intval($_POST['enchants'] ?? 0);
        $autobalance = intval($_POST['autobalance'] ?? 0);
        $sololfg = intval($_POST['sololfg'] ?? 0);
        $aoeloot = intval($_POST['aoeloot'] ?? 0);
        $mythicplus = intval($_POST['mythicplus'] ?? 0);
        $itemupgrade = intval($_POST['itemupgrade'] ?? 0);
        $freeprof = intval($_POST['freeprof'] ?? 0);
        $accmount = intval($_POST['accmount'] ?? 0);
        $accachieve = intval($_POST['accachieve'] ?? 0);
        $questParty = intval($_POST['questparty'] ?? 0);
        $groupQuests = intval($_POST['groupquests'] ?? 0);

        $transPath = '/home/coyofroyo/azeroth-server/etc/modules/transmog.conf';
        $enchantsPath = '/home/coyofroyo/azeroth-server/etc/modules/random_enchants.conf';
        $abPath = '/home/coyofroyo/azeroth-server/etc/modules/AutoBalance.conf';
        $sololfgPath = '/home/coyofroyo/azeroth-server/etc/modules/SoloLfg.conf';
        $aoelootPath = '/home/coyofroyo/azeroth-server/etc/modules/mod_aoe_loot.conf';
        $mythicplusPath = '/home/coyofroyo/azeroth-server/etc/modules/mod_mythic_plus.conf';
        $itemupgradePath = '/home/coyofroyo/azeroth-server/etc/modules/mod_item_upgrade.conf';
        $freeprofPath = '/home/coyofroyo/azeroth-server/etc/modules/mod_npc_free_professions.conf';
        $accmountPath = '/home/coyofroyo/azeroth-server/etc/modules/mod_account_mount.conf';
        $accachievePath = '/home/coyofroyo/azeroth-server/etc/modules/mod_achievements.conf';

        if (file_exists($transPath) && is_writable($transPath)) {
            $content = file_get_contents($transPath);
            $content = preg_replace('/^Transmogrification\.Enable\s*=\s*\d+/m', "Transmogrification.Enable = $transmog", $content);
            file_put_contents($transPath, $content);
        }
        if (file_exists($enchantsPath) && is_writable($enchantsPath)) {
            $content = file_get_contents($enchantsPath);
            $content = preg_replace('/^RandomEnchants\.Enable\s*=\s*\d+/m', "RandomEnchants.Enable = $enchants", $content);
            file_put_contents($enchantsPath, $content);
        }
        if (file_exists($abPath) && is_writable($abPath)) {
            $content = file_get_contents($abPath);
            $content = preg_replace('/^AutoBalance\.Enable\.Global\s*=\s*\d+/m', "AutoBalance.Enable.Global = $autobalance", $content);
            file_put_contents($abPath, $content);
        }
        if (file_exists($sololfgPath) && is_writable($sololfgPath)) {
            $content = file_get_contents($sololfgPath);
            $content = preg_replace('/^SoloLFG\.Enable\s*=\s*\d+/m', "SoloLFG.Enable = $sololfg", $content);
            file_put_contents($sololfgPath, $content);
        }
        if (file_exists($aoelootPath) && is_writable($aoelootPath)) {
            $content = file_get_contents($aoelootPath);
            $content = preg_replace('/^AOELoot\.Enable\s*=\s*\d+/m', "AOELoot.Enable = $aoeloot", $content);
            file_put_contents($aoelootPath, $content);
        }
        if (file_exists($mythicplusPath) && is_writable($mythicplusPath)) {
            $content = file_get_contents($mythicplusPath);
            $content = preg_replace('/^MythicPlus\.Enable\s*=\s*\d+/m', "MythicPlus.Enable = $mythicplus", $content);
            file_put_contents($mythicplusPath, $content);
        }
        if (file_exists($itemupgradePath) && is_writable($itemupgradePath)) {
            $content = file_get_contents($itemupgradePath);
            $content = preg_replace('/^ItemUpgrade\.Enable\s*=\s*\d+/m', "ItemUpgrade.Enable = $itemupgrade", $content);
            file_put_contents($itemupgradePath, $content);
        }
        if (file_exists($freeprofPath) && is_writable($freeprofPath)) {
            $content = file_get_contents($freeprofPath);
            $content = preg_replace('/^NpcFreeProfessions\.Enable\s*=\s*\d+/m', "NpcFreeProfessions.Enable = $freeprof", $content);
            file_put_contents($freeprofPath, $content);
        }
        if (file_exists($accmountPath) && is_writable($accmountPath)) {
            $content = file_get_contents($accmountPath);
            $content = preg_replace('/^Account\.Mounts\.Enable\s*=\s*\d+/m', "Account.Mounts.Enable = $accmount", $content);
            file_put_contents($accmountPath, $content);
        }
        if (file_exists($accachievePath) && is_writable($accachievePath)) {
            $content = file_get_contents($accachievePath);
            $content = preg_replace('/^Account\.Achievements\.Enable\s*=\s*\d+/m', "Account.Achievements.Enable = $accachieve", $content);
            file_put_contents($accachievePath, $content);
        }

        // Save Quest Loot Party
        $qpPaths = [
            '/home/coyofroyo/azeroth-server/etc/modules/mod_quest_loot_party.conf',
            '/home/coyofroyo/azeroth-server/etc/modules/mod-quest-loot-party.conf'
        ];
        $qpUpdated = false;
        $boolVal = $questParty ? 'true' : 'false';
        foreach ($qpPaths as $path) {
            if (file_exists($path)) {
                $c = file_get_contents($path);
                $c = preg_replace('/^QuestParty\.Enable\s*=\s*(true|false|1|0)/m', "QuestParty.Enable = $boolVal", $c);
                file_put_contents($path, $c);
                $qpUpdated = true;
                break;
            }
        }
        if (!$qpUpdated) {
            $defaultPath = '/home/coyofroyo/azeroth-server/etc/modules/mod-quest-loot-party.conf';
            $defaultContent = "[worldserver]\nQuestParty.Enable = $boolVal\nQuestParty.Message = true\n";
            file_put_contents($defaultPath, $defaultContent);
        }

        // Save Group Quests
        $gqPaths = [
            '/home/coyofroyo/azeroth-server/etc/modules/mod_groupquests.conf',
            '/home/coyofroyo/azeroth-server/etc/modules/mod-quest-loot-party.conf',
            '/home/coyofroyo/azeroth-server/etc/modules/groupquests.conf'
        ];
        $gqUpdated = false;
        foreach ($gqPaths as $path) {
            if (file_exists($path)) {
                $c = file_get_contents($path);
                $c = preg_replace('/^(GroupQuests|mod_groupquests)\.Enable\s*=\s*(true|false|1|0)/m', "$1.Enable = $groupQuests", $c);
                file_put_contents($path, $c);
                $gqUpdated = true;
                break;
            }
        }
        if (!$gqUpdated) {
            $defaultPath = '/home/coyofroyo/azeroth-server/etc/modules/mod_groupquests.conf';
            $defaultContent = "[worldserver]\nGroupQuests.Enable = $groupQuests\n";
            file_put_contents($defaultPath, $defaultContent);
        }

        $res = sendSoapCommand('reload config');
        if ($res['success']) {
            $res['output'] = "Server modules config updated and reloaded successfully!";
        }
        echo json_encode($res);
        exit;
    }

    if ($action === 'get_auctions') {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=acore_characters;charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));
            
            $stmt = $pdo->query("
                SELECT 
                    ah.id,
                    ah.houseid,
                    ii.itemEntry,
                    it.name AS item_name,
                    it.Quality AS item_quality,
                    ii.count AS item_count,
                    c.name AS owner_name,
                    ah.buyoutprice,
                    ah.startbid,
                    ah.lastbid,
                    ah.time AS expire_time
                FROM acore_characters.auctionhouse ah
                JOIN acore_characters.item_instance ii ON ah.itemguid = ii.guid
                LEFT JOIN acore_world.item_template it ON ii.itemEntry = it.entry
                LEFT JOIN acore_characters.characters c ON ah.itemowner = c.guid
                ORDER BY ah.id DESC
                LIMIT 200
            ");
            $auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(array('success' => true, 'auctions' => $auctions));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'output' => $e->getMessage(), 'auctions' => []));
        }
        exit;
    }

    if ($action === 'get_character_personality') {
        $guid = intval($_GET['guid'] ?? 0);
        if ($guid === 0) {
            echo json_encode(array('success' => false, 'personality' => '', 'prompt' => ''));
            exit;
        }

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=acore_characters;charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));

            $stmt = $pdo->prepare("SELECT personality FROM mod_ollama_chat_personality WHERE guid = :guid");
            $stmt->execute(array('guid' => $guid));
            $pers = $stmt->fetchColumn() ?: '';

            $prompt = '';
            if (!empty($pers)) {
                $stmt2 = $pdo->prepare("SELECT prompt FROM mod_ollama_chat_personality_templates WHERE `key` = :key");
                $stmt2->execute(array('key' => $pers));
                $prompt = $stmt2->fetchColumn() ?: '';
            }

            echo json_encode(array('success' => true, 'personality' => $pers, 'prompt' => $prompt));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
        exit;
    }

    if ($action === 'save_character_personality') {
        $guid = intval($_POST['guid'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $personality = trim($_POST['personality'] ?? '');
        $prompt = trim($_POST['prompt'] ?? '');

        if ($guid === 0 || empty($name)) {
            echo json_encode(array('success' => false, 'output' => 'Character guid and name are required.'));
            exit;
        }

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=acore_characters;charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));

            if ($personality === 'custom') {
                $key = "CUSTOM_" . strtoupper($name);
                $stmt = $pdo->prepare("
                    INSERT INTO mod_ollama_chat_personality_templates (`key`, prompt, manual_only)
                    VALUES (:key, :prompt, 1)
                    ON DUPLICATE KEY UPDATE prompt = :prompt
                ");
                $stmt->execute(array('key' => $key, 'prompt' => $prompt));
            } else {
                $key = $personality;
            }

            if (empty($key)) {
                $stmt2 = $pdo->prepare("DELETE FROM mod_ollama_chat_personality WHERE guid = :guid");
                $stmt2->execute(array('guid' => $guid));
            } else {
                $stmt2 = $pdo->prepare("
                    INSERT INTO mod_ollama_chat_personality (guid, personality)
                    VALUES (:guid, :key)
                    ON DUPLICATE KEY UPDATE personality = :key
                ");
                $stmt2->execute(array('guid' => $guid, 'key' => $key));
            }

            echo json_encode(array('success' => true, 'output' => "AI Personality successfully saved for $name!"));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'output' => $e->getMessage()));
        }
        exit;
    }

    if ($action === 'get_character_inventory') {
        $guid = intval($_GET['guid'] ?? 0);
        if ($guid === 0) {
            echo json_encode(array('success' => false, 'items' => []));
            exit;
        }

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=acore_characters;charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));

            $stmt = $pdo->prepare("
                SELECT i.itemEntry AS entry, t.name, t.Quality AS quality, SUM(i.count) AS count
                FROM acore_characters.character_inventory ci
                JOIN acore_characters.item_instance i ON ci.item = i.guid
                JOIN acore_world.item_template t ON i.itemEntry = t.entry
                WHERE ci.guid = :guid
                GROUP BY i.itemEntry, t.name, t.Quality
                ORDER BY t.Quality DESC, t.name ASC
            ");
            $stmt->execute(array('guid' => $guid));
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(array('success' => true, 'items' => $items));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'error' => $e->getMessage()));
        }
        exit;
    }

    if ($action === 'search_characters') {
        $query = trim($_POST['query'] ?? '');
        if (empty($query)) {
            echo json_encode(array('success' => false, 'characters' => []));
            exit;
        }
        
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=acore_characters;charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));
            
            $stmt = $pdo->prepare("
                SELECT c.guid, c.name, c.level, c.race, c.class, c.gender, c.money, c.map, c.position_x, c.position_y, c.position_z, c.online, c.account
                FROM acore_characters.characters c
                WHERE c.name LIKE :search
                LIMIT 20
            ");
            $stmt->execute(array('search' => "%$query%"));
            $chars = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($chars as &$ch) {
                $stmtBot = $pdo->prepare("SELECT COUNT(*) FROM acore_playerbots.playerbots_random_bots WHERE bot = :guid");
                $stmtBot->execute(array('guid' => $ch['guid']));
                $ch['is_bot'] = $stmtBot->fetchColumn() > 0;
            }
            
            echo json_encode(array('success' => true, 'characters' => $chars));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'output' => $e->getMessage(), 'characters' => []));
        }
        exit;
    }

    if ($action === 'modify_character') {
        $charName = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? '';
        $val = trim($_POST['value'] ?? '');
        
        if (empty($charName)) {
            echo json_encode(array('success' => false, 'output' => 'Character name is required.'));
            exit;
        }
        
        if ($type === 'level') {
            $level = intval($val);
            $res = sendSoapCommand("character level $charName $level");
        } elseif ($type === 'gold') {
            $gold = intval($val);
            $copper = $gold * 10000;
            $res = sendSoapCommand("character change gold $charName $copper");
        } elseif ($type === 'teleport') {
            $city = strtolower($val);
            $res = sendSoapCommand("teleport name $charName $city");
        } elseif ($type === 'unstuck') {
            sendSoapCommand("revive $charName");
            $res = sendSoapCommand("teleport name $charName dalaran");
        } elseif ($type === 'gmlevel') {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=acore_characters;charset=utf8mb4";
                $pdo = new PDO($dsn, DB_USER, DB_PASS);
                $stmt = $pdo->prepare("SELECT account FROM characters WHERE name = :name");
                $stmt->execute(array('name' => $charName));
                $accId = $stmt->fetchColumn();
                
                if ($accId) {
                    $stmt2 = $pdo->prepare("SELECT username FROM acore_auth.account WHERE id = :id");
                    $stmt2->execute(array('id' => $accId));
                    $accName = $stmt2->fetchColumn();
                    if ($accName) {
                        $gmlevel = intval($val);
                        $res = sendSoapCommand("account set gmlevel $accName $gmlevel -1");
                    } else {
                        $res = array('success' => false, 'output' => 'Account name not found.');
                    }
                } else {
                    $res = array('success' => false, 'output' => 'Character not found.');
                }
            } catch (Exception $e) {
                $res = array('success' => false, 'output' => $e->getMessage());
            }
        } else {
            $res = array('success' => false, 'output' => 'Unknown modification type.');
        }
        
        echo json_encode($res);
        exit;
    }

    if ($action === 'create_follower') {
        $masterName = trim($_POST['master'] ?? '');
        $followerName = trim($_POST['name'] ?? '');
        $race = intval($_POST['race'] ?? 0);
        $class = intval($_POST['class'] ?? 0);
        $gender = intval($_POST['gender'] ?? 0);
        $level = intval($_POST['level'] ?? 1);
        $ollama = trim($_POST['ollama_personality'] ?? '');
        $customPrompt = trim($_POST['custom_personality_prompt'] ?? '');

        if (empty($masterName) || empty($followerName)) {
            echo json_encode(array('success' => false, 'output' => 'All fields are required.'));
            exit;
        }

        if (!preg_match('/^[a-zA-Z]{2,12}$/', $followerName)) {
            echo json_encode(array('success' => false, 'output' => 'Follower name must contain only letters and be between 2 and 12 characters.'));
            exit;
        }

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=acore_characters;charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT account, map, position_x, position_y, position_z, orientation, zone FROM characters WHERE name = :name");
            $stmt->execute(array('name' => $masterName));
            $masterInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$masterInfo) {
                echo json_encode(array('success' => false, 'output' => "Master character '$masterName' not found. Please log in first."));
                exit;
            }

            $accountId = intval($masterInfo['account']);
            $map = intval($masterInfo['map']);
            $x = floatval($masterInfo['position_x']);
            $y = floatval($masterInfo['position_y']);
            $z = floatval($masterInfo['position_z']);
            $o = floatval($masterInfo['orientation']);
            $zone = intval($masterInfo['zone']);

            $stmtName = $pdo->prepare("SELECT COUNT(*) FROM characters WHERE name = :name");
            $stmtName->execute(array('name' => $followerName));
            if ($stmtName->fetchColumn() > 0) {
                echo json_encode(array('success' => false, 'output' => "Character name '$followerName' is already taken."));
                exit;
            }

            // Create new dedicated bot account to prevent character list cluttering
            $botAccountName = "BOT_" . strtoupper($followerName);
            sendSoapCommand("account create " . $botAccountName . " password");

            // Fetch the newly created account ID
            $authDsn = "mysql:host=" . DB_HOST . ";dbname=acore_auth;charset=utf8mb4";
            $authPdo = new PDO($authDsn, DB_USER, DB_PASS);
            $authPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmtAcc = $authPdo->prepare("SELECT id FROM account WHERE username = :username");
            $stmtAcc->execute(array('username' => $botAccountName));
            $accInfo = $stmtAcc->fetch(PDO::FETCH_ASSOC);

            if (!$accInfo) {
                echo json_encode(array('success' => false, 'output' => "Failed to retrieve or create bot account '$botAccountName'."));
                exit;
            }
            $botAccountId = intval($accInfo['id']);

            $guid = 1;
            $resGuid = $pdo->query("SELECT MAX(guid) FROM characters");
            $maxGuid = $resGuid->fetchColumn();
            if ($maxGuid) {
                $guid = intval($maxGuid) + 1;
            }

            $sql = "INSERT INTO characters (
                guid, account, name, race, class, gender, level, xp, money,
                skin, face, hairStyle, hairColor, facialStyle, bankSlots,
                restState, playerFlags, position_x, position_y, position_z,
                map, instance_id, instance_mode_mask, orientation, taximask,
                online, cinematic, totaltime, leveltime, logout_time,
                is_logout_resting, rest_bonus, resettalents_cost, resettalents_time,
                trans_x, trans_y, trans_z, trans_o, transguid, extra_flags,
                stable_slots, at_login, zone, death_expire_time, arenaPoints,
                totalHonorPoints, todayHonorPoints, yesterdayHonorPoints, totalKills,
                todayKills, yesterdayKills, chosenTitle, knownCurrencies,
                watchedFaction, drunk, health, power1, power2, power3, power4,
                power5, power6, power7, latency, talentGroupsCount, activeTalentGroup,
                ammoId, actionBars, grantableLevels, innTriggerId, extraBonusTalentCount
            ) VALUES (
                :guid, :account, :name, :race, :class, :gender, :level, 0, 0,
                0, 0, 0, 0, 0, 0,
                0, 0, :x, :y, :z,
                :map, 0, 0, :o, '',
                0, 0, 0, 0, 0,
                0, 0, 0, 0,
                0, 0, 0, 0, 0, 0,
                0, 0, :zone, 0, 0,
                0, 0, 0, 0,
                0, 0, 0, 0,
                0, 0, 100, 100, 0, 0, 0,
                0, 0, 0, 0, 1, 0,
                0, 0, 0, 0, 0
            )";

            $stmtInsert = $pdo->prepare($sql);
            $stmtInsert->execute(array(
                'guid' => $guid,
                'account' => $botAccountId,
                'name' => $followerName,
                'race' => $race,
                'class' => $class,
                'gender' => $gender,
                'level' => $level,
                'x' => $x,
                'y' => $y,
                'z' => $z,
                'map' => $map,
                'o' => $o,
                'zone' => $zone
            ));

            $dbBot = new PDO("mysql:host=" . DB_HOST . ";dbname=acore_playerbots;charset=utf8mb4", DB_USER, DB_PASS);
            
            $stmtLink = $dbBot->prepare("INSERT IGNORE INTO playerbots_account_links (account_id, linked_account_id) VALUES (:accountId, :botAccountId)");
            // Link master -> bot
            $stmtLink->execute(array(
                'accountId' => $accountId,
                'botAccountId' => $botAccountId
            ));
            // Link bot -> master
            $stmtLink->execute(array(
                'accountId' => $botAccountId,
                'botAccountId' => $accountId
            ));

            $stmtBot = $dbBot->prepare("INSERT INTO playerbots_random_bots (bot, owner, time, validIn) VALUES (:bot, :owner, :time, 28800)");
            $stmtBot->execute(array(
                'bot' => $guid,
                'owner' => $accountId,
                'time' => time()
            ));

            // Assign Ollama Personality
            if ($ollama === 'CUSTOM' && !empty($customPrompt)) {
                $customKey = "CUSTOM_" . strtoupper($followerName);
                // Insert the custom template
                $stmtTemp = $pdo->prepare("INSERT INTO mod_ollama_chat_personality_templates (`key`, prompt, manual_only) VALUES (:key, :prompt, 0) ON DUPLICATE KEY UPDATE prompt = :prompt");
                $stmtTemp->execute(array(
                    'key' => $customKey,
                    'prompt' => $customPrompt
                ));
                // Bind character GUID to custom template
                $stmtBind = $pdo->prepare("INSERT INTO mod_ollama_chat_personality (guid, personality) VALUES (:guid, :personality) ON DUPLICATE KEY UPDATE personality = :personality");
                $stmtBind->execute(array(
                    'guid' => $guid,
                    'personality' => $customKey
                ));
            } else if (!empty($ollama)) {
                // Bind character GUID to predefined template
                $stmtBind = $pdo->prepare("INSERT INTO mod_ollama_chat_personality (guid, personality) VALUES (:guid, :personality) ON DUPLICATE KEY UPDATE personality = :personality");
                $stmtBind->execute(array(
                    'guid' => $guid,
                    'personality' => $ollama
                ));
            }

            echo json_encode(array('success' => true, 'output' => "Follower '$followerName' successfully created and bound to random playerbots."));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'output' => "Database write failed: " . $e->getMessage()));
        }
        exit;
    }

    // Creature Editor AJAX Actions
    if ($action === 'search_creatures') {
        $query = trim($_POST['query'] ?? '');
        if (empty($query)) {
            echo json_encode(array('success' => false, 'creatures' => []));
            exit;
        }

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_WORLD . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));

            $rankFilter = null;
            $searchName = $query;

            // Parse special rank keywords to make elite/rare search work
            if (stripos($searchName, 'rare elite') !== false) {
                $rankFilter = array(2);
                $searchName = trim(str_ireplace('rare elite', '', $searchName));
            } elseif (stripos($searchName, 'rare') !== false) {
                $rankFilter = array(2, 4);
                $searchName = trim(str_ireplace('rare', '', $searchName));
            } elseif (stripos($searchName, 'elite') !== false) {
                $rankFilter = array(1, 2);
                $searchName = trim(str_ireplace('elite', '', $searchName));
            } elseif (stripos($searchName, 'boss') !== false) {
                $rankFilter = array(3);
                $searchName = trim(str_ireplace('boss', '', $searchName));
            }

            $sql = "
                SELECT ct.entry, ct.name, ct.subname, ct.minlevel, ct.maxlevel, 
                       ct.HealthModifier AS minhealth, ct.ManaModifier AS maxhealth, 
                       ct.ArmorModifier AS armor, ct.DamageModifier AS damage_multiplier, 
                       ct.rank, ct.unit_class, ct.exp,
                       s_min.basehp0 AS min_hp0, s_min.basehp1 AS min_hp1, s_min.basehp2 AS min_hp2,
                       s_min.basearmor AS min_base_armor,
                       s_max.basehp0 AS max_hp0, s_max.basehp1 AS max_hp1, s_max.basehp2 AS max_hp2,
                       s_max.basearmor AS max_base_armor
                FROM creature_template ct
                LEFT JOIN creature_classlevelstats s_min ON s_min.level = ct.minlevel AND s_min.class = ct.unit_class
                LEFT JOIN creature_classlevelstats s_max ON s_max.level = ct.maxlevel AND s_max.class = ct.unit_class
                WHERE ";
            $params = array();
            $conditions = array();

            if (is_numeric($query)) {
                $conditions[] = "ct.entry = :entry";
                $params['entry'] = intval($query);
            } else {
                if (!empty($searchName)) {
                    $conditions[] = "ct.name LIKE :search";
                    $params['search'] = "%$searchName%";
                }
                if ($rankFilter !== null) {
                    $conditions[] = "ct.rank IN (" . implode(',', $rankFilter) . ")";
                }
            }

            if (empty($conditions)) {
                $conditions[] = "1=1";
            }

            $sql .= implode(" AND ", $conditions);
            $sql .= " LIMIT 50";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $creatures = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Compute actual estimated health and armor
            foreach ($creatures as &$c) {
                $exp = intval($c['exp'] ?? 0);
                
                $minHpCol = "min_hp" . $exp;
                $minBaseHp = floatval($c[$minHpCol] ?? $c['min_hp0'] ?? 1.0);
                
                $maxHpCol = "max_hp" . $exp;
                $maxBaseHp = floatval($c[$maxHpCol] ?? $c['max_hp0'] ?? 1.0);
                
                $minBaseArmor = floatval($c['min_base_armor'] ?? 1.0);
                $maxBaseArmor = floatval($c['max_base_armor'] ?? 1.0);
                
                $c['base_min_hp'] = $minBaseHp;
                $c['base_max_hp'] = $maxBaseHp;
                $c['base_min_armor'] = $minBaseArmor;
                $c['base_max_armor'] = $maxBaseArmor;
            }

            echo json_encode(array('success' => true, 'creatures' => $creatures));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'output' => $e->getMessage(), 'creatures' => []));
        }
        exit;
    }

    if ($action === 'update_creature') {
        $entry = intval($_POST['entry'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $subname = trim($_POST['subname'] ?? '');
        $minlevel = intval($_POST['minlevel'] ?? 1);
        $maxlevel = intval($_POST['maxlevel'] ?? 1);
        $minhealth = intval($_POST['minhealth'] ?? 1);
        $maxhealth = intval($_POST['maxhealth'] ?? 1);
        $armor = intval($_POST['armor'] ?? 0);
        $damage_multiplier = floatval($_POST['damage_multiplier'] ?? 1.0);

        if ($entry <= 0 || empty($name)) {
            echo json_encode(array('success' => false, 'output' => 'Invalid entry or name.'));
            exit;
        }

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_WORLD . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));

            $stmt = $pdo->prepare("
                UPDATE creature_template
                SET name = :name, subname = :subname, minlevel = :minlevel, maxlevel = :maxlevel, 
                    HealthModifier = :minhealth, ManaModifier = :maxhealth, ArmorModifier = :armor, DamageModifier = :damage_multiplier
                WHERE entry = :entry
            ");
            $stmt->execute(array(
                'entry' => $entry,
                'name' => $name,
                'subname' => $subname,
                'minlevel' => $minlevel,
                'maxlevel' => $maxlevel,
                'minhealth' => $minhealth,
                'maxhealth' => $maxhealth,
                'armor' => $armor,
                'damage_multiplier' => $damage_multiplier
            ));

            $res = sendSoapCommand("reload creature_template");
            if ($res['success']) {
                $res['output'] = "Monster stats updated successfully and reloaded in-game!";
            }
            echo json_encode($res);
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'output' => $e->getMessage()));
        }
        exit;
    }

    // Loot Editor AJAX Actions
    // Get LLM Chat configuration parameters
    if ($action === 'get_llm_config') {
        $confPath = '/home/coyofroyo/azeroth-server/etc/modules/mod_ollama_chat.conf';
        $config = array(
            'host' => 'http://127.0.0.1',
            'port' => '11434',
            'model' => 'llama3',
            'temp' => '0.7',
            'system_prompt' => ''
        );
        if (file_exists($confPath)) {
            $c = file_get_contents($confPath);
            if (preg_match('/^OllamaChat\.Host\s*=\s*"(.*)"/m', $c, $m)) $config['host'] = $m[1];
            if (preg_match('/^OllamaChat\.Port\s*=\s*(\d+)/m', $c, $m)) $config['port'] = $m[1];
            if (preg_match('/^OllamaChat\.Model\s*=\s*"(.*)"/m', $c, $m)) $config['model'] = $m[1];
            if (preg_match('/^OllamaChat\.Temperature\s*=\s*([0-9.]+)/m', $c, $m)) $config['temp'] = $m[1];
            if (preg_match('/^OllamaChat\.SystemPrompt\s*=\s*"(.*)"/m', $c, $m)) $config['system_prompt'] = $m[1];
        }
        echo json_encode(array('success' => true, 'config' => $config));
        exit;
    }

    // Save LLM Chat configuration parameters
    if ($action === 'set_llm_config') {
        $host = trim($_POST['host'] ?? 'http://127.0.0.1');
        $port = intval($_POST['port'] ?? 11434);
        $model = trim($_POST['model'] ?? 'llama3');
        $temp = floatval($_POST['temp'] ?? 0.7);
        $system_prompt = trim($_POST['system_prompt'] ?? '');

        $confPath = '/home/coyofroyo/azeroth-server/etc/modules/mod_ollama_chat.conf';
        if (file_exists($confPath)) {
            $c = file_get_contents($confPath);
            $c = preg_replace('/^OllamaChat\.Host\s*=\s*".*"/m', 'OllamaChat.Host = "' . $host . '"', $c);
            $c = preg_replace('/^OllamaChat\.Port\s*=\s*\d+/m', 'OllamaChat.Port = ' . $port, $c);
            $c = preg_replace('/^OllamaChat\.Model\s*=\s*".*"/m', 'OllamaChat.Model = "' . $model . '"', $c);
            $c = preg_replace('/^OllamaChat\.Temperature\s*=\s*[0-9.]+/m', 'OllamaChat.Temperature = ' . $temp, $c);
            $c = preg_replace('/^OllamaChat\.SystemPrompt\s*=\s*".*"/m', 'OllamaChat.SystemPrompt = "' . addslashes($system_prompt) . '"', $c);
            file_put_contents($confPath, $c);
            
            // Reload configs in-game via SOAP
            sendSoapCommand('reload config');
            echo json_encode(array('success' => true, 'output' => 'LLM Configuration updated and reloaded in-game!'));
        } else {
            echo json_encode(array('success' => false, 'output' => 'Config file not found.'));
        }
        exit;
    }

    // Get Active Playerbots List
    if ($action === 'get_active_bots') {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=acore_characters;charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));

            $stmt = $pdo->prepare("
                SELECT c.guid, c.name, c.race, c.class, c.level, c.zone, c.online,
                       g.name AS guild_name
                FROM characters c
                JOIN acore_auth.account a ON c.account = a.id
                LEFT JOIN guild_member gm ON c.guid = gm.guid
                LEFT JOIN guild g ON gm.guildid = g.guildid
                WHERE c.online = 1 AND (a.username LIKE 'RNDBOT%' OR a.username LIKE 'BOT_%')
                ORDER BY c.level DESC, c.name ASC
            ");
            $stmt->execute();
            $bots = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(array('success' => true, 'bots' => $bots));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'output' => $e->getMessage(), 'bots' => []));
        }
        exit;
    }

    // Execute Playerbots Command
    if ($action === 'execute_bot_command') {
        $cmd = trim($_POST['command'] ?? '');
        $class = trim($_POST['class'] ?? '');
        
        $soapCmd = '';
        if ($cmd === 'summon') $soapCmd = 'bot summon';
        elseif ($cmd === 'dismiss') $soapCmd = 'bot remove';
        elseif ($cmd === 'gear') $soapCmd = 'bot gear';
        elseif ($cmd === 'add') $soapCmd = 'bot add ' . $class;

        if (empty($soapCmd)) {
            echo json_encode(array('success' => false, 'output' => 'Invalid bot command.'));
            exit;
        }

        $res = sendSoapCommand($soapCmd);
        echo json_encode($res);
        exit;
    }

    // Send Custom Mail Action (Subject, Body, Gold, and Attachments)
    if ($action === 'send_custom_mail') {
        $recipient = trim($_POST['recipient'] ?? '');
        $subject = trim($_POST['subject'] ?? 'System Message');
        $body = trim($_POST['body'] ?? 'Here is your package.');
        $gold = floatval($_POST['gold'] ?? 0.0);
        $attachments = json_decode($_POST['attachments'] ?? '[]', true);

        if (empty($recipient)) {
            echo json_encode(array('success' => false, 'output' => 'Recipient name is required.'));
            exit;
        }

        // Clean subject and body from double quotes to prevent SOAP syntax error
        $subject = str_replace('"', "'", $subject);
        $body = str_replace('"', "'", $body);

        $outputs = array();
        
        // 1. Send Money if specified (convert gold to copper)
        if ($gold > 0) {
            $copper = round($gold * 10000);
            $moneyCmd = 'send money ' . $recipient . ' "' . $subject . '" "' . $body . '" ' . $copper;
            $res = sendSoapCommand($moneyCmd);
            $outputs[] = "Money Mail: " . $res['output'];
        }

        // 2. Send Items if attached
        if (!empty($attachments) && is_array($attachments)) {
            // Build item arguments: itemid1[:count1] itemid2[:count2] ...
            $itemArgs = array();
            foreach ($attachments as $att) {
                $itemId = intval($att['id']);
                $count = intval($att['count']);
                if ($itemId > 0 && $count > 0) {
                    $itemArgs[] = $itemId . ':' . $count;
                }
            }

            if (!empty($itemArgs)) {
                $itemsCmd = 'send items ' . $recipient . ' "' . $subject . '" "' . $body . '" ' . implode(' ', $itemArgs);
                $res = sendSoapCommand($itemsCmd);
                $outputs[] = "Items Mail: " . $res['output'];
            }
        }

        // If neither money nor items sent, send a plain mail
        if ($gold <= 0 && empty($attachments)) {
            $mailCmd = 'send mail ' . $recipient . ' "' . $subject . '" "' . $body . '"';
            $res = sendSoapCommand($mailCmd);
            $outputs[] = "Plain Mail: " . $res['output'];
        }

        echo json_encode(array('success' => true, 'output' => implode("\n", $outputs)));
        exit;
    }

    // Send Mail Packages (Gold, Bags, Mounts)
    if ($action === 'send_mail_package') {
        $name = trim($_POST['name'] ?? '');
        $package = trim($_POST['package'] ?? '');

        if (empty($name)) {
            echo json_encode(array('success' => false, 'output' => 'No character name provided.'));
            exit;
        }

        $commands = array();
        if ($package === 'bags') {
            // Send 4x Netherweave Bags (ID: 21841)
            $commands[] = 'send mail ' . $name . ' "Starter Bags Pack" "Here are 4 Netherweave Bags for your adventures!" 21841 21841 21841 21841';
        } elseif ($package === 'gold_1') {
            $commands[] = 'send money ' . $name . ' "1 Gold Gift" "Enjoy 1 gold." 10000';
        } elseif ($package === 'gold_10') {
            $commands[] = 'send money ' . $name . ' "10 Gold Gift" "Enjoy 10 gold." 100000';
        } elseif ($package === 'gold_100') {
            $commands[] = 'send money ' . $name . ' "100 Gold Gift" "Enjoy 100 gold." 1000000';
        } elseif ($package === 'gold_1000') {
            $commands[] = 'send money ' . $name . ' "1000 Gold Gift" "Enjoy 1000 gold." 10000000';
        } elseif ($package === 'gold_5000') {
            $commands[] = 'send money ' . $name . ' "5000 Gold Gift" "Enjoy 5000 gold." 50000000';
        } elseif ($package === 'mount') {
            // Teach Riding & Mail mount item: Swift Red Hawkstrider (Item ID: 29221)
            $commands[] = 'character learn ' . $name . ' 33391'; // Journeyman Riding
            $commands[] = 'send mail ' . $name . ' "Mount Pack" "Here is your 100% ground mount." 29221';
        }

        if (empty($commands)) {
            echo json_encode(array('success' => false, 'output' => 'Unknown package type.'));
            exit;
        }

        $outputs = array();
        foreach ($commands as $cmd) {
            $r = sendSoapCommand($cmd);
            $outputs[] = $r['output'];
        }

        echo json_encode(array('success' => true, 'output' => implode("
", $outputs)));
        exit;
    }

    // Teach GM Unlocks (Flight paths, reveal maps)
    if ($action === 'teach_gm_unlock') {
        $name = trim($_POST['name'] ?? '');
        $unlock = trim($_POST['unlock'] ?? '');

        if (empty($name)) {
            echo json_encode(array('success' => false, 'output' => 'No character name provided.'));
            exit;
        }

        $soapCmd = '';
        if ($unlock === 'taxi') {
            $soapCmd = 'taxi taximax ' . $name;
        } elseif ($unlock === 'maps') {
            // Learn Explorer achievements & reveal maps
            $soapCmd = 'character learn ' . $name . ' 2259'; // Explorer rank
        }

        if (empty($soapCmd)) {
            echo json_encode(array('success' => false, 'output' => 'Unknown unlock type.'));
            exit;
        }

        $res = sendSoapCommand($soapCmd);
        echo json_encode($res);
        exit;
    }

    // Create Custom Item Template & Spawns
    if ($action === 'create_custom_item') {
        $name = trim($_POST['name'] ?? 'Custom Legendary Blade');
        $quality = intval($_POST['quality'] ?? 5); // 5 = Legendary
        $slot = intval($_POST['slot'] ?? 13); // 13 = One hand weapon
        $class = intval($_POST['class'] ?? 2); // 2 = Weapon
        $subclass = intval($_POST['subclass'] ?? 7); // 7 = One-handed Sword
        $min_dmg = floatval($_POST['min_dmg'] ?? 150.0);
        $max_dmg = floatval($_POST['max_dmg'] ?? 250.0);
        $speed = intval($_POST['speed'] ?? 2000); // 2.0s speed
        $stamina = intval($_POST['stamina'] ?? 50);
        $strength = intval($_POST['strength'] ?? 50);
        $agility = intval($_POST['agility'] ?? 50);
        $intellect = intval($_POST['intellect'] ?? 50);
        $spellpower = intval($_POST['spellpower'] ?? 100);
        $crit = intval($_POST['crit'] ?? 40);
        $haste = intval($_POST['haste'] ?? 40);
        $char_name = trim($_POST['char_name'] ?? '');
        $displayid = intval($_POST['displayid'] ?? 17291);

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_WORLD . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));

            // Find next free custom item template entry starting at 99000
            $q = $pdo->query("SELECT MAX(entry) AS max_entry FROM item_template WHERE entry >= 99000");
            $max_row = $q->fetch(PDO::FETCH_ASSOC);
            $new_entry = intval($max_row['max_entry'] ?? 98999) + 1;
            if ($new_entry < 99000) $new_entry = 99000;

            // Stats array mapping
            $stats = array();
            if ($stamina > 0) $stats[] = array('type' => 12, 'val' => $stamina); // 12 = Stamina
            if ($strength > 0) $stats[] = array('type' => 4, 'val' => $strength); // 4 = Strength
            if ($agility > 0) $stats[] = array('type' => 3, 'val' => $agility); // 3 = Agility
            if ($intellect > 0) $stats[] = array('type' => 5, 'val' => $intellect); // 5 = Intellect

            $sql = "
                INSERT INTO item_template (
                    entry, class, subclass, name, displayid, Quality, Flags, BuyCount, BuyPrice, SellPrice, 
                    InventoryType, AllowableClass, AllowableRace, ItemLevel, RequiredLevel,
                    stat_type1, stat_value1, stat_type2, stat_value2, stat_type3, stat_value3, stat_type4, stat_value4,
                    dmg_min1, dmg_max1, dmg_type1, delay, spellid_1, spellcategory_1, Bonding
                ) VALUES (
                    :entry, :class, :subclass, :name, :displayid, :quality, 0, 1, 1000, 200, 
                    :slot, -1, -1, 200, 1,
                    :st1, :sv1, :st2, :sv2, :st3, :sv3, :st4, :sv4,
                    :min_dmg, :max_dmg, 0, :delay, 0, 0, 1
                )
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array(
                'entry' => $new_entry,
                'class' => $class,
                'subclass' => $subclass,
                'name' => $name,
                'quality' => $quality,
                'slot' => $slot,
                'st1' => isset($stats[0]) ? $stats[0]['type'] : 0,
                'sv1' => isset($stats[0]) ? $stats[0]['val'] : 0,
                'st2' => isset($stats[1]) ? $stats[1]['type'] : 0,
                'sv2' => isset($stats[1]) ? $stats[1]['val'] : 0,
                'st3' => isset($stats[2]) ? $stats[2]['type'] : 0,
                'sv3' => isset($stats[2]) ? $stats[2]['val'] : 0,
                'st4' => isset($stats[3]) ? $stats[3]['type'] : 0,
                'sv4' => isset($stats[3]) ? $stats[3]['val'] : 0,
                'min_dmg' => $min_dmg,
                'max_dmg' => $max_dmg,
                'delay' => $speed,
                'displayid' => $displayid
            ));

            // Optional: If Spell Power/Crit/Haste is set, we can add a custom item_enchantment_template or socket
            // For simplicity, inserting into item_template is enough to generate the item!
            
            // Reload item_template in-game
            sendSoapCommand('reload item_template');
            
            $output = "Custom Item Created (Entry ID: $new_entry) & reloaded in-game!";
            
            // Mail to character if name provided
            if (!empty($char_name)) {
                $r = sendSoapCommand('send mail ' . $char_name . ' "Custom Weapon Created" "Here is your custom forged blade!" ' . $new_entry);
                $output .= "\nMailed to $char_name: " . $r['output'];
            }

            echo json_encode(array('success' => true, 'output' => $output));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'output' => $e->getMessage()));
        }
        exit;
    }

    if ($action === 'get_creature_loot') {
        $entry = intval($_GET['entry'] ?? 0);
        if ($entry <= 0) {
            echo json_encode(array('success' => false, 'loot' => []));
            exit;
        }

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_WORLD . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));

            $stmt = $pdo->prepare("
                SELECT clt.Item AS item_entry, it.name AS item_name, it.Quality AS item_quality, 
                       clt.Chance AS chance, clt.MinCount AS mincount, clt.MaxCount AS maxcount,
                       clt.Reference AS reference, clt.Comment AS comment, clt.GroupId AS groupid
                FROM creature_loot_template clt
                LEFT JOIN item_template it ON clt.Item = it.entry
                WHERE clt.Entry = :entry
                ORDER BY clt.Chance DESC
            ");
            $stmt->execute(array('entry' => $entry));
            $loot = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(array('success' => true, 'loot' => $loot));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'output' => $e->getMessage(), 'loot' => []));
        }
        exit;
    }

    if ($action === 'get_reference_loot') {
        $entry = intval($_GET['entry'] ?? 0);
        if ($entry <= 0) {
            echo json_encode(array('success' => false, 'loot' => []));
            exit;
        }

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_WORLD . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));

            $stmt = $pdo->prepare("
                SELECT rlt.Item AS item_entry, it.name AS item_name, it.Quality AS item_quality, 
                       rlt.Chance AS chance, rlt.MinCount AS mincount, rlt.MaxCount AS maxcount,
                       rlt.Reference AS reference, rlt.Comment AS comment, rlt.GroupId AS groupid
                FROM reference_loot_template rlt
                LEFT JOIN item_template it ON rlt.Item = it.entry
                WHERE rlt.Entry = :entry
                ORDER BY rlt.Chance DESC
            ");
            $stmt->execute(array('entry' => $entry));
            $loot = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(array('success' => true, 'loot' => $loot));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'output' => $e->getMessage(), 'loot' => []));
        }
        exit;
    }

    if ($action === 'search_items') {
        $query = trim($_GET['query'] ?? '');
        if (empty($query)) {
            echo json_encode(array('success' => false, 'items' => []));
            exit;
        }

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_WORLD . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));

            $stmt = $pdo->prepare("
                SELECT entry, name, Quality AS quality, displayid
                FROM item_template
                WHERE name LIKE :search OR entry = :entry
                LIMIT 20
            ");
            $stmt->execute(array(
                'search' => "%$query%",
                'entry' => is_numeric($query) ? intval($query) : -1
            ));
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(array('success' => true, 'items' => $items));
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'output' => $e->getMessage(), 'items' => []));
        }
        exit;
    }

    if ($action === 'save_loot_item') {
        $creature_entry = intval($_POST['creature_entry'] ?? 0);
        $item_entry = intval($_POST['item_entry'] ?? 0);
        $reference = intval($_POST['reference'] ?? 0);
        $chance = floatval($_POST['chance'] ?? 1.0);
        $mincount = intval($_POST['mincount'] ?? 1);
        $maxcount = intval($_POST['maxcount'] ?? 1);

        if ($creature_entry <= 0 || ($item_entry <= 0 && $reference <= 0) || $chance <= 0) {
            echo json_encode(array('success' => false, 'output' => 'Invalid parameters. Chance must be greater than 0%.'));
            exit;
        }

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_WORLD . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));

            $stmt = $pdo->prepare("
                INSERT INTO creature_loot_template (Entry, Item, Reference, Chance, MinCount, MaxCount, GroupId, QuestRequired, LootMode, Comment)
                VALUES (:creature_entry, :item_entry, :reference, :chance, :mincount, :maxcount, 0, 0, 1, :comment)
                ON DUPLICATE KEY UPDATE Chance = :chance, MinCount = :mincount, MaxCount = :maxcount
            ");
            
            $comment = "";
            if ($reference > 0) {
                $comment = "Creature " . $creature_entry . " - ReferenceTable " . $reference;
            }

            $stmt->execute(array(
                'creature_entry' => $creature_entry,
                'item_entry' => $item_entry,
                'reference' => $reference,
                'chance' => $chance,
                'mincount' => $mincount,
                'maxcount' => $maxcount,
                'comment' => $comment
            ));

            $res = sendSoapCommand("reload creature_loot_template");
            if ($res['success']) {
                $res['output'] = "Loot item drop table updated and reloaded in-game successfully!";
            }
            echo json_encode($res);
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'output' => $e->getMessage()));
        }
        exit;
    }

    if ($action === 'delete_loot_item') {
        $creature_entry = intval($_POST['creature_entry'] ?? 0);
        $item_entry = intval($_POST['item_entry'] ?? 0);
        $reference = intval($_POST['reference'] ?? 0);

        if ($creature_entry <= 0 || ($item_entry <= 0 && $reference <= 0)) {
            echo json_encode(array('success' => false, 'output' => 'Invalid parameters.'));
            exit;
        }

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_WORLD . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, array(
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));

            if ($item_entry > 0) {
                $stmt = $pdo->prepare("
                    DELETE FROM creature_loot_template
                    WHERE Entry = :creature_entry AND Item = :item_entry AND Reference = 0
                ");
                $stmt->execute(array(
                    'creature_entry' => $creature_entry,
                    'item_entry' => $item_entry
                ));
            } else {
                $stmt = $pdo->prepare("
                    DELETE FROM creature_loot_template
                    WHERE Entry = :creature_entry AND Item = 0 AND Reference = :reference
                ");
                $stmt->execute(array(
                    'creature_entry' => $creature_entry,
                    'reference' => $reference
                ));
            }

            $res = sendSoapCommand("reload creature_loot_template");
            if ($res['success']) {
                $res['output'] = "Loot item removed and table reloaded in-game successfully!";
            }
            echo json_encode($res);
        } catch (Exception $e) {
            echo json_encode(array('success' => false, 'output' => $e->getMessage()));
        }
        exit;
    }
}

// Establish temporary DB connection for UI rendering queries
$dbOnline = false;
$onlinePlayers = 0;
$totalCharacters = 0;
$activeAccounts = 0;

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_WORLD . ";charset=utf8mb4", DB_USER, DB_PASS, array(
        PDO::ATTR_TIMEOUT => 2,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ));
    $dbOnline = true;
    
    // Fetch stats
    $onlinePlayers = $pdo->query("SELECT COUNT(*) FROM acore_characters.characters WHERE online = 1")->fetchColumn();
    $totalCharacters = $pdo->query("SELECT COUNT(*) FROM acore_characters.characters")->fetchColumn();
    $activeAccounts = $pdo->query("SELECT COUNT(*) FROM acore_auth.account")->fetchColumn();
} catch (Exception $e) {
    // DB is offline
}

// Read current active config options to populate forms
$pbEnabled = 0;
$pbMinBots = 0;
$pbMaxBots = 0;
$pbConfigPath = '/home/coyofroyo/azeroth-server/etc/modules/playerbots.conf';
if (file_exists($pbConfigPath)) {
    $content = file_get_contents($pbConfigPath);
    if (preg_match('/^AiPlayerbot\.Enabled\s*=\s*(\d+)/m', $content, $matches)) {
        $pbEnabled = intval($matches[1]);
    }
    if (preg_match('/^AiPlayerbot\.MinRandomBots\s*=\s*(\d+)/m', $content, $matches)) {
        $pbMinBots = intval($matches[1]);
    }
    if (preg_match('/^AiPlayerbot\.MaxRandomBots\s*=\s*(\d+)/m', $content, $matches)) {
        $pbMaxBots = intval($matches[1]);
    }
}

$cmEnabled = 0;
$cmHardcore = 0;
$cmSemiHardcore = 0;
$cmSelfCrafted = 0;
$cmItemQuality = 0;
$cmSlowXp = 0;
$cmVerySlowXp = 0;
$cmQuestXp = 0;
$cmIronMan = 0;
$cmConfigPath = '/home/coyofroyo/azeroth-server/etc/modules/challenge_modes.conf';
if (file_exists($cmConfigPath)) {
    $content = file_get_contents($cmConfigPath);
    if (preg_match('/^ChallengeModes\.Enable\s*=\s*(\d+)/m', $content, $matches)) {
        $cmEnabled = intval($matches[1]);
    }
    if (preg_match('/^Hardcore\.Enable\s*=\s*(\d+)/m', $content, $matches)) {
        $cmHardcore = intval($matches[1]);
    }
    if (preg_match('/^SemiHardcore\.Enable\s*=\s*(\d+)/m', $content, $matches)) {
        $cmSemiHardcore = intval($matches[1]);
    }
    if (preg_match('/^SelfCrafted\.Enable\s*=\s*(\d+)/m', $content, $matches)) {
        $cmSelfCrafted = intval($matches[1]);
    }
    if (preg_match('/^ItemQualityLevel\.Enable\s*=\s*(\d+)/m', $content, $matches)) {
        $cmItemQuality = intval($matches[1]);
    }
    if (preg_match('/^SlowXpGain\.Enable\s*=\s*(\d+)/m', $content, $matches)) {
        $cmSlowXp = intval($matches[1]);
    }
    if (preg_match('/^VerySlowXpGain\.Enable\s*=\s*(\d+)/m', $content, $matches)) {
        $cmVerySlowXp = intval($matches[1]);
    }
    if (preg_match('/^QuestXpOnly\.Enable\s*=\s*(\d+)/m', $content, $matches)) {
        $cmQuestXp = intval($matches[1]);
    }
    if (preg_match('/^IronMan\.Enable\s*=\s*(\d+)/m', $content, $matches)) {
        $cmIronMan = intval($matches[1]);
    }
}

$transmogEnabled = 0;
$transmogConfigPath = '/home/coyofroyo/azeroth-server/etc/modules/transmog.conf';
if (file_exists($transmogConfigPath)) {
    $content = file_get_contents($transmogConfigPath);
    if (preg_match('/^Transmogrification\.Enable\s*=\s*(\d+)/m', $content, $matches)) {
        $transmogEnabled = intval($matches[1]);
    }
}

$randomEnchantsEnabled = 0;
$randomEnchantsConfigPath = '/home/coyofroyo/azeroth-server/etc/modules/random_enchants.conf';
if (file_exists($randomEnchantsConfigPath)) {
    $content = file_get_contents($randomEnchantsConfigPath);
    if (preg_match('/^RandomEnchants\.Enable\s*=\s*(\d+)/m', $content, $matches)) {
        $randomEnchantsEnabled = intval($matches[1]);
    }
}

$autobalanceEnabled = 0;
$autoberanceConfigPath = '/home/coyofroyo/azeroth-server/etc/modules/AutoBalance.conf';
if (file_exists($autoberanceConfigPath)) {
    $content = file_get_contents($autoberanceConfigPath);
    if (preg_match('/^AutoBalance\.Enable\.Global\s*=\s*(\d+)/m', $content, $matches)) {
        $autobalanceEnabled = intval($matches[1]);
    }
}

$sololfgEnabled = 0;
$sololfgConfigPath = '/home/coyofroyo/azeroth-server/etc/modules/SoloLfg.conf';
if (file_exists($sololfgConfigPath)) {
    $content = file_get_contents($sololfgConfigPath);
    if (preg_match('/^SoloLFG\.Enable\s*=\s*(\d+)/m', $content, $matches)) {
        $sololfgEnabled = intval($matches[1]);
    }
}

$aoeLootEnabled = 0;
$aoeLootConfigPath = '/home/coyofroyo/azeroth-server/etc/modules/mod_aoe_loot.conf';
if (file_exists($aoeLootConfigPath)) {
    $content = file_get_contents($aoeLootConfigPath);
    if (preg_match('/^AOELoot\.Enable\s*=\s*(\d+)/m', $content, $matches)) {
        $aoeLootEnabled = intval($matches[1]);
    }
}

$questPartyEnabled = 0;
$questPartyPaths = [
    '/home/coyofroyo/azeroth-server/etc/modules/mod_quest_loot_party.conf',
    '/home/coyofroyo/azeroth-server/etc/modules/mod-quest-loot-party.conf'
];
foreach ($questPartyPaths as $path) {
    if (file_exists($path)) {
        $fileContent = file_get_contents($path);
        if (preg_match('/^QuestParty\.Enable\s*=\s*(true|1|false|0)/m', $fileContent, $matches)) {
            $val = strtolower(trim($matches[1]));
            $questPartyEnabled = ($val === 'true' || $val === '1') ? 1 : 0;
        }
        break;
    }
}

$groupQuestsEnabled = 0;
$groupQuestsPaths = [
    '/home/coyofroyo/azeroth-server/etc/modules/mod_groupquests.conf',
    '/home/coyofroyo/azeroth-server/etc/modules/mod-groupquests.conf',
    '/home/coyofroyo/azeroth-server/etc/modules/groupquests.conf'
];
foreach ($groupQuestsPaths as $path) {
    if (file_exists($path)) {
        $fileContent = file_get_contents($path);
        if (preg_match('/^(GroupQuests|mod_groupquests)\.Enable\s*=\s*(true|1|false|0)/m', $fileContent, $matches)) {
            $val = strtolower(trim($matches[2]));
            $groupQuestsEnabled = ($val === 'true' || $val === '1') ? 1 : 0;
        }
        break;
    }
}

$mythicPlusEnabled = 0;
$mythicPlusConfigPath = '/home/coyofroyo/azeroth-server/etc/modules/mod_mythic_plus.conf';
if (file_exists($mythicPlusConfigPath)) {
    $content = file_get_contents($mythicPlusConfigPath);
    if (preg_match('/^MythicPlus\.Enable\s*=\s*(\d+)/m', $content, $matches)) {
        $mythicPlusEnabled = intval($matches[1]);
    }
}

$itemUpgradeEnabled = 0;
$itemUpgradeConfigPath = '/home/coyofroyo/azeroth-server/etc/modules/mod_item_upgrade.conf';
if (file_exists($itemUpgradeConfigPath)) {
    $content = file_get_contents($itemUpgradeConfigPath);
    if (preg_match('/^ItemUpgrade\.Enable\s*=\s*(\d+)/m', $content, $matches)) {
        $itemUpgradeEnabled = intval($matches[1]);
    }
}

$freeProfessionsEnabled = 0;
$freeProfessionsConfigPath = '/home/coyofroyo/azeroth-server/etc/modules/mod_npc_free_professions.conf';
if (file_exists($freeProfessionsConfigPath)) {
    $content = file_get_contents($freeProfessionsConfigPath);
    if (preg_match('/^NpcFreeProfessions\.Enable\s*=\s*(\d+)/m', $content, $matches)) {
        $freeProfessionsEnabled = intval($matches[1]);
    }
}

$accountMountEnabled = 0;
$accountMountConfigPath = '/home/coyofroyo/azeroth-server/etc/modules/mod_account_mount.conf';
if (file_exists($accountMountConfigPath)) {
    $content = file_get_contents($accountMountConfigPath);
    if (preg_match('/^Account\.Mounts\.Enable\s*=\s*(\d+)/m', $content, $matches)) {
        $accountMountEnabled = intval($matches[1]);
    }
}

$accountAchievementsEnabled = 0;
$accountAchievementsConfigPath = '/home/coyofroyo/azeroth-server/etc/modules/mod_achievements.conf';
if (file_exists($accountAchievementsConfigPath)) {
    $content = file_get_contents($accountAchievementsConfigPath);
    if (preg_match('/^Account\.Achievements\.Enable\s*=\s*(\d+)/m', $content, $matches)) {
        $accountAchievementsEnabled = intval($matches[1]);
    }
}

$onlineBots = [];
$onlineBotsCount = 0;
if ($dbOnline) {
    try {
        $stmt = $pdo->query("
            SELECT c.guid, c.name, c.level, c.race, c.class, c.gender, c.map, c.online
            FROM acore_characters.characters c
            JOIN acore_playerbots.playerbots_random_bots b ON c.guid = b.bot
            WHERE c.online = 1
            ORDER BY c.level DESC, c.name ASC
        ");
        $onlineBots = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $onlineBotsCount = count($onlineBots);
    } catch (Exception $e) {
        // Empty
    }
}

// Fetch active events from MariaDB and check active status via SOAP
$activeEventsList = [];
$soapActiveEventIds = [];
$soapRes = sendSoapCommand('event active');
if ($soapRes['success']) {
    preg_match_all('/^\s*(\d+)\s*-/m', $soapRes['output'], $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $id) {
            $soapActiveEventIds[] = intval($id);
        }
    }
}

if ($dbOnline) {
    try {
        $stmtEvents = $pdo->query("
            SELECT ge.eventEntry AS id, ge.description AS name
            FROM game_event ge
            WHERE ge.description IS NOT NULL AND ge.description != '' AND ge.eventEntry > 0
            ORDER BY ge.description ASC
        ");
        $allEvents = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);
        foreach ($allEvents as $evt) {
            $evt['active'] = in_array(intval($evt['id']), $soapActiveEventIds) ? 1 : 0;
            $activeEventsList[] = $evt;
        }
    } catch (Exception $e) {
        // Empty
    }
}

// Read progression limit
$progressionLimit = 0;
if (file_exists(CONFIG_PATH)) {
    $content = file_get_contents(CONFIG_PATH);
    if (preg_match('/^IndividualProgression\.ProgressionLimit\s*=\s*(\d+)/m', $content, $matches)) {
        $progressionLimit = intval($matches[1]);
    }
}

$ollamaPersonalitiesList = [];
if ($dbOnline) {
    try {
        $stmtPers = $pdo->query("SELECT `key`, prompt FROM acore_characters.mod_ollama_chat_personality_templates ORDER BY `key` ASC");
        $ollamaPersonalitiesList = $stmtPers->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Empty
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OurAzeroth Server Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0b0f19;
            --bg-secondary: rgba(17, 24, 39, 0.65);
            --bg-glass: rgba(17, 24, 39, 0.45);
            --border-glass: rgba(255, 255, 255, 0.075);
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --accent-primary: #6366f1;
            --accent-hover: #4f46e5;
            --accent-glow: rgba(99, 102, 241, 0.35);
            --status-success: #10b981;
            --status-danger: #ef4444;
            --status-warning: #f59e0b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.1) transparent;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.08) 0px, transparent 50%);
            background-attachment: fixed;
            overflow-x: hidden;
        }

        /* Sidebar styling */
        .sidebar {
            width: 280px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-glass);
            backdrop-filter: blur(16px);
            padding: 2rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 100;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-glass);
        }

        .logo-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--accent-primary), var(--status-success));
            border-radius: 10px;
            box-shadow: 0 0 15px var(--accent-glow);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            color: #fff;
        }

        .logo-text {
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #fff, #9ca3af);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            flex-grow: 1;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.25s ease;
            border: 1px solid transparent;
        }

        .nav-item:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.03);
        }

        .nav-item.active {
            color: #fff;
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.25);
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.1);
        }

        .nav-footer {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.3);
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid var(--border-glass);
        }

        /* Main content styling */
        .main-content {
            margin-left: 280px;
            flex-grow: 1;
            padding: 2.5rem;
            min-height: 100vh;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Top header metrics bar */
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .db-status-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: var(--status-success);
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .db-status-badge.offline {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--status-danger);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: currentColor;
            box-shadow: 0 0 8px currentColor;
        }

        /* Dashboard Grid layouts */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .stat-card {
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 1.5rem;
            backdrop-filter: blur(12px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s, border-color 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.12);
        }

        .stat-title {
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
        }

        .stat-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
        }

        .stat-subtext {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }

        /* Widgets cards */
        .card {
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            padding: 2rem;
            backdrop-filter: blur(12px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
            margin-bottom: 2rem;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #fff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 0.75rem;
        }

        .card-split-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2.5rem;
        }

        @media (max-width: 900px) {
            .card-split-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        /* Forms inputs & components */
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-secondary);
        }

        input, select, textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-glass);
            border-radius: 10px;
            color: #fff;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            margin-bottom: 1rem;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 10px rgba(99, 102, 241, 0.25);
            background: rgba(255, 255, 255, 0.05);
        }

        .search-input {
            background: rgba(255, 255, 255, 0.04) url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="rgba(255,255,255,0.6)" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>') no-repeat 0.85rem center !important;
            padding-left: 2.5rem !important;
            border: 1px solid var(--border-glass) !important;
            border-radius: 10px !important;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15) !important;
            transition: all 0.2s ease !important;
        }

        .search-input:focus {
            border-color: var(--accent-primary) !important;
            box-shadow: 0 0 12px rgba(99, 102, 241, 0.35), inset 0 2px 4px rgba(0, 0, 0, 0.15) !important;
            background-color: rgba(255, 255, 255, 0.07) !important;
        }

        .search-input {
            background: rgba(255, 255, 255, 0.04) url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="rgba(255,255,255,0.6)" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>') no-repeat 0.85rem center !important;
            padding-left: 2.5rem !important;
            border: 1px solid var(--border-glass) !important;
            border-radius: 10px !important;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15) !important;
            transition: all 0.2s ease !important;
        }

        .search-input:focus {
            border-color: var(--accent-primary) !important;
            box-shadow: 0 0 12px rgba(99, 102, 241, 0.35), inset 0 2px 4px rgba(0, 0, 0, 0.15) !important;
            background-color: rgba(255, 255, 255, 0.07) !important;
        }

        select option {
            background-color: #111827;
            color: #fff;
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 0.75rem 1.5rem;
            background: var(--accent-primary);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            text-align: center;
        }

        .btn:hover {
            background: var(--accent-hover);
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.4);
        }

        .btn-danger {
            background: var(--status-danger);
        }

        .btn-danger:hover {
            background: #dc2626;
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
        }

        .btn-success {
            background: var(--status-success);
        }

        .btn-success:hover {
            background: #059669;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.4);
        }

        /* Database tables */
        .admin-table-container {
            width: 100%;
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--border-glass);
            background: rgba(0, 0, 0, 0.15);
            margin-top: 1rem;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.9rem;
        }

        .admin-table th, .admin-table td {
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .admin-table th {
            background: rgba(255, 255, 255, 0.02);
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        .admin-table tr:last-child td {
            border-bottom: none;
        }

        .admin-table tr:hover td {
            background: rgba(255, 255, 255, 0.015);
        }

        /* Interactive Shell Terminal */
        .console-area {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--border-glass);
            border-radius: 12px;
            padding: 1rem;
            height: 380px;
            overflow-y: auto;
            font-family: monospace;
            color: var(--status-success);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        /* Autocomplete dropdown */
        .autocomplete-suggestions {
            position: absolute;
            background: #111827;
            border: 1px solid var(--border-glass);
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            width: 100%;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        .autocomplete-suggestion {
            padding: 0.5rem 1rem;
            cursor: pointer;
            color: var(--text-primary);
        }
        .autocomplete-suggestion:hover {
            background: var(--accent-primary);
            color: #fff;
        }

        /* Layout Grid Helpers */
        .col-12 { grid-column: span 12; }
        .flex-row {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.65rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            font-size: 0.9rem;
        }
        .detail-label {
            color: var(--text-secondary);
        }
        .detail-val {
            color: #fff;
            font-weight: 500;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo-area">
            <div class="logo-icon">AC</div>
            <div class="logo-text">OurAzeroth Server</div>
        </div>

        <div class="nav-menu">
            <div class="nav-item active" onclick="switchTab('dashboard')" id="nav-dashboard">
                <span>📊</span> Dashboard & Controls
            </div>
            <div class="nav-item" onclick="switchTab('auction-house')" id="nav-auction-house">
                <span>⚖️</span> Live Auction House
            </div>
            <div class="nav-item" onclick="switchTab('char-tools')" id="nav-char-tools">
                <span>👥</span> Character Tools
            </div>
            <div class="nav-item" onclick="switchTab('playerbots')" id="nav-playerbots">
                <span>🤖</span> Playerbots Manager
            </div>
            <div class="nav-item" onclick="switchTab('llm-tuner')" id="nav-llm-tuner">
                <span>🧠</span> Ollama LLM Tuner
            </div>
            <div class="nav-item" onclick="switchTab('rare-spawner')" id="nav-rare-spawner">
                <span>👾</span> Rare Spawner Tool
            </div>
            <div class="nav-item" onclick="switchTab('db-editors')" id="nav-db-editors">
                <span>🗄️</span> Database Editors
            </div>
            <div class="nav-item" onclick="switchTab('item-creator')" id="nav-item-creator">
                <span>⚔️</span> Item Creator & Spawner
            </div>
            <div class="nav-item" onclick="switchTab('system-logs')" id="nav-system-logs">
                <span>📰</span> Chat & Event Logs
            </div>
            <div class="nav-item" onclick="switchTab('console-tab')" id="nav-console-tab">
                <span>💬</span> SOAP Console
            </div>
        </div>

        <div class="nav-footer">
            Admin Dashboard v3.0<br>
            AzerothCore Engine
        </div>
    </div>

    <!-- MAIN BODY -->
    <div class="main-content">

        <!-- HEADER STATUS BAR -->
        <div class="header-bar">
            <div>
                <h1 class="page-title">Management Dashboard</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.25rem;">Orchestrate server parameters, database values, and player sessions</p>
            </div>

            <div class="db-status-badge <?php echo $dbOnline ? '' : 'offline'; ?>">
                <div class="status-dot"></div>
                Database: <?php echo $dbOnline ? 'Online' : 'Offline'; ?>
            </div>
        </div>

        <!-- TAB 1: DASHBOARD & CONTROLS -->
        <div class="tab-content active" id="tab-dashboard">
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-title">Host CPU Usage</div>
                    <div class="stat-value" id="stat-cpu">--%</div>
                    <div class="stat-subtext" id="stat-cpu-load">Load: --</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Host CPU Temp</div>
                    <div class="stat-value" id="stat-temp">--°C</div>
                    <div class="stat-subtext" id="stat-temp-status">Thermal Status: Safe</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Host RAM Usage</div>
                    <div class="stat-value" id="stat-ram">--%</div>
                    <div class="stat-subtext" id="stat-ram-GB">-- / -- GB</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Host Disk Space</div>
                    <div class="stat-value" id="stat-disk">--%</div>
                    <div class="stat-subtext" id="stat-disk-GB">-- / -- GB</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Host Uptime</div>
                    <div class="stat-value" style="font-size: 1.1rem; line-height: 1.6;" id="stat-uptime">Loading...</div>
                    <div class="stat-subtext">Operating System</div>
                </div>
            </div>

            <!-- Server Status & Controls -->
            <div class="card">
                <div class="card-title">Server Daemon Process Control</div>
                <div class="card-split-grid">
                    <div>
                        <h3 style="font-size: 1.1rem; font-weight: 500; margin-bottom: 1rem; color: var(--text-primary);">Subsystem Status</h3>
                        <div class="detail-row">
                            <span class="detail-label">Authserver Process:</span>
                            <span class="detail-val" id="svc-auth">Checking...</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Worldserver Process:</span>
                            <span class="detail-val" id="svc-world">Checking...</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">SOAP Daemon:</span>
                            <span class="detail-val" id="svc-soap">Checking...</span>
                        </div>
                    </div>
                    <div>
                        <h3 style="font-size: 1.1rem; font-weight: 500; margin-bottom: 1rem; color: var(--text-primary);">Actions</h3>
                        <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.25rem;">
                            Control server processes directly. Shuttings down saves player stats and terminates the tmux container.
                        </p>
                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <button onclick="triggerRestart()" class="btn btn-warning" style="padding: 0.75rem 1.25rem; margin-top: 0; width: auto; flex: 1;">🔄 Restart Server</button>
                            <button onclick="triggerShutdown()" class="btn btn-danger" style="padding: 0.75rem 1.25rem; margin-top: 0; width: auto; flex: 1;">🛑 Shutdown Server</button>
                            <button onclick="triggerStart()" class="btn btn-success" style="padding: 0.75rem 1.25rem; margin-top: 0; width: auto; flex: 1;">⚡ Start Server</button>
                        </div>
                        <div id="restartStatus" style="margin-top: 1rem; font-weight: 500; font-size: 0.9rem;"></div>
                    </div>
                </div>
            </div>

            <!-- Custom Modules and Random Bots -->
            <div class="card">
                <div class="card-title">Server Custom Modules Control</div>
                <form onsubmit="saveFeaturesConfig(event)">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div>
                            <label for="featTransmog">Transmogrification System</label>
                            <select id="featTransmog">
                                <option value="1" <?php echo $transmogEnabled == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $transmogEnabled == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="featEnchants">Diablo-style Random Enchants</label>
                            <select id="featEnchants">
                                <option value="1" <?php echo $randomEnchantsEnabled == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $randomEnchantsEnabled == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="featAutobalance">Autobalance Difficulty Scaling</label>
                            <select id="featAutobalance">
                                <option value="1" <?php echo $autobalanceEnabled == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $autobalanceEnabled == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="featSoloLfg">Solo LFG System</label>
                            <select id="featSoloLfg">
                                <option value="1" <?php echo $sololfgEnabled == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $sololfgEnabled == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="featAoELoot">AoE Looting System</label>
                            <select id="featAoELoot">
                                <option value="1" <?php echo $aoeLootEnabled == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $aoeLootEnabled == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="featMythicPlus">Mythic+ Dungeons</label>
                            <select id="featMythicPlus">
                                <option value="1" <?php echo $mythicPlusEnabled == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $mythicPlusEnabled == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="featItemUpgrade">Item Upgrade System</label>
                            <select id="featItemUpgrade">
                                <option value="1" <?php echo $itemUpgradeEnabled == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $itemUpgradeEnabled == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="featFreeProfessions">Free Professions NPCs</label>
                            <select id="featFreeProfessions">
                                <option value="1" <?php echo $freeProfessionsEnabled == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $freeProfessionsEnabled == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="featAccountMount">Account-Wide Mounts</label>
                            <select id="featAccountMount">
                                <option value="1" <?php echo $accountMountEnabled == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $accountMountEnabled == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="featAccountAchievements">Account-Wide Achievements</label>
                            <select id="featAccountAchievements">
                                <option value="1" <?php echo $accountAchievementsEnabled == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $accountAchievementsEnabled == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="featQuestParty">Quest Loot Sharing (QuestParty)</label>
                            <select id="featQuestParty">
                                <option value="1" <?php echo $questPartyEnabled == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $questPartyEnabled == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="featGroupQuests" style="color: var(--text-secondary);">Objective Sharing (GroupQuests) <span style="font-size:0.75rem; color:var(--status-danger);">[Not Installed]</span></label>
                            <select id="featGroupQuests" disabled style="opacity: 0.6; cursor: not-allowed; background: rgba(0, 0, 0, 0.3);">
                                <option value="0" selected>Disabled (Requires Recompile)</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: auto;">Save Features Config & Reload</button>
                </form>
            </div>

            <!-- Challenge Modes -->
            <div class="card">
                <div class="card-title">Challenge Modes Configurations</div>
                <form onsubmit="saveChallengeConfig(event)">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div>
                            <label for="cmEnabled">Global Challenge Modes</label>
                            <select id="cmEnabled">
                                <option value="1" <?php echo $cmEnabled == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $cmEnabled == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="cmHardcore">Hardcore Mode (1 Life)</label>
                            <select id="cmHardcore">
                                <option value="1" <?php echo $cmHardcore == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $cmHardcore == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="cmSemiHardcore">Semi-Hardcore Mode</label>
                            <select id="cmSemiHardcore">
                                <option value="1" <?php echo $cmSemiHardcore == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $cmSemiHardcore == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="cmSelfCrafted">Self-Crafted Mode</label>
                            <select id="cmSelfCrafted">
                                <option value="1" <?php echo $cmSelfCrafted == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $cmSelfCrafted == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="cmItemQuality">Item Quality Mode</label>
                            <select id="cmItemQuality">
                                <option value="1" <?php echo $cmItemQuality == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $cmItemQuality == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="cmSlowXp">Slow XP (0.5x)</label>
                            <select id="cmSlowXp">
                                <option value="1" <?php echo $cmSlowXp == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $cmSlowXp == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="cmVerySlowXp">Very Slow XP (0.25x)</label>
                            <select id="cmVerySlowXp">
                                <option value="1" <?php echo $cmVerySlowXp == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $cmVerySlowXp == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="cmQuestXp">Quest XP Only</label>
                            <select id="cmQuestXp">
                                <option value="1" <?php echo $cmQuestXp == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $cmQuestXp == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="cmIronMan">Ironman Mode</label>
                            <select id="cmIronMan">
                                <option value="1" <?php echo $cmIronMan == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $cmIronMan == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: auto;">Save Challenge Config & Reload</button>
                </form>
            </div>

            <!-- Bot Settings -->
            <div class="card">
                <div class="card-title">Random Playerbots Config</div>
                <form onsubmit="saveBotConfig(event)">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div>
                            <label for="botEnabled">Playerbots System</label>
                            <select id="botEnabled">
                                <option value="1" <?php echo $pbEnabled == 1 ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo $pbEnabled == 0 ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div>
                            <label for="botMin">Min Random Bots</label>
                            <input type="number" id="botMin" value="<?php echo $pbMinBots; ?>">
                        </div>
                        <div>
                            <label for="botMax">Max Random Bots</label>
                            <input type="number" id="botMax" value="<?php echo $pbMaxBots; ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: auto;">Apply Range & Reload Config</button>
                </form>
            </div>

            <!-- Progression & Events -->
            <div class="card">
                <div class="card-title">Server Progression and Seasonal Events</div>
                <div class="card-split-grid">
                    <div>
                        <h3 style="font-size: 1.1rem; font-weight: 500; margin-bottom: 1rem; color: var(--text-primary);">Seasonal Events Toggler</h3>
                        <input type="text" id="eventSearchInput" class="search-input" onkeyup="filterEvents(this.value)" placeholder="Search events (e.g. Midsummer)..." style="margin-bottom: 0.75rem;">
                        <div style="max-height: 350px; overflow-y: auto; border: 1px solid var(--border-glass); padding: 0.5rem; border-radius: 8px; background: rgba(0, 0, 0, 0.15);">
                            <?php foreach ($activeEventsList as $evt): ?>
                            <div class="detail-row event-row" data-name="<?php echo htmlspecialchars(strtolower($evt['name'])); ?>" style="padding: 0.5rem 0.25rem;">
                                <span><?php echo htmlspecialchars($evt['name']); ?> (ID: <?php echo $evt['id']; ?>)</span>
                                <button onclick="toggleServerEvent(<?php echo $evt['id']; ?>, <?php echo $evt['active'] ? 1 : 0; ?>)" class="btn <?php echo $evt['active'] ? 'btn-success' : 'btn-danger'; ?>" style="font-size: 0.75rem; padding: 0.25rem 0.5rem; width: auto; margin-top: 0;">
                                    <?php echo $evt['active'] ? 'Active 🟢' : 'Off 🔴'; ?>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <h3 style="font-size: 1.1rem; font-weight: 500; margin-bottom: 1rem; color: var(--text-primary);">Individual Progression Gating</h3>
                        <form onsubmit="saveProgressionLimit(event)">
                            <label for="progressionLimit">Expansion Level Lock</label>
                            <select id="progressionLimit">
                                <option value="0" <?php echo $progressionLimit == 0 ? 'selected' : ''; ?>>0 - Pre-Vanilla (Level 60, no raids)</option>
                                <option value="1" <?php echo $progressionLimit == 1 ? 'selected' : ''; ?>>1 - MC & Onyxia Gated</option>
                                <option value="2" <?php echo $progressionLimit == 2 ? 'selected' : ''; ?>>2 - BWL Gated</option>
                                <option value="3" <?php echo $progressionLimit == 3 ? 'selected' : ''; ?>>3 - Zul'Gurub Gated</option>
                                <option value="4" <?php echo $progressionLimit == 4 ? 'selected' : ''; ?>>4 - AQ Gated</option>
                                <option value="5" <?php echo $progressionLimit == 5 ? 'selected' : ''; ?>>5 - Naxxramas 40 Gated</option>
                                <option value="6" <?php echo $progressionLimit == 6 ? 'selected' : ''; ?>>6 - Burning Crusade TBC Pre-patch (Level 60)</option>
                                <option value="7" <?php echo $progressionLimit == 7 ? 'selected' : ''; ?>>7 - TBC Karazhan / Gruul / Magtheridon Lock (Level 70)</option>
                                <option value="8" <?php echo $progressionLimit == 8 ? 'selected' : ''; ?>>8 - TBC Serpentshrine Cavern / Tempest Keep Lock</option>
                                <option value="9" <?php echo $progressionLimit == 9 ? 'selected' : ''; ?>>9 - TBC Mount Hyjal / Black Temple Lock</option>
                                <option value="10" <?php echo $progressionLimit == 10 ? 'selected' : ''; ?>>10 - TBC Zul'Aman Lock</option>
                                <option value="11" <?php echo $progressionLimit == 11 ? 'selected' : ''; ?>>11 - TBC Sunwell Plateau Lock</option>
                                <option value="12" <?php echo $progressionLimit == 12 ? 'selected' : ''; ?>>12 - Wrath of the Lich King Pre-patch (Level 70)</option>
                                <option value="13" <?php echo $progressionLimit == 13 ? 'selected' : ''; ?>>13 - WotLK Naxx / Malygos / Sartharion Gated (Level 80)</option>
                                <option value="14" <?php echo $progressionLimit == 14 ? 'selected' : ''; ?>>14 - WotLK Ulduar Gated</option>
                                <option value="15" <?php echo $progressionLimit == 15 ? 'selected' : ''; ?>>15 - WotLK Trial of the Crusader Gated</option>
                                <option value="16" <?php echo $progressionLimit == 16 ? 'selected' : ''; ?>>16 - WotLK Icecrown Citadel (ICC) Gated</option>
                                <option value="17" <?php echo $progressionLimit == 17 ? 'selected' : ''; ?>>17 - WotLK Ruby Sanctum Gated</option>
                                <option value="18" <?php echo $progressionLimit == 18 ? 'selected' : ''; ?>>18 - Gating Fully Unlocked (All content open)</option>
                            </select>
                            <button type="submit" class="btn btn-primary">Lock Expansion Stage</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: DATABASE EDITORS -->
        <div class="tab-content" id="tab-db-editors">
            <!-- Creature Template Editor -->
            <div class="card">
                <div class="card-title">Monster (Creature Template) Editor</div>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">
                    Search the world database for monsters. Edit levels, names, health pools, damage, and armor, then hot-reload immediately.
                </p>
                <div class="card-split-grid">
                    <div>
                        <label for="creatureSearch">Search Monsters</label>
                        <div class="search-input-wrapper" style="position: relative; display: flex; align-items: center; margin-bottom: 1rem;">
                            <span class="search-icon" style="position: absolute; left: 12px; color: var(--text-secondary); pointer-events: none; z-index: 5;">🔍</span>
                            <input type="text" id="creatureSearch" class="search-input" placeholder="Type name, ID, or ranks (e.g. 'elite wolf')..." autocomplete="off" oninput="filterAdminCreatureSearch(this.value)" style="padding-left: 36px; margin-bottom: 0; width: 100%;">
                            <button class="clear-search-btn" id="clearAdminCreatureSearchBtn" onclick="clearAdminCreatureSearch()" style="position: absolute; right: 12px; background: transparent; border: none; color: var(--text-secondary); cursor: pointer; display: none; font-size: 1.1rem; line-height: 1; z-index: 5;">✕</button>
                            <div id="creatureSearchDropdown" class="autocomplete-dropdown" style="display: none; position: absolute; top: 100%; left: 0; width: 100%; max-height: 250px; overflow-y: auto; background: #121826; border: 1px solid var(--border-glass); border-radius: 8px; z-index: 1000; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); margin-top: 5px; padding: 0.25rem;"></div>
                        </div>
                        <div class="admin-table-container" style="max-height: 380px;">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Entry ID</th>
                                        <th>Name</th>
                                        <th>Lvl Range</th>
                                        <th style="text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="creatureSearchResultBody">
                                    <tr>
                                        <td colspan="4" style="color: var(--text-secondary); text-align: center; padding: 2rem;">Search for a monster to begin editing.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="creatureFormArea" style="opacity: 0.5; pointer-events: none; transition: opacity 0.3s;">
                        <h3 style="font-size: 1.1rem; font-weight: 500; margin-bottom: 1rem; color: var(--accent-primary);">
                            Editing Template ID: <span id="editCreatureEntry">--</span>
                        </h3>
                        <form onsubmit="saveCreatureDetails(event)">
                            <input type="hidden" id="editCreatureId">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <label for="editCreatureName">Display Name</label>
                                    <input type="text" id="editCreatureName" required>
                                </div>
                                <div>
                                    <label for="editCreatureSubname">Subname (e.g. Rare, Vendor)</label>
                                    <input type="text" id="editCreatureSubname">
                                </div>
                                <div>
                                    <label for="editCreatureMinLvl">Minimum Level</label>
                                    <input type="number" id="editCreatureMinLvl" required>
                                </div>
                                <div>
                                    <label for="editCreatureMaxLvl">Maximum Level</label>
                                    <input type="number" id="editCreatureMaxLvl" required>
                                </div>
                                <div>
                                    <label for="editCreatureMinHealth">Health Modifier</label>
                                    <input type="number" step="0.01" id="editCreatureMinHealth" required oninput="updateActualStatsDisplay()">
                                    <span id="actualHealthDisplay" style="font-size: 0.8rem; color: #10b981; display: block; margin-top: 4px; font-weight: 500;">(Estimated HP: --)</span>
                                </div>
                                <div>
                                    <label for="editCreatureMaxHealth">Mana Modifier</label>
                                    <input type="number" step="0.01" id="editCreatureMaxHealth" required>
                                </div>
                                <div>
                                    <label for="editCreatureArmor">Armor Modifier</label>
                                    <input type="number" step="0.01" id="editCreatureArmor" required oninput="updateActualStatsDisplay()">
                                    <span id="actualArmorDisplay" style="font-size: 0.8rem; color: #38bdf8; display: block; margin-top: 4px; font-weight: 500;">(Estimated Armor: --)</span>
                                </div>
                                <div>
                                    <label for="editCreatureDamageMult">Damage Multiplier</label>
                                    <input type="number" step="0.1" id="editCreatureDamageMult" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success" style="margin-top: 1rem;">Save Stats & Reload Creature</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Creature Loot Table Editor -->
            <div class="card">
                <div class="card-title">Monster Loot Table Editor</div>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">
                    Load the loot drop table for a specific monster, modify drop rates, add new item drops (with autocomplete), or remove items.
                </p>
                <div class="card-split-grid">
                    <div>
                        <h3 style="font-size: 1.1rem; font-weight: 500; margin-bottom: 1rem;">Active Drop Table</h3>
                        <div id="lootCreatureHeader" style="margin-bottom: 1rem; font-weight: 600; color: var(--accent-primary);">No Monster Selected</div>
                        <div class="admin-table-container" style="max-height: 400px;">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Item Name (ID)</th>
                                        <th>Qty Range</th>
                                        <th>Drop Chance</th>
                                        <th style="text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="creatureLootTableBody">
                                    <tr>
                                        <td colspan="4" style="color: var(--text-secondary); text-align: center; padding: 2rem;">Select a monster from the list above to view its loot drops.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="lootAddArea" style="opacity: 0.5; pointer-events: none; transition: opacity 0.3s;">
                        <h3 style="font-size: 1.1rem; font-weight: 500; margin-bottom: 1rem; color: var(--accent-primary);">Add / Edit Loot Drop</h3>
                        <form onsubmit="saveLootDrop(event)">
                            <div style="position: relative;">
                                <label for="lootItemSearch">Search Item Name or Entry ID</label>
                                <input type="text" id="lootItemSearch" class="search-input" autocomplete="off" oninput="autocompleteItemSearch(this.value)" style="margin-bottom: 0;">
                                <div id="itemAutocompleteSuggestions" class="autocomplete-suggestions" style="display: none;"></div>
                            </div>
                            <input type="hidden" id="lootItemEntry">
                            <input type="hidden" id="lootItemReference" value="0">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                                <div>
                                    <label for="lootMinCount">Min Stack Quantity</label>
                                    <input type="number" id="lootMinCount" value="1" min="1">
                                </div>
                                <div>
                                    <label for="lootMaxCount">Max Stack Quantity</label>
                                    <input type="number" id="lootMaxCount" value="1" min="1">
                                </div>
                            </div>

                            <label for="lootChance" style="margin-top: 1rem;">Drop Chance Percentage: <span id="lootChanceVal" style="color: var(--accent-primary); font-weight: 600;">100</span>%</label>
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                <input type="range" id="lootChance" min="0" max="100" step="0.0001" value="100" style="flex: 1; margin-bottom: 0;" oninput="document.getElementById('lootChanceVal').textContent = this.value; document.getElementById('lootChanceInput').value = this.value">
                                <input type="number" id="lootChanceInput" min="0" max="100" step="0.0001" value="100" style="width: 110px; padding: 0.35rem 0.5rem; background: rgba(0,0,0,0.2); border: 1px solid var(--border-glass); border-radius: 6px; color: #fff; margin-bottom: 0;" oninput="document.getElementById('lootChanceVal').textContent = this.value; document.getElementById('lootChance').value = this.value">
                            </div>
                            
                            <button type="submit" class="btn btn-success" style="margin-top: 1.5rem;">Save Loot Entry & Reload Tables</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: CHARACTER TOOLS -->
        <div class="tab-content" id="tab-char-tools">
            <div class="card-split-grid">
                <!-- Character Search and Editor -->
                <div class="card">
                    <div class="card-title">Character Editor & Teleporter</div>
                    <label for="charSearchInput">Search Character Name</label>
                    <div class="search-input-wrapper" style="position: relative; display: flex; align-items: center; margin-bottom: 1rem;">
                        <span class="search-icon" style="position: absolute; left: 12px; color: var(--text-secondary); pointer-events: none; z-index: 5;">🔍</span>
                        <input type="text" id="charSearchInput" class="search-input" placeholder="Type character name..." autocomplete="off" oninput="filterAdminCharSearch(this.value)" style="padding-left: 36px; margin-bottom: 0; width: 100%;">
                        <button class="clear-search-btn" id="clearAdminCharSearchBtn" onclick="clearAdminCharSearch()" style="position: absolute; right: 12px; background: transparent; border: none; color: var(--text-secondary); cursor: pointer; display: none; font-size: 1.1rem; line-height: 1; z-index: 5;">✕</button>
                        <div id="charSearchDropdown" class="autocomplete-dropdown" style="display: none; position: absolute; top: 100%; left: 0; width: 100%; max-height: 250px; overflow-y: auto; background: #121826; border: 1px solid var(--border-glass); border-radius: 8px; z-index: 1000; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); margin-top: 5px; padding: 0.25rem;"></div>
                    </div>
                    <div class="admin-table-container" style="max-height: 250px; margin-bottom: 1.5rem;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Level</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="charsResultBody">
                                <tr>
                                    <td colspan="4" style="color: var(--text-secondary); text-align: center; padding: 2rem;">Search for characters in the database.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="characterEditorArea" style="opacity: 0.5; pointer-events: none; transition: all 0.3s;">
                        <h3 style="font-size: 1.1rem; font-weight: 500; margin-bottom: 1rem; color: var(--accent-primary);">
                            Editing: <span id="editCharName">--</span>
                        </h3>
                        <div class="detail-row"><span class="detail-label">Online:</span><span class="detail-val" id="editCharOnline">--</span></div>
                        <div class="detail-row"><span class="detail-label">Gold Balance:</span><span class="detail-val" id="editCharGold">--</span></div>
                        
                        <div class="inventory-section" style="margin-top: 1.5rem; margin-bottom: 1.5rem;">
                            <h4 style="font-size: 1rem; font-weight: 500; margin-bottom: 0.5rem; color: var(--accent-primary);">Inventory / Equipped Gear</h4>
                            <div id="charInventoryContainer" style="max-height: 200px; overflow-y: auto; background: rgba(0, 0, 0, 0.25); padding: 0.75rem; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.05); font-size: 0.85rem;">
                                Select a character to load inventory...
                            </div>
                        </div>
                        
                        <div class="personality-section" style="margin-top: 1.5rem; margin-bottom: 1.5rem;">
                            <h4 style="font-size: 1rem; font-weight: 500; margin-bottom: 0.5rem; color: var(--accent-primary);">Ollama AI Chat Personality</h4>
                            <label for="charBotOllama">Select Personality</label>
                            <select id="charBotOllama" onchange="toggleCharOllamaPrompt(this)" style="width: 100%; margin-bottom: 1rem;">
                                <option value="">-- No AI Chat Personality --</option>
                                <option value="friendly">Friendly</option>
                                <option value="grumpy">Grumpy</option>
                                <option value="sarcastic">Sarcastic</option>
                                <option value="custom">Custom Personality Prompt...</option>
                            </select>
                            
                            <div id="charCustomPromptArea" style="display: none; margin-bottom: 1rem;">
                                <label for="charCustomOllamaPrompt">Custom Personality System Prompt</label>
                                <textarea id="charCustomOllamaPrompt" rows="4" placeholder="Describe how the bot should talk and behave..." style="width: 100%; box-sizing: border-box; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 0.5rem; border-radius: 6px; font-family: inherit; font-size: 0.9rem; resize: vertical;"></textarea>
                            </div>
                            <button onclick="saveCharacterPersonality()" class="btn btn-success" style="width: 100%;">Save AI Personality</button>
                            <div id="charPersonalityOutput" style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--status-success);"></div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem;">
                            <div>
                                <label for="editSetLevel">Set Level (1-80)</label>
                                <input type="number" id="editSetLevel" placeholder="Level...">
                                <button onclick="modifyCharacter('level')" class="btn" style="padding: 0.5rem;">Set</button>
                            </div>
                            <div>
                                <label for="editSetGold">Add Gold</label>
                                <input type="number" id="editSetGold" placeholder="Gold amount...">
                                <button onclick="modifyCharacter('gold')" class="btn" style="padding: 0.5rem;">Add</button>
                            </div>
                            <div>
                                <label for="editTeleportCity">Teleport to City</label>
                                <select id="editTeleportCity">
                                    <option value="stormwind">Stormwind</option>
                                    <option value="ironforge">Ironforge</option>
                                    <option value="darnassus">Darnassus</option>
                                    <option value="orgrimmar">Orgrimmar</option>
                                    <option value="undercity">Undercity</option>
                                    <option value="thunderbluff">Thunder Bluff</option>
                                    <option value="shattrath">Shattrath</option>
                                    <option value="dalaran">Dalaran</option>
                                </select>
                                <button onclick="modifyCharacter('teleport')" class="btn" style="padding: 0.5rem;">Teleport</button>
                            </div>
                            <div>
                                <label for="editGmLevel">GM Access Level</label>
                                <select id="editGmLevel">
                                    <option value="0">0 - Player</option>
                                    <option value="1">1 - Moderator</option>
                                    <option value="2">2 - Gamemaster</option>
                                    <option value="3">3 - Admin</option>
                                    <option value="4">4 - Console</option>
                                </select>
                                <button onclick="modifyCharacter('gmlevel')" class="btn" style="padding: 0.5rem;">Set GM</button>
                            </div>
                        </div>
                        <button onclick="modifyCharacter('unstuck')" class="btn btn-danger" style="margin-top: 1rem; width: 100%;">Instant Unstuck / Dalaran Revive</button>

                        <div class="gm-mail-section" style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.05);">
                            <h4 style="font-size: 1rem; font-weight: 500; margin-bottom: 0.75rem; color: var(--accent-primary);">In-game Mail Packages</h4>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                <button onclick="sendMailPackage('bags')" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.5rem;">🎒 Send 4x Bags</button>
                                <button onclick="sendMailPackage('mount')" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.5rem;">🏇 Send Mount & Riding</button>
                                <button onclick="sendMailPackage('gold_1')" class="btn btn-success" style="font-size: 0.8rem; padding: 0.5rem;">🪙 Send 1 Gold</button>
                                <button onclick="sendMailPackage('gold_10')" class="btn btn-success" style="font-size: 0.8rem; padding: 0.5rem;">🪙 Send 10 Gold</button>
                                <button onclick="sendMailPackage('gold_100')" class="btn btn-success" style="font-size: 0.8rem; padding: 0.5rem;">💰 Send 100 Gold</button>
                                <button onclick="sendMailPackage('gold_1000')" class="btn btn-success" style="font-size: 0.8rem; padding: 0.5rem;">👑 Send 1000 Gold</button>
                            </div>
                        </div>

                        <div class="gm-unlocks-section" style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.05);">
                            <h4 style="font-size: 1rem; font-weight: 500; margin-bottom: 0.75rem; color: var(--accent-primary);">GM Cheat Unlocks</h4>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                <button onclick="teachGmUnlock('taxi')" class="btn" style="font-size: 0.8rem; padding: 0.5rem; background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.4); color: #818cf8;">🗺️ Unlock Flight Paths</button>
                                <button onclick="teachGmUnlock('maps')" class="btn" style="font-size: 0.8rem; padding: 0.5rem; background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.4); color: #818cf8;">👁️ Reveal World Map</button>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Custom Mail Service Card -->
                <div class="card" style="margin-top: 1.5rem;">
                    <div class="card-title">Custom Mail & Item Spawner Service</div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">
                        Compose custom messages, send specific gold amounts, and attach up to 12 items to any character.
                    </p>
                    <form onsubmit="sendCustomMail(event)">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
                            <div>
                                <label for="mailRecipient">Recipient Character Name</label>
                                <input type="text" id="mailRecipient" placeholder="Character name..." required>
                            </div>
                            <div>
                                <label for="mailGold">Send Gold Amount</label>
                                <input type="number" step="0.0001" id="mailGold" value="0" min="0">
                            </div>
                        </div>

                        <div style="margin-bottom: 1.25rem;">
                            <label for="mailSubject">Subject Line</label>
                            <input type="text" id="mailSubject" placeholder="Enter subject..." value="GM Package" required>
                        </div>

                        <div style="margin-bottom: 1.25rem;">
                            <label for="mailBody">Mail Body Text</label>
                            <textarea id="mailBody" rows="4" placeholder="Type message body here..." style="width: 100%; box-sizing: border-box; background: rgba(0,0,0,0.25); border: 1px solid var(--border-glass); border-radius: 8px; color: #fff; padding: 0.75rem; font-family: inherit; font-size: 0.9rem; resize: vertical;">Enjoy this delivery!</textarea>
                        </div>

                        <!-- Item Autocomplete Attachment Builder -->
                        <div style="margin-bottom: 1.5rem; background: rgba(255,255,255,0.02); padding: 1.25rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                            <h4 style="font-size: 1rem; font-weight: 500; margin-bottom: 0.75rem; color: var(--accent-primary);">Attach Database Items (Max 12)</h4>
                            <div style="display: flex; gap: 0.75rem; align-items: center; position: relative; margin-bottom: 1rem;">
                                <div style="flex: 1; position: relative;">
                                    <input type="text" id="mailItemSearch" placeholder="Type item name or ID to attach..." autocomplete="off" oninput="filterMailItemSearch(this.value)" style="margin-bottom: 0;">
                                    <div id="mailItemSearchDropdown" class="autocomplete-dropdown" style="display: none; position: absolute; bottom: 100%; left: 0; width: 100%; max-height: 200px; overflow-y: auto; background: #121826; border: 1px solid var(--border-glass); border-radius: 8px; z-index: 1000; box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.5); margin-bottom: 5px; padding: 0.25rem;"></div>
                                </div>
                                <input type="number" id="mailItemQty" value="1" min="1" style="width: 80px; margin-bottom: 0; text-align: center;" placeholder="Qty">
                                <button type="button" onclick="addMailAttachment()" class="btn btn-primary" style="margin-top: 0; padding: 0.65rem 1rem; width: auto;">Attach</button>
                            </div>
                            
                            <!-- Visual attachment slots -->
                            <div id="mailAttachmentsList" style="display: flex; flex-wrap: wrap; gap: 0.5rem; min-height: 40px; padding: 0.5rem; background: rgba(0,0,0,0.15); border-radius: 6px; border: 1px dashed rgba(255,255,255,0.08); align-items: center;">
                                <span style="color: var(--text-secondary); font-size: 0.85rem; font-style: italic;">No items attached yet.</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success" style="width: auto;">🚀 Send Custom Mail Delivery</button>
                    </form>
                </div>

                <!-- Account Creator Card -->
                <div class="card">
                    <div class="card-title">Create Game Account</div>
                    <form onsubmit="createGameAccountForm(event)">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label for="accUsername">Username</label>
                                <input type="text" id="accUsername" class="search-input" required placeholder="Account name..." style="margin-bottom: 0;">
                            </div>
                            <div>
                                <label for="accPassword">Password</label>
                                <input type="password" id="accPassword" required placeholder="Password...">
                            </div>
                            <div>
                                <label for="accGmLevel">Access Level</label>
                                <select id="accGmLevel" style="width: 100%;">
                                    <option value="0">0 - Player (Default)</option>
                                    <option value="1">1 - Moderator</option>
                                    <option value="2">2 - Gamemaster</option>
                                    <option value="3">3 - Admin</option>
                                    <option value="4">4 - Console</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success" style="margin-top: 1.5rem;">Create Account</button>
                    </form>
                    <div id="accountCreationOutput" style="margin-top: 1rem; border-radius: 8px; padding: 0.75rem; font-size: 0.9rem;"></div>
                </div>
            </div>
        </div>

        <!-- TAB 4: LIVE AUCTION HOUSE -->
        <div class="tab-content" id="tab-auction-house">
            <div class="card">
                <div class="card-title" style="display: flex; justify-content: space-between; align-items: center;">
                    Live Auction House Viewer
                    <button onclick="loadAuctions()" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem; background-color: var(--accent-primary); width: auto; margin-top: 0;">🔄 Refresh Auctions</button>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">
                    List all active listings on the server's Auction House by players and random bots.
                </p>
                <div class="admin-table-container">
                    <table class="admin-table" id="auctionsTable">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Qty</th>
                                <th>Seller</th>
                                <th>Faction/House</th>
                                <th>Current Bid</th>
                                <th>Buyout Price</th>
                                <th>Expires In</th>
                            </tr>
                        </thead>
                        <tbody id="auctionsTableBody">
                            <tr>
                                <td colspan="7" style="color: var(--text-secondary); text-align: center; padding: 2rem;">Click "Refresh Auctions" to load active listings.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 5: SOAP REMOTE ACCESS CONSOLE -->
        <div class="tab-content" id="tab-console-tab">
            <div class="card">
                <div class="card-title">Live Server Console Link</div>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">
                    Direct secure remote connection to the running game server daemon. Input console commands (e.g. <code>.server status</code>).
                </p>
                <div class="console-area" id="terminalOutput">
                    Welcome to the AzerothCore Remote Console Link.<br>
                    Type a command in the input box below and hit enter.
                </div>
                <form onsubmit="submitConsoleCommand(event)">
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" id="terminalInput" class="search-input" placeholder="Enter console command (like '.server status' or '.reload config')..." style="margin-bottom: 0;" required>
                        <button type="submit" class="btn btn-primary" style="width: auto; margin-bottom: 0;">Execute</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TAB: SYSTEM & CHAT LOGS -->
        <div id="tab-system-logs" class="tab-content">
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-title">Filter Logs</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.5rem; align-items: flex-end;">
                    <div>
                        <label for="logSearchName">Character Name</label>
                        <input type="text" id="logSearchName" class="search-input" placeholder="e.g. Testmee" style="margin-bottom: 0;">
                    </div>
                    <div>
                        <label for="logSearchKeyword">Message Keyword</label>
                        <input type="text" id="logSearchKeyword" class="search-input" placeholder="e.g. hello" style="margin-bottom: 0;">
                    </div>
                    <div>
                        <label for="logSearchType">Log Category</label>
                        <select id="logSearchType" style="margin-bottom: 0;">
                            <option value="ALL">-- All Logs --</option>
                            <option value="CHAT_ALL">-- All In-Game Chat --</option>
                            <option value="SAY">Say Chat</option>
                            <option value="YELL">Yell Chat</option>
                            <option value="WHISPER">Whispers</option>
                            <option value="PARTY">Party Chat</option>
                            <option value="GUILD">Guild Chat</option>
                            <option value="EVENTS_ALL">-- All Autobiography Events --</option>
                            <option value="LEVEL_UP">Level Ups</option>
                            <option value="QUEST_COMPLETE">Quest Completions</option>
                            <option value="BOSS_KILL">Boss/Elite Kills</option>
                            <option value="DEATH">Character Deaths</option>
                            <option value="PVP_KILL">PvP Defeats</option>
                            <option value="LOGIN">Sessions (Logins/Logouts)</option>
                            <option value="AUCTION_POST">Posted Auctions</option>
                            <option value="AUCTION_SOLD">Sold Auctions</option>
                            <option value="AUCTION_BUY">Bought Auctions</option>
                        </select>
                    </div>
                    <div>
                        <button class="btn btn-primary" onclick="searchLogs()" style="height: 42px; margin-bottom: 0;">Search Logs</button>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-title" id="logsResultsTitle">Latest Global Logs</div>
                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 150px;">Timestamp</th>
                                <th style="width: 120px;">Character</th>
                                <th style="width: 120px;">Log Type</th>
                                <th>Details / Message</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <tr>
                                <td colspan="4" style="color: var(--text-secondary); text-align: center; padding: 2rem;">Click Search to load logs...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: RARE SPAWNER TOOL -->
        <!-- TAB: PLAYERBOTS MANAGER -->
        <div class="tab-content" id="tab-playerbots">
            <div class="card">
                <div class="card-title">Live Playerbots Session Controller</div>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">
                    Monitor simulated AI bots currently playing on the server, scale their level range, or summon/dismiss group members.
                </p>

                <!-- Bot Command Actions -->
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem; background: rgba(255,255,255,0.02); padding: 1.25rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                    <button onclick="executeBotCommand('summon')" class="btn btn-warning" style="width: auto; margin-top: 0; padding: 0.75rem 1.25rem;">👥 Summon Active Bots</button>
                    <button onclick="executeBotCommand('dismiss')" class="btn btn-danger" style="width: auto; margin-top: 0; padding: 0.75rem 1.25rem;">🛑 Dismiss All Bots</button>
                    <button onclick="executeBotCommand('gear')" class="btn btn-success" style="width: auto; margin-top: 0; padding: 0.75rem 1.25rem;">⚙️ Re-Gear Online Bots</button>
                    
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-left: auto;">
                        <select id="botSpawnClass" style="margin-bottom: 0; padding: 0.5rem; border-radius: 6px;">
                            <option value="warrior">Warrior</option>
                            <option value="paladin">Paladin</option>
                            <option value="rogue">Rogue</option>
                            <option value="mage">Mage</option>
                            <option value="priest">Priest</option>
                            <option value="warlock">Warlock</option>
                            <option value="druid">Druid</option>
                            <option value="shaman">Shaman</option>
                            <option value="hunter">Hunter</option>
                        </select>
                        <button onclick="executeBotCommand('add', document.getElementById('botSpawnClass').value)" class="btn btn-primary" style="width: auto; margin-top: 0; padding: 0.75rem 1.25rem;">➕ Spawn Class Bot</button>
                    </div>
                </div>

                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>GUID</th>
                                <th>Name</th>
                                <th>Race / Class</th>
                                <th>Level</th>
                                <th>Location (Zone)</th>
                                <th>Guild</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="playerbotsTableBody">
                            <tr>
                                <td colspan="7" style="color: var(--text-secondary); text-align: center; padding: 2rem;">Loading online bots session data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: OLLAMA LLM TUNER -->
        <div class="tab-content" id="tab-llm-tuner">
            <div class="card">
                <div class="card-title">Ollama LLM Spatial Chat Configurator</div>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">
                    Configure connection hosts, LLM models, parameters, and global system instruction prompts for spatial AI bot chat interactions.
                </p>
                <form onsubmit="saveLLMConfig(event)" style="max-width: 650px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div>
                            <label for="llmHost">Ollama Host Connection</label>
                            <input type="text" id="llmHost" placeholder="http://127.0.0.1" required>
                        </div>
                        <div>
                            <label for="llmPort">Ollama Port</label>
                            <input type="number" id="llmPort" placeholder="11434" required>
                        </div>
                        <div>
                            <label for="llmModel">Active LLM Model</label>
                            <input type="text" id="llmModel" placeholder="llama3" required>
                        </div>
                        <div>
                            <label for="llmTemp">Chat Temperature (0.0 - 1.0)</label>
                            <input type="number" step="0.1" id="llmTemp" min="0" max="1" placeholder="0.7" required>
                        </div>
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <label for="llmPrompt">Default Spatial Chat Prompt Instructions</label>
                        <textarea id="llmPrompt" rows="8" placeholder="Enter default instructions guiding LLM responses in spatial environment chat..." style="width: 100%; box-sizing: border-box; background: rgba(0,0,0,0.25); border: 1px solid var(--border-glass); border-radius: 8px; color: #fff; padding: 0.75rem; font-family: inherit; font-size: 0.9rem; resize: vertical;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success" style="width: auto;">Save & Apply Config</button>
                </form>
            </div>
        </div>

        <!-- TAB: ITEM CREATOR & SPAWNER -->
        <div class="tab-content" id="tab-item-creator">
            <div class="card">
                <div class="card-title">Visual Custom Item Creator & Spawner</div>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">
                    Design legendary, epic, or custom weapons and armor, save them into the game database, and automatically mail them to characters.
                </p>
                <form onsubmit="createCustomItem(event)">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div>
                            <label for="itmName">Weapon / Armor Name</label>
                            <input type="text" id="itmName" placeholder="e.g. Antigravity Blade" required>
                        </div>
                        <div>
                            <label for="itmQuality">Quality / Rarity</label>
                            <select id="itmQuality">
                                <option value="2" style="color: #1eff00;">Green (Uncommon)</option>
                                <option value="3" style="color: #0070dd;">Blue (Rare)</option>
                                <option value="4" style="color: #a335ee;">Purple (Epic)</option>
                                <option value="5" style="color: #ff8000;" selected>Orange (Legendary)</option>
                            </select>
                        </div>
                        <div>
                            <label for="itmSlot">Equipment Slot</label>
                            <select id="itmSlot" onchange="toggleItemTypeFields(this.value)">
                                <option value="13">One-Handed Weapon</option>
                                <option value="17">Two-Handed Weapon</option>
                                <option value="14">Shield</option>
                                <option value="5">Chest Armor</option>
                                <option value="1">Head / Helm</option>
                                <option value="3">Shoulders</option>
                            </select>
                        </div>
                        <div>
                            <label for="itmSpeed">Weapon Attack Speed (Seconds)</label>
                            <input type="number" step="0.1" id="itmSpeed" value="2.0">
                        </div>
                        <div>
                            <label for="itmMinDmg">Minimum Damage</label>
                            <input type="number" id="itmMinDmg" value="150">
                        </div>
                        <div>
                            <label for="itmMaxDmg">Maximum Damage</label>
                            <input type="number" id="itmMaxDmg" value="250">
                        </div>
                        <div>
                            <label for="itmModelSearch">Copy Look from Database Item</label>
                            <div style="position: relative;">
                                <input type="text" id="itmModelSearch" placeholder="Type item to copy visual..." autocomplete="off" oninput="filterItemModelSearch(this.value)">
                                <div id="itmModelSearchDropdown" class="autocomplete-dropdown" style="display: none; position: absolute; top: 100%; left: 0; width: 100%; max-height: 200px; overflow-y: auto; background: #121826; border: 1px solid var(--border-glass); border-radius: 8px; z-index: 1000; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); margin-top: 5px; padding: 0.25rem;"></div>
                            </div>
                        </div>
                        <div>
                            <label for="itmDisplayId">Display ID (Model ID)</label>
                            <input type="number" id="itmDisplayId" value="17291" placeholder="Thunderfury: 17291" required>
                        </div>
                    </div>

                    <h4 style="font-size: 1.05rem; font-weight: 500; margin-bottom: 1rem; color: var(--accent-primary); border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.5rem;">Item Custom Stats</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                        <div>
                            <label for="itmStamina">Stamina</label>
                            <input type="number" id="itmStamina" value="50">
                        </div>
                        <div>
                            <label for="itmStrength">Strength</label>
                            <input type="number" id="itmStrength" value="50">
                        </div>
                        <div>
                            <label for="itmAgility">Agility</label>
                            <input type="number" id="itmAgility" value="50">
                        </div>
                        <div>
                            <label for="itmIntellect">Intellect</label>
                            <input type="number" id="itmIntellect" value="50">
                        </div>
                    </div>

                    <div style="background: rgba(255,255,255,0.02); padding: 1.25rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); max-width: 450px; margin-bottom: 1.5rem;">
                        <label for="itmCharName">Mail Directly to Character (Name)</label>
                        <input type="text" id="itmCharName" placeholder="Enter character name to receive item..." style="margin-bottom: 0;">
                    </div>

                    <button type="submit" class="btn btn-success" style="width: auto;">Forging & Spawn Custom Item ⚡</button>
                </form>
            </div>
        </div>

        <div id="tab-rare-spawner" class="tab-content">
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-title">Spawn Rare Creature</div>
                <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                    Select an online character and choose a rare monster or boss to summon at their location instantly.
                </p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div>
                        <label for="rarePlayerSelect">Online Character</label>
                        <?php
                        $onlineChars = [];
                        try {
                            $charDsn = "mysql:host=" . DB_HOST . ";dbname=acore_characters;charset=utf8mb4";
                            $charPdoTemp = new PDO($charDsn, DB_USER, DB_PASS, [
                                PDO::ATTR_TIMEOUT => 3,
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                            ]);
                            // Filter out bot accounts and sort by online status
                            $stmtOnline = $charPdoTemp->query("
                                SELECT c.name, c.online
                                FROM acore_characters.characters c
                                JOIN acore_auth.account a ON c.account = a.id
                                WHERE a.username NOT LIKE 'RNDBOT%' AND a.username NOT LIKE 'BOT_%'
                                ORDER BY c.online DESC, c.name ASC
                            ");
                            $onlineChars = $stmtOnline->fetchAll(PDO::FETCH_ASSOC);
                        } catch (Exception $e) {}
                        ?>
                        <select id="rarePlayerSelect">
                            <option value="">-- Choose Character --</option>
                            <?php foreach ($onlineChars as $c): ?>
                                <option value="<?php echo htmlspecialchars($c['name']); ?>">
                                    <?php echo htmlspecialchars($c['name']) . ($c['online'] ? ' (Online)' : ' (Offline)'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="position: relative;">
                        <label for="rareMonsterSearch">Search Monster / Boss to Spawn</label>
                        <input type="text" id="rareMonsterSearch" class="search-input" placeholder="Type name (e.g. Time-Lost) or entry ID..." oninput="searchRares()" autocomplete="off" style="width: 100%; margin-bottom: 0;">
                        <input type="hidden" id="rareMonsterSelect" value="">
                        <div id="rareSuggestions" class="autocomplete-suggestions" style="position: absolute; left: 0; right: 0; background: #121826; border: 1px solid var(--border-glass); border-radius: 6px; z-index: 1000; max-height: 250px; overflow-y: auto; display: none; box-shadow: 0 4px 12px rgba(0,0,0,0.5);"></div>
                    </div>
                </div>
                
                <button class="btn btn-primary" onclick="spawnRareNearPlayer()">🔮 Summon Rare Near Player</button>
            </div>
        </div>

    </div>

    <!-- JAVASCRIPT CONTROLLERS -->
    <script>
        // Main Tab Switcher
        function switchTab(tabId) {
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));

            const items = document.querySelectorAll('.nav-item');
            items.forEach(item => item.classList.remove('active'));

            document.getElementById('tab-' + tabId).classList.add('active');
            document.getElementById('nav-' + tabId).classList.add('active');

            if (tabId === 'auction-house') {
                loadAuctions();
            } else if (tabId === 'system-logs') {
                searchLogs();
            } else if (tabId === 'playerbots') {
                loadPlayerbots();
            } else if (tabId === 'llm-tuner') {
                loadLLMConfig();
            }
        }

        function searchLogs() {
            const name = document.getElementById('logSearchName').value;
            const keyword = document.getElementById('logSearchKeyword').value;
            const type = document.getElementById('logSearchType').value;
            const body = document.getElementById('logsTableBody');
            
            body.innerHTML = `<tr><td colspan="4" style="color: var(--text-secondary); text-align: center; padding: 2rem;">Searching logs...</td></tr>`;
            
            fetch('index.php?action=search_logs', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `name=${encodeURIComponent(name)}&keyword=${encodeURIComponent(keyword)}&type=${type}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.logs && data.logs.length > 0) {
                    body.innerHTML = data.logs.map(log => {
                        let badgeColor = 'rgba(255, 255, 255, 0.08)';
                        let textColor = 'var(--text-secondary)';
                        
                        if (log.type === 'SAY' || log.type === 'YELL') {
                            badgeColor = 'rgba(16, 185, 129, 0.08)';
                            textColor = 'var(--status-success)';
                        } else if (log.type === 'WHISPER') {
                            badgeColor = 'rgba(139, 92, 246, 0.08)';
                            textColor = 'var(--accent-hover)';
                        } else if (log.type === 'PARTY') {
                            badgeColor = 'rgba(59, 130, 246, 0.08)';
                            textColor = '#60a5fa';
                        } else if (log.type === 'GUILD') {
                            badgeColor = 'rgba(245, 158, 11, 0.08)';
                            textColor = '#fbbf24';
                        } else if (log.type === 'LEVEL_UP') {
                            badgeColor = 'rgba(16, 185, 129, 0.15)';
                            textColor = 'var(--status-success)';
                        } else if (log.type === 'BOSS_KILL') {
                            badgeColor = 'rgba(239, 68, 68, 0.15)';
                            textColor = 'var(--status-danger)';
                        } else if (log.type === 'DEATH') {
                            badgeColor = 'rgba(239, 68, 68, 0.08)';
                            textColor = 'var(--status-danger)';
                        } else if (log.type === 'AUCTION_POST') {
                            badgeColor = 'rgba(245, 158, 11, 0.1)';
                            textColor = '#f59e0b';
                        } else if (log.type === 'AUCTION_SOLD') {
                            badgeColor = 'rgba(16, 185, 129, 0.12)';
                            textColor = 'var(--status-success)';
                        } else if (log.type === 'AUCTION_BUY') {
                            badgeColor = 'rgba(59, 130, 246, 0.12)';
                            textColor = '#3b82f6';
                        }
                        
                        return `
                            <tr>
                                <td style="color: var(--text-secondary); font-family: monospace;">${log.timestamp}</td>
                                <td><strong>${log.name}</strong></td>
                                <td><span style="display: inline-block; padding: 0.2rem 0.5rem; border-radius: 4px; background: ${badgeColor}; color: ${textColor}; font-size: 0.75rem; font-weight: 600;">${log.type}</span></td>
                                <td style="color: var(--text-primary);">${log.details}</td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    body.innerHTML = `<tr><td colspan="4" style="color: var(--text-secondary); text-align: center; padding: 2rem;">No logs found matching criteria.</td></tr>`;
                }
            })
            .catch(err => {
                body.innerHTML = `<tr><td colspan="4" style="color: var(--status-danger); text-align: center; padding: 2rem;">Failed to fetch logs: ${err}</td></tr>`;
            });
        }

        let rareSearchTimeout = null;
        function searchRares() {
            const query = document.getElementById('rareMonsterSearch').value.trim();
            const suggestions = document.getElementById('rareSuggestions');
            const hiddenSelect = document.getElementById('rareMonsterSelect');
            
            hiddenSelect.value = "";
            
            if (query.length < 2) {
                suggestions.style.display = 'none';
                return;
            }
            
            clearTimeout(rareSearchTimeout);
            rareSearchTimeout = setTimeout(() => {
                fetch('index.php?action=search_creatures', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `query=${encodeURIComponent(query)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.creatures.length > 0) {
                        suggestions.innerHTML = data.creatures.map(c => {
                            const subname = c.subname ? ` <span style="font-size: 0.8rem; opacity: 0.7;">(${c.subname})</span>` : '';
                            return `
                                <div onclick="selectRare(${c.entry}, '${c.name.replace(/'/g, "\\'")}')" style="padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff;" onmouseover="this.style.background='var(--primary-glow)'" onmouseout="this.style.background='transparent'">
                                    <strong>${c.name}</strong>${subname} - Lvl ${c.minlevel}-${c.maxlevel} (Entry: ${c.entry})
                                </div>
                            `;
                        }).join('');
                        suggestions.style.display = 'block';
                    } else {
                        suggestions.innerHTML = `<div style="padding: 0.75rem 1rem; color: var(--text-secondary);">No creatures found.</div>`;
                        suggestions.style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error("Failed to search rares:", err);
                });
            }, 300);
        }

        function selectRare(entry, name) {
            document.getElementById('rareMonsterSelect').value = entry;
            document.getElementById('rareMonsterSearch').value = `${name} (Entry: ${entry})`;
            document.getElementById('rareSuggestions').style.display = 'none';
        }
        
        document.addEventListener('click', function(e) {
            const suggestions = document.getElementById('rareSuggestions');
            const searchInput = document.getElementById('rareMonsterSearch');
            if (suggestions && e.target !== searchInput && e.target !== suggestions) {
                suggestions.style.display = 'none';
            }
        });

        function toggleCharOllamaPrompt(select) {
            const area = document.getElementById('charCustomPromptArea');
            if (select.value === 'custom') {
                area.style.display = 'block';
            } else {
                area.style.display = 'none';
            }
        }

        function saveCharacterPersonality() {
            if (!selectedCharacter) return;
            const selectVal = document.getElementById('charBotOllama').value;
            const promptVal = document.getElementById('charCustomOllamaPrompt').value;
            const outputDiv = document.getElementById('charPersonalityOutput');
            outputDiv.style.color = '#fff';
            outputDiv.textContent = 'Saving personality...';

            fetch('index.php?action=save_character_personality', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `guid=${selectedCharacter.guid}&name=${encodeURIComponent(selectedCharacter.name)}&personality=${selectVal}&prompt=${encodeURIComponent(promptVal)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    outputDiv.style.color = 'var(--status-success)';
                    outputDiv.textContent = data.output;
                } else {
                    outputDiv.style.color = 'var(--status-danger)';
                    outputDiv.textContent = 'Error: ' + data.output;
                }
            })
            .catch(err => {
                outputDiv.style.color = 'var(--status-danger)';
                outputDiv.textContent = 'Error: ' + err;
            });
        }

        function spawnRareNearPlayer() {
            const player = document.getElementById('rarePlayerSelect').value;
            const entry = document.getElementById('rareMonsterSelect').value;
            
            if (!player) {
                alert('Please select an online character first.');
                return;
            }
            if (!entry) {
                alert('Please select a rare monster template to spawn.');
                return;
            }
            
            fetch('index.php?action=spawn_rare_near_player', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `player_name=${encodeURIComponent(player)}&creature_entry=${entry}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Portal opened! The rare has been queued to spawn near ' + player + ' in 2 seconds.');
                } else {
                    alert('Failed to summon rare: ' + data.output);
                }
            })
            .catch(err => alert('Request failed: ' + err));
        }

        // Close autocomplete dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            const charDropdown = document.getElementById('charSearchDropdown');
            const charInput = document.getElementById('charSearchInput');
            if (charDropdown && e.target !== charDropdown && e.target !== charInput) {
                charDropdown.style.display = 'none';
            }

            const creatureDropdown = document.getElementById('creatureSearchDropdown');
            const creatureInput = document.getElementById('creatureSearch');
            if (creatureDropdown && e.target !== creatureDropdown && e.target !== creatureInput) {
                creatureDropdown.style.display = 'none';
            }
        });

        // Live stats fetcher
        function fetchSystemStats() {
            fetch('index.php?action=get_system_stats')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('stat-cpu').textContent = data.cpu + '%';
                    document.getElementById('stat-cpu-load').textContent = 'Load: ' + data.cpu_load;
                    
                    document.getElementById('stat-temp').textContent = data.cpu_temp;
                    const tempNum = parseFloat(data.cpu_temp.replace('+', '').replace('°C', ''));
                    const statusEl = document.getElementById('stat-temp-status');
                    if (isNaN(tempNum)) {
                        statusEl.textContent = 'Thermal Status: Unknown';
                        statusEl.style.color = 'var(--text-secondary)';
                    } else if (tempNum >= 90.0) {
                        statusEl.textContent = 'Thermal Status: CRITICAL';
                        statusEl.style.color = '#ef4444';
                    } else if (tempNum >= 80.0) {
                        statusEl.textContent = 'Thermal Status: HIGH WARNING';
                        statusEl.style.color = '#f59e0b';
                    } else {
                        statusEl.textContent = 'Thermal Status: Safe';
                        statusEl.style.color = 'var(--status-success)';
                    }
                    
                    document.getElementById('stat-ram').textContent = data.ram_percent + '%';
                    document.getElementById('stat-ram-GB').textContent = data.ram_used + ' / ' + data.ram_total + ' GB';
                    
                    document.getElementById('stat-disk').textContent = data.disk_percent + '%';
                    document.getElementById('stat-disk-GB').textContent = data.disk_used + ' / ' + data.disk_total + ' GB';
                    
                    document.getElementById('stat-uptime').textContent = data.uptime;
                    
                    // Services status
                    document.getElementById('svc-auth').innerHTML = data.auth_running ? '<span style="color: var(--status-success)">Running 🟢</span>' : '<span style="color: var(--status-danger)">Stopped 🔴</span>';
                    document.getElementById('svc-world').innerHTML = data.world_running ? '<span style="color: var(--status-success)">Running 🟢</span>' : '<span style="color: var(--status-danger)">Stopped 🔴</span>';
                    document.getElementById('svc-soap').innerHTML = data.soap_online ? '<span style="color: var(--status-success)">Listening 🟢</span>' : '<span style="color: var(--status-danger)">Connection Error 🔴</span>';
                }
            })
            .catch(err => console.error("Error fetching stats:", err));
        }

        setInterval(fetchSystemStats, 5000);
        fetchSystemStats();

        // Control handlers
        function triggerRestart() {
            if (!confirm("Are you sure you want to restart the game server? If online, it will perform a safe 30-second countdown in-game and auto-save player progress before rebooting.")) return;
            const statusDiv = document.getElementById('restartStatus');
            statusDiv.style.color = 'var(--status-warning)';
            statusDiv.textContent = "Initiating safe server reboot...";
            
            fetch('index.php?action=restart_server')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    statusDiv.style.color = 'var(--status-success)';
                    statusDiv.innerHTML = "Success: " + data.output.replace(/\n/g, "<br>");
                } else {
                    statusDiv.style.color = 'var(--status-danger)';
                    statusDiv.textContent = "Error: " + data.output;
                }
                fetchSystemStats();
            })
            .catch(err => {
                statusDiv.style.color = 'var(--status-danger)';
                statusDiv.textContent = "Request failed: " + err;
            });
        }

        function triggerShutdown() {
            if (!confirm("Are you sure you want to shut down the server? If online, it will perform a safe 30-second countdown in-game and auto-save player progress before turning off.")) return;
            const statusDiv = document.getElementById('restartStatus');
            statusDiv.style.color = 'var(--status-warning)';
            statusDiv.textContent = "Initiating safe server shutdown...";
            
            fetch('index.php?action=shutdown_server')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    statusDiv.style.color = 'var(--status-danger)';
                    statusDiv.innerHTML = "Success: " + data.output.replace(/\n/g, "<br>");
                } else {
                    statusDiv.style.color = 'var(--status-danger)';
                    statusDiv.textContent = "Failed to shut down: " + data.output;
                }
                fetchSystemStats();
            })
            .catch(err => alert('Request failed: ' + err));
        }

        function triggerStart() {
            const statusDiv = document.getElementById('restartStatus');
            statusDiv.style.color = 'var(--status-warning)';
            statusDiv.textContent = "Starting server services...";
            
            fetch('index.php?action=start_server')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    statusDiv.style.color = 'var(--status-success)';
                    statusDiv.innerHTML = "Success: " + data.output.replace(/\n/g, "<br>");
                } else {
                    statusDiv.style.color = 'var(--status-danger)';
                    statusDiv.textContent = "Failed to start: " + data.output;
                }
                fetchSystemStats();
            })
            .catch(err => alert('Request failed: ' + err));
        }

        function toggleServerEvent(id, currentlyActive) {
            const label = currentlyActive ? "disable" : "enable";
            if (!confirm(`Are you sure you want to ${label} event ID ${id}?`)) return;

            fetch('index.php?action=toggle_event', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `event_id=${id}&active=${currentlyActive}`
            })
            .then(res => res.json())
            .then(data => {
                alert(data.output);
                location.reload();
            })
            .catch(err => alert("Failed to toggle event: " + err));
        }

        // Configuration submit handlers
        function saveFeaturesConfig(e) {
            e.preventDefault();
            const transmog = document.getElementById('featTransmog').value;
            const enchants = document.getElementById('featEnchants').value;
            const autobalance = document.getElementById('featAutobalance').value;
            const sololfg = document.getElementById('featSoloLfg').value;
            const aoeloot = document.getElementById('featAoELoot').value;
            const mythicplus = document.getElementById('featMythicPlus').value;
            const itemupgrade = document.getElementById('featItemUpgrade').value;
            const freeprof = document.getElementById('featFreeProfessions').value;
            const accmount = document.getElementById('featAccountMount').value;
            const accachieve = document.getElementById('featAccountAchievements').value;
            const questparty = document.getElementById('featQuestParty').value;
            const groupquests = document.getElementById('featGroupQuests').value;

            const params = `transmog=${transmog}&enchants=${enchants}&autobalance=${autobalance}&sololfg=${sololfg}&aoeloot=${aoeloot}&mythicplus=${mythicplus}&itemupgrade=${itemupgrade}&freeprof=${freeprof}&accmount=${accmount}&accachieve=${accachieve}&questparty=${questparty}&groupquests=${groupquests}`;

            fetch('index.php?action=set_features_config', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            })
            .then(res => res.json())
            .then(data => alert(data.output))
            .catch(err => alert("Error saving config: " + err));
        }

        function saveChallengeConfig(e) {
            e.preventDefault();
            const enabled = document.getElementById('cmEnabled').value;
            const hardcore = document.getElementById('cmHardcore').value;
            const semihardcore = document.getElementById('cmSemiHardcore').value;
            const selfcrafted = document.getElementById('cmSelfCrafted').value;
            const itemquality = document.getElementById('cmItemQuality').value;
            const slowxp = document.getElementById('cmSlowXp').value;
            const veryslowxp = document.getElementById('cmVerySlowXp').value;
            const questxp = document.getElementById('cmQuestXp').value;
            const ironman = document.getElementById('cmIronMan').value;

            const params = `enabled=${enabled}&hardcore=${hardcore}&semihardcore=${semihardcore}&selfcrafted=${selfcrafted}&itemquality=${itemquality}&slowxp=${slowxp}&veryslowxp=${veryslowxp}&questxp=${questxp}&ironman=${ironman}`;

            fetch('index.php?action=set_challenge_config', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            })
            .then(res => res.json())
            .then(data => alert(data.output))
            .catch(err => alert("Error saving challenge config: " + err));
        }

        function saveBotConfig(e) {
            e.preventDefault();
            const enabled = document.getElementById('botEnabled').value;
            const min = document.getElementById('botMin').value;
            const max = document.getElementById('botMax').value;

            fetch('index.php?action=set_bot_config', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `enabled=${enabled}&min_bots=${min}&max_bots=${max}`
            })
            .then(res => res.json())
            .then(data => alert(data.output))
            .catch(err => alert("Error saving bot config: " + err));
        }

        function saveProgressionLimit(e) {
            e.preventDefault();
            const limit = document.getElementById('progressionLimit').value;
            fetch('index.php?action=set_progression', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `limit=${limit}`
            })
            .then(res => res.json())
            .then(data => alert(data.output))
            .catch(err => alert("Error saving progression lock: " + err));
        }

        // Creature autocomplete search logic (Wiki Style)
        let adminCreatureSearchTimeout = null;
        function filterAdminCreatureSearch(val) {
            const dropdown = document.getElementById('creatureSearchDropdown');
            const clearBtn = document.getElementById('clearAdminCreatureSearchBtn');
            
            if (!val.trim()) {
                dropdown.style.display = 'none';
                clearBtn.style.display = 'none';
                return;
            }
            
            clearBtn.style.display = 'block';
            
            clearTimeout(adminCreatureSearchTimeout);
            adminCreatureSearchTimeout = setTimeout(() => {
                fetch('index.php?action=search_creatures', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `query=${encodeURIComponent(val)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.creatures && data.creatures.length > 0) {
                        dropdown.innerHTML = data.creatures.map(c => {
                            const subname = c.subname ? ` <span style="font-size: 0.8rem; opacity: 0.7;">(${c.subname})</span>` : '';
                            const rankNames = { 0: '', 1: 'Elite', 2: 'Rare Elite', 3: 'Boss', 4: 'Rare' };
                            const rankBadge = c.rank > 0 ? ` <span style="color: #6366f1; border: 1px solid #6366f1; font-size: 0.7rem; background: rgba(99, 102, 241, 0.1); padding: 1px 4px; border-radius: 4px; margin-left: 5px;">${rankNames[c.rank]}</span>` : '';
                            return `
                                <div class="autocomplete-item" onclick="selectAdminCreature(${c.entry}, '${encodeURIComponent(c.name)}', '${encodeURIComponent(c.subname || '')}', ${c.minlevel}, ${c.maxlevel}, ${c.minhealth}, ${c.maxhealth}, ${c.armor}, ${c.damage_multiplier}, ${c.base_min_hp}, ${c.base_max_hp}, ${c.base_min_armor}, ${c.base_max_armor})" style="padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff; display: flex; justify-content: space-between; align-items: center;" onmouseover="this.style.background='var(--primary-glow)'" onmouseout="this.style.background='transparent'">
                                    <div>
                                        <strong style="color: #fff;">${c.name}</strong>${subname}${rankBadge}
                                        <span style="color: var(--text-secondary); font-size: 0.8rem; margin-left: 0.5rem;">Lvl ${c.minlevel}-${c.maxlevel}</span>
                                    </div>
                                    <span style="font-size: 0.8rem; color: var(--text-secondary);">ID: ${c.entry}</span>
                                </div>
                            `;
                        }).join('');
                        dropdown.style.display = 'block';
                    } else {
                        dropdown.innerHTML = `<div style="color: var(--text-secondary); padding: 0.75rem 1rem; font-size: 0.85rem; text-align: center;">No creatures found</div>`;
                        dropdown.style.display = 'block';
                    }
                })
                .catch(err => {
                    dropdown.innerHTML = `<div style="color: var(--status-danger); padding: 0.75rem 1rem; font-size: 0.85rem; text-align: center;">Search error</div>`;
                    dropdown.style.display = 'block';
                });
            }, 150);
        }

        // Global variables to store base class stats for dynamic preview
        let baseMinHp = 1;
        let baseMaxHp = 1;
        let baseMinArmor = 1;
        let baseMaxArmor = 1;

        function selectAdminCreature(entry, name, subname, minlevel, maxlevel, minhealth, maxhealth, armor, damage_multiplier, baseMinHpVal = 1, baseMaxHpVal = 1, baseMinArmorVal = 1, baseMaxArmorVal = 1) {
            baseMinHp = parseFloat(baseMinHpVal) || 1;
            baseMaxHp = parseFloat(baseMaxHpVal) || 1;
            baseMinArmor = parseFloat(baseMinArmorVal) || 1;
            baseMaxArmor = parseFloat(baseMaxArmorVal) || 1;
            
            const decodedName = decodeURIComponent(name);
            document.getElementById('creatureSearch').value = decodedName;
            document.getElementById('creatureSearchDropdown').style.display = 'none';
            selectCreature(entry, decodedName, decodeURIComponent(subname), minlevel, maxlevel, minhealth, maxhealth, armor, damage_multiplier);
        }

        function clearAdminCreatureSearch() {
            document.getElementById('creatureSearch').value = '';
            document.getElementById('creatureSearchDropdown').style.display = 'none';
            document.getElementById('clearAdminCreatureSearchBtn').style.display = 'none';
        }

        // Creature Editor script controllers
        let selectedCreature = null;

        function searchCreatures() {
            const query = document.getElementById('creatureSearch').value;
            const body = document.getElementById('creatureSearchResultBody');
            body.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--text-secondary); padding: 1.5rem;">Searching...</td></tr>';

            fetch('index.php?action=search_creatures', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `query=${encodeURIComponent(query)}`
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.creatures.length === 0) {
                    body.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--status-danger); padding: 1.5rem;">No monsters found.</td></tr>';
                    return;
                }

                let html = '';
                data.creatures.forEach(cr => {
                    html += `
                        <tr>
                            <td>${cr.entry}</td>
                            <td>
                                <a href="https://www.wowhead.com/wotlk/npc=${cr.entry}" target="_blank" style="color: #fff; text-decoration: none; border-bottom: 1px dashed rgba(255,255,255,0.3);" onmouseover="this.style.color='var(--accent-primary)'" onmouseout="this.style.color='#fff'" title="View on Wowhead">
                                    <strong>${cr.name}</strong> 🔗
                                </a>
                                ${cr.subname ? `<span style="color: var(--text-secondary); display:block; font-size:0.8rem;">(${cr.subname})</span>` : ''}
                            </td>
                            <td>Lvl ${cr.minlevel}-${cr.maxlevel}</td>
                            <td style="text-align: right;">
                                <button onclick="baseMinHp=${cr.base_min_hp}; baseMaxHp=${cr.base_max_hp}; baseMinArmor=${cr.base_min_armor}; baseMaxArmor=${cr.base_max_armor}; selectCreature(${cr.entry}, '${encodeURIComponent(cr.name)}', '${encodeURIComponent(cr.subname || '')}', ${cr.minlevel}, ${cr.maxlevel}, ${cr.minhealth}, ${cr.maxhealth}, ${cr.armor}, ${cr.damage_multiplier})" class="btn" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; width: auto; margin-top:0;">Edit</button>
                            </td>
                        </tr>
                    `;
                });
                body.innerHTML = html;
            })
            .catch(err => {
                body.innerHTML = `<tr><td colspan="4" style="text-align: center; color: var(--status-danger); padding: 1.5rem;">Search failed: ${err}</td></tr>`;
            });
        }

        function selectCreature(entry, name, subname, minLvl, maxLvl, minHp, maxHp, armor, damageMult) {
            selectedCreature = { entry, name: decodeURIComponent(name) };
            
            document.getElementById('creatureFormArea').style.opacity = '1';
            document.getElementById('creatureFormArea').style.pointerEvents = 'auto';

            document.getElementById('editCreatureEntry').textContent = entry;
            document.getElementById('editCreatureId').value = entry;
            document.getElementById('editCreatureName').value = decodeURIComponent(name);
            document.getElementById('editCreatureSubname').value = decodeURIComponent(subname);
            document.getElementById('editCreatureMinLvl').value = minLvl;
            document.getElementById('editCreatureMaxLvl').value = maxLvl;
            document.getElementById('editCreatureMinHealth').value = minHp;
            document.getElementById('editCreatureMaxHealth').value = maxHp;
            document.getElementById('editCreatureArmor').value = armor;
            document.getElementById('editCreatureDamageMult').value = damageMult;

            // Populate the search table below with the currently selected creature row + Wowhead link
            const searchBody = document.getElementById('creatureSearchResultBody');
            searchBody.innerHTML = `
                <tr style="background: rgba(99, 102, 241, 0.05); border-left: 3px solid var(--accent-primary);">
                    <td>${entry}</td>
                    <td style="font-weight: 500;">
                        <a href="https://www.wowhead.com/wotlk/npc=${entry}" target="_blank" style="color: var(--accent-primary); text-decoration: none; border-bottom: 1px dashed var(--accent-primary)55;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                            ${decodeURIComponent(name)} 🔗
                        </a>
                        ${subname ? `<span style="font-size:0.8rem; color:var(--text-secondary); display:block; font-weight:normal;">${decodeURIComponent(subname)}</span>` : ''}
                    </td>
                    <td>${minLvl === maxLvl ? minLvl : `${minLvl}-${maxLvl}`}</td>
                    <td style="text-align: right; color: var(--status-success); font-weight: 500; font-size: 0.85rem; padding-right: 1.5rem;">
                        Editing ✏️
                    </td>
                </tr>
            `;

            // Load loot drop table
            loadCreatureLoot(entry);
            updateActualStatsDisplay();
        }

        function saveCreatureDetails(e) {
            e.preventDefault();
            const entry = document.getElementById('editCreatureId').value;
            const name = document.getElementById('editCreatureName').value;
            const subname = document.getElementById('editCreatureSubname').value;
            const minlevel = document.getElementById('editCreatureMinLvl').value;
            const maxlevel = document.getElementById('editCreatureMaxLvl').value;
            const minhealth = document.getElementById('editCreatureMinHealth').value;
            const maxhealth = document.getElementById('editCreatureMaxHealth').value;
            const armor = document.getElementById('editCreatureArmor').value;
            const damage_multiplier = document.getElementById('editCreatureDamageMult').value;

            const params = `entry=${entry}&name=${encodeURIComponent(name)}&subname=${encodeURIComponent(subname)}&minlevel=${minlevel}&maxlevel=${maxlevel}&minhealth=${minhealth}&maxhealth=${maxhealth}&armor=${armor}&damage_multiplier=${damage_multiplier}`;

            fetch('index.php?action=update_creature', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            })
            .then(res => res.json())
            .then(data => {
                alert(data.output);
                searchCreatures();
            })
            .catch(err => alert("Error saving creature details: " + err));
        }

        // Loot Editor script controllers
        // Helper to calculate exact probabilities for grouped items (where Chance=0)
        function calculateLootChances(lootArray) {
            const groupStats = {};
            
            lootArray.forEach(item => {
                const gId = parseInt(item.groupid || 0);
                const chance = parseFloat(item.chance);
                
                if (gId > 0) {
                    if (!groupStats[gId]) {
                        groupStats[gId] = {
                            totalExplicit: 0,
                            zeroChanceCount: 0
                        };
                    }
                    if (chance > 0) {
                        groupStats[gId].totalExplicit += chance;
                    } else {
                        groupStats[gId].zeroChanceCount++;
                    }
                }
            });
            
            return lootArray.map(item => {
                const gId = parseInt(item.groupid || 0);
                const chance = parseFloat(item.chance);
                let calculatedChance = chance;
                let chanceLabel = `${chance}%`;
                let badge = '';

                if (gId > 0) {
                    const stats = groupStats[gId];
                    if (chance === 0) {
                        const remaining = Math.max(0, 100 - stats.totalExplicit);
                        calculatedChance = stats.zeroChanceCount > 0 ? (remaining / stats.zeroChanceCount) : 0;
                        calculatedChance = Math.round(calculatedChance * 10000) / 10000;
                        chanceLabel = `${calculatedChance}% (Equal Share)`;
                        badge = `<span style="font-size:0.7rem; background:rgba(16, 185, 129, 0.15); color:#10b981; border:1px solid #10b98155; padding:1px 4px; border-radius:4px; margin-left:5px;">Group ${gId}</span>`;
                    } else {
                        chanceLabel = `${chance}% (Group Weight)`;
                        badge = `<span style="font-size:0.7rem; background:rgba(99, 102, 241, 0.15); color:#818cf8; border:1px solid #818cf855; padding:1px 4px; border-radius:4px; margin-left:5px;">Group ${gId}</span>`;
                    }
                }
                
                return { ...item, calculatedChance, chanceLabel, badge };
            });
        }

        function loadCreatureLoot(entry) {
            document.getElementById('lootCreatureHeader').textContent = `${selectedCreature.name} (Entry: ${entry})`;
            
            document.getElementById('lootAddArea').style.opacity = '1';
            document.getElementById('lootAddArea').style.pointerEvents = 'auto';

            const body = document.getElementById('creatureLootTableBody');
            body.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--text-secondary); padding: 1.5rem;">Loading drops...</td></tr>';

            fetch(`index.php?action=get_creature_loot&entry=${entry}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.loot.length === 0) {
                    body.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--text-secondary); padding: 1.5rem;">No drops configured for this creature template.</td></tr>';
                    return;
                }

                let html = '';
                const processedLoot = calculateLootChances(data.loot);
                processedLoot.forEach(item => {
                    let displayName = item.item_name || 'Unknown Item';
                    let displayId = `#${item.item_entry}`;
                    let isRef = false;
                    let refId = parseInt(item.reference) || 0;

                    if (parseInt(item.item_entry) === 0 && refId > 0) {
                        isRef = true;
                        let match = item.comment ? item.comment.match(/\(([^)]+)\)/) : null;
                        let refName = match ? match[1] : "Reference Table";
                        displayName = "🔄 " + refName;
                        displayId = "Ref: #" + refId;
                    }

                    const quality = isRef ? -1 : (parseInt(item.item_quality) || 0);
                    let color = '#fff';
                    if (isRef) {
                        color = '#38bdf8'; // Sky-400 for references
                    } else {
                        if (quality === 2) color = '#1eff00';
                        if (quality === 3) color = '#0070dd';
                        if (quality === 4) color = '#a335ee';
                        if (quality === 5) color = '#ff8000';
                    }

                    // Format name with modal link or Wowhead link
                    let displayLabel = displayName;
                    if (isRef) {
                        displayLabel = `<span onclick="openReferenceLootModal(${refId}, '${displayName.replace(/'/g, "\'")}')" style="cursor: pointer; color: ${color}; border-bottom: 1px dashed ${color}aa;" title="Click to view reference table drops">${displayName} 👁️</span>`;
                    } else if (item.item_entry > 0) {
                        displayLabel = `<a href="https://www.wowhead.com/wotlk/item=${item.item_entry}" target="_blank" style="color: ${color}; text-decoration: none; border-bottom: 1px dashed ${color}55;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'" title="View on Wowhead">${displayName} 🔗</a>`;
                    }

                    html += `
                        <tr>
                            <td style="color: ${color}; font-weight: 500;">${displayLabel} <span style="font-size:0.8rem; color:var(--text-secondary)">(${displayId})</span></td>
                            <td>${item.mincount === item.maxcount ? item.mincount : `${item.mincount}-${item.maxcount}`}</td>
                            <td><strong style="color: var(--accent-primary)">${item.chanceLabel}</strong>${item.badge}</td>
                            <td style="text-align: right;">
                                <button onclick="editLootItem(${item.item_entry}, '${encodeURIComponent(displayName)}', ${item.chance}, ${item.mincount}, ${item.maxcount}, ${refId})" class="btn" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; width: auto; margin-right:0.25rem; margin-top:0;">Edit</button>
                                <button onclick="deleteLootItem(${item.item_entry}, ${refId})" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; width: auto; margin-top:0;">Delete</button>
                            </td>
                        </tr>
                    `;
                });
                body.innerHTML = html;
            })
            .catch(err => {
                body.innerHTML = `<tr><td colspan="4" style="text-align: center; color: var(--status-danger); padding: 1.5rem;">Failed loading drops: ${err}</td></tr>`;
            });
        }

        let autocompleteTimeout = null;
        function autocompleteItemSearch(val) {
            clearTimeout(autocompleteTimeout);
            const container = document.getElementById('itemAutocompleteSuggestions');
            if (val.length < 2) {
                container.style.display = 'none';
                return;
            }

            autocompleteTimeout = setTimeout(() => {
                fetch(`index.php?action=search_items&query=${encodeURIComponent(val)}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.success || data.items.length === 0) {
                        container.style.display = 'none';
                        return;
                    }

                    let html = '';
                    data.items.forEach(it => {
                        html += `<div class="autocomplete-suggestion" onclick="selectAutocompleteItem(${it.entry}, '${encodeURIComponent(it.name)}')">${it.name} (#${it.entry})</div>`;
                    });
                    container.innerHTML = html;
                    container.style.display = 'block';
                });
            }, 300);
        }

        function selectAutocompleteItem(entry, name) {
            document.getElementById('lootItemEntry').value = entry;
            document.getElementById('lootItemReference').value = '0';
            document.getElementById('lootItemSearch').value = `${decodeURIComponent(name)} (#${entry})`;
            document.getElementById('itemAutocompleteSuggestions').style.display = 'none';
        }

        function editLootItem(entry, name, chance, min, max, refId = 0) {
            document.getElementById('lootItemEntry').value = entry;
            document.getElementById('lootItemReference').value = refId;
            if (refId > 0) {
                document.getElementById('lootItemSearch').value = `${decodeURIComponent(name)} (Ref: #${refId})`;
            } else {
                document.getElementById('lootItemSearch').value = `${decodeURIComponent(name)} (#${entry})`;
            }
            document.getElementById('lootMinCount').value = min;
            document.getElementById('lootMaxCount').value = max;
            document.getElementById('lootChance').value = chance;
            document.getElementById('lootChanceInput').value = chance;
            document.getElementById('lootChanceVal').textContent = chance;
        }

        function saveLootDrop(e) {
            e.preventDefault();
            const item_entry = document.getElementById('lootItemEntry').value;
            const reference = document.getElementById('lootItemReference').value || '0';
            const mincount = document.getElementById('lootMinCount').value;
            const maxcount = document.getElementById('lootMaxCount').value;
            const chance = document.getElementById('lootChanceInput').value || document.getElementById('lootChance').value;

            if (!selectedCreature || (item_entry === '' && reference === '0')) {
                alert("Please select a monster and an item/reference.");
                return;
            }

            const params = `creature_entry=${selectedCreature.entry}&item_entry=${item_entry}&reference=${reference}&chance=${chance}&mincount=${mincount}&maxcount=${maxcount}`;

            fetch('index.php?action=save_loot_item', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            })
            .then(res => res.json())
            .then(data => {
                alert(data.output);
                loadCreatureLoot(selectedCreature.entry);
                document.getElementById('lootItemEntry').value = '';
                document.getElementById('lootItemSearch').value = '';
            })
            .catch(err => alert("Error saving loot item: " + err));
        }

        function deleteLootItem(itemEntry, refId = 0) {
            if (!confirm("Are you sure you want to delete this item drop from the creature's loot table?")) return;

            const params = `creature_entry=${selectedCreature.entry}&item_entry=${itemEntry}&reference=${refId}`;

            fetch('index.php?action=delete_loot_item', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            })
            .then(res => res.json())
            .then(data => {
                alert(data.output);
                loadCreatureLoot(selectedCreature.entry);
            })
            .catch(err => alert("Error removing loot: " + err));
        }

        document.addEventListener('click', function(e) {
            if (e.target.id !== 'lootItemSearch') {
                document.getElementById('itemAutocompleteSuggestions').style.display = 'none';
            }
        });

        // Character autocomplete search logic (Wiki Style)
        let adminCharSearchTimeout = null;
        function filterAdminCharSearch(val) {
            const dropdown = document.getElementById('charSearchDropdown');
            const clearBtn = document.getElementById('clearAdminCharSearchBtn');
            
            if (!val.trim()) {
                dropdown.style.display = 'none';
                clearBtn.style.display = 'none';
                return;
            }
            
            clearBtn.style.display = 'block';
            
            clearTimeout(adminCharSearchTimeout);
            adminCharSearchTimeout = setTimeout(() => {
                fetch('index.php?action=search_characters', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `query=${encodeURIComponent(val)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.characters && data.characters.length > 0) {
                        dropdown.innerHTML = data.characters.map(c => {
                            const botBadge = c.is_bot ? ' <span style="color: var(--status-warning); font-size: 0.7rem; background: rgba(245, 158, 11, 0.1); padding: 2px 6px; border-radius: 4px; margin-left: 5px;">BOT</span>' : '';
                            return `
                                <div class="autocomplete-item" onclick="selectAdminChar(${c.guid}, '${encodeURIComponent(c.name)}', ${c.level}, ${c.online}, ${c.money})" style="padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff; display: flex; justify-content: space-between; align-items: center;" onmouseover="this.style.background='var(--primary-glow)'" onmouseout="this.style.background='transparent'">
                                    <div>
                                        <strong style="color: #fff;">${c.name}</strong>${botBadge}
                                        <span style="color: var(--text-secondary); font-size: 0.8rem; margin-left: 0.5rem;">Lvl ${c.level}</span>
                                    </div>
                                    <span style="font-size: 0.8rem; color: ${c.online ? 'var(--status-success)' : 'var(--text-secondary)'};">
                                        ${c.online ? 'Online' : 'Offline'}
                                    </span>
                                </div>
                            `;
                        }).join('');
                        dropdown.style.display = 'block';
                    } else {
                        dropdown.innerHTML = `<div style="color: var(--text-secondary); padding: 0.75rem 1rem; font-size: 0.85rem; text-align: center;">No characters found</div>`;
                        dropdown.style.display = 'block';
                    }
                })
                .catch(err => {
                    dropdown.innerHTML = `<div style="color: var(--status-danger); padding: 0.75rem 1rem; font-size: 0.85rem; text-align: center;">Search error</div>`;
                    dropdown.style.display = 'block';
                });
            }, 150);
        }

        function selectAdminChar(guid, name, level, online, money) {
            const decodedName = decodeURIComponent(name);
            document.getElementById('charSearchInput').value = decodedName;
            document.getElementById('charSearchDropdown').style.display = 'none';
            selectCharacter(guid, decodedName, level, online, money);
        }

        function clearAdminCharSearch() {
            document.getElementById('charSearchInput').value = '';
            document.getElementById('charSearchDropdown').style.display = 'none';
            document.getElementById('clearAdminCharSearchBtn').style.display = 'none';
        }

        // Character quick editor script controllers
        let selectedCharacter = null;

        function searchCharacters() {
            const query = document.getElementById('charSearchInput').value;
            const body = document.getElementById('charsResultBody');
            body.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--text-secondary); padding: 1.5rem;">Searching...</td></tr>';

            fetch('index.php?action=search_characters', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `query=${encodeURIComponent(query)}`
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.characters.length === 0) {
                    body.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--status-danger); padding: 1.5rem;">No characters found.</td></tr>';
                    return;
                }

                let html = '';
                data.characters.forEach(ch => {
                    html += `
                        <tr>
                            <td><strong>${ch.name}</strong> ${ch.is_bot ? '<span style="color: var(--status-warning); font-size: 0.75rem;">BOT</span>' : ''}</td>
                            <td>Level ${ch.level}</td>
                            <td>${ch.online ? '<span style="color: var(--status-success)">Online</span>' : '<span style="color: var(--text-secondary)">Offline</span>'}</td>
                            <td style="text-align: right;">
                                <button onclick="selectCharacter(${ch.guid}, '${encodeURIComponent(ch.name)}', ${ch.level}, ${ch.online}, ${ch.money})" class="btn" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; width: auto; margin-top:0;">Select</button>
                            </td>
                        </tr>
                    `;
                });
                body.innerHTML = html;
            })
            .catch(err => {
                body.innerHTML = `<tr><td colspan="4" style="text-align: center; color: var(--status-danger); padding: 1.5rem;">Search failed: ${err}</td></tr>`;
            });
        }

        function selectCharacter(guid, name, level, online, money) {
            selectedCharacter = { guid, name: decodeURIComponent(name), level, online, money };
            document.getElementById('characterEditorArea').style.opacity = '1';
            document.getElementById('characterEditorArea').style.pointerEvents = 'auto';

            document.getElementById('editCharName').textContent = decodeURIComponent(name);
            document.getElementById('editCharOnline').innerHTML = online ? '<span style="color: var(--status-success)">Online 🟢</span>' : '<span style="color: var(--text-secondary)">Offline 🔴</span>';
            
            const gold = Math.floor(money / 10000);
            document.getElementById('editCharGold').textContent = gold + ' Gold';

            // Fetch and display personality
            const ollamaSelect = document.getElementById('charBotOllama');
            const customArea = document.getElementById('charCustomPromptArea');
            const customPromptInput = document.getElementById('charCustomOllamaPrompt');
            document.getElementById('charPersonalityOutput').textContent = '';
            
            fetch('index.php?action=get_character_personality&guid=' + guid)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    let persValue = data.personality;
                    if (persValue.startsWith('CUSTOM_')) {
                        ollamaSelect.value = 'custom';
                        customArea.style.display = 'block';
                        customPromptInput.value = data.prompt;
                    } else {
                        ollamaSelect.value = persValue.toLowerCase();
                        customArea.style.display = 'none';
                        customPromptInput.value = '';
                    }
                } else {
                    ollamaSelect.value = '';
                    customArea.style.display = 'none';
                    customPromptInput.value = '';
                }
            })
            .catch(() => {
                ollamaSelect.value = '';
                customArea.style.display = 'none';
                customPromptInput.value = '';
            });

            // Fetch and display inventory
            const invContainer = document.getElementById('charInventoryContainer');
            invContainer.innerHTML = '<span style="color: var(--text-secondary);">Loading inventory...</span>';
            fetch('index.php?action=get_character_inventory&guid=' + guid)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.items && data.items.length > 0) {
                    let itemsHtml = '<table style="width: 100%; border-collapse: collapse;">';
                    data.items.forEach(it => {
                        let color = '#fff';
                        if (it.quality === 1) color = '#ffffff'; // Common
                        else if (it.quality === 2) color = '#1eff00'; // Uncommon
                        else if (it.quality === 3) color = '#0070dd'; // Rare
                        else if (it.quality === 4) color = '#a335ee'; // Epic
                        else if (it.quality === 5) color = '#ff8000'; // Legendary
                        
                        itemsHtml += `<tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                            <td style="color: ${color}; padding: 0.35rem 0;">${it.name} <span style="color:var(--text-secondary); font-size:0.75rem;">(#${it.entry})</span></td>
                            <td style="text-align: right; color: var(--text-secondary); padding: 0.35rem 0;">x${it.count}</td>
                        </tr>`;
                    });
                    itemsHtml += '</table>';
                    invContainer.innerHTML = itemsHtml;
                } else {
                    invContainer.innerHTML = '<span style="color: var(--text-secondary);">No items found in bags or equipment.</span>';
                }
            })
            .catch(err => {
                invContainer.innerHTML = '<span style="color: var(--status-danger);">Failed to load inventory: ' + err + '</span>';
            });
        }

        function modifyCharacter(type) {
            if (!selectedCharacter) return;
            let val = '';
            if (type === 'level') {
                val = document.getElementById('editSetLevel').value;
            } else if (type === 'gold') {
                val = document.getElementById('editSetGold').value;
            } else if (type === 'teleport') {
                val = document.getElementById('editTeleportCity').value;
            } else if (type === 'gmlevel') {
                val = document.getElementById('editGmLevel').value;
            }

            if ((type === 'level' || type === 'gold' || type === 'teleport' || type === 'gmlevel') && !val) {
                alert('Please enter or select a value.');
                return;
            }

            if (!confirm(`Execute character modification (${type}) for ${selectedCharacter.name}?`)) {
                return;
            }

            fetch('index.php?action=modify_character', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `name=${encodeURIComponent(selectedCharacter.name)}&type=${type}&value=${encodeURIComponent(val)}`
            })
            .then(res => res.json())
            .then(data => {
                alert(data.output || 'Action completed successfully!');
                searchCharacters();
            })
            .catch(err => alert('Error updating character property: ' + err));
        }

        function createGameAccountForm(e) {
            e.preventDefault();
            const username = document.getElementById('accUsername').value;
            const password = document.getElementById('accPassword').value;
            const gmlevel = document.getElementById('accGmLevel').value;

            const resultDiv = document.getElementById('accountCreationOutput');
            resultDiv.style.background = 'rgba(255, 255, 255, 0.03)';
            resultDiv.style.color = '#fff';
            resultDiv.textContent = 'Creating account...';

            fetch('index.php?action=create_account', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}&gmlevel=${gmlevel}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    resultDiv.style.background = 'rgba(16, 185, 129, 0.05)';
                    resultDiv.style.color = 'var(--status-success)';
                    resultDiv.innerHTML = `<strong>Success:</strong> ${data.output}`;
                    // Clear inputs on success
                    document.getElementById('accUsername').value = '';
                    document.getElementById('accPassword').value = '';
                } else {
                    resultDiv.style.background = 'rgba(239, 68, 68, 0.05)';
                    resultDiv.style.color = 'var(--status-danger)';
                    resultDiv.innerHTML = `<strong>Error:</strong> ${data.output || 'Unknown error'}`;
                }
            })
            .catch(err => {
                resultDiv.style.background = 'rgba(239, 68, 68, 0.05)';
                resultDiv.style.color = 'var(--status-danger)';
                resultDiv.textContent = 'Error: ' + err;
            });
        }



        // Live Auction House
        function loadAuctions() {
            const body = document.getElementById('auctionsTableBody');
            body.innerHTML = `<tr><td colspan="7" style="color: var(--text-secondary); text-align: center; padding: 2rem;">Loading auctions...</td></tr>`;

            fetch('index.php?action=get_auctions')
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    body.innerHTML = `<tr><td colspan="7" style="color: var(--status-danger); text-align: center; padding: 2rem;">Error: ${data.output}</td></tr>`;
                    return;
                }
                
                if (data.auctions.length === 0) {
                    body.innerHTML = `<tr><td colspan="7" style="color: var(--text-secondary); text-align: center; padding: 2rem;">No active auctions found. Bots will populate items as they progress and gather loot.</td></tr>`;
                    return;
                }

                let html = '';
                data.auctions.forEach(auc => {
                    const itemName = auc.item_name || `Unknown Item #${auc.itemEntry}`;
                    const quality = parseInt(auc.item_quality) || 0;
                    
                    let colorClass = 'color: #9d9d9d;';
                    if (quality === 1) colorClass = 'color: #ffffff;';
                    if (quality === 2) colorClass = 'color: #1eff00; font-weight: 500;';
                    if (quality === 3) colorClass = 'color: #0070dd; font-weight: 500;';
                    if (quality === 4) colorClass = 'color: #a335ee; font-weight: 500;';
                    if (quality === 5) colorClass = 'color: #ff8000; font-weight: 500;';

                    let house = 'Neutral';
                    if (auc.houseid == 1) house = 'Alliance 🔵';
                    if (auc.houseid == 2) house = 'Horde 🔴';

                    const buyoutGold = Math.floor(auc.buyoutprice / 10000);
                    const buyoutSilver = Math.floor((auc.buyoutprice % 10000) / 100);
                    const buyoutCopper = auc.buyoutprice % 100;
                    
                    const bidPrice = auc.lastbid > 0 ? auc.lastbid : auc.startbid;
                    const bidGold = Math.floor(bidPrice / 10000);
                    const bidSilver = Math.floor((bidPrice % 10000) / 100);
                    const bidCopper = bidPrice % 100;

                    const buyoutStr = `${buyoutGold}g ${buyoutSilver}s ${buyoutCopper}c`;
                    const bidStr = `${bidGold}g ${bidSilver}s ${bidCopper}c`;

                    const now = Math.floor(Date.now() / 1000);
                    const diff = auc.expire_time - now;
                    let timeStr = 'Expired';
                    if (diff > 0) {
                        const hours = Math.floor(diff / 3600);
                        const mins = Math.floor((diff % 3600) / 60);
                        timeStr = `${hours}h ${mins}m`;
                    }

                    html += `
                        <tr>
                            <td style="${colorClass}">${itemName}</td>
                            <td>${auc.item_count}</td>
                            <td>${auc.owner_name || 'Bot'}</td>
                            <td>${house}</td>
                            <td>${bidStr}</td>
                            <td>${buyoutStr}</td>
                            <td>${timeStr}</td>
                        </tr>
                    `;
                });
                body.innerHTML = html;
            })
            .catch(err => {
                body.innerHTML = `<tr><td colspan="7" style="color: var(--status-danger); text-align: center; padding: 2rem;">Error loading auctions: ${err}</td></tr>`;
            });
        }

        // Live Console script controllers
        function submitConsoleCommand(e) {
            e.preventDefault();
            const input = document.getElementById('terminalInput');
            const output = document.getElementById('terminalOutput');
            const command = input.value;
            
            output.innerHTML += `<br><span style="color: #fff">> ${command}</span>`;
            input.value = '';
            
            fetch('index.php?action=console', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `command=${encodeURIComponent(command)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    output.innerHTML += `<br>${data.output.replace(/\n/g, "<br>")}`;
                } else {
                    output.innerHTML += `<br><span style="color: var(--status-danger)">Error: ${data.output}</span>`;
                }
                output.scrollTop = output.scrollHeight;
            })
            .catch(err => {
                output.innerHTML += `<br><span style="color: var(--status-danger)">Request failed: ${err}</span>`;
                output.scrollTop = output.scrollHeight;
            });
        }

        // Modal for reference table popups
        function openReferenceLootModal(refId, refName) {
            const modal = document.getElementById('lootModal');
            const title = document.getElementById('lootModalTitle');
            const body = document.getElementById('lootModalTableBody');
            
            title.textContent = `Reference Table: ${refName} (#${refId})`;
            body.innerHTML = '<tr><td colspan="3" style="text-align: center; color: var(--text-secondary); padding: 1.5rem;">Loading reference loot...</td></tr>';
            modal.style.display = 'flex';

            fetch(`index.php?action=get_reference_loot&entry=${refId}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.loot.length === 0) {
                    body.innerHTML = '<tr><td colspan="3" style="text-align: center; color: var(--text-secondary); padding: 1.5rem;">No loot entries found in this reference table.</td></tr>';
                    return;
                }
                
                let html = '';
                const processedLoot = calculateLootChances(data.loot);
                processedLoot.forEach(item => {
                    let displayName = item.item_name || 'Unknown Item';
                    let displayId = `#${item.item_entry}`;
                    let isRef = false;
                    let innerRefId = parseInt(item.reference) || 0;

                    if (parseInt(item.item_entry) === 0 && innerRefId > 0) {
                        isRef = true;
                        let match = item.comment ? item.comment.match(/\(([^)]+)\)/) : null;
                        let refNameInner = match ? match[1] : "Reference Table";
                        displayName = "🔄 " + refNameInner;
                        displayId = "Ref: #" + innerRefId;
                    }

                    const quality = isRef ? -1 : (parseInt(item.item_quality) || 0);
                    let color = '#fff';
                    if (isRef) color = '#38bdf8';
                    else {
                        if (quality === 2) color = '#1eff00';
                        if (quality === 3) color = '#0070dd';
                        if (quality === 4) color = '#a335ee';
                        if (quality === 5) color = '#ff8000';
                    }

                    let displayLabel = displayName;
                    if (!isRef && item.item_entry > 0) {
                        displayLabel = `<a href="https://www.wowhead.com/wotlk/item=${item.item_entry}" target="_blank" style="color: ${color}; text-decoration: none; border-bottom: 1px dashed ${color}55;" title="View on Wowhead">${displayName} 🔗</a>`;
                    } else if (isRef) {
                        displayLabel = `<span onclick="openReferenceLootModal(${innerRefId}, '${displayName.replace(/'/g, "\'")}')" style="cursor: pointer; color: ${color}; border-bottom: 1px dashed ${color}aa;" title="Click to view inner reference table">${displayName} 👁️</span>`;
                    }

                    html += `
                        <tr>
                            <td style="color: ${color}; font-weight: 500;">${displayLabel} <span style="font-size:0.8rem; color:var(--text-secondary)">(${displayId})</span></td>
                            <td>${item.mincount === item.maxcount ? item.mincount : `${item.mincount}-${item.maxcount}`}</td>
                            <td><strong style="color: var(--accent-primary)">${item.chanceLabel}</strong>${item.badge}</td>
                        </tr>
                    `;
                });
                body.innerHTML = html;
            })
            .catch(err => {
                body.innerHTML = `<tr><td colspan="3" style="text-align: center; color: var(--status-danger); padding: 1.5rem;">Failed loading reference: ${err}</td></tr>`;
            });
        }

        function closeLootModal() {
            document.getElementById('lootModal').style.display = 'none';
        }

        function updateActualStatsDisplay() {
            const healthMod = parseFloat(document.getElementById('editCreatureMinHealth').value) || 1.0;
            const armorMod = parseFloat(document.getElementById('editCreatureArmor').value) || 1.0;
            const minLvl = parseInt(document.getElementById('editCreatureMinLvl').value) || 1;
            const maxLvl = parseInt(document.getElementById('editCreatureMaxLvl').value) || 1;

            const minHpVal = Math.round(baseMinHp * healthMod);
            const maxHpVal = Math.round(baseMaxHp * healthMod);
            const minArmorVal = Math.round(baseMinArmor * armorMod);
            const maxArmorVal = Math.round(baseMaxArmor * armorMod);

            let hpText = `(Estimated HP: ${minHpVal})`;
            if (minLvl !== maxLvl) {
                hpText = `(Estimated HP: ${minHpVal} - ${maxHpVal})`;
            }
            
            let armorText = `(Estimated Armor: ${minArmorVal})`;
            if (minLvl !== maxLvl) {
                armorText = `(Estimated Armor: ${minArmorVal} - ${maxArmorVal})`;
            }

            document.getElementById('actualHealthDisplay').textContent = hpText;
            document.getElementById('actualArmorDisplay').textContent = armorText;
        }

        // ----------------------------------------------------
        // GM Cheat & Mail Package Actions
        // ----------------------------------------------------
        function sendMailPackage(packageType) {
            const charName = document.getElementById('editCharName').textContent;
            if (!charName || charName === '--') {
                alert("Please select a character first.");
                return;
            }
            if (!confirm(`Are you sure you want to mail the '${packageType}' package to ${charName}?`)) {
                return;
            }
            fetch('index.php?action=send_mail_package', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `name=${encodeURIComponent(charName)}&package=${encodeURIComponent(packageType)}`
            })
            .then(res => res.json())
            .then(data => {
                alert(data.output);
            })
            .catch(err => alert("Error mailing package: " + err));
        }

        function teachGmUnlock(unlockType) {
            const charName = document.getElementById('editCharName').textContent;
            if (!charName || charName === '--') {
                alert("Please select a character first.");
                return;
            }
            fetch('index.php?action=teach_gm_unlock', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `name=${encodeURIComponent(charName)}&unlock=${encodeURIComponent(unlockType)}`
            })
            .then(res => res.json())
            .then(data => {
                alert(data.output || "Cheat unlock sent successfully!");
            })
            .catch(err => alert("Error triggering unlock: " + err));
        }

        // ----------------------------------------------------
        // Playerbots Controller
        // ----------------------------------------------------
        function loadPlayerbots() {
            const body = document.getElementById('playerbotsTableBody');
            body.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 2rem;">Loading online bots sessions...</td></tr>';
            
            fetch('index.php?action=get_active_bots')
            .then(res => res.json())
            .then(data => {
                if (!data.success || data.bots.length === 0) {
                    body.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 2rem;">No playerbots online currently. Spawn one below!</td></tr>';
                    return;
                }
                
                const classes = {
                    1: 'Warrior 🛡️', 2: 'Paladin ⚔️', 3: 'Hunter 🏹', 4: 'Rogue 🗡️',
                    5: 'Priest ✝️', 6: 'Death Knight 💀', 7: 'Shaman ⚡', 8: 'Mage 🔮',
                    9: 'Warlock 👿', 11: 'Druid 🌿'
                };
                const races = {
                    1: 'Human', 2: 'Orc', 3: 'Dwarf', 4: 'Night Elf', 5: 'Undead',
                    6: 'Tauren', 7: 'Gnome', 8: 'Troll', 10: 'Blood Elf', 11: 'Draenei'
                };
                const zones = {
                    12: 'Elwynn Forest', 14: 'Durotar', 1: 'Dun Morogh', 141: 'Teldrassil',
                    85: 'Tirisfal Glades', 215: 'Mulgore', 3430: 'Eversong Woods', 3524: 'Azuremyst Isle',
                    1519: 'Stormwind City', 1637: 'Orgrimmar', 1537: 'Ironforge', 1497: 'Undercity',
                    1657: 'Darnassus', 1638: 'Thunder Bluff', 3487: 'Silvermoon City', 3557: 'The Exodar',
                    3711: 'Shattrath City', 4395: 'Dalaran'
                };

                let html = '';
                data.bots.forEach(bot => {
                    const classLabel = classes[bot.class] || `Class ${bot.class}`;
                    const raceLabel = races[bot.race] || `Race ${bot.race}`;
                    const zoneLabel = zones[bot.zone] || `Zone ${bot.zone}`;
                    const guild = bot.guild_name || '<None>';

                    html += `
                        <tr>
                            <td>${bot.guid}</td>
                            <td style="font-weight: 600; color: #fff;">${bot.name}</td>
                            <td>${raceLabel} ${classLabel}</td>
                            <td><strong style="color: var(--accent-primary)">${bot.level}</strong></td>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">${zoneLabel}</td>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">${guild}</td>
                            <td><span style="color: var(--status-success); font-weight: 500;">Online 🟢</span></td>
                        </tr>
                    `;
                });
                body.innerHTML = html;
            })
            .catch(err => {
                body.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--status-danger); padding: 2rem;">Failed loading bots: ${err}</td></tr>`;
            });
        }

        function executeBotCommand(cmd, botClass = '') {
            fetch('index.php?action=execute_bot_command', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `command=${cmd}&class=${botClass}`
            })
            .then(res => res.json())
            .then(data => {
                alert(data.output || "Bot command processed successfully!");
                loadPlayerbots();
            })
            .catch(err => alert("Error running command: " + err));
        }

        // ----------------------------------------------------
        // Ollama LLM Tuner Controller
        // ----------------------------------------------------
        function loadLLMConfig() {
            fetch('index.php?action=get_llm_config')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.config) {
                    document.getElementById('llmHost').value = data.config.host;
                    document.getElementById('llmPort').value = data.config.port;
                    document.getElementById('llmModel').value = data.config.model;
                    document.getElementById('llmTemp').value = data.config.temp;
                    document.getElementById('llmPrompt').value = data.config.system_prompt;
                }
            })
            .catch(err => alert("Error fetching LLM config: " + err));
        }

        function saveLLMConfig(e) {
            e.preventDefault();
            const host = document.getElementById('llmHost').value;
            const port = document.getElementById('llmPort').value;
            const model = document.getElementById('llmModel').value;
            const temp = document.getElementById('llmTemp').value;
            const prompt = document.getElementById('llmPrompt').value;

            const params = `host=${encodeURIComponent(host)}&port=${port}&model=${encodeURIComponent(model)}&temp=${temp}&system_prompt=${encodeURIComponent(prompt)}`;
            fetch('index.php?action=set_llm_config', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            })
            .then(res => res.json())
            .then(data => {
                alert(data.output);
                loadLLMConfig();
            })
            .catch(err => alert("Failed saving LLM settings: " + err));
        }

        // ----------------------------------------------------
        // Custom Mail & Attachment Spawner Service
        // ----------------------------------------------------
        let mailAttachments = [];
        let mailItemSearchTimeout = null;
        let selectedMailItem = null;

        function filterMailItemSearch(val) {
            const dropdown = document.getElementById('mailItemSearchDropdown');
            if (val.length < 2) {
                dropdown.style.display = 'none';
                return;
            }
            
            clearTimeout(mailItemSearchTimeout);
            mailItemSearchTimeout = setTimeout(() => {
                fetch('index.php?action=search_items', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `query=${encodeURIComponent(val)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.items && data.items.length > 0) {
                        dropdown.innerHTML = data.items.map(item => {
                            const quality = parseInt(item.quality) || 0;
                            let color = '#fff';
                            if (quality === 2) color = '#1eff00';
                            if (quality === 3) color = '#0070dd';
                            if (quality === 4) color = '#a335ee';
                            if (quality === 5) color = '#ff8000';
                            return `
                                <div class="autocomplete-item" onclick="selectMailItem(${item.entry}, '${encodeURIComponent(item.name)}')" style="padding: 0.5rem 0.75rem; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.05); color: ${color};" onmouseover="this.style.background='var(--primary-glow)'" onmouseout="this.style.background='transparent'">
                                    <strong>${item.name}</strong> <span style="font-size:0.75rem; color:var(--text-secondary); float:right;">#${item.entry}</span>
                                </div>
                            `;
                        }).join('');
                        dropdown.style.display = 'block';
                    } else {
                        dropdown.innerHTML = `<div style="color: var(--text-secondary); padding: 0.5rem; text-align: center; font-size:0.8rem;">No items found</div>`;
                        dropdown.style.display = 'block';
                    }
                })
                .catch(err => {
                    dropdown.innerHTML = `<div style="color: var(--status-danger); padding: 0.5rem; text-align: center; font-size:0.8rem;">Search error</div>`;
                    dropdown.style.display = 'block';
                });
            }, 150);
        }

        function selectMailItem(entry, name) {
            selectedMailItem = { entry, name: decodeURIComponent(name) };
            document.getElementById('mailItemSearch').value = decodeURIComponent(name) + ` (#${entry})`;
            document.getElementById('mailItemSearchDropdown').style.display = 'none';
        }

        function addMailAttachment() {
            if (!selectedMailItem) {
                const rawVal = document.getElementById('mailItemSearch').value.trim();
                if (rawVal && !isNaN(rawVal)) {
                    selectedMailItem = { entry: parseInt(rawVal), name: `Item #${rawVal}` };
                } else {
                    alert("Please search and select an item first.");
                    return;
                }
            }

            if (mailAttachments.length >= 12) {
                alert("You can attach a maximum of 12 items.");
                return;
            }

            const qty = parseInt(document.getElementById('mailItemQty').value) || 1;
            mailAttachments.push({
                id: selectedMailItem.entry,
                name: selectedMailItem.name,
                count: qty
            });

            document.getElementById('mailItemSearch').value = '';
            document.getElementById('mailItemQty').value = '1';
            selectedMailItem = null;

            renderMailAttachments();
        }

        function removeMailAttachment(index) {
            mailAttachments.splice(index, 1);
            renderMailAttachments();
        }

        function renderMailAttachments() {
            const container = document.getElementById('mailAttachmentsList');
            if (mailAttachments.length === 0) {
                container.innerHTML = '<span style="color: var(--text-secondary); font-size: 0.85rem; font-style: italic;">No items attached yet.</span>';
                return;
            }

            container.innerHTML = mailAttachments.map((att, idx) => `
                <div style="background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.3); padding: 0.35rem 0.75rem; border-radius: 20px; display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 500; color: #a5b4fc;">
                    <span>${att.name} x${att.count}</span>
                    <span onclick="removeMailAttachment(${idx})" style="color: #ef4444; cursor: pointer; font-weight: bold; font-size: 1rem; line-height: 1;" onmouseover="this.style.color='#f87171'" onmouseout="this.style.color='#ef4444'">✕</span>
                </div>
            `).join('');
        }

        function sendCustomMail(e) {
            e.preventDefault();
            const recipient = document.getElementById('mailRecipient').value;
            const gold = document.getElementById('mailGold').value;
            const subject = document.getElementById('mailSubject').value;
            const body = document.getElementById('mailBody').value;

            if (!confirm(`Are you ready to send this custom delivery to ${recipient}?`)) {
                return;
            }

            const params = `recipient=${encodeURIComponent(recipient)}&gold=${gold}&subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}&attachments=${encodeURIComponent(JSON.stringify(mailAttachments))}`;

            fetch('index.php?action=send_custom_mail', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            })
            .then(res => res.json())
            .then(data => {
                alert(data.output);
                if (data.success) {
                    document.getElementById('mailGold').value = '0';
                    document.getElementById('mailSubject').value = 'GM Package';
                    document.getElementById('mailBody').value = 'Enjoy this delivery!';
                    mailAttachments = [];
                    renderMailAttachments();
                }
            })
            .catch(err => alert("Error sending custom mail: " + err));
        }

        // ----------------------------------------------------
        // Visual Model search and display ID grabber
        // ----------------------------------------------------
        let itmModelSearchTimeout = null;

        function filterItemModelSearch(val) {
            const dropdown = document.getElementById('itmModelSearchDropdown');
            if (val.length < 2) {
                dropdown.style.display = 'none';
                return;
            }
            
            clearTimeout(itmModelSearchTimeout);
            itmModelSearchTimeout = setTimeout(() => {
                fetch('index.php?action=search_items', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `query=${encodeURIComponent(val)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.items && data.items.length > 0) {
                        dropdown.innerHTML = data.items.map(item => {
                            const quality = parseInt(item.quality) || 0;
                            let color = '#fff';
                            if (quality === 2) color = '#1eff00';
                            if (quality === 3) color = '#0070dd';
                            if (quality === 4) color = '#a335ee';
                            if (quality === 5) color = '#ff8000';
                            return `
                                <div class="autocomplete-item" onclick="selectItemModel(${item.displayid}, '${encodeURIComponent(item.name)}')" style="padding: 0.5rem 0.75rem; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.05); color: ${color};" onmouseover="this.style.background='var(--primary-glow)'" onmouseout="this.style.background='transparent'">
                                    <strong>${item.name}</strong> <span style="font-size:0.75rem; color:var(--text-secondary); float:right;">Model: #${item.displayid}</span>
                                </div>
                            `;
                        }).join('');
                        dropdown.style.display = 'block';
                    } else {
                        dropdown.innerHTML = `<div style="color: var(--text-secondary); padding: 0.5rem; text-align: center; font-size:0.8rem;">No items found</div>`;
                        dropdown.style.display = 'block';
                    }
                })
                .catch(err => {
                    dropdown.innerHTML = `<div style="color: var(--status-danger); padding: 0.5rem; text-align: center; font-size:0.8rem;">Search error</div>`;
                    dropdown.style.display = 'block';
                });
            }, 150);
        }

        function selectItemModel(displayid, name) {
            document.getElementById('itmDisplayId').value = displayid;
            document.getElementById('itmModelSearch').value = decodeURIComponent(name);
            document.getElementById('itmModelSearchDropdown').style.display = 'none';
        }

        // Close autocomplete dropdown on click outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('itmModelSearchDropdown');
            const input = document.getElementById('itmModelSearch');
            if (dropdown && e.target !== dropdown && e.target !== input) {
                dropdown.style.display = 'none';
            }
        });
        // Close autocomplete dropdown on click outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('mailItemSearchDropdown');
            const input = document.getElementById('mailItemSearch');
            if (dropdown && e.target !== dropdown && e.target !== input) {
                dropdown.style.display = 'none';
            }
        });

        // ----------------------------------------------------
        // Visual Custom Item Creator Controller
        // ----------------------------------------------------
        function toggleItemTypeFields(slotVal) {
            slotVal = parseInt(slotVal);
            const speedInput = document.getElementById('itmSpeed');
            const minDmgInput = document.getElementById('itmMinDmg');
            const maxDmgInput = document.getElementById('itmMaxDmg');
            
            // If weapon (slot 13 or 17), enable speed/dmg. If shield/armor, disable
            if (slotVal === 13 || slotVal === 17) {
                speedInput.disabled = false;
                minDmgInput.disabled = false;
                maxDmgInput.disabled = false;
                speedInput.style.opacity = '1';
                minDmgInput.style.opacity = '1';
                maxDmgInput.style.opacity = '1';
            } else {
                speedInput.disabled = true;
                minDmgInput.disabled = true;
                maxDmgInput.disabled = true;
                speedInput.style.opacity = '0.5';
                minDmgInput.style.opacity = '0.5';
                maxDmgInput.style.opacity = '0.5';
            }
        }

        function createCustomItem(e) {
            e.preventDefault();
            const name = document.getElementById('itmName').value;
            const quality = document.getElementById('itmQuality').value;
            const slot = document.getElementById('itmSlot').value;
            const speed = Math.round(parseFloat(document.getElementById('itmSpeed').value || 2.0) * 1000);
            const min_dmg = document.getElementById('itmMinDmg').value;
            const max_dmg = document.getElementById('itmMaxDmg').value;
            const stamina = document.getElementById('itmStamina').value;
            const strength = document.getElementById('itmStrength').value;
            const agility = document.getElementById('itmAgility').value;
            const intellect = document.getElementById('itmIntellect').value;
            const char_name = document.getElementById('itmCharName').value;
            const displayid = document.getElementById('itmDisplayId').value;

            // Determine item class/subclass
            let itemClass = 2; // Default weapon
            let itemSubclass = 7; // One hand sword
            if (parseInt(slot) === 14) {
                itemClass = 4; // Armor
                itemSubclass = 6; // Shield
            } else if (parseInt(slot) === 5 || parseInt(slot) === 1 || parseInt(slot) === 3) {
                itemClass = 4; // Armor
                itemSubclass = 4; // Cloth/Plate placeholder
            }

            const params = `name=${encodeURIComponent(name)}&quality=${quality}&slot=${slot}&speed=${speed}&min_dmg=${min_dmg}&max_dmg=${max_dmg}&stamina=${stamina}&strength=${strength}&agility=${agility}&intellect=${intellect}&char_name=${encodeURIComponent(char_name)}&class=${itemClass}&subclass=${itemSubclass}&displayid=${displayid}`;

            if (!confirm(`Are you ready to forge the Legendary item '${name}' and add it to the server database?`)) {
                return;
            }

            fetch('index.php?action=create_custom_item', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            })
            .then(res => res.json())
            .then(data => {
                alert(data.output);
                if (data.success) {
                    document.getElementById('itmName').value = '';
                    document.getElementById('itmCharName').value = '';
                }
            })
            .catch(err => alert("Forging error: " + err));
        }

        // Live Event filter controller
        function filterEvents(query) {
            query = query.toLowerCase();
            const rows = document.querySelectorAll('.event-row');
            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                if (name.includes(query)) {
                    row.style.display = 'flex';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
    <!-- Modal for Reference Table Loot -->
    <div id="lootModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="card" style="width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto; position: relative; border: 1px solid var(--border-glass); background: #0f172a; padding: 2rem;">
            <span onclick="closeLootModal()" style="position: absolute; top: 1rem; right: 1.25rem; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary);" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-secondary)'">&times;</span>
            <div class="card-title" id="lootModalTitle" style="margin-bottom: 1.5rem;">Reference Table Content</div>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Item Name (ID)</th>
                            <th>Qty</th>
                            <th>Chance</th>
                        </tr>
                    </thead>
                    <tbody id="lootModalTableBody">
                        <!-- Filled by JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
