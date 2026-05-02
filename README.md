# TaskPad PHP

A lightweight task management application built with PHP that allows users to create, organize, complete, filter, and delete tasks through a clean web interface.

Tasks are stored in a local JSON file, making the project simple to run without requiring a database setup.

---

## Features

- Create new tasks
- Mark tasks as completed
- Delete tasks
- Filter tasks by status
- Persistent JSON-based storage
- Automated black-box testing support
- Simple and minimal PHP architecture

---

## Tech Stack

- PHP 7.4+ / 8.x
- HTML/CSS
- JSON file storage
- CLI-based automated testing

---

## Project Structure

```bash
taskpad-php/
│
├── public/              # Public web pages
├── tests/               # Automated test runner and test cases
├── storage/             # JSON task storage
├── docs/                # Screenshots and UML diagrams
└── README.md
```

---

## Requirements

Before running the project, make sure you have:

- PHP 7.4 or newer
- Terminal / Command Prompt access

Check your PHP version:

```bash
php -v
```

---

## Running the Application

From the project root directory, start the PHP development server:

```bash
php -S localhost:8080 -t public
```

Then open your browser and navigate to:

| Page | URL |
|---|---|
| Task List | `http://localhost:8080/index.php` |
| Create Task | `http://localhost:8080/create.php` |

---

## Running Automated Tests

From the project root:

```bash
php tests/run_tests.php
```

This will execute the automated black-box test suite defined in:

```bash
tests/test_cases.json
```

The console will display a pass/fail report for each test case.

---

## Screenshots & UML Diagrams

Project documentation, screenshots, and UML diagrams are included in the `docs/` folder.

Included documentation:

- Task creation interface
- Task list view
- Complete/delete actions
- Use Case Diagram
- Class Diagram
- Activity Diagram

---

## Design Goals

This project was designed to demonstrate:

- Core PHP development skills
- CRUD operations without a database
- File-based persistence using JSON
- Basic software testing workflows
- Clean project organization

---

## Future Improvements

Potential enhancements for future versions:

- User authentication
- Due dates and task priorities
- SQLite/MySQL database integration
- Responsive UI improvements
- REST API support
- Search functionality

---

## Author

**Mohamad Syaj**