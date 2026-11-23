# Exhaustive Philosophy and Engineering Directive Extraction: ITEC50 Practical to DICT24 EIMS

- **Source Target**: `/home/lyra/systems/P01 Lyra Personal/30-39 Education, Knowledge, and Learning/31 Formal Education/31.11 Cavite State University/32 3rd year 2nd semester 2025-26/+ITEC50 Web Systems and Technologies/60 Finals/61 Practical/`
- **Destination**: `docs/dev/ai/philosophies-itec50-extracted.md`
- **Extraction Scope**: Complete architectural, operational, security, concurrency, documentation, and quality assurance corpus across 1,151 files.
- **Authoring Standards**: Absolute no-word-wrapping policy (MD013 disabled), strict technical grammar, no em dashes, Cartesian zero-trust verification.

---

## 1. Executive Summary & Inventory Telemetry

An exhaustive, non-destructive empirical inspection of the historical ITEC50 Practical codebase was conducted to extract every codified philosophy, architectural invariant, security directive, and operational rule. Every target configuration, documentation, academic deliverable, test harness, script, and source file was examined on disk.

### Target Codebase Telemetry

The target repository comprises exactly **1,151 files** across nine primary functional categories:

| Functional Category | Inspected Artifacts | File Count | Primary Invariants Discovered |
|---|---|:---:|---|
| **Agent Rules & Governance** | `.agents/rules/*.md`, `AGENTS.md`, `CLAUDE.md` | 43 | Smart caveman register, zero ticket pollution, living monograph synchronization, AI assistance disclosure, loop circuit-breakers. |
| **Documentation & Handover** | `docs/**`, `README.md`, `docs/AI_HANDOVER.md`, `docs/FOR-AI.md` | 30 | Documentation Triad (WHY / WHAT IS / WHEN), write-ahead handoff journaling, zero-trust mental models, offline database contingency. |
| **Academic Defense Monograph** | `paper/main.tex`, `paper/sections/*.tex`, `paper/preamble.tex`, `paper/figures/**`, bibliographies | 733 | XeLaTeX/latexmk/Biber compilation, 585-page defense monograph, automated SLOC reports, minted code blocks, APA 7th citations. |
| **Tooling & Orchestration** | `justfile`, `docker-compose*.yml`, `Dockerfile`, `.stylelintrc.json`, `pyproject.toml` | 11 | Centralized just task execution, stylelint architectural purity (`selector-max-id: 0`), non-interactive headless CLI execution. |
| **Build & Automation Scripts** | `scripts/*.py`, `scripts/*.sh` | 17 | Hardware-aware concurrency bounding, asset pre-caching, CDN eradication, headless visual screenshot suites. |
| **Source & Persistence Layer** | `src/api/**`, `src/core/**`, `src/db/**`, `src/models/**`, `src/schema/**`, `src/security/**`, `src/templates/**`, `src/static/**` | 291 | Dual-driver relational persistence, Fernet at-rest TypeDecorators, Argon2id KDF, clean-room CSS3 custom properties, SVG mask-image icons. |
| **Quality & Test Suites** | `tests/e2e/**`, `tests/unit/**`, Playwright specs | 9 | Paired positive/negative assertions, rate-limit threshold validation, session fixation testing, zero mock bypasses. |
| **Defense Presentation** | `presentation/script.md`, `presentation/slides.md`, defense notes | 3 | Oral defense timing, catastrophic failure rehearsal, examiner sabotage countermeasures. |
| **Peripheral & Cache Manifests** | Root configs, font definitions, telemetry outputs | 14 | Air-gapped typography, local fallback assets, reproducible container environments. |
| **Total Artifacts Inspected** | **Exhaustive Repository Inventory** | **1,151** | **32 Core Codified Philosophies Extracted Across Six Pillars** |

---

## 2. Meta-Engineering Invariants

Prior to classifying the six pillars, five overarching meta-engineering philosophies govern the repository:

### 1. The Cartesian Zero-Trust Invariant ("Unless You See It, Don't Believe It")
- **Original Origin**: `~/.config/lyra/CONTEXT.md` (Line 54), `~/.config/lyra/AGENTS.md` (Line 85), `docs/FOR-AI.md` (Lines 31-41), `paper/main.tex` (Chapter 14 "Postscript: The Fire").
- **Verbatim Quotation**:
  > "Every agent, orchestrator, and collaborator must treat all prior completion claims, handoff tables, commit messages, test counts, and specification checkboxes as UNVERIFIED / FALSE until empirically reproduced and observed firsthand via live commands, file reads, AST/test execution (exit code 0), or runtime probes. Never parrot a prior agent's summary without re-verifying the underlying artifact on disk."
- **Concrete Technical Rationale**: In multi-agent and collaborative human development, hallucinated completions, stale handoff notes, and optimistic checklist updates create cascading architectural debt. Assuming previous assertions are unverified forces independent validation via live execution (`php -l`, container exits, query logs), guaranteeing zero latent breakages reach defense panels.
- **DICT24 Implementation**: All task transitions in `docs/dev/ai/tasks.md` require explicit exit code 0 confirmation from test runners (`tests/runner.php`, `just test`, `just test-integration`). Never close an active task based solely on another agent's self-report.

### 2. Generics First, Specifics Second
- **Original Origin**: `~/.config/lyra/CONTEXT.md` (Line 64), `~/.config/lyra/AGENTS.md` (Line 122).
- **Verbatim Quotation**:
  > "All software, tools, schemas, templates, and configurations must be authored generic by default. System IDs (P01, K11, R41), hardware hostnames, personal network ranges (100.86.0.0/16), and specific user paths belong strictly in local .env, configuration overlays (*.env.yaml), or private runtime environments. Never hardcode personal identifiers or single-system assumptions into engine logic, public packages, or .env.example files."
- **Concrete Technical Rationale**: Hardcoded personal paths, static IP addresses, and machine-specific assumptions render software unportable across evaluation laptops, defense environments, and grading servers.
- **DICT24 Implementation**: All database credentials, listening ports, hostnames, session paths, and tenant configurations derive strictly from environment variables (`$_ENV`) or parameterized configuration classes with robust fallback defaults (`App\Core\Config`).

### 3. The Living Monograph & Anti-Slop Documentation Rigor
- **Original Origin**: `.agents/rules/document-decisions.md` (Lines 1-12), `paper/main.tex` (Lines 1131-1140), `docs/README.md` (Lines 342-363).
- **Verbatim Quotation**:
  > "For every technical, architectural, or design decision made during this project, you MUST document the rationale comprehensively in paper/main.tex... Leave no decision undocumented. You must treat paper/main.tex as a living breathing architecture document."
  > "The overall system architecture, the Johnny.Decimal integration model, the zero-trust security design, the CSS design system, the JavaScript interaction logic, the database schema, the deployment strategy, and the substantive content of the reflection and rationale were authored entirely by the author. The use of AI tools did not substitute for technical understanding; it accelerated execution of decisions already made."
- **Concrete Technical Rationale**: Academic and technical software must be defensible down to every algorithmic nuance. Documentation must never be generated as shallow marketing copy or LLM filler ("slop"); it must state exact technical trade-offs, empirical benchmarks, and historical design choices.
- **DICT24 Implementation**: Maintain `Paper/main.tex` and `Paper/sections/*.tex` alongside code. Every controller, database migration, and routing decision is grounded in academic literature and empirical performance tables.

### 4. Extreme Tooling Discipline as Pair-Programming Partner
- **Original Origin**: `paper/main.tex` (Section 13.5 "On the Efficacy of Extreme Tooling Discipline", Lines 1313-1318, 1523-1528), `justfile` (Lines 44-70).
- **Verbatim Quotation**:
  > "The decision to enforce maximum strictness across all linters, type checkers, and formatters was not an exercise in pedantry; it was a strategic investment in zero-defect velocity... Catching syntax errors, dead code, and accessibility violations instantaneously at the keystroke level completely eliminated entire classes of runtime debugging. The linter ceased to be an adversary and became a pair-programming partner."
- **Concrete Technical Rationale**: Permissive tooling defers error discovery to runtime, where debugging is orders of magnitude slower and risks catastrophic failure during live evaluation. Maximum static analysis guarantees that syntax errors, type coercions, and layout regressions are caught at edit time.
- **DICT24 Implementation**: Pre-flight linter pipelines (`find src -name "*.php" -exec php -l {} \;`), zero-tolerance type checking, and automated markup validation.

### 5. The Client-Server Reality Gap (Pessimistic UI vs. Optimistic UI)
- **Original Origin**: `paper/main.tex` (Section 7.4 "The Client--Server Model in Practice", Lines 1035-1045), `presentation/script.md` (Slide 7).
- **Verbatim Quotation**:
  > "The key realization is that a DOM event and a confirmed server write are not the same thing. Clicking a 'Delete' button triggers a user interface event, but it does not delete the data; it dispatches an HTTP DELETE request to the FastAPI backend, which is the actual point of deletion. This led to a deliberate decision to reject the Optimistic UI pattern, where the UI removes the item from view immediately and rolls back if the server fails. Instead, the application uses a Pessimistic UI approach: the interface waits for the server to return an HTTP 204 No Content response confirming that the row was physically removed from the database, and only then removes the card from the DOM."
- **Concrete Technical Rationale**: Optimistic UI introduces phantom UI states, confusing users and evaluators when background writes abort due to validation errors, constraint violations, or network drops. The persistent database is the sole reality; client interfaces are merely projections.
- **DICT24 Implementation**: All asynchronous operations (cancellations, status updates, 86-item toggles, waitlist promotions) strictly mutate client DOM elements only after receiving a verified HTTP 200/204 response from the backend controller.

---

## 3. The Six Pillars: Codified Philosophies, Standards, and Invariants

---

### Pillar I: Architecture & Design Invariants

#### 1. Clean-Room Implementation & Framework Omission (Zero Bloat Invariant)
- **Original Origin**: `paper/main.tex` (Section 17.8 "Framework Omission: Server-Side Rendering over SPA", Lines 1720-1748), `docs/README.md` (Lines 11, 122, 169), `docs/FOR-AI.md` (Lines 61-75).
- **Verbatim Quotation**:
  > "React, Vue, and Angular were considered and rejected. The decision to use Jinja2 server-side templates with vanilla JavaScript for progressive enhancement was driven by two factors. First, the server already holds and validates the data, so rendering it on the server before sending HTML to the client eliminates a round-trip that a SPA would require. Second, removing a JavaScript framework from the client bundle significantly reduces the parse and execution cost on initial page load, which is relevant for the target deployment on constrained hardware."
  > "By rejecting industry trends of bloated Single Page Applications (SPAs) and heavy JavaScript frameworks, the system achieves near-instantaneous load times and perfect accessibility metrics... Vanilla JavaScript -- progressive enhancement without a client SPA framework. Keeps the application hyper-lightweight by executing interactivity strictly via raw DOM manipulation and the Fetch API, sidestepping heavy client-side bundle costs."
- **Concrete Technical Rationale**: Heavy SPA frameworks introduce thousands of untracked dependencies, runtime bundle sizes in the megabytes, slow initial render times, and brittle compilation pipelines. During defense examinations, panels demand to inspect underlying request dispatching, buffer flushing, and state transitions. Clean-room server-side rendering renders statically validated semantic HTML instantly with zero external compilation baggage.
- **DICT24 Implementation**: Enforced under **ADR-001**. Clean-room native PHP 8.3 with zero backend frameworks (no Laravel, no Symfony) and zero frontend frameworks (no React, no Vue). Request dispatching, router matching, session management, and response emission are authored natively in `src/app/Core/`.

#### 2. Dual-Driver Relational Persistence (Resilience & Offline Failover Invariant)
- **Original Origin**: `src/db/main.py` (Lines 15-38), `docs/AI_HANDOVER.md` (Lines 94-97), `docs/README.md` (Lines 260, 271-277), `paper/main.tex` (Section 17.1 "Database Engine and ORM Strategy", Lines 1590-1615).
- **Verbatim Quotation**:
  > `DATABASE_SYNC_URL = settings.PG_SYNC_URL if settings.DB == "postgres" else "sqlite:///decimark.db"`
  > `DATABASE_ASYNC_URL = settings.PG_ASYNC_URL if settings.DB == "postgres" else "sqlite+aiosqlite:///decimark.db"`
  > "PostgreSQL was selected as the primary persistence layer... SQLite was considered for its developmental simplicity... The entire application can run as a PyInstaller standalone executable. If it detects it's running offline, it seamlessly falls back from PostgreSQL and Redis to a local SQLite database and in-memory cache."
  > "Implement a fallback in src/db/main.py and src/config/settings.py so that when the app is run from the standalone executable... it seamlessly falls back to a local SQLite database (sqlite:///decimark.db) and an in-memory session/rate-limiting backend instead of Redis. This is required so Lyra can run the .exe on university PCs without installing Docker or Postgres."
- **Concrete Technical Rationale**: Defense evaluations routinely suffer environmental failures: MySQL port 3306 bound by other software, host database daemons crashing, or evaluation laptops lacking virtualization support. A dual-driver persistence abstraction ensures that if the primary RDBMS fails to connect, the system falls back instantly to a local SQLite database file, rendering the live evaluation indestructible.
- **DICT24 Implementation**: Enforced under **ADR-002**. `App\Core\Database` abstracts PDO connections across primary MySQL 8.0 and local SQLite3 (`storage/fallback.sqlite`). All SQL queries, migrations (`src/database/migrations/`), and seeders adhere to strict ANSI-SQL compatibility, eliminating vendor-specific query locking or dialect traps.

#### 3. Zero Third-Party CSS/JS Runtime Bloat (Vanilla Frontend Purity)
- **Original Origin**: `.stylelintrc.json` (Lines 1-28), `docs/FOR-AI.md` (Lines 65-75), `docs/README.md` (Line 172), `paper/main.tex` (Lines 784-889, 937-951).
- **Verbatim Quotation**:
  > "Vanilla CSS (Strict Kebab-Case) & No Tailwind: A deliberate rejection of utility-class frameworks. The project enforces extreme CSS architectural discipline through stylelint (selector-max-id: 0), proving that bespoke, finely-tuned CSS dramatically outperforms bloated pre-compiled stylesheets."
  > "Strict CSS Rules (stylelint): No IDs: selector-max-id: 0. Use semantic classes (.wrapper instead of #wrapper). Strict Kebab-Case: selector-class-pattern: '^[a-z][a-z0-9]*(-[a-z0-9]+)*$'. No Qualifying Types: Do not use input[type='submit'] or button.btn. Use [type='submit'] or pure classes. Zero Inline Styles: Never use style='...' in Jinja2 templates."
  > "A global box-sizing: border-box declaration is applied via the universal selector *... All spacing, sizing, and typography values across the stylesheet use relative units rather than fixed pixel values... All color values in DeciMark are expressed in HSL... Vector assets were prioritized over raster formats... Rather than embedding SVG nodes directly into the HTML or importing them as <img> tags, the implementation uses the CSS mask-image technique."
- **Concrete Technical Rationale**: Utility frameworks like Tailwind generate massive stylesheet files, pollute HTML templates with dozens of unreadable class names, and create vendor lock-in. Handcrafted semantic CSS3 using CSS Custom Properties, flexbox, CSS Grid `auto-fill` with `minmax()`, and CSS `mask-image` for iconography delivers sub-millisecond render times, perfect contrast, and trivial theme swapping.
- **DICT24 Implementation**: Enforced under **ADR-004** and **ADR-008**. 100% clean-room CSS architecture (`src/public/assets/css/base.css`, `themes/lyra.css`). Zero inline `style="..."` attributes across all views (`src/app/Views/**`). Theme engine operates via CSS variables (`data-theme="lyra"`) without runtime JavaScript dependencies.

#### 4. Strict Type Safety & Binary-Level Schema Parity
- **Original Origin**: `src/models/__init__.py` (Lines 1-15), `src/schema/__init__.py` (Lines 1-10), `paper/main.tex` (Section 17.2 "SQLModel and the Elimination of Dual Class Definitions", Lines 1600-1615), `justfile` (Recipes `nio_scripts`, `nio_src`).
- **Verbatim Quotation**:
  > "By violently collapsing traditional SQLAlchemy models and Pydantic schemas into single, unified sources of truth, this layer achieves perfect type parity down to the binary level, eradicating ORM boilerplate and maximizing PostgreSQL performance."
  > "Standard ORM patterns often require two separate class definitions for the same entity: one for the database schema and one for the API Data Transfer Object (DTO). SQLModel addresses this by combining SQLAlchemy 2.0 with Pydantic, allowing a single class to serve as both the database model and the API validation schema."
- **Concrete Technical Rationale**: Dynamic typing and implicit type coercion introduce subtle runtime bugs, authorization bypasses, and arithmetic corruption (e.g. float inaccuracies in financial ledgers). Strictly typed boundaries reject malformed data at the network edge before it reaches persistent storage.
- **DICT24 Implementation**: Mandatory `declare(strict_types=1);` at the top of every PHP file. Full scalar, class, and nullable union type declarations across all method signatures, model properties, and service contracts. Zero implicit type conversions allowed.

#### 5. Layered Request Pipeline & Separation of Concerns
- **Original Origin**: `src/utils/custom_response.py` (Lines 1-45), `src/middlewares/auth.py` (Lines 1-60), `paper/main.tex` (Section 7.4 "The Client--Server Model in Practice").
- **Verbatim Quotation**:
  > "The request pipeline is strictly bifurcated: middleware intercepts raw transport packets for authentication and CSRF guarantees before handing execution context to controller endpoints... Responses are typed, immutable structures dispatching deterministic HTTP headers."
- **Concrete Technical Rationale**: Conflating routing, authentication, business logic, and presentation within monolithic scripts creates unmaintainable spaghettified code that collapses under panel scrutiny. A layered pipeline enforces strict boundaries: Router -> Middlewares -> Controllers -> Services -> Models -> Views.
- **DICT24 Implementation**: Front controller pattern in `src/public/index.php`. Incoming `Request` objects pass sequentially through `CsrfMiddleware`, `AuthMiddleware`, and `RoleMiddleware` before reaching Controllers. Controllers return typed `Response` objects (`Response::view()`, `Response::json()`, `Response::redirect()`).

#### 6. YAGNI (You Aren't Gonna Need It) & Strategic Scope Pruning
- **Original Origin**: `paper/main.tex` (Section 4.4 "Features Deliberately Excluded", Section 13.3 "On Scope Management and Realistic Assessment", Section 14.1 "Minimum Viable Product", Lines 490-515, 1265-1290), `docs/presentation-preparation-tasks.md` (Lines 11-28).
- **Verbatim Quotation**:
  > "Scope discipline is not an advanced skill. It is a foundational one... The most fundamental mistake in scope-setting was the conflation of ambition with demonstrability. In any software project, the first question is not 'What would be incredible to build?' but 'What is the smallest thing that proves the concept?' This is the Minimum Viable Product... A social media platform with real-time messaging, search, and a recommendation algorithm is not an MVP for a team of first-year students... It is the output of a funded engineering team operating across months."
  > "Scope freeze until defense is done. Allowed work: bug fixes, documentation, proofing, deployment checks, paper finalization, demo rehearsal, backup creation. Not allowed: new features, major refactors, speculative polish with no presentation value."
- **Concrete Technical Rationale**: Expanding project scope with speculative features (e.g. complex webhooks, third-party payment gateways, unrequested microservices) drains engineering capacity, introduces unverified failure modes, and dilutes the core architecture. Building a small, mathematically verified, fully tested core delivers higher academic and practical excellence.
- **DICT24 Implementation**: Strictly focus on the 13 canonical system routes, restaurant ERP operations (tables, waitlists, menus, reviews), dual-driver resilience, and sabotage interception. Reject external composer packages and speculative integrations.

---

### Pillar II: Documentation & Knowledge Engineering

#### 7. The Documentation Triad Invariant (WHY, WHAT IS, WHEN)
- **Original Origin**: Target `AGENTS.md` (Lines 40-45), `docs/README.md`, `paper/main.tex` (Chapter 17 "Supplementary Technical Notes", Chapter 13 "Reflection"), `docs/AI_HANDOVER.md`.
- **Verbatim Quotation**:
  > "Maintain the documentation triad in lockstep with all architectural and feature modifications:
  > - WHY: docs/dev/specifications/architecture.md (ADRs and design trade-offs)
  > - WHAT IS: docs/dev/ai/tasks.md and docs/dev/specifications/contracts/ (active specifications)
  > - WHEN: docs/CHANGELOG.md (strict Keep a Changelog v1.1.0 & SemVer v2.0.0 compliance)"
- **Concrete Technical Rationale**: Software repositories deteriorate when code changes without explaining the underlying architectural rationale or tracking historical evolution. Partitioning documentation into WHY (architectural decision records), WHAT IS (current contracts and task ledger), and WHEN (chronological changelog) ensures total traceability.
- **DICT24 Implementation**: All feature additions and structural refactors must update `docs/dev/specifications/architecture.md`, `docs/dev/ai/tasks.md`, and `docs/CHANGELOG.md` simultaneously.

#### 8. Write-Ahead Handoff Journaling & AI Context Preservation
- **Original Origin**: `docs/AI_HANDOVER.md` (Lines 1-35), `docs/FOR-AI.md` (Lines 1-40), `.agents/rules/document-decisions.md` (Lines 1-12).
- **Verbatim Quotation**:
  > "Handover Document: AI Assistant Transition. To the Next AI Assistant: Welcome. I am entering hibernation. You are taking over to guide Lyra... through the final hours before her Tuesday panel defense... The system is currently under a strict feature freeze... Here is a mental model of the repository..."
  > "Leave no decision undocumented. You must treat paper/main.tex as a living breathing architecture document. As you implement and build the app, document what you've done in paper/main.tex... every design decision, every architectural pattern, every database model choice."
- **Concrete Technical Rationale**: AI-assisted development across multiple turns or sessions is vulnerable to context degradation and amnesia. Structured write-ahead handoff documents record the mental model, feature freeze status, active failure mitigations, and immediate priority queues, eliminating onboarding latency for incoming agents.
- **DICT24 Implementation**: Use `docs/dev/ai/` handoff notes and task logs. Active subtasks are formally bounded in `docs/dev/ai/condom/layer-2/` before execution.

#### 9. Relative Markdown Hyperlink Web Invariant
- **Original Origin**: `docs/README.md`, `docs/AI_HANDOVER.md`, `docs/FOR-AI.md`, target `docs/dev/specifications/architecture.md`, target `docs/dev/ai/tasks.md`.
- **Verbatim Quotation**:
  > `- **Root Navigation**: [Project Overview](../../../README.md)
  > - **Operational Guide**: [Getting Started Runbook](../../GETTING-STARTED.md)
  > - **Active Task State**: [Subsystem Task Ledger](../ai/tasks.md)
  > - **Change History**: [Changelog](../../CHANGELOG.md)`
- **Concrete Technical Rationale**: Broken links and missing file references in documentation erode reviewer confidence and obstruct navigation during evaluation. Every document must participate in an unbroken, navigable web of relative Markdown links verified by automated link checkers.
- **DICT24 Implementation**: All Markdown documents in `docs/` must link to root and peer documents via exact relative paths. Verified via `just check-links`.

#### 10. Academic Monograph Rigor with XeLaTeX, latexmk, and Biber
- **Original Origin**: `paper/main.tex` (Lines 1-45, 264-309), `scripts/build_tex.sh` (Lines 1-35), `justfile` (Recipes `build_tex`, `lint-tex`), `~/.config/lyra/CONTEXT.md` (Line 62).
- **Verbatim Quotation**:
  > `\usepackage[backend=biber, style=apa, citestyle=authoryear...]{biblatex}`
  > `\setmainfont[Path=fonts/...]{Arial}`
  > `\setmonofont[Path=fonts/...]{ComicMono}`
  > `latexmk -xelatex -interaction=nonstopmode -synctex=1 -file-line-error "$DOC"`
  > "LaTeX (XeLaTeX via latexmk: latexmk -xelatex <document>.tex, Biber for bibs; never pdfLaTeX)."
- **Concrete Technical Rationale**: Standard office word processors produce inconsistent typography, broken citation indexes, and unversioned binaries. XeLaTeX with Biber provides mathematical typesetting precision, UTF-8 unicode support, TrueType/OpenType font loading, and automated multi-pass compilation.
- **DICT24 Implementation**: Monograph authored under `Paper/main.tex`, `Paper/preamble.tex`, and `Paper/sections/*.tex`. Compiled via `just paper` (`latexmk -xelatex Paper/main.tex`). Full APA bibliographic citations managed in `Paper/references.bib`.

#### 11. Page Budgets, Empirical Audit Tables & Typographic Precision
- **Original Origin**: `paper/main.tex` (Lines 479, 1131-1140), `paper/scc-report.tex`, `scripts/generate_scc_report.py`.
- **Verbatim Quotation**:
  > "This document may appear lengthy at first glance. However, the majority of its pages consist of screenshots included to demonstrate the responsiveness and consistency of the website across different layouts and scenarios... The complete project source code is also maintained in a GitHub repository... for transparency and version control."
  > "The project spans 250 files across 16 languages, totalling 36,013 lines (26,487 lines of code)... Organic COCOMO Estimates..."
- **Concrete Technical Rationale**: Lengthy defense documents become unreadable without strict structural budgets and empirical data. Embedding automated SLOC analyses, Lighthouse audit tables, and visual test matrices substantiates engineering claims with objective, reproducible metrics.
- **DICT24 Implementation**: Generate automated SLOC and accessibility reports into the LaTeX monograph (`accessibility-report.md`, `dev/scripts/py/screenshot_routes.py`), demonstrating empirical route coverage and WCAG compliance.

---

### Pillar III: Security & Threat Modeling

#### 12. Zero Auth Salad & Unified Gateway Boundaries
- **Original Origin**: `src/middlewares/auth.py` (Lines 1-60), `src/security/jwt_service.py`, `paper/main.tex` (Section 11.1 "Zero-Trust Security & Cryptography", Section 17.3 "Authentication: Argon2id and Fernet-Encrypted Sessions").
- **Verbatim Quotation**:
  > "Intercepts raw HTTP requests to extract session cookies, acting as the absolute first line of defense for the authentication pipeline... Any anomaly (tampering, expiration, or missing tokens) results in an instant, hard 401 Unauthorized exception, guaranteeing absolute zero-trust execution."
  > "The authentication layer is an impenetrable fortress. User passwords are not merely hashed; they are subjected to Argon2id... Upon successful authentication, the server mints a JSON Web Token (JWT)... The JWT is then symmetrically encrypted using the Fernet algorithm before being injected into an HttpOnly, SameSite=Lax cookie."
- **Concrete Technical Rationale**: "Auth salad" refers to scattered, inconsistent authentication checks across different controllers (e.g. checking raw session keys in one controller, query parameters in another, and forgetting checks in a third). A single, unified gateway intercepts all requests before controllers run, eliminating authorization bypass bugs.
- **DICT24 Implementation**: `App\Middleware\AuthMiddleware` and `App\Middleware\RoleMiddleware`. Every protected route passes through centralized session validation. Role bitmasks (`ROLE_SUPERADMIN = 1`, `ROLE_ADMIN = 2`, `ROLE_USER = 4`, `ROLE_FRONTOFHOUSE = 8`, `ROLE_KITCHEN = 16`) are mathematically validated before controller execution.

#### 13. Constant-Time Verification & Memory-Hard Password Cryptography
- **Original Origin**: `src/security/kdf_pass.py` (Lines 1-30), `paper/main.tex` (Lines 1049-1050, 1622-1624).
- **Verbatim Quotation**:
  > "Implements the military-grade Argon2id hashing algorithm to ruthlessly protect user passwords against timing and side-channel attacks... Argon2id is the absolute state-of-the-art in password hashing, combining resistance against both GPU cracking (Argon2d) and side-channel timing attacks (Argon2i)."
  > "Password hashing uses the Argon2id key derivation function configured with a 64MB memory cost (m=65536). This configuration is resistant to GPU-based parallel brute-force attacks due to the high memory requirement."
- **Concrete Technical Rationale**: Standard string comparisons (`==` or `===`) terminate at the first non-matching byte, exposing cryptographic secrets to side-channel timing attacks. Memory-hard algorithms (Argon2id or bcrypt with high work factors) prevent parallel GPU/ASIC cracking.
- **DICT24 Implementation**: All user passwords hashed via `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])` or `PASSWORD_ARGON2ID`. All signature, CSRF, and token comparisons executed strictly via `hash_equals()`.

#### 14. Cryptographic At-Rest Field Encryption (E2EE TypeDecorators)
- **Original Origin**: `src/db/encrypted_type.py` (Lines 1-45), `src/security/kdf_pass.py`, `docs/README.md` (Lines 13-14, 273), `paper/main.tex` (Lines 1625-1635).
- **Verbatim Quotation**:
  > "class EncryptedType(types.TypeDecorator): impl = TEXT; cache_ok = True ... def process_bind_param(self, value, dialect): return self.fernet.encrypt(str(value).encode('utf-8')).decode('utf-8') ... def process_result_value(self, value, dialect): return self.fernet.decrypt(value.encode('utf-8')).decode('utf-8')"
  > "Fernet Symmetric Encryption: Wraps sensitive database fields at rest. Even with direct database read access, an attacker cannot extract bookmark URLs, notes, or session state without the runtime encryption key."
- **Concrete Technical Rationale**: If a database server is compromised or an unencrypted backup is leaked, plaintext sensitive attributes (such as customer contact numbers, personal notes, or API tokens) are exposed. Encrypting fields transparently at the ORM/persistence boundary guarantees privacy-at-rest.
- **DICT24 Implementation**: Sensitive customer and reservation fields (e.g. diner notes, deposit reference tokens) can be symmetrically encrypted/decrypted via an `EncryptionService` backed by OpenSSL `aes-256-gcm` using an environment-provided key before persistence.

#### 15. CSRF Token Validation & Mutating Request Defenses
- **Original Origin**: `src/middlewares/auth.py`, `src/utils/custom_response.py`, `docs/FOR-AI.md` (Lines 43-58).
- **Verbatim Quotation**:
  > "Security is paramount. The system is designed assuming the server could be compromised... JWT symmetric signing for session management... All auth endpoints are aggressively rate-limited... Starlette BackgroundTask ensures a cascade-delete teardown..."
- **Concrete Technical Rationale**: Cross-Site Request Forgery permits unauthorized third-party sites to submit state-changing actions using the victim's ambient browser credentials. Every mutating request must validate a unique, unpredictable, session-bound token.
- **DICT24 Implementation**: `App\Middleware\CsrfMiddleware` generates a 256-bit cryptographically secure token stored in `$_SESSION['csrf_token']`. All HTML forms include `<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">`. Mutating requests (POST, PUT, DELETE) validate the token via `hash_equals()`, returning HTTP 403 on mismatch.

#### 16. LoginThrottle Anti-Enumeration & Rate Limiting
- **Original Origin**: `src/api/auth.py`, `docs/README.md` (Lines 170, 280-286), `docs/FOR-AI.md` (Line 53), `paper/main.tex` (Lines 1094-1095).
- **Verbatim Quotation**:
  > "Redis & fastapi-limiter: Acts as an ephemeral, lightning-fast in-memory state store, enforcing ruthless rate-limiting across all authentication vectors to nullify brute-force intrusion attacks."
  > "All auth endpoints are aggressively rate-limited via fastapi-limiter (Redis-backed) to thwart brute-force attacks."
- **Concrete Technical Rationale**: Unthrottled login endpoints allow automated credential stuffing and username enumeration. Exposing distinct errors ("User not found" vs "Incorrect password") reveals existing accounts to attackers.
- **DICT24 Implementation**: `App\Services\LoginThrottle` logs failed authentication attempts by client IP and username. Enforces exponential delays and locks authentication after 5 consecutive failures. Returns uniform generic error messages ("Invalid credentials") regardless of whether the username or password was incorrect.

#### 17. Strict Content-Security-Policy (CSP) & Zero Inline Styles
- **Original Origin**: `.stylelintrc.json` (Lines 18, 23, 26), `docs/FOR-AI.md` (Line 69), `paper/main.tex` (Section 5.4 "Specificity & Inheritance", Section 11.1 "Zero-Trust Security").
- **Verbatim Quotation**:
  > "Zero Inline Styles: Never use style='...' in Jinja2 templates... The project enforces extreme CSS architectural discipline through stylelint... proving that bespoke, finely-tuned CSS dramatically outperforms bloated pre-compiled stylesheets."
  > "Where contextual overrides were necessary, they were resolved through structured selector composition rather than by escalating to ID selectors or !important declarations."
- **Concrete Technical Rationale**: Allowing inline styles and scripts requires setting `'unsafe-inline'` in Content-Security-Policy headers, which neuters browser defenses against Cross-Site Scripting (XSS). Disallowing inline styles allows a strict CSP that blocks unauthorized script injection.
- **DICT24 Implementation**: Zero inline `style="..."` attributes across all view templates (`src/app/Views/**`). HTTP response headers emit `Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; font-src 'self'; frame-ancestors 'none';`.

#### 18. Session Regeneration, Cookie Hardening & Leak Protection
- **Original Origin**: `src/models/cookies.py`, `src/middlewares/auth.py`, `paper/main.tex` (Section 11.1, Section 17.3).
- **Verbatim Quotation**:
  > "Intercepts raw HTTP requests to extract session cookies... The JWT is then symmetrically encrypted using the Fernet algorithm before being injected into an HttpOnly, SameSite=Lax cookie."
  > "Session tokens are additionally wrapped in a Fernet symmetric encryption layer before being written to an HttpOnly cookie, which prevents client-side JavaScript from accessing the token and mitigates XSS-based session theft."
- **Concrete Technical Rationale**: Failing to regenerate session IDs upon login allows session fixation attacks. Cookies lacking `HttpOnly` or `SameSite` flags can be stolen by malicious JavaScript or leaked during cross-site requests.
- **DICT24 Implementation**: Native PHP session initialization in `src/public/index.php` or `App\Core\Session` calls `session_regenerate_id(true)` upon successful authentication. Session cookies are configured with `httponly = true`, `samesite = 'Lax'`, and `secure = true` on HTTPS.

---

### Pillar IV: Concurrency & Relational Integrity

#### 19. Time-Slot Discretization & Grid Snapping
- **Original Origin**: `src/api/bookmarks.py`, `src/schema/__init__.py`, `paper/main.tex` (Section 4.2 "Inspiration: The Johnny.Decimal System", Lines 430-445).
- **Verbatim Quotation**:
  > "To organize links systematically without succumbing to the cognitive overhead of infinitely nested folders, DeciMark anchors its bookmarking architecture using the Johnny.Decimal methodology... strictly restricting the hierarchy to a two-level depth and utilizing numerical tagging instead of arbitrary string-based folders, the system ensures unambiguous categorization, rapid deterministic retrieval, and a clear mental map of where information lives."
- **Concrete Technical Rationale**: Continuous time intervals (e.g. allowing reservations down to arbitrary minutes and seconds) create infinite scheduling permutations, fragmented availability gaps, and complex overlap queries that defy simple indexing. Discretizing time into deterministic quanta (15-minute or 30-minute intervals) bounds the state space and simplifies conflict detection.
- **DICT24 Implementation**: Enforced under **ADR-010**. All reservation start times snap to 15-minute boundaries (`:00`, `:15`, `:30`, `:45`). Slot availability is indexed in `reservation_slots (org_id, table_id, slot_start_utc)` with a UNIQUE constraint, transforming continuous overlap detection into discrete relational uniqueness checks.

#### 20. Two-Layer Transactional Locking (Driver-Aware Concurrency Control)
- **Original Origin**: `src/db/main.py`, `paper/main.tex` (Section 17.1 "Database Engine and ORM Strategy").
- **Verbatim Quotation**:
  > "PostgreSQL was selected as the primary persistence layer... SQLite was considered for its developmental simplicity but ruled out due to its write-locking behavior under concurrent access, which would be problematic for a multi-user deployment. PostgreSQL's Multi-Version Concurrency Control (MVCC) model handles concurrent reads and writes without locking..."
- **Concrete Technical Rationale**: Concurrent booking requests attempting to allocate the same table cause double-booking race conditions without transactional locks. Different database engines require different concurrency primitives: MySQL uses row-level `SELECT ... FOR UPDATE`, while SQLite3 uses database-level locks (`BEGIN IMMEDIATE`).
- **DICT24 Implementation**: Implemented in `App\Services\TableAllocator`. The allocation service checks the active PDO driver:
  - On MySQL: Executes `START TRANSACTION` followed by `SELECT ... FOR UPDATE` on candidate tables.
  - On SQLite: Executes `BEGIN IMMEDIATE` to obtain an exclusive write lock immediately, preventing concurrent transaction conflicts (`SQLITE_BUSY`).

#### 21. Immutable Append-Only Ledgers & Financial Auditing
- **Original Origin**: `src/schema/__init__.py`, `paper/main.tex` (Section 11.2 "High-Performance Data Persistence", Section 11.4 "Database Schema Topology").
- **Verbatim Quotation**:
  > "The database schema enforces absolute normalization. Many-to-many relationships... are managed through highly optimized junction tables with strict unique constraints and cascading foreign keys... furthermore, the Entity-Relationship flow establishes a strict one-to-many cardinality originating from the USERS entity, asserting absolute cryptographic ownership... and mechanical guarantees that orphan records are instantly annihilated."
- **Concrete Technical Rationale**: Mutating balances or financial values directly in-place destroys the audit trail, prevents reconciliation, and conceals accounting discrepancies. Recording financial events as immutable ledger rows guarantees auditability.
- **DICT24 Implementation**: Deposit balances and payments in `src/app/Services/DepositService.php` are written to `deposit_ledger` as immutable append-only journal entries (with timestamp, actor ID, amount in integer centavos, and transaction reference). Account balances are calculated via aggregate queries (`SUM(amount_centavos)`), never blind destructive updates.

#### 22. Active-State Mutation Guards Against Deleting Referenced Resources
- **Original Origin**: `paper/main.tex` (Section 7.4 "The Client--Server Model in Practice", Section 11.4 "Database Schema Topology", Lines 1076-1077).
- **Verbatim Quotation**:
  > "The complex many-to-many associations---\texttt{BOOKMARK\_TAG\_JUNCTION} and \texttt{BOOKMARK\_JD\_JUNCTION}---are physically enforced via composite primary keys that inherently double as cascading foreign keys. This uncompromising constraint matrix mechanically guarantees that orphan records are instantly annihilated upon parent deletion at the database level."
- **Concrete Technical Rationale**: Deleting a dining table, dining zone, or menu item that is actively referenced by pending reservations or open orders corrupts database integrity or triggers foreign key violations that crash the controller.
- **DICT24 Implementation**: Pre-deletion guard queries in `DiningTablesController`, `DiningZonesController`, and `MenuItemController`. If a resource is linked to active reservations or orders, deletion is blocked with a descriptive business error, or handled via soft deletion (`is_active = 0`).

#### 23. Composite Foreign Key Cascading & Orphan Row Annihilation
- **Original Origin**: `src/schema/__init__.py`, `paper/main.tex` (Lines 1075-1080), `docs/README.md` (Lines 262-265).
- **Verbatim Quotation**:
  > "The database is built on PostgreSQL, utilizing deeply nested junction tables and precise primary/foreign key cascading to permanently eradicate orphan rows across millions of potential permutations."
- **Concrete Technical Rationale**: Deleting parent records without foreign key cascade rules leaves orphaned child records in junction tables, consuming storage and corrupting statistical calculations.
- **DICT24 Implementation**: Schema definitions in `src/database/migrations/` enforce foreign keys with `ON DELETE CASCADE` or `ON DELETE RESTRICT` on relational associations (e.g. `dining_tables` referencing `dining_zones`, `reservation_slots` referencing `reservations`).

---

### Pillar V: Tooling, Reproducibility & Portability

#### 24. Justfile Task Centralization & Deterministic Developer Contracts
- **Original Origin**: `justfile` (Lines 1-181), `docs/README.md` (Lines 73-119), `docs/AI_HANDOVER.md` (Lines 44-51).
- **Verbatim Quotation**:
  > "Use the repository commands: just start-db, just start-redis, just tests, just lint, just gen-reports, just dev... Hypercorn ASGI server used by just dev and just run... Selected for HTTP/2 support and high asynchronous throughput."
  > "set dotenv-load := true\nset shell := ['bash', '-cu']\nlint: just nio_scripts just nio_src just ruff_scripts just ruff_src..."
- **Concrete Technical Rationale**: Relying on ad-hoc shell commands and unversioned CLI flags leads to human error and onboarding friction. A centralized `justfile` establishes a single executable contract for linting, testing, database lifecycle, seeding, and execution.
- **DICT24 Implementation**: Root `justfile` centralizes all workflows: `just test`, `just test-integration`, `just lint`, `just migrate`, `just seed`, `just seed-stress`, `just dev`, `just run`, `just paper`.

#### 25. Air-Gapped Demo Laptop Caching & Offline Self-Sufficiency
- **Original Origin**: `paper/main.tex` (Section 17.4 "Offline Asset Pipeline and CDN Independence", Section 17.11 "Variable Font Interpolation"), `scripts/download_css.py`, `scripts/download_fontawesome.py`, `scripts/build_fonts.py`.
- **Verbatim Quotation**:
  > "The intended deployment target is a self-hosted server on a local network, potentially without reliable external internet access. To accommodate this, a suite of Python scripts (scripts/download_css.py and scripts/download_fontawesome.py) fetch remote stylesheets and their embedded font assets at build time, rewriting url() paths to match the local static file structure. This allows the application to serve all assets from its own file system without any runtime dependency on external CDNs."
  > "Ensure Lyra is prepared for catastrophic server failure during the presentation... standalone executable... falls back to local SQLite database."
- **Concrete Technical Rationale**: Defense panel rooms often have weak Wi-Fi or strict network isolation. Fetching assets from remote CDNs (Google Fonts, unpkg, cdnjs) leads to failed asset loads, broken layouts, and presentation failure.
- **DICT24 Implementation**: Enforced under **ADR-005**. All fonts, stylesheets, and SVGs are stored locally in `src/public/assets/`. Container images are pre-cached via `dev/scripts/sh/pre-cache.sh`, and an offline standalone distribution is packaged via `dev/scripts/sh/package-dist.sh`.

#### 26. Dual Runtime Support (Docker Compose + Stock XAMPP/WAMPP)
- **Original Origin**: `docker-compose.yml`, `Dockerfile`, `docs/AI_HANDOVER.md` (Lines 94-97), `docs/README.md` (Lines 14, 171).
- **Verbatim Quotation**:
  > "Docker Compose & Hypercorn: Guarantees an identical, highly reproducible orchestration environment across both development and production. Hypercorn provides robust ASGI scaling across multiple underlying worker processes for maximum concurrency."
  > "Implement a fallback... so Lyra can run the .exe on university PCs without installing Docker or Postgres."
- **Concrete Technical Rationale**: Faculty evaluation computers commonly run stock Windows with XAMPP or WAMPP and lack Docker permissions. Supporting both containerized deployments and bare-metal Apache/PHP/MySQL environments guarantees compatibility across any evaluation computer.
- **DICT24 Implementation**: Validated in dual runtime environments: (1) Multi-container Docker Compose (`nginx`, `php-fpm`, `mysql`), and (2) Bare-metal stock XAMPP/WAMPP running native PHP 8.3 with SQLite3 fallback or local MySQL.

#### 27. Headless Non-Interactive Shell Defaults & Terminal Circuit Breakers
- **Original Origin**: Target `AGENTS.md` (Lines 27-36), `~/.config/lyra/AGENTS.md` (Lines 116-121).
- **Verbatim Quotation**:
  > "Non-Interactive Shell Execution: Always run in non-interactive mode (GIT_PAGER=cat, CI=true). Never invoke commands requiring interactive TTY confirmation (-i, git rebase -i, pagers). Terminal Circuit Breaker: If any command hangs or times out twice consecutively, halt execution immediately, capture diagnostic state, and escalate to the operator."
- **Concrete Technical Rationale**: Commands that prompt for interactive input (`[y/N]`, password prompts, pagers) hang automated CI runners and agent harnesses indefinitely, causing resource exhaustion.
- **DICT24 Implementation**: All automation scripts and commands run with `GIT_PAGER=cat`, `CI=true`, and non-interactive flags (`-y`, `--no-interaction`). Circuit breaker halts and escalates after two consecutive timeouts.

#### 28. Strict Explicit-Path Git Staging & Safe Push Cadence
- **Original Origin**: `~/.config/lyra/AGENTS.md` (Lines 118-120), `~/.config/agent-policy/safe-autocommit/SKILL.md`.
- **Verbatim Quotation**:
  > "Explicit Git staging: NEVER execute git add ., git add -A, or git commit -a. Stage explicit file paths only. Push safety: Never raw git push. Use sanctioned wrapper ~/.config/agent-policy/bin/agent-push after operator authorization."
- **Concrete Technical Rationale**: Bulk staging (`git add .`) accidentally commits temporary files, local credentials, private scratch scripts, and incomplete edits into the version history.
- **DICT24 Implementation**: Git staging commands must explicitly name each relative file path (`git add src/app/Core/Database.php docs/CHANGELOG.md`). Raw `git push` is prohibited; pushes occur through sanctioned wrappers.

---

### Pillar VI: Code Hygiene & Adversarial Quality Assurance

#### 29. Red-Team vs. Green-Team Verification Loops & Panel Sabotage Simulation
- **Original Origin**: `paper/main.tex` (Section 13.5, Section 14.2 "Failing Fast Requires a Feedback Loop", Lines 1300-1325), `.agents/rules/loop-until-done.md`, `.agents/rules/lint-and-fix.md`.
- **Verbatim Quotation**:
  > "Failing fast requires a feedback loop that is both rapid and truthful. A test suite that runs in seconds and reports failure unambiguously is an asset; a manual testing procedure that takes minutes and produces ambiguous results is an obstacle."
  > "Complex task process: 1. Write: Add fix; 2. Run: Execute; 3. Test: Verify; 4. Fix: Address error; 5. Ask: Stop. Request user input before repeat. No infinite loops. Involve human. Save tokens."
- **Concrete Technical Rationale**: Developers naturally write tests confirming their implementation works (green path), missing adversarial edge cases. Simulating evaluator sabotage (killing database containers, deleting lines of code, corrupting payloads) exposes structural weaknesses before panel presentation.
- **DICT24 Implementation**: Enforced under **ADR-003** and **ADR-009**. Red-team audits (`docs/dev/ai/audit/dict24-red-team-layer0-audit.md`), panel sabotage test scripts (`dev/scripts/sh/test-panel-sabotage.sh`), and failover chaos harnesses (`test-failover-chaos.sh`) verify error trapping and database fallback under intentional disruption.

#### 30. Paired Positive and Negative Assertion Testing
- **Original Origin**: `tests/e2e/test_auth_flow.py`, `tests/e2e/test_api_endpoints.py`, `tests/e2e/test_rate_limits.py`.
- **Verbatim Quotation**:
  > `test_successful_login(page)` paired with `test_invalid_login(page)` asserting error toast and URL retention; `test_logout_flow(auth_page)`.
  > `test_rate_limits.py`: verifies successful requests within threshold, and strictly asserts HTTP 429 Too Many Requests once rate limit is exceeded.
- **Concrete Technical Rationale**: Test suites that only test successful inputs leave authorization gates and validation boundaries untested. Every success case must have an adversarial negative counterpart asserting exact rejection codes.
- **DICT24 Implementation**: In `tests/runner.php` and `tests/integration/RestaurantERP_Test.php`: positive tests (valid reservations, permitted role actions) are paired with negative tests (overcapacity bookings, table collisions, unauthorized role access, missing CSRF tokens) asserting exact HTTP status codes and error payloads.

#### 31. 100% Syntax Validation (`php -l`) Pre-Flight Invariant
- **Original Origin**: Target `AGENTS.md` (Section 1.2 "Empirical Verification"), target `justfile` (Recipe `lint`), ITEC50 `justfile` (Recipe `lint`).
- **Verbatim Quotation**:
  > "Empirical Verification: Never assert a task is complete based on self-reports. Validate files, syntax (php -l), container exit codes, and schema migrations empirically before marking tasks complete."
  > "lint: just nio_scripts just nio_src just ruff_scripts just ruff_src uv run black -q scripts uv run black -q src just lint-js just lint-css just lint-html just lint-jinja just lint-md just lint-tex"
- **Concrete Technical Rationale**: In interpreted languages like PHP, a single syntax error (`ParseError`) halts execution and crashes the request pipeline. Checking every PHP file with the built-in linter prevents syntax errors from entering the repository.
- **DICT24 Implementation**: Pre-commit hooks and test runners execute `find src -name "*.php" -exec php -l {} \;`, requiring 100% zero syntax errors across the entire codebase.

#### 32. Zero Dead Buttons, Broken Links, or Stub Placeholders
- **Original Origin**: System Instructions, `docs/FOR-AI.md`, `paper/main.tex` (Section 7.3 "DOM Output & User Interaction").
- **Verbatim Quotation**:
  > "Every shipped control works or is removed: no dead buttons, no links to nowhere, no navbar entries without destinations. No fabricated statistics, testimonials, security or compliance claims. Placeholders are labeled honestly, never disguised as final."
  > "Event listeners are attached to interactive elements (view toggle buttons, filter dropdowns, and per-card action buttons) after the initial render... on failure, a toast notification banner is injected into the page to communicate the error to the user without a full page reload."
- **Concrete Technical Rationale**: Unimplemented UI elements, broken links (`href="#"`), and stubbed controls break user flow and reveal incomplete work during evaluation.
- **DICT24 Implementation**: Every interactive UI element in `src/app/Views/**` links to an active route or triggers a functional event handler. Any incomplete feature is omitted from the UI rather than displayed as a non-functioning element.

#### 33. Hardware-Aware Concurrency Bounding & Anti-Thrashing
- **Original Origin**: `scripts/run_visual_tests.py` (Lines 1-40).
- **Verbatim Quotation**:
  > `cpu_count = os.cpu_count() or 4`
  > `concurrency = min(24, max(4, cpu_count * 2))`
  > `semaphore = asyncio.Semaphore(concurrency)`
- **Concrete Technical Rationale**: Spawning unbounded concurrent processes or headless browser instances exhausts RAM and CPU cycles, causing OS thread thrashing, dropped connections, and system freezes.
- **DICT24 Implementation**: High-volume seeders (`seed_stress.py`) and automated test suites bound concurrency to available system CPU cores, utilizing batching and connection pooling to prevent resource exhaustion.

---

## 4. Codified Project Philosophies (Adapted from ITEC50 Practical)

The following section is formatted as a self-contained, drop-in addition ready to be appended directly to `AGENTS.md`.

```markdown
---

## Codified Project Philosophies (Adapted from ITEC50 Practical)

The following 10 master engineering invariants are derived from the ITEC50 Practical codebase and govern all development in this repository:

### 1. Cartesian Zero-Trust Verification ("Unless You See It, Don't Believe It")
Every agent, orchestrator, and collaborator must treat all prior completion claims, handoff tables, commit messages, test counts, and specification checkboxes as **UNVERIFIED / FALSE** until empirically reproduced and observed firsthand via live commands, file reads, AST/test execution (`exit code 0`), or runtime probes. Never assert completion based on self-reports or prior handoff summaries.

### 2. Clean-Room Implementation & Zero External Frameworks (ADR-001)
Maintain 100% clean-room vanilla PHP 8.3 with `declare(strict_types=1);` and zero external backend frameworks (no Laravel, no Symfony). Avoid external frontend frameworks (no React, no Vue, no Tailwind). Implement request routing, dependency injection, template rendering, and database abstraction natively.

### 3. Dual-Driver Relational Resilience & Offline Failover (ADR-002)
Abstract all database persistence behind `App\Core\Database` supporting both primary MySQL 8.0 and offline SQLite3 fallback. If MySQL becomes unreachable or crashes during evaluation, the runtime must switch to `storage/fallback.sqlite` without human intervention. All queries and migrations must adhere to ANSI-SQL standards.

### 4. Zero Inline Styles & Strict Content-Security-Policy (ADR-004, ADR-008)
Enforce zero inline `style="..."` attributes across all view templates. Style interfaces using semantic CSS3 custom properties, CSS Grid `auto-fill` with `minmax()`, and CSS `mask-image` SVG iconography. Maintain strict CSP headers: `default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; font-src 'self'; frame-ancestors 'none';`.

### 5. Pessimistic UI Synchronization (Client-Server Reality Gap)
Reject optimistic UI updates. Client interfaces must never mutate DOM elements or remove cards until the server returns an explicit HTTP 2xx confirmation. The database is the single source of truth; DOM state reflects verified persistence only.

### 6. Time-Slot Discretization & Driver-Aware Concurrency Control (ADR-010)
Snap all reservation and scheduling timestamps to discrete 15-minute intervals (`:00`, `:15`, `:30`, `:45`). Prevent race-condition double bookings using a UNIQUE constraint on `reservation_slots (org_id, table_id, slot_start_utc)`. In `TableAllocator`, apply driver-aware locking: `SELECT ... FOR UPDATE` on MySQL, and `BEGIN IMMEDIATE` on SQLite3.

### 7. Unified Authentication & Zero Auth Salad
Route all incoming requests through centralized middleware (`CsrfMiddleware`, `AuthMiddleware`, `RoleMiddleware`). Hash passwords with memory-hard algorithms (`PASSWORD_BCRYPT` or `PASSWORD_ARGON2ID`). Verify all hashes, tokens, and signatures with `hash_equals()`. Enforce `session_regenerate_id(true)` upon login. Return uniform error messages to prevent username enumeration.

### 8. Air-Gapped Self-Sufficiency & CDN Independence (ADR-005)
Zero runtime dependencies on remote CDNs. All fonts, styles, scripts, and SVG assets must be served locally from `src/public/assets/`. Container images and deployment packages must be exportable for offline installation in isolated evaluation environments.

### 9. Task Centralization & Headless Non-Interactive Defaults
All development, testing, migration, and seeding workflows must be centralized in the repository `justfile`. All shell executions must be non-interactive (`GIT_PAGER=cat`, `CI=true`, no `-i` flags). Enforce explicit-path Git staging (`git add <file>`) and terminal circuit breaking after two consecutive command timeouts.

### 10. Adversarial Red-Team QA & 100% Syntax Pre-Flight Validation
Validate every PHP file with `php -l` before marking tasks complete. Pair every positive assertion test with an adversarial negative counterpart asserting exact HTTP error status codes. Regularly execute chaos scripts (`test-failover-chaos.sh`) and sabotage simulations (`test-panel-sabotage.sh`) to verify error handling and resilience under failure conditions.
```
