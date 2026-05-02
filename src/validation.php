<?php
declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sanitize_string(?string $value): string
{
    if ($value === null) {
        return '';
    }
    return trim($value);
}

/**
 * Validate and sanitize input for "Create Task".
 *
 * @param array<string,mixed> $input
 * @return array{0: array<string,string>, 1: array<string,mixed>}
 */
function validate_create_task(array $input): array
{
    $errors    = [];
    $sanitized = [];

    $title = sanitize_string($input['title'] ?? '');
    if ($title === '') {
        $errors['title'] = 'Title is required.';
    }
    $sanitized['title'] = $title;

    $description = sanitize_string($input['description'] ?? '');
    $sanitized['description'] = $description;

    $priority = $input['priority'] ?? 'Medium';
    $allowed  = ['Low', 'Medium', 'High'];
    if (!in_array($priority, $allowed, true)) {
        $errors['priority'] = 'Invalid priority.';
        $priority           = 'Medium';
    }
    $sanitized['priority'] = $priority;

    $dueRaw = sanitize_string($input['due'] ?? '');
    if ($dueRaw === '') {
        $sanitized['due'] = null;
    } else {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueRaw)) {
            $errors['due'] = 'Invalid date.';
            $sanitized['due'] = null;
        } else {
            [$y, $m, $d] = array_map('intval', explode('-', $dueRaw));
            if (!checkdate($m, $d, $y)) {
                $errors['due'] = 'Invalid date.';
                $sanitized['due'] = null;
            } else {
                $sanitized['due'] = $dueRaw;
            }
        }
    }

    return [$errors, $sanitized];
}
