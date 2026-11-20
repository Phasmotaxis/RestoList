# Student AI Troubleshooting Prompt Templates

- **Root Hub**: [Getting Started Runbook](../../GETTING-STARTED.md)
- **Handbook Index**: [Getting Started Handbook](../README.md)
- **Troubleshooting Guide**: [Windows Troubleshooting](troubleshooting.md)
- **Task Ledger**: [Subsystem Task Registry](../../dev/ai/tasks.md)

---

## Document Purpose & Audience
- **What this is for**: Structured AI prompt templates for students to debug container and PHP errors without AI hallucination.
- **Who reads this**: Students seeking guided troubleshooting assistance from AI tools.
- **Last verified**: 2026-11-19 (UNVERIFIED: current host is Linux x86_64; Windows/WSL2 AI prompt flows were not evaluated on Windows host).

---

## Core Definitions
- **Terminal**: The command window where you copy error text to paste into AI prompts.
- **Container**: The Docker runtime instances mentioned in the prompt templates.
- **Environment variable**: The configuration keys (like `DB_CONNECTION`) passed to scripts.

---

## 1. How to Use These Templates
When encountering confusing errors during development, copy one of the structured templates below and paste it into Claude or ChatGPT. Providing exact operating system details, terminal errors, and code prevents AI hallucination and avoids introducing breaking regressions.

---

## 2. Template 1: General Terminal & Container Error

```text
I am working on our university Enterprise Information Management System (EIMS) project.
Operating System: Windows 11 (WSL2 Ubuntu 22.04)
Command I ran: [PASTE COMMAND HERE, e.g. just up OR just test]

Exact error message:
```
[PASTE ENTIRE TERMINAL OUTPUT AND ERROR STACK HERE]
```

Project constraints:
- Pure clean-room vanilla PHP 8.3 with declare(strict_types=1);
- Zero external backend frameworks (no Laravel/Symfony).
- MySQL 8.0 Primary with SQLite3 local fallback.
- Docker Compose container architecture (eims_web, eims_app, eims_db).

Please analyze what caused this error, explain it in simple terms, and provide the exact command or code edit to fix it without introducing breaking changes.
```

---

## 3. Template 2: PHP Syntax or Type Error

```text
I encountered a PHP runtime or syntax error in our EIMS university codebase.
File path: [PASTE REPO-RELATIVE PATH HERE, e.g. src/app/Controllers/AdminController.php]

Exact error message:
```
[PASTE PHP ERROR / STACK TRACE HERE]
```

Relevant code snippet:
```php
[PASTE AROUND 10-20 LINES OF RELEVANT PHP CODE HERE]
```

Please explain why PHP threw this error under strict typing (declare(strict_types=1);), show the corrected snippet, and explain the difference so I understand the concept.
```

---

## 4. Template 3: Database Migration or SQL Failure

```text
Our EIMS database migration or SQL query failed.
Driver: [MySQL 8.0 / SQLite3]
Command: python3 dev/scripts/py/migrate.py --migrate

Exact error:
```
[PASTE ERROR OUTPUT HERE]
```

SQL Statement that failed:
```sql
[PASTE FAILING SQL STATEMENT HERE]
```

Please provide ANSI SQL syntax that works seamlessly across both MySQL 8.0 and SQLite3 with parameterized PDO bindings.
```
