<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/storage.php';
require_once __DIR__ . '/../src/validation.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/flash.php';

$flash = get_flash();
$tasks = load_tasks();

$q              = sanitize_string($_GET['q'] ?? '');
$priorityFilter = $_GET['priority'] ?? '';

$filtered = array_filter($tasks, function (array $task) use ($q, $priorityFilter): bool {
    $ok = true;

    if ($q !== '') {
        $qLower     = mb_strtolower($q);
        $titleLower = mb_strtolower($task['title'] ?? '');
        $descLower  = mb_strtolower($task['description'] ?? '');

        if (strpos($titleLower, $qLower) === false &&
            strpos($descLower, $qLower) === false) {
            $ok = false;
        }
    }

    if ($ok && $priorityFilter !== '' &&
        in_array($priorityFilter, ['Low', 'Medium', 'High'], true)) {

        if (($task['priority'] ?? '') !== $priorityFilter) {
            $ok = false;
        }
    }

    return $ok;
});

$totalCount     = count($tasks);
$openCount      = count(array_filter($tasks, fn($t) => empty($t['completed'])));
$completedCount = count(array_filter($tasks, fn($t) => !empty($t['completed'])));
$shownCount     = count($filtered);

$csrfToken = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>TaskPad PHP - Tasks</title>

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
            max-width: 1100px;
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

        .summary {
            margin: 1rem 0;
            font-size: 0.95rem;
            color: var(--muted);
        }

        .summary strong { color: var(--text); }

        .filters-form {
            display: flex;
            flex-wrap: wrap;
            gap: 0.9rem;
            align-items: flex-end;
        }

        .field-inline { max-width: 260px; }

        label {
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
            color: var(--muted);
        }

        input[type="text"],
        select {
            border-radius: 999px;
            border: 1px solid rgba(148,163,184,0.7);
            padding: 0.45rem 0.7rem;
            font-size: 0.9rem;
            outline: none;
            background: rgba(15,23,42,0.95);
            color: var(--text);
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }

        input:focus,
        select:focus {
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

        .btn-small {
            padding: 0.25rem 0.7rem;
            font-size: 0.78rem;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f87171, #ef4444);
            border-color: #fca5a5;
            color: #111827;
        }

        .flash {
            border-radius: 999px;
            padding: 0.5rem 0.9rem;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
        }

        .flash-success {
            background: var(--success-soft);
            color: var(--success);
            border: 1px solid rgba(74,222,128,0.6);
        }

        .flash-error {
            background: var(--danger-soft);
            color: var(--danger);
            border: 1px solid rgba(248,113,113,0.6);
        }

        .muted {
            color: var(--muted);
            font-size: 0.78rem;
        }

        .table-wrapper { width: 100%; overflow-x: auto; }

        .task-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }

        .task-table th,
        .task-table td {
            padding: 0.55rem 0.6rem;
            border-bottom: 1px solid rgba(31,41,55,0.9);
            text-align: left;
            vertical-align: top;
        }

        .task-table th {
            background: rgba(15,23,42,0.95);
            font-weight: 600;
        }

        .row-open:hover,
        .row-completed:hover {
            background: rgba(15,23,42,0.75);
        }

        .row-completed td {
            color: var(--muted);
            text-decoration: line-through;
        }

        .actions-col { white-space: nowrap; }

        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.12rem 0.55rem;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .badge-priority.badge-low {
            background: rgba(74,222,128,0.18);
            color: #4ade80;
        }

        .badge-priority.badge-medium {
            background: rgba(250,204,21,0.18);
            color: #facc15;
        }

        .badge-priority.badge-high {
            background: rgba(248,113,113,0.18);
            color: #f97373;
        }

        .badge-status.badge-open {
            background: rgba(56,189,248,0.2);
            color: #7dd3fc;
        }

        .badge-status.badge-completed {
            background: rgba(148,163,184,0.25);
            color: #e5e7eb;
        }

        .inline-form { display: inline-block; margin: 0 0.1rem; }

        @media (max-width: 720px) {
            .topbar .container {
                flex-direction: column;
                align-items: flex-start;
            }

            .filters-form {
                flex-direction: column;
                align-items: stretch;
            }

            .field-inline {
                max-width: 100%;
            }

            .table-wrapper {
                overflow: visible;
            }

            .task-table {
                border-collapse: separate;
                border-spacing: 0 0.75rem;
            }

            .task-table thead {
                display: none;
            }

            .task-table tr {
                display: block;
                background: rgba(15,23,42,0.95);
                border-radius: 0.9rem;
                padding: 0.7rem 0.8rem;
                box-shadow: 0 10px 30px rgba(15,23,42,0.9);
            }

            .task-table td {
                display: block;
                width: 100%;
                border-bottom: none;
                padding: 0.25rem 0;
            }

            .task-table td::before {
                content: attr(data-label);
                display: block;
                font-size: 0.7rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: var(--muted);
                margin-bottom: 0.05rem;
            }

            .actions-col {
                margin-top: 0.4rem;
            }

            .actions-col .inline-form {
                display: inline-flex;
                margin: 0.15rem 0.15rem 0 0;
            }

            .btn-small {
                width: auto;
            }
        }
    </style>
</head>
<body>
<header class="topbar">
    <div class="container">
        <h1>TaskPad PHP</h1>
        <nav>
            <a href="create.php" class="btn btn-primary">+ New Task</a>
        </nav>
    </div>
</header>

<main class="container">
    <?php if ($flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <section class="summary">
        <p>
            Total: <strong><?= $totalCount ?></strong> ·
            Open: <strong><?= $openCount ?></strong> ·
            Completed: <strong><?= $completedCount ?></strong> ·
            Showing: <strong><?= $shownCount ?></strong>
        </p>
    </section>

    <section class="card">
        <h2>Filters</h2>
        <form method="get" action="index.php" class="filters-form">
            <div class="field-inline">
                <label>
                    Text search
                    <input
                        type="text"
                        name="q"
                        value="<?= e($q) ?>"
                        placeholder="Search title/description"
                    >
                </label>
            </div>

            <div class="field-inline">
                <label>
                    Priority
                    <select name="priority">
                        <option value="">Any</option>
                        <option value="Low"    <?= $priorityFilter === 'Low' ? 'selected' : '' ?>>Low</option>
                        <option value="Medium" <?= $priorityFilter === 'Medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="High"   <?= $priorityFilter === 'High' ? 'selected' : '' ?>>High</option>
                    </select>
                </label>
            </div>

            <div class="field-inline">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="index.php" class="btn btn-ghost">Clear</a>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Tasks</h2>

        <?php if ($totalCount === 0): ?>
            <p>No tasks yet. <a href="create.php">Create your first task.</a></p>
        <?php elseif ($shownCount === 0): ?>
            <p>No tasks matched your filters.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="task-table">
                    <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Priority</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th class="actions-col">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($filtered as $task): ?>
                        <tr class="<?= !empty($task['completed']) ? 'row-completed' : 'row-open' ?>">
                            <td data-label="Title"><?= e($task['title'] ?? '') ?></td>
                            <td data-label="Description"><?= e($task['description'] ?? '') ?></td>
                            <td data-label="Priority">
                                <span class="badge badge-priority badge-<?= e(strtolower($task['priority'] ?? 'low')) ?>">
                                    <?= e($task['priority'] ?? '') ?>
                                </span>
                            </td>
                            <td data-label="Due">
                                <?php if (!empty($task['due'])): ?>
                                    <?= e($task['due']) ?>
                                <?php else: ?>
                                    <span class="muted">No due date</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Status">
                                <?php if (!empty($task['completed'])): ?>
                                    <span class="badge badge-status badge-completed">Completed</span>
                                <?php else: ?>
                                    <span class="badge badge-status badge-open">Open</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions-col" data-label="Actions">
                                <?php if (empty($task['completed'])): ?>
                                    <form method="post" action="actions.php" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="complete">
                                        <input type="hidden" name="id" value="<?= e($task['id']) ?>">
                                        <button type="submit" class="btn btn-small">Complete</button>
                                    </form>
                                <?php endif; ?>

                                <form method="post" action="actions.php" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= e($task['id']) ?>">
                                    <button
                                        type="submit"
                                        class="btn btn-small btn-danger"
                                        onclick="return confirm('Delete this task?');"
                                    >
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
