TaskPad PHP is a minimal task tracke.  
Users can create tasks, filter them, mark them complete, and delete them.  
Tasks are stored in a JSON file on the server (no database).
---
Requirements

- PHP 7.4+ or 8.x
- Command line access (Terminal on macOS, PowerShell/Command Prompt on Windows)

---
How to Run the App

From the project root (`taskpad-php`), run:

```bash
php -S localhost:8080 -t public
Then open your browser and go to:

http://localhost:8080/index.php — Task list & filters

http://localhost:8080/create.php — Create new task

How to Run Tests
From the project root:

bash
Copy code
php tests/run_tests.php
This will execute the automated black-box test cases defined in tests/test_cases.json
and print a pass/fail report to the console.

Screenshots and Diagrams
All required screenshots and UML diagrams for this project are included in the document/folder:

Project-3-CIS435

This contains:

Screenshots (create form, list view, complete/delete actions)

UML diagrams (Use Case, Class, and optional Activity)# Taskpad-php
