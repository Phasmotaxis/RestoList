# Contributing to EIMS (Enterprise Information Management System)

Thank you for contributing to the Enterprise Information Management System (EIMS) for Cavite State University (CvSU) DCIT 24 Information Management!

This repository adheres to strict engineering rigor, clean-room standards, and high-assurance architectural invariants. Whether you are writing PHP backend code, curating menu data, designing slide decks, or recording demonstration walkthroughs, this guide outlines the standards and workflows required for all contributions.

---

## 1. Master Onboarding Handbook

Before writing any code or claiming a task, **read the comprehensive developer handbook**:

👉 **[docs/TEAM-ONBOARDING-GUIDE.md](docs/TEAM-ONBOARDING-GUIDE.md)**

The handbook covers:
- System mission, architectural context, and the 18-table relational data model across 5 domains.
- Local environment setup (Docker Compose vs Native XAMPP).
- Step-by-step Golden Git Workflow with atomic commit best practices.
- How to resolve merge conflicts safely without data loss.
- Zero-Git and Zero-LaTeX workflows for non-technical teammates.
- Ready-to-use AI companion prompts for mentorship on any ticket.

---

## 2. Core Architectural & Code Invariants

Every contribution touching code, views, or database migrations must respect these non-negotiable invariants:

1. **Strict Typing**: Every PHP source file must begin with:
   ```php
   <?php
   declare(strict_types=1);
   ```
2. **Zero External Frameworks**: Clean-room implementation in vanilla PHP 8.3. No Laravel, Symfony, React, Vue, or Tailwind.
3. **Dual-Driver Relational Resilience**: All database queries must run seamlessly on both MySQL 8.0 and SQLite3 fallback via `App\Core\Database`. Use ANSI-SQL syntax only.
4. **Zero Inline Styles**: Never use inline `style="..."` attributes in HTML or PHP views. All styling must consume semantic CSS custom properties defined in `src/public/assets/css/`.
5. **Multi-Tenant Scoping**: All tenant-level operations must strictly filter by `org_id` or `tenant_id` to prevent cross-tenant data leakage.
6. **Concurrency Safety**: Snapped 15-minute slot boundaries and unique constraints on `reservation_slots (org_id, table_id, slot_start_utc)` through `TableAllocator`.
7. **Pessimistic UI Synchronization**: The database is the single source of truth. Never mutate DOM cards or UI states optimistically before server HTTP 2xx confirmation.

---

## 3. The Golden Contribution Workflow

```
+---------------------------------------------------------------------------------------------------+
|                                      CONTRIBUTION LIFECYCLE                                       |
+---------------------------------------------------------------------------------------------------+
|  1. CLAIM TICKET  -->  2. BRANCH MAIN  -->  3. ATOMIC COMMITS  -->  4. LOCAL QA  --> 5. OPEN PR   |
|     TEAM-TASK-QUEUE    git checkout -b         Stage specific           php -l         Link ticket|
|     or GitHub Issue    feat/TSK-xxx-...        files only               Run tests      Peer review|
+---------------------------------------------------------------------------------------------------+
```

### Rule 0: `main` is Sacred
Direct pushes to `main` are strictly forbidden. All modifications arrive via Pull Requests targeting `main`.

### Step 1: Claim or Create a Ticket
- Select an unassigned ticket from `docs/TEAM-TASK-QUEUE.md` or pick up a GitHub Issue using `.github/ISSUE_TEMPLATE/task_ticket.md`.
- Coordinate in the team Facebook Messenger chat following the claiming protocol (`🎯 CLAIMING TICKET...`).

### Step 2: Branch Off Fresh `main`
```bash
git checkout main
git pull origin main
git checkout -b feat/TSK-xxx-short-description
```

Branch naming conventions:
- `feat/TSK-xxx-description`: New features, endpoints, or seed datasets.
- `fix/TSK-xxx-description`: Bug fixes, layout corrections, error handling.
- `docs/TSK-xxx-description`: Documentation, guides, manual updates.
- `test/TSK-xxx-description`: Test runner cases, QA automation, test scripts.

### Step 3: Author Atomic Commits
- **What is an atomic commit?** A single logical unit of work that leaves the repository in a passing state.
- **Stage explicit files only**: Always use `git add path/to/file.php`. NEVER execute `git add .`, `git add -A`, or `git commit -a`.
- **Review before committing**: Always inspect `git status` and `git diff --staged`.
- **Follow Conventional Commits**:
  ```text
  type(scope): imperative summary
  ```
  Allowed types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`. Keep the title concise and do not put ticket tracking numbers in the commit title.

### Step 4: Run the Pre-Push Local Quality Gate
Before pushing to GitHub, you must verify that all three local checks succeed:

```bash
# 1. Syntax check all PHP files
find src/ -name "*.php" -exec php -l {} \; | grep -v "No syntax errors detected" || echo "All PHP files have valid syntax!"

# 2. Execute full integration test suite
php tests/runner.php

# 3. Verify zero inline styles
grep -rn "style=" src/app/Views/ || echo "Zero inline styles confirmed!"
```

### Step 5: Push and Open a Pull Request
```bash
git push -u origin feat/TSK-xxx-short-description
```
- Open a Pull Request on GitHub using `.github/PULL_REQUEST_TEMPLATE.md`.
- Fill in all sections: Ticket link, changes summary, quality checklist, and test evidence.
- Request review from at least one teammate or the Project Lead.
- Notify the team in Messenger with the PR link.

---

## 4. Non-Developer & Beginner Contributions (Zero-Git Path)

Teammates working on video scripts, slide presentations, food copywriting, or user manuals do **not** need Git installed on their computers:
1. Work in everyday student tools (Google Docs, Canva, OBS Studio, Word).
2. Follow the Messenger ticket claiming and submission templates documented in [Chapter 5 of the Onboarding Guide](docs/TEAM-ONBOARDING-GUIDE.md#chapter-5-social-workflow--messenger-protocol-for-non-git--beginner-members).
3. The Project Lead will review your work, convert text to LaTeX/Markdown, stage files explicitly, and commit with proper teammate attribution.

---

## 5. Getting Help & Asking Questions

If you encounter blockers, questions, or unexpected errors:
- Consult the [FAQ & Troubleshooting in the Onboarding Guide](docs/TEAM-ONBOARDING-GUIDE.md#appendix-faq--common-troubleshooting-for-college-students).
- Use the [AI Companion Prompt](docs/TEAM-ONBOARDING-GUIDE.md#chapter-6-teammate-ai-companion-prompt) in ChatGPT or Claude for step-by-step guidance.
- Post in our Facebook Messenger group chat for immediate peer support.
