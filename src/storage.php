<?php
declare(strict_types=1);

const TASKS_FILE = __DIR__ . '/../data/tasks.json';

/**
 * Load all tasks from JSON file.
 *
 * @return array<int,array<string,mixed>>
 */
function load_tasks(): array
{
    if (!file_exists(TASKS_FILE)) {
        return [];
    }

    $json = file_get_contents(TASKS_FILE);
    if ($json === false || $json === '') {
        return [];
    }

    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/**
 * Save all tasks to JSON file.
 *
 * @param array<int,array<string,mixed>> $tasks
 */
function save_tasks(array $tasks): void
{
    $dir = dirname(TASKS_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents(TASKS_FILE, json_encode($tasks, JSON_PRETTY_PRINT));
}

/**
 * Append a single task to the JSON store.
 *
 * @param array<string,mixed> $task
 */
function add_task(array $task): void
{
    $tasks = load_tasks();
    $tasks[] = $task;
    save_tasks($tasks);
}
