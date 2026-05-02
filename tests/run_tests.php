<?php
declare(strict_types=1);


$casesFile = __DIR__ . '/test_cases.json';
if (!file_exists($casesFile)) {
    fwrite(STDERR, "ERROR: tests/test_cases.json not found.\n");
    exit(1);
}

$raw = file_get_contents($casesFile);
$cases = json_decode($raw, true);
if (!is_array($cases)) {
    fwrite(STDERR, "ERROR: Could not decode test_cases.json\n");
    exit(1);
}

require_once __DIR__ . '/../src/storage.php';
require_once __DIR__ . '/../src/validation.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/flash.php';


function test_tasks_file(): string {
    return __DIR__ . '/../data/tasks.json';
}

function test_write_tasks(array $tasks): void {
    $file = test_tasks_file();
    $dir  = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($file, json_encode($tasks, JSON_PRETTY_PRINT));
}

function test_read_tasks(): array {
    $file = test_tasks_file();
    if (!file_exists($file)) {
        return [];
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

/**
 * Run a single test case.
 *
 * @param array<string,mixed> $case
 * @return array{passed: bool, details: array<int,string>}
 */
function run_case(array $case): array
{
    if (isset($case['pre_state']['tasks']) && is_array($case['pre_state']['tasks'])) {
        test_write_tasks($case['pre_state']['tasks']);
    } else {
        test_write_tasks([]);
    }

    $_GET  = $case['get']  ?? [];
    $_POST = $case['post'] ?? [];
    $_SERVER['REQUEST_METHOD'] = strtoupper($case['method'] ?? 'GET');

    $_SESSION = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = csrf_token(); 

        if (!isset($_POST['csrf_token'])) {
            $_POST['csrf_token'] = $token;
        } else {
            $_SESSION['csrf_token'] = $_POST['csrf_token'];
        }
    }

    if (function_exists('header_remove')) {
        header_remove();
    }

    $endpointPath = __DIR__ . '/../' . $case['endpoint'];
    if (!file_exists($endpointPath)) {
        return [
            'passed'  => false,
            'details' => ["Endpoint file not found: {$case['endpoint']}"],
        ];
    }

    ob_start();
    include $endpointPath;
        $output  = ob_get_clean();
    $headers = headers_list();

    $status   = 200;
    $location = null;

    if (isset($GLOBALS['__TEST_REDIRECT_TO'])) {
        $location = $GLOBALS['__TEST_REDIRECT_TO'];
        $status   = 302;
        unset($GLOBALS['__TEST_REDIRECT_TO']);
    } else {
        foreach ($headers as $h) {
            if (stripos($h, 'Location:') === 0) {
                $location = trim(substr($h, strlen('Location:')));
                $status = 302;
            }
        }
    }


    $expect  = $case['expect'] ?? [];
    $passed  = true;
    $details = [];

    if (isset($expect['status'])) {
        if ((int)$expect['status'] !== (int)$status) {
            $passed    = false;
            $details[] = "Expected status {$expect['status']} got {$status}";
        }
    }

    if (isset($expect['redirect_contains'])) {
        if ($location === null || strpos($location, $expect['redirect_contains']) === false) {
            $passed    = false;
            $details[] = "Expected redirect containing '{$expect['redirect_contains']}', got '" . ($location ?? 'none') . "'";
        }
    }

    if (isset($expect['html_contains']) && is_array($expect['html_contains'])) {
        foreach ($expect['html_contains'] as $needle) {
            if (strpos($output, $needle) === false) {
                $passed    = false;
                $details[] = "Expected HTML to contain '{$needle}'";
            }
        }
    }

    $tasksAfter = test_read_tasks();

    if (isset($expect['tasks_count'])) {
        $count = count($tasksAfter);
        if ($count !== (int)$expect['tasks_count']) {
            $passed    = false;
            $details[] = "Expected tasks_count {$expect['tasks_count']}, got {$count}";
        }
    }

    if (isset($expect['any_completed'])) {
        $anyCompleted = false;
        foreach ($tasksAfter as $t) {
            if (!empty($t['completed'])) {
                $anyCompleted = true;
                break;
            }
        }
        if ((bool)$expect['any_completed'] !== $anyCompleted) {
            $passed    = false;
            $details[] = "Expected any_completed=" . ($expect['any_completed'] ? 'true' : 'false') .
                         ", got " . ($anyCompleted ? 'true' : 'false');
        }
    }

    if (isset($expect['title_exists'])) {
        $found = false;
        foreach ($tasksAfter as $t) {
            if (($t['title'] ?? '') === $expect['title_exists']) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $passed    = false;
            $details[] = "Expected a task with title '{$expect['title_exists']}'";
        }
    }

    if (isset($expect['deleted_id'])) {
        $id    = $expect['deleted_id'];
        $alive = false;
        foreach ($tasksAfter as $t) {
            if (($t['id'] ?? '') === $id) {
                $alive = true;
                break;
            }
        }
        if ($alive) {
            $passed    = false;
            $details[] = "Expected task id '{$id}' to be deleted";
        }
    }

    return [
        'passed'  => $passed,
        'details' => $details,
    ];
}


$total   = 0;
$pass    = 0;
$fail    = 0;
$logRows = [];

foreach ($cases as $case) {
    $total++;
    $id   = $case['id']   ?? "TC{$total}";
    $desc = $case['desc'] ?? '';

    $result = run_case($case);

    if ($result['passed']) {
        $pass++;
        $logRows[] = "[{$id}] PASS - {$desc}";
    } else {
        $fail++;
        $logRows[] = "[{$id}] FAIL - {$desc}";
        foreach ($result['details'] as $d) {
            $logRows[] = "   - {$d}";
        }
    }
}

echo "Running TaskPad PHP tests...\n\n";
foreach ($logRows as $line) {
    echo $line, "\n";
}
echo "\nTotal: {$total} | Passed: {$pass} | Failed: {$fail}\n";

exit($fail > 0 ? 1 : 0);
