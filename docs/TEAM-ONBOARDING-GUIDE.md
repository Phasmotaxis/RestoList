# DCIT 24 Enterprise Information Management System (EIMS): Team Onboarding Handbook & Engineering Standards

- **Course**: DCIT 24 - Information Management (Academic Year 2026-2027)
- **Institution**: Department of Information Technology, Cavite State University (CvSU) - Main Campus
- **Project**: Enterprise Information Management System (EIMS) - Multi-Tenant Restaurant ERP & Reservation Engine
- **Target Audience**: All 8 Project Team Members (from absolute beginners to core kernel contributors)
- **Document Status**: OFFICIAL OPERATIONAL STANDARD FOR TEAM COLLABORATION & CODE HYGIENE
- **Companion Documents**: [Project README](../README.md) | [Root Contributing Guide](../CONTRIBUTING.md) | [54-Ticket Atomic Volunteer Task Queue](TEAM-TASK-QUEUE.md) | [Department Role Backlog](TEAM-ALLOCATION-TASKS.md) | [Technical Architecture Overview](TEAM-ALLOCATION.md) | [Evaluator Demo Runbook](DEMO-RUNBOOK.md)

---

## Table of Contents
1. [Chapter 1: The Big Picture & Architectural Context](#chapter-1-the-big-picture--architectural-context)
2. [Chapter 2: Getting Started & Local Environment](#chapter-2-getting-started--local-environment)
3. [Chapter 3: The Golden Workflow (Issue -> Branch -> Atomic Commit -> PR -> Review)](#chapter-3-the-golden-workflow-issue---branch---atomic-commit---pr---review)
4. [Chapter 4: Resolving Merge Conflicts Without Crying](#chapter-4-resolving-merge-conflicts-without-crying)
5. [Chapter 5: Social Workflow & Messenger Protocol (For Non-Git / Beginner Members)](#chapter-5-social-workflow--messenger-protocol-for-non-git--beginner-members)
6. [Chapter 6: Teammate AI Companion Prompt](#chapter-6-teammate-ai-companion-prompt)
7. [Appendix: FAQ & Common Troubleshooting for College Students](#appendix-faq--common-troubleshooting-for-college-students)

---

## Chapter 1: The Big Picture & Architectural Context

### 1.1 What is EIMS?
The Enterprise Information Management System (EIMS) is a high-assurance, multi-tenant restaurant enterprise resource planning (ERP) platform and real-time table reservation engine. Engineered as our capstone deliverable for DCIT 24 (Information Management) at Cavite State University (CvSU) Main Campus, EIMS demonstrates rigorous computer science foundations alongside battle-tested information technology infrastructure.

The platform provides complete multi-organization isolation. A single deployment hosts multiple independent restaurant brands (such as *Hiraya Bistro* and *Katipunan Grill*) beneath a unified platform governance plane managed by university campus administrators (CvSU Main Commissary SuperAdmin).

### 1.2 What Real-World Problems Does EIMS Solve?
Modern hospitality and food service enterprises suffer from operational fragmentation, data loss, and concurrency races:
1. **Multi-Location Franchise Management**: Independent restaurant branches require isolated dining zones, custom menu pricing, localized staff permissions, and independent table layouts while allowing parent franchises or campus commissaries to audit system-wide telemetry.
2. **Race-Condition Double Bookings**: When multiple diners attempt to reserve the same table for Valentine's Day dinner simultaneously, traditional naive web applications overwrite each other or double-book tables. EIMS introduces a mathematical two-layer concurrency control engine (`TableAllocator`) that enforces discretized 15-minute slot bucketing with strict database-level unique constraints.
3. **Supply Chain and Recipe Bill of Materials (BOM)**: Dishes linked to real-time ingredient inventories automatically notify the kitchen when stock drops. When an ingredient is depleted, the system flags the dish on the real-time "86-List" (sold-out registry) to prevent waiters from taking orders that cannot be fulfilled.
4. **Auditability and Tamper-Resistant Financial Ledgers**: Cash deposits and customer loyalty points are recorded using append-only immutable double-entry style accounting ledgers, preventing floating-point rounding errors through integer centavo representations.

### 1.3 Tech Stack Rationale
Every technology choice in EIMS was selected deliberately to maximize reliability, evaluation survivability, and transparency:

| Technology | Architectural Choice | Why We Chose It (The Engineering Rationale) |
| :--- | :--- | :--- |
| **Backend Runtime** | **Native PHP 8.3** (Strict Types) | Zero heavy framework bloat (no Laravel, no Symfony). Starts in milliseconds, runs seamlessly on standard XAMPP or Docker, and provides 100% auditable code during live oral panel defense. Every file declares `declare(strict_types=1);`. |
| **Data Persistence** | **Dual-Driver MySQL 8.0 + SQLite3 Fallback** | Primary production runs on MySQL 8.0. If MySQL crashes, encounters port conflicts, or is killed during live panel sabotage evaluation, `App\Core\Database` automatically catches the exception and switches persistence to local `storage/fallback.sqlite` with zero human intervention. |
| **Frontend Architecture** | **Clean-Room Semantic CSS3 & HTML5** | Zero NPM build tools, zero Node.js daemon dependencies, and zero Tailwind/Bootstrap CSS bloat. Interfaces use native CSS Custom Properties, CSS Grid, and flexbox. Guaranteed air-gapped performance that renders perfectly even if venue internet fails. |
| **Academic Monograph** | **XeLaTeX & latexmk** | A 60+ page academic monograph typeset in XeLaTeX with Biber bibliographies. Generates publication-grade PDF documentation reflecting true computer science research standards. |

### 1.4 The 18-Table Data Model at a Glance
Our database schema is structured into 5 cohesive functional domains:

```
+---------------------------------------------------------------------------------------------------+
|                                 EIMS 18-TABLE RELATIONAL DATA MODEL                               |
+---------------------------------------------------------------------------------------------------+
|  1. IDENTITY & RBAC       2. FLOOR & SPATIAL      3. RESERVATION ENGINE   4. KITCHEN & INVENTORY  |
|  - tenants                - dining_zones          - reservations          - ingredients           |
|  - roles                  - dining_tables         - reservation_tables    - menu_items            |
|  - permissions                                    - reservation_slots     - menu_item_recipes     |
|  - users                                          - waitlist_entries      - purchase_orders       |
|  - login_attempts                                                         - stock_movements       |
|  - audit_logs                                                                                     |
|  - org_feature_flags                                                      5. DINER & LEDGERS      |
|  - org_invites                                                            - reviews               |
|                                                                           - deposit_ledger        |
|                                                                           - loyalty_ledger        |
+---------------------------------------------------------------------------------------------------+
```

- **Domain 1: Core Identity, RBAC & Governance** (`tenants`, `roles`, `permissions`, `users`, `login_attempts`, `audit_logs`, `org_feature_flags`, `org_invites`): Manages multi-tenant isolation, bitwise role-based access control, cryptographic password hashing, brute-force rate limiting, and immutable administrative audit logs.
- **Domain 2: Floor Plan & Spatial Layout** (`dining_zones`, `dining_tables`): Maps physical restaurant dining rooms into discrete 12x8 floor plan grids with coordinate positioning (`coord_x`, `coord_y`), table capacities, and combinable table flags.
- **Domain 3: Reservation Engine & Concurrency Control** (`reservations`, `reservation_tables`, `reservation_slots`, `waitlist_entries`): Coordinates booking lifecycles, 15-minute slot discretization, dynamic waitlist queue management, and automated cancellation backfilling.
- **Domain 4: Kitchen, Menu & Inventory Management** (`ingredients`, `menu_items`, `menu_item_recipes`, `purchase_orders`, `stock_movements`): Handles dish catalogs, Recipe Bill of Materials (BOM), real-time 86-list toggling, supplier purchase orders, and stock movements.
- **Domain 5: Customer Engagement & Financial Ledgers** (`reviews`, `deposit_ledger`, `loyalty_ledger`): Houses customer ratings with anti-spam auto-moderation, immutable deposit ledger balances in integer centavos, and diner loyalty points accounting.

### 1.5 Key Invariants Every Teammate Must Respect
1. **Strict Type Safety**: Every single PHP file must start with `<?php declare(strict_types=1);`.
2. **Zero Inline Styles**: Never write `<div style="...">`. All styles must be defined in modular semantic stylesheets (`base.css`, `app.css`, or theme files) using CSS custom properties.
3. **Multi-Tenant Isolation**: Every operational SQL query touching tenant resources must explicitly filter by `org_id = :org_id` or `tenant_id = :tenant_id`. Cross-tenant data leakage is a critical defect.
4. **Driver-Portable ANSI-SQL**: All SQL queries must execute identically on both MySQL 8.0 and SQLite3. Avoid vendor-specific SQL extensions.
5. **Pessimistic UI Synchronization**: The database is the single source of truth. Never update the DOM or hide elements optimistically before receiving an HTTP 2xx confirmation from the server.

---

## Chapter 2: Getting Started & Local Environment

### 2.1 Cloning the Repository & Directory Layout
Clone the project from GitHub and navigate to the project directory:

```bash
git clone https://github.com/whinee/dict24-resto-erp.git
cd dict24-resto-erp
```

Here is how the project repository is organized:

```
dict24-resto-erp/
├── .github/                   # GitHub workflows, Issue and PR templates
│   ├── ISSUE_TEMPLATE/        # Task ticket issue templates
│   └── PULL_REQUEST_TEMPLATE.md
├── dev/                       # Developer automation and test harnesses
│   ├── scripts/               # Helper scripts (bash, python, powershell)
│   └── tests/                 # Integration test suite files
├── docs/                      # Comprehensive documentation hub
│   ├── dev/                   # Architectural ADRs, contracts, task ledgers
│   ├── getting-started/       # Linux and Windows installation runbooks
│   ├── TEAM-ALLOCATION.md     # Technical orchestration overview
│   ├── TEAM-ALLOCATION-TASKS.md # Department role backlog
│   ├── TEAM-ONBOARDING-GUIDE.md # This onboarding handbook
│   └── TEAM-TASK-QUEUE.md     # 54-ticket atomic volunteer backlog
├── paper/                     # Academic monograph (XeLaTeX source and figures)
├── src/                       # Application source code
│   ├── app/
│   │   ├── Controllers/       # HTTP Request Controllers
│   │   ├── Core/              # Database, Router, Request, ErrorHandler kernels
│   │   ├── Middleware/        # Auth, CSRF, and Role middlewares
│   │   ├── Services/          # Business logic (TableAllocator, Ledgers)
│   │   └── Views/             # Semantic HTML view templates
│   ├── database/              # Schema definitions and seed SQL
│   └── public/                # Public document root (index.php, assets)
│       └── assets/css/        # Pure CSS design system and themes
├── tests/                     # Test runner entrypoint (tests/runner.php)
├── justfile                   # Task runner commands
├── CONTRIBUTING.md            # Root contributor guidelines
└── README.md                  # Main project landing page
```

### 2.2 Verifying System Health with `just doctor`
The project includes an automated diagnostic doctor script. Run it from your terminal:

```bash
just doctor
```

The script checks for PHP 8.3, required extensions (`pdo`, `pdo_mysql`, `pdo_sqlite`, `mbstring`, `json`), Docker availability, port conflicts on 80/3306, and disk write permissions.

### 2.3 Booting the Application Stack

You have two options for running the project locally. Choose the option that matches your setup:

#### Option A: Docker Compose (Recommended for Mac / Linux / WSL2)
Docker provides an isolated container environment matching the live evaluation environment:

```bash
# 1. Copy local environment variables
cp .env.example .env

# 2. Boot containers (Nginx, PHP-FPM, MySQL)
just up

# 3. Open in browser
# Visit: http://localhost:8080 or http://localhost
```

To stop containers when finished:
```bash
just down
```

#### Option B: Native XAMPP (Recommended for Windows)
If you prefer running XAMPP without Docker:
1. Extract or clone the project into your XAMPP `htdocs` directory:
   `C:\xampp\htdocs\dict24-resto-erp`
2. Open XAMPP Control Panel and start **Apache** and **MySQL**.
3. Open your browser and go to phpMyAdmin: `http://localhost/phpmyadmin/`
4. Create a new database named `eims_db` with collation `utf8mb4_unicode_ci`.
5. Click **Import** and select `src/database/dict24_resto_erp.sql` to import the schema and baseline data.
6. Open the application in your browser:
   `http://localhost/dict24-resto-erp/src/public/` (or `http://localhost/dict24-resto-erp/`).

### 2.4 Running Verification Suites
Before making any changes, verify that the existing codebase passes all checks:

```bash
# Method 1: Using justfile
just test

# Method 2: Running test runner directly with PHP
php tests/runner.php
```

You should see all integration tests pass with `[SUCCESS] All integration tests passed (exit code 0)`.

### 2.5 Accessing Pre-Configured Test Accounts
Use these pre-seeded credentials to explore the system. Every account uses the password: `Password123!`

| Role | Email | Organization / Tenant | What You Can Test |
| :--- | :--- | :--- | :--- |
| **SuperAdmin** | `superadmin@cvsu.edu.ph` | CvSU Main Commissary | Platform dashboard, tenant provisioning, audit logs, feature flags |
| **Admin** | `manager@hirayabistro.ph` | Hiraya Bistro | Restaurant operations, menu items, table management, floor plan |
| **FrontOfHouse**| `foh@cvsu.edu.ph` | Hiraya Bistro | Floor plan view, table status updates, in-person bookings |
| **Kitchen** | `kitchen@cvsu.edu.ph` | Hiraya Bistro | Recipe Bill of Materials (BOM), 86-list toggling, ingredient stock |
| **Staff** | `user@cvsu.edu.ph` | CvSU Main | Self-service shift view, personal data export |
| **Customer** | `customer@cvsu.edu.ph` | Hiraya Bistro Portal | Public discovery, online reservation booking, waitlist queue |

---

## Chapter 3: The Golden Workflow (Issue -> Branch -> Atomic Commit -> PR -> Review)

To maintain software quality across all 8 teammates, we follow a strict six-step engineering workflow.

```
+---------------------------------------------------------------------------------------------------+
|                                      THE GOLDEN GIT WORKFLOW                                      |
+---------------------------------------------------------------------------------------------------+
|  1. CLAIM TICKET  -->  2. BRANCH MAIN  -->  3. ATOMIC COMMITS  -->  4. LOCAL QA  --> 5. OPEN PR   |
|     Pick ticket        git pull origin         Stage specific           php -l         Link issue |
|     from queue or      git checkout -b         files, write             Run tests      Wait for   |
|     GitHub Issue       feat/TSK-xxx-...        Conventional Msg         Check CSP      2 reviews  |
+---------------------------------------------------------------------------------------------------+
```

### Rule 0: `main` is Sacred
**Never push directly to `main`.** Direct pushes to `main` are blocked. All code must arrive on `main` through a reviewed and approved Pull Request.

### Step 1: Pick an Atomic Ticket
- Open `docs/TEAM-TASK-QUEUE.md` and choose an unassigned ticket (e.g., `TSK-DOC-01`, `TSK-MEN-02`, `TSK-SLD-03`).
- Alternatively, check the GitHub Issues tab for available tickets.
- Follow the Messenger Claiming Protocol (see Chapter 5) before starting.

### Step 2: Sync and Branch Off Latest `main`
Always create your feature branch from an up-to-date `main` branch:

```bash
# 1. Switch to main
git checkout main

# 2. Pull the latest commits from GitHub
git pull origin main

# 3. Create and switch to your feature branch
git checkout -b feat/TSK-MEN-01-filipino-appetizers
```

#### Branch Naming Conventions:
Use a clear prefix, your ticket ID, and a 2-4 word hyphenated description:
- `feat/TSK-xxx-short-description` (New features, new pages, or new data seeds)
- `fix/TSK-xxx-short-description` (Bug fixes, layout adjustments, typo corrections)
- `docs/TSK-xxx-short-description` (Markdown guides, user manuals, documentation)
- `test/TSK-xxx-short-description` (New automated tests, QA verification scripts)

*Good Examples:*
- `feat/TSK-MEN-01-filipino-appetizers`
- `docs/TSK-DOC-02-admin-user-manual`
- `fix/TSK-QA-03-chrome-form-alignment`

*Bad Examples:*
- `my-branch` (Meaningless name)
- `updates` (No ticket reference, no context)
- `john-work` (Personal name instead of feature scope)

### Step 3: Making Atomic Commits (Crucial Deep Dive)

#### What is an Atomic Commit?
An **atomic commit** is a single, indivisible logical unit of work. It does one thing, does it completely, and leaves the repository in a working, compilable state with passing tests.

#### Why Atomic Commits Matter:
1. **Effortless Code Reviews**: Teammates can review small, clear diffs without getting overwhelmed.
2. **Safe Bisecting & Debugging**: If a bug appears, `git bisect` can pinpoint the exact commit that introduced it.
3. **Clean Reverts**: If a feature needs to be rolled back, an atomic commit can be reverted with `git revert <hash>` without breaking unrelated parts of the codebase.

#### The Deadly Anti-Patterns (Strictly Forbidden!):
- **NEVER use `git add .` or `git add -A`**: Bulk staging accidentally stages temporary files, editor settings (`.vscode/`), database dumps, and credentials.
- **NEVER use `git commit -am "updates"`**: Combines indiscriminate staging with lazy commit messages.
- **NEVER use lazy commit messages**: Messages like "fixed stuff", "wip", "update", "asdf", or "changes" will be rejected during code review.

#### The Golden Staging Practice:
Always stage specific, intentional files:

```bash
# 1. Inspect what files you touched
git status

# 2. Stage only the files relevant to this atomic step
git add src/app/Views/customer/restaurant.php

# 3. Review the exact staged changes before committing
git diff --staged

# 4. Commit with a Conventional Commit message
git commit -m "feat(customer): display allergen warning badges on dish cards"
```

#### Conventional Commit Messages:
Every commit message must follow the Conventional Commits format:

```text
type(scope): imperative summary

[optional body explaining WHY the change was made]
```

- **Allowed Types**: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`.
- **Imperative Voice**: Write "add feature", not "added feature" or "adds feature".
- **No Ticket Numbers in the Commit Title**: Keep commit summaries clean and human-readable. Reference tickets in the PR description or issue tracker.

*Examples of Good Commits:*
- `feat(menu): add authentic Bicol Express recipe and BOM entries`
- `docs(onboarding): clarify XAMPP port 80 conflict resolution steps`
- `fix(table-allocator): resolve 15-minute floor boundary rounding error`
- `test(auth): add negative assertion for invalid CSRF tokens`

*Examples of Bad Commits:*
- `git commit -m "wip"` (Vague, incomplete)
- `git commit -m "fixed bugs and added stuff and edited css"` (Lacks atomicity; should be 3 separate commits)
- `git commit -m "TSK-MEN-01 completed"` (Ticket pollution; describes task metadata instead of code change)

### Step 4: Pre-Push Local Quality Gate
Before pushing your branch to GitHub, run this 3-step verification check:

```bash
# 1. PHP Syntax Pre-Flight Validation (Must return zero syntax errors)
find src/ -name "*.php" -exec php -l {} \; | grep -v "No syntax errors detected" || echo "All PHP files have valid syntax!"

# 2. Run the Full Integration Test Suite (Must exit code 0)
php tests/runner.php

# 3. Check for Zero Inline Styles (Must return zero matches)
grep -rn "style=" src/app/Views/ || echo "Zero inline styles confirmed!"
```

If any check fails, fix the issue locally before pushing.

### Step 5: Pushing and Opening a Pull Request
Once your local checks pass, push your branch to GitHub:

```bash
# Push your branch and set upstream tracking
git push -u origin feat/TSK-MEN-01-filipino-appetizers
```

Then open GitHub in your browser:
1. Navigate to the repository: `https://github.com/whinee/dict24-resto-erp`
2. You will see a yellow banner: *"feat/TSK-MEN-01-filipino-appetizers had recent pushes"*. Click **Compare & pull request**.
3. Ensure the base branch is `main` and the compare branch is your feature branch.
4. Fill out the Pull Request template completely:
   - Link the Ticket ID (e.g., `Resolves TSK-MEN-01`).
   - Summarize what you changed.
   - Check the quality gate verification boxes.
   - Attach screenshots or command outputs as visual evidence.
5. Click **Create pull request**.

### Step 6: "Respectfully Wait" & Code Review Etiquette
Opening a PR is a collaborative milestone:
1. **Notify the Team in Messenger**: Drop your PR link in our Messenger group chat:
   ```text
   🚀 PR OPEN FOR REVIEW: feat/TSK-MEN-01-filipino-appetizers
   PR Link: https://github.com/whinee/dict24-resto-erp/pull/12
   Ticket: TSK-MEN-01 (Filipino Appetizers Menu Data)
   Please review and leave feedback or approval! 👍
   ```
2. **Review Etiquette**:
   - Every PR requires review and approval from at least **1 peer reviewer** or the **Project Lead**.
   - Reviewers check: Does it satisfy the ticket Definition of Done? Does it introduce inline styles? Are tests passing?
3. **Addressing Review Feedback**:
   - If a reviewer requests changes, make the fix on your local branch, commit it atomically, and push. GitHub will update the PR automatically.
   - Respond politely and constructively in the PR thread.
4. **Merge**: Once approvals are given and CI passes, the Project Lead merges the branch to `main`.

---

## Chapter 4: Resolving Merge Conflicts Without Crying

### 4.1 What Causes a Merge Conflict?
A merge conflict occurs when two branches make different changes to the exact same line of code in the same file, or when one branch deletes a file that another branch modified. Git pauses and asks a human: *"Which version do you want to keep?"*

Merge conflicts are completely normal in software development. They are not mistakes or disasters.

### 4.2 Step-by-Step Conflict Resolution Workflow
If GitHub shows *"This branch has conflicts that must be resolved"*, follow these steps locally:

```bash
# Step 1: Switch to your feature branch
git checkout feat/your-branch

# Step 2: Fetch the latest changes from GitHub
git fetch origin

# Step 3: Merge latest main into your feature branch
git merge origin/main
```

Git will notify you: `CONFLICT (content): Merge conflict in src/...`.

#### Understanding Git Conflict Markers:
Open the conflicting file in your text editor (VS Code, Notepad++, or Sublime). Look for Git's conflict markers:

```text
<<<<<<< HEAD (Your Branch Changes)
$pageTitle = 'Hiraya Bistro: Reservation Portal';
=======
$pageTitle = 'Hiraya Bistro: Online Table Booking';
>>>>>>> origin/main (Changes from Main)
```

- Everything between `<<<<<<< HEAD` and `=======` is what **you** wrote on your branch.
- Everything between `=======` and `>>>>>>> origin/main` is what **another teammate** merged into `main`.

#### How to Resolve:
1. Decide with your groupmate which code to keep (or combine the best parts of both).
2. Delete the conflict markers (`<<<<<<<`, `=======`, `>>>>>>>`).
3. Save the clean file.

#### Complete the Merge:
```bash
# Step 4: Verify the resolved file
git status

# Step 5: Stage the resolved file explicitly
git add src/app/Views/customer/booking.php

# Step 6: Commit the merge resolution
git commit -m "merge: resolve conflicts with main"

# Step 7: Run verification tests to ensure nothing broke
php tests/runner.php

# Step 8: Push the resolved branch back to GitHub
git push origin feat/your-branch
```

The PR on GitHub will automatically detect that conflicts are resolved and display green checkmarks!

### 4.3 Golden Rule of Conflicts
**When in doubt, stop and ask.** If you are unsure which code to keep during conflict resolution, pause and ask the Project Lead or your groupmate in Messenger. Never guess or blindly delete someone else's work.

---

## Chapter 5: Social Workflow & Messenger Protocol (For Non-Git / Beginner Members)

Not every task requires writing PHP or touching Git commands. For copywriting, slide presentations, video narration, manual browser testing, and user guides, teammates can work entirely through our Facebook Messenger protocol and standard student tools.

### 5.1 The Zero-Git Collaboration Flow

```
+---------------------------------------------------------------------------------------------------+
|                                  ZERO-GIT COLLABORATION PIPELINE                                  |
+---------------------------------------------------------------------------------------------------+
|  1. DOWNLOAD       2. PRODUCE           3. SUBMIT           4. LEAD COMMITS                       |
|  Assets/Zips  -->  Work in Google  -->  Send Link/File  --> Lead Stages,                          |
|  from Drive/       Docs, Canva, Word,   via Messenger/      Verifies & Pushes                     |
|  GitHub Web        Notepad, or OBS      Google Drive        to Repository                         |
+---------------------------------------------------------------------------------------------------+
```

1. **Download Assets Without Git**: Download project screenshots, assets, or code zips from our Google Drive or via the green **Code -> Download ZIP** button on GitHub.
2. **Work in Familiar Tools**:
   - Writing & Copywriting: Google Docs, Microsoft Word, Notepad.
   - Slide Decks & Graphics: Canva, PowerPoint, Google Slides.
   - Audio & Video Recording: OBS Studio, Audacity, smartphone voice recorder.
   - Manual Testing: Chrome, Edge, Safari, Firefox.
3. **Submit via Messenger**: Share your deliverable link in our Messenger group chat with sharing permissions set to *"Anyone with the link can view"*.
4. **Project Lead Handles Version Control**: The Project Lead reviews your deliverable, integrates it into the repo, attributes you in the commit history, and marks your ticket complete!

### 5.2 Facebook Messenger Ticket Claiming Rules

#### Rule 1: Check Availability First
- Check `docs/TEAM-TASK-QUEUE.md` or the pinned post in Messenger.
- Only claim tickets marked `[ ] Unassigned (Volunteer)`.
- **WIP Limit = 1**: You may only hold one active ticket at a time.

#### Rule 2: Post Claiming Template
Post this exact message in the group chat:

```text
🎯 CLAIMING TICKET: [TICKET-ID] (e.g. TSK-MEN-01)
Task Title: [Task Title]
Estimated Completion: [e.g., Today 8:00 PM / Tomorrow 2:00 PM]
React 👍 to confirm!
```

#### Rule 3: Reaction Gate (2 👍 Reactions)
Wait until at least **2 teammates** or the **Project Lead** react with thumbs up (`👍`). If anyone reacts with `✋` or questions the claim, pause and coordinate.

#### Rule 4: Submission Template
When your work meets all items in the ticket Definition of Done, post:

```text
✅ COMPLETED TICKET: [TICKET-ID]
Deliverable Link: [Google Docs / Google Drive / Attached File]
Summary: [2-3 sentences explaining what was written/tested]
```

### 5.3 The Google Docs to LaTeX Conversion Bridge
Our 60+ page academic monograph (`paper/main.pdf`) is compiled with XeLaTeX, but writing members do **not** need to install LaTeX:
1. Write your assigned section in Google Docs or Microsoft Word using plain headings, bullet lists, and tables.
2. Convert your draft using the **LaTeX AI Converter Prompt** below, or share your Google Docs link in chat for the Lead to convert.
3. Review the compiled PDF shared in Google Drive.

#### LaTeX AI Converter Prompt:
Paste this prompt into ChatGPT or Claude along with your written draft:

```text
You are an expert LaTeX typesetter formatting an academic monograph for Cavite State University (CvSU) for DCIT 24 Information Management.
Convert the following plain text into clean, compilable LaTeX syntax suitable for inclusion in our paper sections.

Formatting rules:
1. Use standard sectioning: \section{...}, \subsection{...}, \subsubsection{...}. Provide sensible \label{...} tags.
2. Format bullet lists with \begin{itemize} \item ... \end{itemize} and numbered lists with \begin{enumerate} \item ... \end{enumerate}.
3. Format code terms, file paths, and database identifiers using \texttt{...}.
4. Escape special characters like %, _, &, and # properly (\%, \_, \&, \#).
5. Format tables using standard \begin{table}[htbp] \centering \begin{tabular}{...} ... \end{tabular} \caption{...} \label{...} \end{table}.
6. Use proper quotes (`` and '').
7. Return ONLY the LaTeX code block without conversational filler.

Text to convert:
[PASTE YOUR DRAFT TEXT HERE]
```

---

## Chapter 6: Teammate AI Companion Prompt

To ensure every teammate feels supported and never gets stuck, use our dedicated university project mentor prompt. Copy and paste this into **ChatGPT**, **Claude**, or **Google Gemini** whenever starting a ticket:

```text
You are my friendly university project mentor for DCIT 24 Information Management at Cavite State University.
Our project is an Enterprise Restaurant ERP & Reservation System (EIMS).
I am a student working on ticket: [INSERT TICKET ID].
Ticket details: [PASTE TICKET DETAILS FROM docs/TEAM-TASK-QUEUE.md].

Please explain what I need to do step-by-step in simple non-technical terms, help me draft the content, and review my deliverable against the ticket's Definition of Done before I submit it to our team group chat.
```

---

## Appendix: FAQ & Common Troubleshooting for College Students

### Q1: What if I don't know PHP or backend programming?
**You do not need to know PHP.**
The entire backend engine (database schemas, TableAllocator concurrency engine, bitwise RBAC, dual SQLite/MySQL drivers, and session handlers) is 100% finished and verified by the Project Lead. The remaining volunteer tickets focus on video narration, slide deck creation, food copywriting, photo curation, manual browser testing, user guides, and oral defense practice. Every student can contribute successfully regardless of coding background.

### Q2: What if XAMPP won't start on my laptop?
If Apache or MySQL fails to start in XAMPP:
1. **Port 80/443 Conflict (Apache)**: World Wide Web Publishing Service (IIS) or Skype might be occupying port 80. In Windows Services (`services.msc`), find "World Wide Web Publishing Service", stop it, and set its Startup Type to "Manual". Alternatively, configure Apache to listen on port `8080` in `httpd.conf`.
2. **Port 3306 Conflict (MySQL)**: If you previously installed a standalone MySQL Server or MariaDB, it might already be holding port 3306. Stop the existing MySQL service in Windows Services before clicking "Start" in the XAMPP Control Panel.
3. **The 15-Minute Rule**: If you cannot resolve XAMPP issues within 15 minutes, **do not waste your afternoon getting frustrated**. Switch immediately to a non-XAMPP ticket! Claim a copywriting ticket (`TSK-MEN-01` to `06`), slide deck ticket (`TSK-SLD-01` to `06`), video script ticket (`TSK-VID-01` to `08`), or documentation ticket (`TSK-DOC-01` to `08`).

### Q3: What if I am shy about speaking in the demonstration video?
**We have your back!**
- Every member's speaking role is only **1.5 to 2 minutes** long.
- Complete, word-for-word scripts are pre-written in Epic 1 (`TSK-VID-01` to `TSK-VID-08`). You do not need to improvise or guess what to say.
- You do **not** need to show your face on camera if you are uncomfortable. The video consists of clean screen recordings of our running software with voiceover narration.
- You can record your voiceover privately in your room using your smartphone's voice recorder or OBS Studio, take as many takes as you need until you are happy with it, and send the audio file to the Video Lead for editing.

### Q4: Do I need a powerful computer to work on these tasks?
**No.** All volunteer tasks can be completed on standard laptops, budget computers, school library PCs, or even smartphones and tablets (for Google Docs and Messenger). You do not need high-end GPUs, Docker, or Linux installations.

### Q5: How much time will each ticket take?
Each ticket is explicitly sized for university students balancing multiple classes:
- **Easy tickets**: 45 minutes to 1.5 hours (e.g., writing appetizer descriptions, testing Chrome forms, or drafting speaker notes).
- **Medium tickets**: 1.5 to 2.5 hours (e.g., photo sourcing, user guide writing, or OBS walkthrough recording).
Most tickets can be completed comfortably in a single evening.

### Q6: What if I get stuck, get busy with other subjects, or make a mistake?
**Communicate early in our Messenger group chat!**
College life is hectic and unexpected family or academic emergencies happen. If you realize you cannot complete a claimed ticket within your estimated time, simply send a message in chat:
*"Hey team, got caught up with another class exam. Unclaiming TSK-MEN-03 so someone else can pick it up!"*
There is zero penalty or judgment for communicating. Transparent communication keeps the team moving forward smoothly!
