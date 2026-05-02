<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/storage.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/flash.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Method Not Allowed';
    return;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid CSRF token.';
    return;
}

$action = $_POST['action'] ?? '';
$id     = $_POST['id'] ?? '';

$tasks = load_tasks();

if ($action === 'complete') {
    $found = false;
    foreach ($tasks as &$task) {
        if (($task['id'] ?? '') === $id) {
            $task['completed'] = true;
            $found = true;
            break;
        }
    }
    unset($task);

    if ($found) {
        save_tasks($tasks);
        set_flash('success', 'Task marked complete.');
    } else {
        set_flash('error', 'Task not found.');
    }

} elseif ($action === 'delete') {
    $before = count($tasks);
    $tasks  = array_values(array_filter($tasks, function ($t) use ($id) {
        return ($t['id'] ?? '') !== $id;
    }));
    $after  = count($tasks);

    if ($after !== $before) {
        save_tasks($tasks);
        set_flash('success', 'Task deleted.');
    } else {
        set_flash('error', 'Task not found.');
    }

} else {
    set_flash('error', 'Unknown action.');
}

if (PHP_SAPI === 'cli') {
    $GLOBALS['__TEST_REDIRECT_TO'] = 'index.php';
}

header('Location: index.php');
return;

