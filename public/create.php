<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/storage.php';
require_once __DIR__ . '/../src/validation.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/flash.php';

$errors = [];
$values = [
    'title'       => '',
    'description' => '',
    'priority'    => 'Medium',
    'due'         => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Invalid CSRF token.';
        return;
    }

    [$errors, $sanitized] = validate_create_task($_POST);

    $values['title']       = $sanitized['title'];
    $values['description'] = $sanitized['description'];
    $values['priority']    = $sanitized['priority'];
    $values['due']         = $sanitized['due'] ?? '';

    if (empty($errors)) {
        $task = [
            'id'         => uniqid('task_', true),
            'title'      => $sanitized['title'],
            'description'=> $sanitized['description'],
            'priority'   => $sanitized['priority'],
            'due'        => $sanitized['due'],
            'completed'  => false,
        ];

        add_task($task);
        set_flash('success', 'Task created successfully.');

        if (PHP_SAPI === 'cli') {
            $GLOBALS['__TEST_REDIRECT_TO'] = 'index.php';
        }

        header('Location: index.php');
        return;
    }

}

$csrfToken = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create Task - TaskPad PHP</title>

    <style>
        :root {
            --bg: #0f172a;
            --bg-card: #020617;
            --border: #1f2937;
            --text: #e5e7eb;
            --muted: #9ca3af;
            --primary: #38bdf8;
            --primary-soft: rgba(56,189,248,0.16);
            --danger: #f97373;
            --danger-soft: rgba(248,113,113,0.16);
            --success: #4ade80;
            --success-soft: rgba(74,222,128,0.16);
            --radius: 12px;
            --shadow: 0 18px 45px rgba(15,23,42,0.75);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, #1d4ed8 0, transparent 55%),
                radial-gradient(circle at bottom right, #22c55e 0, transparent 55%),
                linear-gradient(160deg, #020617, #020617 70%);
            color: var(--text);
        }

        a { color: var(--primary); text-decoration: none; }
        a:hover { text-decoration: underline; }

        .container {
            max-width: 700px;
            margin: 0 auto;
            padding: 1.25rem;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(15,23,42,0.95);
            backdrop-filter: blur(16px);
            box-shadow: 0 1px 0 rgba(148,163,184,0.25);
        }

        .topbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .topbar h1 {
            margin: 0;
            font-size: 1.4rem;
            letter-spacing: 0.05em;
        }

        main.container {
            margin-top: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .card {
            background: radial-gradient(circle at top left, rgba(56,189,248,0.1), transparent 55%),
                        radial-gradient(circle at bottom right, rgba(59,130,246,0.15), transparent 55%),
                        var(--bg-card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1.25rem 1.35rem;
            margin-bottom: 1.25rem;
            border: 1px solid rgba(148,163,184,0.25);
        }

        .card h2 {
            margin-top: 0;
            margin-bottom: 0.75rem;
            font-size: 1.05rem;
        }

        .field {
            display: flex;
            flex-direction: column;
            margin-bottom: 0.9rem;
        }

        .field-inline { max-width: 260px; }

        label {
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
            color: var(--muted);
        }

        input[type="text"],
        input[type="date"],
        select,
        textarea {
            border-radius: 999px;
            border: 1px solid rgba(148,163,184,0.7);
            padding: 0.45rem 0.7rem;
            font-size: 0.9rem;
            outline: none;
            background: rgba(15,23,42,0.95);
            color: var(--text);
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }

        textarea {
            border-radius: 10px;
            min-height: 90px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 1px var(--primary-soft);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid transparent;
            padding: 0.4rem 0.95rem;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            background: rgba(31,41,55,0.9);
            color: #e5e7eb;
            text-decoration: none;
            transition: transform 0.08s, box-shadow 0.15s, background 0.15s, border-color 0.15s;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 30px rgba(15,23,42,0.9);
            text-decoration: none;
        }

        .btn-primary {
            background: radial-gradient(circle at top left, #38bdf8, #0ea5e9);
            border-color: rgba(56,189,248,0.9);
            color: #0b1120;
        }

        .btn-ghost {
            background: transparent;
            border-color: rgba(148,163,184,0.5);
            color: #e5e7eb;
        }

        .actions {
            display: flex;
            gap: 0.6rem;
            margin-top: 1rem;
        }

        .error {
            color: var(--danger);
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        .required { color: var(--danger); }

        @media (max-width: 720px) {
            .topbar .container {
                flex-direction: column;
                align-items: flex-start;
            }
            .field-inline { max-width: 100%; }
        }
    </style>
</head>
<body>
<header class="topbar">
    <div class="container">
        <h1>Create Task</h1>
        <nav>
            <a href="index.php" class="btn btn-ghost">&larr; Back to list</a>
        </nav>
    </div>
</header>

<main class="container">
    <section class="card">
        <h2>New Task</h2>

        <form method="post" action="create.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

            <div class="field">
                <label for="title">
                    Title <span class="required">*</span>
                </label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    value="<?= e($values['title']) ?>"
                    required
                >
                <?php if (isset($errors['title'])): ?>
                    <div class="error"><?= e($errors['title']) ?></div>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea
                    id="description"
                    name="description"
                    rows="3"
                ><?= e($values['description']) ?></textarea>
            </div>

            <div class="field field-inline">
                <label for="priority">Priority</label>
                <select id="priority" name="priority">
                    <option value="Low"    <?= $values['priority'] === 'Low' ? 'selected' : '' ?>>Low</option>
                    <option value="Medium" <?= $values['priority'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="High"   <?= $values['priority'] === 'High' ? 'selected' : '' ?>>High</option>
                </select>
            </div>

            <div class="field field-inline">
                <label for="due">Due Date (YYYY-MM-DD)</label>
                <input
                    type="date"
                    id="due"
                    name="due"
                    value="<?= e($values['due']) ?>"
                >
                <?php if (isset($errors['due'])): ?>
                    <div class="error"><?= e($errors['due']) ?></div>
                <?php endif; ?>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary">Create Task</button>
                <a href="index.php" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
