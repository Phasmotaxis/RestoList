# DCIT 24 Information Management: 54-Ticket Atomic Volunteer Task Queue

- **Course**: DCIT 24 - Information Management (Academic Year 2026-2027)
- **Institution**: Department of Information Technology, Cavite State University (CvSU) - Main Campus
- **Project**: Enterprise Information Management System (EIMS) - Multi-Tenant Restaurant ERP & Reservation Engine
- **Target Roster**: 8 Members (1 Project Lead / Kernel Architect + 7 Dedicated Teammates)
- **Queue Model**: Kanban / Volunteer Pull Model (Self-Assigned Atomic Tasks)
- **Status**: OPEN FOR CLAIMING (Wave 3 Presentation, QA, Multimedia & Documentation Sprint)
- **Companion Documents**: [Project README](../README.md) | [Team Onboarding Guide & Developer Handbook](TEAM-ONBOARDING-GUIDE.md) | [Team Technical Allocation](TEAM-ALLOCATION.md) | [30-Task Role Backlog](TEAM-ALLOCATION-TASKS.md) | [Evaluator Demonstration Runbook](DEMO-RUNBOOK.md)

---

## 1. How This Queue Works (Volunteer Onboarding Rules)

Welcome to the active project queue for EIMS. For complete beginner-friendly onboarding, developer workflow guidelines, atomic commit practices, and our official Facebook Messenger ticket claiming rules, consult the **[Team Onboarding Guide & Developer Handbook](TEAM-ONBOARDING-GUIDE.md)** before picking your first ticket.

The remaining deliverables represent the critical human, multimedia, quality assurance, cultural curation, user documentation, and oral defense elements required for a 100% score under the Cavite State University DCIT 24 evaluation rubric.

Every ticket in this backlog is completely atomic, self-contained, bounded, and non-kernel. Completing these tasks requires zero modification to core algorithmic PHP engines.

### 1.1 Five Golden Rules of the Queue

1. **One Ticket at a Time (WIP Limit = 1)**: Every member may claim exactly one ticket at any given moment. Do not hoard multiple tickets. When you begin work, edit this document and update `[ ] Unassigned (Volunteer)` to `[x] @YourName (In Progress)`.
2. **Follow the Step-by-Step Instructions**: Every card contains concrete, non-intimidating instructions formatted as Step 1, Step 2, and Step 3. Follow them sequentially.
3. **Fulfill the Definition of Done (DoD)**: Before declaring a ticket complete, verify that every checkbox under its Definition of Done has been satisfied and that the artifact exists at the specified deliverable path.
4. **Mark as Complete and Hand Off**: Once your deliverable is verified, update your ticket marker to `[x] @YourName (Complete - YYYY-MM-DD)` and notify the team chat.
5. **Protect Kernel Code**: Do not modify files in `src/app/Core/`, `dev/tests/`, or database migrations unless a ticket explicitly provides a localized SQL seed script.

---

## 2. Master 54-Ticket Progress Dashboard

| ID | Title | Epic | Est. Time | Difficulty | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **TSK-VID-01** | Speaking Script: Member 1 (Introduction & High-Assurance Context) | Epic 1: Video | 45 min | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-VID-02** | Speaking Script: Member 2 (Problem Statement & Academic Objectives) | Epic 1: Video | 45 min | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-VID-03** | Speaking Script: Member 3 (Requirements & Clean-Room Architecture) | Epic 1: Video | 45 min | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-VID-04** | Speaking Script: Member 4 (18-Table ERD & Relational Concurrency) | Epic 1: Video | 45 min | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-VID-05** | Speaking Script: Member 5 (SuperAdmin Governance & Audit Trail) | Epic 1: Video | 45 min | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-VID-06** | Speaking Script: Member 6 (Hiraya Bistro ERP & 12x8 Floor Plan) | Epic 1: Video | 45 min | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-VID-07** | Speaking Script: Member 7 (Customer Diner Booking & Waitlist Sync) | Epic 1: Video | 45 min | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-VID-08** | Speaking Script: Member 8 (Quality Assurance, Sabotage & Conclusion) | Epic 1: Video | 45 min | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-VID-09** | OBS Studio Walkthrough Recording (1080p60 Screen Capture) | Epic 1: Video | 2.5 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-VID-10** | Audio Normalization & Cultural Background Music Curation | Epic 1: Video | 1.5 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-VID-11** | Closed Captions & Subtitles (.SRT Generation) | Epic 1: Video | 1.5 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-VID-12** | Video Assembly, Chapter Markers & YouTube/Drive Upload | Epic 1: Video | 2.5 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-SLD-01** | Canva / PPT Master Template in CvSU Green & Gold Palette | Epic 2: Slides | 1.5 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-SLD-02** | Title, Roster & Project Overview Slides (Slides 1-4) | Epic 2: Slides | 1.0 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-SLD-03** | Relational Architecture & 18-Table ERD Diagram Slide (Slides 5-8) | Epic 2: Slides | 1.5 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-SLD-04** | Multi-Tenant Feature Walkthrough & UI Callout Slides (Slides 9-14) | Epic 2: Slides | 1.5 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-SLD-05** | Testing, Hardening & Sabotage Defense Slides (Slides 15-18) | Epic 2: Slides | 1.5 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-SLD-06** | Complete Presenter Speaker Notes Compilation | Epic 2: Slides | 1.0 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-MEN-01** | Hiraya Bistro Filipino Appetizers Copywriting (5 Dishes) | Epic 3: Food & Data | 1.0 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-MEN-02** | Hiraya Bistro Main Ulam Copywriting & Pricing (8 Dishes) | Epic 3: Food & Data | 1.5 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-MEN-03** | Hiraya Bistro Traditional Desserts Copywriting (5 Dishes) | Epic 3: Food & Data | 1.0 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-MEN-04** | Hiraya Bistro Kapeng Barako & Cultural Drinks (5 Beverages) | Epic 3: Food & Data | 1.0 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-MEN-05** | Katipunan Grill Chicken Inasal & BBQ Specifications (6 Items) | Epic 3: Food & Data | 1.0 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-MEN-06** | Katipunan Grill Pinoy Street Skewers & Pulutan (6 Items) | Epic 3: Food & Data | 1.0 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-MEN-07** | Sourcing 25 High-Res Food Photos for Hiraya Bistro (WebP) | Epic 3: Food & Data | 2.0 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-MEN-08** | Sourcing 20 High-Res Food Photos for Katipunan Grill (WebP) | Epic 3: Food & Data | 2.0 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-MEN-09** | 50 Synthetic Filipino Customer Profiles & 5-Star Reviews | Epic 3: Food & Data | 2.0 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-MEN-10** | 100 Historical Reservation Simulation Seed Rows | Epic 3: Food & Data | 2.0 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-MQA-01** | Windows 10 Native XAMPP Clean Installation & Audit Report | Epic 4: Manual QA | 2.0 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-MQA-02** | Windows 11 Native XAMPP Clean Installation & Audit Report | Epic 4: Manual QA | 2.0 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-MQA-03** | Desktop Google Chrome Manual Form & Button Walkthrough | Epic 4: Manual QA | 1.5 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-MQA-04** | Desktop Microsoft Edge Manual Walkthrough & CSRF Audit | Epic 4: Manual QA | 1.5 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-MQA-05** | Desktop Mozilla Firefox Layout & Cookie Session Walkthrough | Epic 4: Manual QA | 1.5 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-MQA-06** | Mobile Safari (iOS) Touch & Viewport Manual Walkthrough | Epic 4: Manual QA | 1.5 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-MQA-07** | Mobile Chrome (Android) Touch & 15-Min Snapping Walkthrough | Epic 4: Manual QA | 1.5 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-MQA-08** | Tablet Viewport (iPad / Galaxy Tab) Grid Inspection | Epic 4: Manual QA | 1.5 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-MQA-09** | Theme Switcher Visual Contrast Audit (Lyra / Hiraya / Katipunan) | Epic 4: Manual QA | 1.5 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-MQA-10** | Error Boundary & Empty State UI Verification | Epic 4: Manual QA | 1.5 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-DOC-01** | Diner Portal Illustrated Step-by-Step User Guide | Epic 5: Docs | 2.0 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-DOC-02** | Restaurant Manager Illustrated Step-by-Step User Guide | Epic 5: Docs | 2.0 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-DOC-03** | SuperAdmin Multi-Tenant Illustrated User Guide | Epic 5: Docs | 2.0 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-DOC-04** | Evaluator Quickstart 1-Page Cheat Sheet (PDF/Poster Format) | Epic 5: Docs | 1.5 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-DOC-05** | 63-Page LaTeX Monograph Typographical & Spelling Proofread | Epic 5: Docs | 2.5 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-DOC-06** | Bibliography Citation Cross-Check against References (`references.bib`) | Epic 5: Docs | 1.5 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-DOC-07** | Physical Deliverable Print Preparation & Bookbinding Checklist | Epic 5: Docs | 1.0 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-DOC-08** | Glossary of Information Management & Restaurant ERP Terms | Epic 5: Docs | 1.5 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-DEF-01** | Top 25 Toughest Defense Questions & Formulated Answers Guide | Epic 6: Defense | 2.5 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-DEF-02** | Member-by-Member Defense Answer Assignment Sheet | Epic 6: Defense | 1.0 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-DEF-03** | Live Demo Script & Step-by-Step Operator Checklist (5-Min Timebox) | Epic 6: Defense | 1.5 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-DEF-04** | Panel Sabotage Drill Demonstration Script | Epic 6: Defense | 1.5 hr | Medium | `[ ] Unassigned (Volunteer)` |
| **TSK-DEF-05** | Offline Emergency Backup USB Flash Drive Setup | Epic 6: Defense | 1.0 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-DEF-06** | Presentation Day Logistics & Hardware Check (HDMI, Audio, Clicker) | Epic 6: Defense | 1.0 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-DEF-07** | Peer Evaluation Form & Contribution Log Verification | Epic 6: Defense | 1.0 hr | Easy | `[ ] Unassigned (Volunteer)` |
| **TSK-DEF-08** | Final Group Full-Dress Rehearsal Coordination & Timing Run | Epic 6: Defense | 2.0 hr | Medium | `[ ] Unassigned (Volunteer)` |

---

## 3. Comprehensive Epic Backlog & Detailed Ticket Cards

```
================================================================================
EPIC 1: VIDEO PRESENTATION & MEDIA PRODUCTION (12 TICKETS)
================================================================================
Goal: Deliver an unlisted YouTube/Drive 10-15 minute demonstration video
satisfying the official DCIT 24 course rubric (100% individual member voice turns,
zero silent pauses, live UI click-throughs, and crisp audio leveling).
```

### TSK-VID-01: Speaking Script: Member 1 (Introduction & High-Assurance Context)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 45 min
- **Prerequisites / Tools Needed**: Text editor, Markdown viewer, review of `README.md` and `docs/DEMO-RUNBOOK.md`
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new document at `docs/media/scripts/speaker-01-intro.md`.
  2. Write a timed 2-minute spoken dialogue (approximately 260-300 words) introducing Cavite State University, the DCIT 24 course, our group identity, and the overarching mission of EIMS as an enterprise multi-tenant restaurant ERP.
  3. Include on-screen stage cues indicating when to display the title slide, group member roster, and GitHub repository overview.
  4. Read the script aloud with a stopwatch to verify it completes between 1:45 and 2:05 minutes.
- **Definition of Done (Acceptance Criteria)**:
  - Script file exists at `docs/media/scripts/speaker-01-intro.md`.
  - Spoken runtime is verified at 2 minutes (+/- 15 seconds).
  - Explicit screen cues are noted for the video editor.
- **Deliverable Path**: `docs/media/scripts/speaker-01-intro.md`

---

### TSK-VID-02: Speaking Script: Member 2 (Problem Statement & Academic Objectives)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 45 min
- **Prerequisites / Tools Needed**: Text editor, review of `docs/dev/specifications/architecture.md` (ADR-001 through ADR-003)
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new document at `docs/media/scripts/speaker-02-objectives.md`.
  2. Compose a 2-minute script explaining the core problems facing Philippine food service enterprises: double bookings during peak dining rushes, unmanaged waitlists, lack of multi-tenant data isolation, and brittle dependency on cloud CDNs during local power or internet disruptions.
  3. Detail our academic solutions: clean-room vanilla PHP 8.3 standard library architecture, air-gapped self-sufficiency, and mathematical time-slot discretization.
  4. Time your read aloud to guarantee crisp pacing within 2 minutes.
- **Definition of Done (Acceptance Criteria)**:
  - Script file exists at `docs/media/scripts/speaker-02-objectives.md`.
  - Highlights specific business pain points and our architectural countermeasures.
  - Formatted with timestamp markers `[02:00 - 04:00]`.
- **Deliverable Path**: `docs/media/scripts/speaker-02-objectives.md`

---

### TSK-VID-03: Speaking Script: Member 3 (Requirements & Clean-Room Architecture)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 45 min
- **Prerequisites / Tools Needed**: Text editor, review of `paper/sections/03-system-architecture-and-design.tex`
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new document at `docs/media/scripts/speaker-03-stack.md`.
  2. Author a 2-minute spoken segment detailing our technology stack choices: PHP 8.3 Alpine (strict types enforced), Nginx reverse proxy, MySQL 8.0 Primary with automated runtime failover to SQLite3, and zero runtime NPM/CSS dependencies.
  3. Explain WHY we rejected bloated web frameworks (Laravel/Symfony) to guarantee deterministic execution, zero framework lock-in, and sub-15ms cold start times.
  4. Include stage directions to showcase terminal diagnostics (`just doctor`) and Docker container health.
- **Definition of Done (Acceptance Criteria)**:
  - Script file exists at `docs/media/scripts/speaker-03-stack.md`.
  - Word count is between 250 and 290 words.
  - Video transition points are documented clearly.
- **Deliverable Path**: `docs/media/scripts/speaker-03-stack.md`

---

### TSK-VID-04: Speaking Script: Member 4 (18-Table ERD & Relational Concurrency)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 45 min
- **Prerequisites / Tools Needed**: Text editor, review of `docs/dev/specifications/contracts/database.contract.md`
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new document at `docs/media/scripts/speaker-04-database.md`.
  2. Write a 2-minute technical walkthrough covering the 18 relational tables: organizations, restaurants, dining_zones, dining_tables, reservation_slots, deposit_ledgers, and audit_logs.
  3. Clearly articulate the 15-minute slot boundary discretization algorithm and how the unique database constraint `(org_id, table_id, slot_start_utc)` mathematically eliminates race-condition double bookings.
  4. Rehearse the script with a timer to ensure fluid delivery without rushing technical terminology.
- **Definition of Done (Acceptance Criteria)**:
  - Script file exists at `docs/media/scripts/speaker-04-database.md`.
  - Accurately references table names, constraints, and ANSI-SQL dual-driver compliance.
  - Includes exact visual cues for displaying the Crow's Foot ERD diagram.
- **Deliverable Path**: `docs/media/scripts/speaker-04-database.md`

---

### TSK-VID-05: Speaking Script: Member 5 (SuperAdmin Governance & Audit Trail)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 45 min
- **Prerequisites / Tools Needed**: Text editor, local browser inspection of `/superadmin/organizations`
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new document at `docs/media/scripts/speaker-05-superadmin.md`.
  2. Write a 2-minute live demo commentary following the SuperAdmin persona (`superadmin@cvsu.edu.ph`).
  3. Cover tenant provisioning, toggling organization feature flags (`enable_deposits`, `enable_waitlist`), viewing system-wide audit logs, and demonstrating bitwise RBAC privilege separation.
  4. Ensure instructions align with the live click actions in `docs/DEMO-RUNBOOK.md` Phase 2.
- **Definition of Done (Acceptance Criteria)**:
  - Script file exists at `docs/media/scripts/speaker-05-superadmin.md`.
  - Matches live SuperAdmin screen recording flow step-by-step.
  - Verified timing is within 1:50 to 2:10 minutes.
- **Deliverable Path**: `docs/media/scripts/speaker-05-superadmin.md`

---

### TSK-VID-06: Speaking Script: Member 6 (Hiraya Bistro ERP & 12x8 Floor Plan)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 45 min
- **Prerequisites / Tools Needed**: Text editor, local browser inspection of `/admin/restaurants/1/tables`
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new document at `docs/media/scripts/speaker-06-restaurant-erp.md`.
  2. Write a 2-minute narration covering the Restaurant Admin perspective (`manager@hirayabistro.ph`).
  3. Guide the viewer through the 12x8 CSS Grid interactive dining floor plan, zone allocations (Al Fresco Garden vs Main Dining Hall), real-time table occupancy status, and the 86-list menu toggle.
  4. Synchronize narrative beats with the mouse clicks showing table capacity adjustments and staff role delegation.
- **Definition of Done (Acceptance Criteria)**:
  - Script file exists at `docs/media/scripts/speaker-06-restaurant-erp.md`.
  - Highlights the Filipino cultural aesthetic of Hiraya Bistro.
  - Contains exact UI callouts for table grid interactions.
- **Deliverable Path**: `docs/media/scripts/speaker-06-restaurant-erp.md`

---

### TSK-VID-07: Speaking Script: Member 7 (Customer Diner Booking & Waitlist Sync)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 45 min
- **Prerequisites / Tools Needed**: Text editor, local browser inspection of `/discover` and `/bookings/new`
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new document at `docs/media/scripts/speaker-07-customer-diner.md`.
  2. Author a 2-minute live walkthrough script for the public Customer Diner portal.
  3. Detail restaurant discovery, dynamic menu browsing respecting the 86-list, 15-minute slot reservation booking with instant confirmation, and remote queueing on the waitlist with real-time position tracking.
  4. Add notes explaining the pessimistic UI synchronization invariant (DOM only updates upon verified HTTP 200 response).
- **Definition of Done (Acceptance Criteria)**:
  - Script file exists at `docs/media/scripts/speaker-07-customer-diner.md`.
  - Covers diner search, reservation creation, and queue status check.
  - Verified read time is 2 minutes.
- **Deliverable Path**: `docs/media/scripts/speaker-07-customer-diner.md`

---

### TSK-VID-08: Speaking Script: Member 8 (Quality Assurance, Sabotage & Conclusion)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 45 min
- **Prerequisites / Tools Needed**: Text editor, review of `dev/scripts/sh/test-panel-sabotage.sh` and test results
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new document at `docs/media/scripts/speaker-08-qa-conclusion.md`.
  2. Author a 2-minute grand finale script summarizing our rigorous verification: 41 integration tests, 82 security hardening tests, 100% PHP syntax linting, and zero WCAG contrast failures.
  3. Narrate the live sabotage test (`just test-sabotage`) demonstrating how simulated database disconnects and SQL injection payloads are intercepted by the Defense Kernel with zero data loss.
  4. Conclude with acknowledgment of Cavite State University faculty and closing remarks.
- **Definition of Done (Acceptance Criteria)**:
  - Script file exists at `docs/media/scripts/speaker-08-qa-conclusion.md`.
  - Details exact test counts and sabotage survival mechanisms.
  - Professional, confident conclusion honoring university project guidelines.
- **Deliverable Path**: `docs/media/scripts/speaker-08-qa-conclusion.md`

---

### TSK-VID-09: OBS Studio Walkthrough Recording (1080p60 Screen Capture)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 2.5 hr
- **Prerequisites / Tools Needed**: OBS Studio (or equivalent screen recorder), 1920x1080 display, clean browser profile
- **Step-by-Step Instructions ("How to Do It")**:
  1. Configure OBS Studio video settings to 1920x1080 resolution at 60 FPS, with high-quality MP4/MKV recording profile.
  2. Open a clean browser window at `http://localhost:8080/` (or local XAMPP host). Set zoom to 100%.
  3. Follow `docs/DEMO-RUNBOOK.md` sequentially, capturing smooth mouse movements across all 6 demonstration phases: Public Landing, SuperAdmin, Restaurant Admin, Floor Plan Grid, Customer Booking, and Terminal Sabotage execution.
  4. Record each section in clean takes without hurried cursor jitter. Save raw footage in `dist/media/recordings/`.
- **Definition of Done (Acceptance Criteria)**:
  - Raw video files recorded at 1080p60 with crisp typography and zero dropped frames.
  - All 6 phases from `docs/DEMO-RUNBOOK.md` recorded completely.
  - Files organized in `dist/media/recordings/` with clear sequence names.
- **Deliverable Path**: `dist/media/recordings/raw-walkthrough-1080p.mp4`

---

### TSK-VID-10: Audio Normalization & Cultural Background Music Curation
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: Audacity, DaVinci Resolve, or any digital audio workstation; copyright-free Filipino acoustic music
- **Step-by-Step Instructions ("How to Do It")**:
  1. Collect recorded audio takes from all 8 team members.
  2. Apply noise reduction, equalization (speech clarity), and loudness normalization to match the YouTube standard (-14 LUFS integrated, -1.0 dB true peak).
  3. Source a royalty-free, low-tempo Filipino acoustic guitar or traditional instrument track (e.g., Kundiman/Harana inspired) for ambient background audio.
  4. Mix dialogue and music ensuring background track remains ducked at -24 dB to preserve vocal intelligibility.
- **Definition of Done (Acceptance Criteria)**:
  - Dialogue levels across all 8 speakers are consistent in volume and clarity.
  - Background music is mixed properly without overpowering speech.
  - Exported master audio track saved in `dist/media/audio/master-voiceover-normalized.wav`.
- **Deliverable Path**: `dist/media/audio/master-voiceover-normalized.wav`

---

### TSK-VID-11: Closed Captions & Subtitles (.SRT Generation)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: Subtitle editor (Aegisub, Subtitle Edit, or YouTube Studio auto-sync), scripts from TSK-VID-01 to 08
- **Step-by-Step Instructions ("How to Do It")**:
  1. Compile the verified text from all 8 speaker scripts into a unified subtitle document.
  2. Synchronize dialogue lines with the master video timeline down to millisecond accuracy.
  3. Ensure lines follow accessibility standards: maximum 37 characters per line, maximum 2 lines per subtitle card, minimum display duration of 1.2 seconds.
  4. Export the completed subtitle file to `docs/media/subtitles.srt`.
- **Definition of Done (Acceptance Criteria)**:
  - Valid `.srt` file created at `docs/media/subtitles.srt`.
  - Zero spelling errors in technical terms (e.g., TableAllocator, RBAC, SQLite, ANSI-SQL).
  - Accurate time synchronization verified against test video playback.
- **Deliverable Path**: `docs/media/subtitles.srt`

---

### TSK-VID-12: Video Assembly, Chapter Markers & YouTube/Drive Upload
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 2.5 hr
- **Prerequisites / Tools Needed**: Video editor (CapCut, Premiere, DaVinci Resolve, Kdenlive), YouTube or Google Drive account
- **Step-by-Step Instructions ("How to Do It")**:
  1. Import 1080p60 screen recordings, normalized 8-speaker audio, slide graphics, and lower-third title cards identifying each student by name and role.
  2. Assemble the complete 12 to 15-minute video presentation adhering strictly to the storyboard in `docs/DEMO-RUNBOOK.md`.
  3. Export the finished render in H.264/AAC format at high bitrate.
  4. Upload to YouTube as an Unlisted video (and create a mirror on Google Drive). Record the shareable link and timestamp chapters in `docs/media/video-submission.md`.
- **Definition of Done (Acceptance Criteria)**:
  - Master video exported at 1080p with all 8 team members featured.
  - YouTube Unlisted URL and Google Drive backup link recorded in `docs/media/video-submission.md`.
  - Detailed description includes all chapter timestamps, project repository link, and group member credits.
- **Deliverable Path**: `docs/media/video-submission.md`

```
================================================================================
EPIC 2: PRESENTATION SLIDE DECK (6 TICKETS)
================================================================================
Goal: Produce an academic slide deck adhering to Cavite State University
branding (Green & Gold palette), visually conveying our architecture,
relational schema, and security defense in oral panel defense.
```

### TSK-SLD-01: Canva / PPT Master Template in CvSU Green & Gold Palette
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: Canva or Microsoft PowerPoint; official CvSU branding guidelines
- **Step-by-Step Instructions ("How to Do It")**:
  1. Create a 16:9 widescreen presentation deck in Canva or PowerPoint.
  2. Establish master color styles: CvSU Primary Green (`#1E4D2B`), CvSU Gold (`#F2C744`), Deep Forest (`#0D2818`), Soft Ivory (`#FAF9F6`), and Charcoal (`#222222`).
  3. Define consistent typographical hierarchy: Bold Serif or Clean Sans header (e.g., Cinzel / Montserrat / Inter) and readable body text (minimum 18pt font).
  4. Include official Cavite State University and Department of Information Technology logos in the header/footer safe zones.
- **Definition of Done (Acceptance Criteria)**:
  - Master template file saved as `docs/media/presentation-template.pptx` (or accessible Canva template link).
  - Palette matches official university colors with contrast ratios exceeding WCAG AA.
  - Master layout slides include Title, Split Content, Architecture Diagram, and Full-Bleed Screenshot layouts.
- **Deliverable Path**: `docs/media/presentation-template.pptx`

---

### TSK-SLD-02: Title, Roster & Project Overview Slides (Slides 1-4)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.0 hr
- **Prerequisites / Tools Needed**: PowerPoint / Canva, student roster details, `README.md`
- **Step-by-Step Instructions ("How to Do It")**:
  1. Slide 1 (Title): Project Title "Enterprise Information Management System (EIMS) - Multi-Tenant Restaurant ERP", course code "DCIT 24", and semester "A.Y. 2026-2027".
  2. Slide 2 (Roster): Complete list of all 8 group members with their respective assigned roles (Project Lead, Video Lead, QA Specialist, Data Curator, Technical Writer, etc.).
  3. Slide 3 (Executive Summary & Problem Statement): High-level overview of manual reservation bottlenecks, concurrency race conditions, and lack of enterprise IT systems in local culinary SMEs.
  4. Slide 4 (Project Objectives & Scope): Clear bulleted list of functional and non-functional goals.
- **Definition of Done (Acceptance Criteria)**:
  - Slides 1 through 4 populated with exact, error-free copy.
  - All 8 student names and student numbers formatted accurately.
  - Exported markdown outline committed to `docs/media/slides/01-overview.md`.
- **Deliverable Path**: `docs/media/slides/01-overview.md`

---

### TSK-SLD-03: Relational Architecture & 18-Table ERD Diagram Slide (Slides 5-8)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: PowerPoint / Canva, `docs/dev/specifications/contracts/database.contract.md`
- **Step-by-Step Instructions ("How to Do It")**:
  1. Slide 5 (Relational Data Model Overview): Explain the 18 tables grouped into 5 logical clusters (Governance, Tenant Hierarchy, Table Allocation, Financial Ledger, Audit Trail).
  2. Slide 6 (Crow's Foot ERD Diagram): Clean, high-resolution rendering of the complete entity-relationship model with primary and foreign key indicators.
  3. Slide 7 (Mathematical Concurrency Engine): Visual diagram explaining 15-minute slot boundary snapping and the `reservation_slots` unique compound index preventing double bookings.
  4. Slide 8 (Dual-Driver Persistence Engine): Infographic showing seamless runtime failover from MySQL 8.0 Primary to local SQLite3 Fallback.
- **Definition of Done (Acceptance Criteria)**:
  - Slides 5 through 8 populated with crisp diagrams and vector icons.
  - Zero blurry or unreadable relationship cardinality labels.
  - Slide content documented in `docs/media/slides/02-architecture-erd.md`.
- **Deliverable Path**: `docs/media/slides/02-architecture-erd.md`

---

### TSK-SLD-04: Multi-Tenant Feature Walkthrough & UI Callout Slides (Slides 9-14)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: PowerPoint / Canva, screenshot assets from `docs/specimens/screenshots/`
- **Step-by-Step Instructions ("How to Do It")**:
  1. Slide 9: SuperAdmin Multi-Tenant Provisioning & Organization Governance.
  2. Slide 10: Restaurant Manager Operations & Zone Management (Hiraya Bistro).
  3. Slide 11: Interactive 12x8 CSS Grid Table Layout & Real-Time Status Indicators.
  4. Slide 12: Customer Diner Discovery, Dynamic Menu Browsing & 86-List Sync.
  5. Slide 13: Customer Booking Flow & Waitlist Queue Position Tracking.
  6. Slide 14: Cultural Visual Theming Engine (Personal Lyra, Filipiniana Hiraya, and Smokehouse Katipunan).
- **Definition of Done (Acceptance Criteria)**:
  - Slides 9 through 14 populated with real application screenshots and clear directional callout boxes.
  - No generic lorem ipsum text; uses real Filipino restaurant dishes and tenant data.
  - Text summary documented in `docs/media/slides/03-features-ui.md`.
- **Deliverable Path**: `docs/media/slides/03-features-ui.md`

---

### TSK-SLD-05: Testing, Hardening & Sabotage Defense Slides (Slides 15-18)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: PowerPoint / Canva, metrics from `LATEX-DATA-PACK.md` and test logs
- **Step-by-Step Instructions ("How to Do It")**:
  1. Slide 15: Quality Assurance Philosophy ("Cartesian Zero-Trust") & Test Hierarchy (Unit, Integration, Security Hardening).
  2. Slide 16: Empirical Verification Metrics (41 Integration Tests, 82 Hardening Tests, 100% PHP Syntax Validation, Sub-15ms Cold Start).
  3. Slide 17: Panel Sabotage Simulation & Resilience (Intercepting SQL Injections, Tampered Session Cookies, and Instant Database Failover).
  4. Slide 18: Academic Conclusion & Future Roadmap for Philippine SME Restaurant Technology.
- **Definition of Done (Acceptance Criteria)**:
  - Slides 15 through 18 completed with verified test numbers and terminal output snippets.
  - Demonstrates rock-solid resilience to the evaluation panel.
  - Text outline saved in `docs/media/slides/04-testing-defense.md`.
- **Deliverable Path**: `docs/media/slides/04-testing-defense.md`

---

### TSK-SLD-06: Complete Presenter Speaker Notes Compilation
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.0 hr
- **Prerequisites / Tools Needed**: Text editor, completed slides from TSK-SLD-01 to 05
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open `docs/media/slide-speaker-notes.md`.
  2. Create slide-by-slide speaker notes for each of the 18 presentation slides.
  3. For every slide, write: Primary Presenter, Target Duration (in seconds), Key Technical Points to Emphasize, and Transition Line to the Next Slide.
  4. Embed these notes directly into the PowerPoint presentation notes panel as well.
- **Definition of Done (Acceptance Criteria)**:
  - Document `docs/media/slide-speaker-notes.md` contains complete cue notes for all 18 slides.
  - Every team member has designated speaking slides.
  - Total deck delivery time is calculated to fit exactly into a 15-minute presentation window.
- **Deliverable Path**: `docs/media/slide-speaker-notes.md`

```
================================================================================
EPIC 3: CULTURAL FOOD ASSETS, MENU COPYWRITING & SEED DATA (10 TICKETS)
================================================================================
Goal: Elevate the presentation from generic placeholder text to authentic,
culturally rich Filipino culinary experiences across our two pilot tenants:
Hiraya Bistro (Filipiniana Fine Dining) and Katipunan Grill (Barako Street Food).
```

### TSK-MEN-01: Hiraya Bistro Filipino Appetizers Copywriting (5 Dishes)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.0 hr
- **Prerequisites / Tools Needed**: Text editor, Filipino culinary research
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new seed file at `dev/seeds/menu/hiraya-appetizers.json`.
  2. Author rich, mouth-watering menu copy for 5 authentic Filipino appetizers:
     - Lumpiang Shanghai Espesyal (crispy pork rolls with sweet chili dip)
     - Kinilaw na Tanigue sa Sukang Paombong (cured Spanish mackerel with ginger and chili)
     - Crispy Calamares Tagalog (deep-fried squid rings with spiced vinegar)
     - Tokwa't Baboy ala Hiraya (fried bean curd and pork belly in savory soy-vinegar glaze)
     - Gambas al Ajillo Filipina (succulent shrimp sautéed in garlic-infused olive oil and chili)
  3. Specify exact centavo prices (e.g., 22000 for PHP 220.00), categories, calorie estimates, preparation times, and allergen tags (Shellfish, Pork, Soy, Gluten).
- **Definition of Done (Acceptance Criteria)**:
  - Valid JSON file committed at `dev/seeds/menu/hiraya-appetizers.json`.
  - All 5 dishes include complete metadata (name, description, price_centavos, allergens, prep_time).
  - Copy is culturally authentic, elegant, and professional.
- **Deliverable Path**: `dev/seeds/menu/hiraya-appetizers.json`

---

### TSK-MEN-02: Hiraya Bistro Main Ulam Copywriting & Pricing (8 Dishes)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: Text editor, Filipino culinary research
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new seed file at `dev/seeds/menu/hiraya-ulam.json`.
  2. Detail 8 signature Filipino main courses:
     - Sinigang na Baboy sa Sampalok (tender pork belly in sour tamarind broth with native vegetables)
     - Kare-Kareng Buntot ng Baka (slow-braised oxtail and tripe in rich peanut gravy with bagoong)
     - Crispy Pata Tradisyonal (deep-fried pork knuckle with crispy crackling skin)
     - Bistek Tagalog Especial (tender beef sirloin braised in calamansi, soy sauce, and white onions)
     - Adobo sa Gata at Dilaw (chicken and pork simmered in coconut milk, turmeric, and garlic)
     - Bicol Express Malalente (spicy pork braised in coconut cream and bird's eye chilies)
     - Lechon Kawali Bagnet Style (double-fried crispy pork belly served with liver sauce)
     - Pinakbet Ilokano Royale (indigenous squash, bitter melon, okra, and eggplant with fermented bagoong)
  3. Include realistic centavo pricing (ranging from PHP 320.00 to PHP 780.00) and allergen tags.
- **Definition of Done (Acceptance Criteria)**:
  - Valid JSON file committed at `dev/seeds/menu/hiraya-ulam.json`.
  - All 8 dishes thoroughly described with allergen and culinary provenance tags.
  - Prices formatted in standard integer centavos.
- **Deliverable Path**: `dev/seeds/menu/hiraya-ulam.json`

---

### TSK-MEN-03: Hiraya Bistro Traditional Desserts Copywriting (5 Dishes)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.0 hr
- **Prerequisites / Tools Needed**: Text editor, traditional Filipino kakanin knowledge
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new seed file at `dev/seeds/menu/hiraya-desserts.json`.
  2. Author copy for 5 authentic desserts:
     - Special Halo-Halo Espesyal (layered sweetened beans, nata de coco, ube halaya, leche flan, and pinipig)
     - Leche Flan Tradisyonal (silky steamed egg custard bathed in rich golden caramel syrup)
     - Bibingka Galapong de Indang (freshly baked rice cake topped with salted egg, butter, and grated coconut)
     - Ube Halaya Supreme (creamy purple yam halaya topped with golden latik curds)
     - Turon de Leche Glazed (crisp plantain banana roll with jackfruit strips and brown sugar glaze)
  3. Populate price_centavos (PHP 120.00 to PHP 220.00) and dietary tags (Vegetarian, Dairy, Egg).
- **Definition of Done (Acceptance Criteria)**:
  - Valid JSON file at `dev/seeds/menu/hiraya-desserts.json`.
  - Captures traditional Cavite culinary heritage.
  - Syntactically verified JSON structure.
- **Deliverable Path**: `dev/seeds/menu/hiraya-desserts.json`

---

### TSK-MEN-04: Hiraya Bistro Kapeng Barako & Cultural Drinks (5 Beverages)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.0 hr
- **Prerequisites / Tools Needed**: Text editor, Cavite coffee and beverage heritage research
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new seed file at `dev/seeds/menu/hiraya-drinks.json`.
  2. Detail 5 regional beverages celebrating Cavite's local produce:
     - Kapeng Barako Brewed (authentic Liberica coffee freshly dripped with muscovado sugar)
     - Tsokolate de Batirol (rich native tablea chocolate frothed with wooden batirol)
     - Sago't Gulaman de Hiraya (refreshing brown sugar syrup cooler with grass jelly and tapioca pearls)
     - Fresh Calamansi Honey Cooler (cold-pressed local calamansi sweetened with wild Cavite honey)
     - Mango Pandan Cream Slush (ripe Carabao mango purée blended with pandan-infused cream)
  3. Provide exact pricing in centavos and hot/cold serving specifications.
- **Definition of Done (Acceptance Criteria)**:
  - Valid JSON file at `dev/seeds/menu/hiraya-drinks.json`.
  - Features authentic regional Cavite ingredients (Amadeo Barako, Alfonso Tablea).
  - All 5 beverages complete with descriptions and price_centavos.
- **Deliverable Path**: `dev/seeds/menu/hiraya-drinks.json`

---

### TSK-MEN-05: Katipunan Grill Chicken Inasal & BBQ Specs (6 Items)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.0 hr
- **Prerequisites / Tools Needed**: Text editor, grill and smokehouse culinary research
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new seed file at `dev/seeds/menu/katipunan-bbq.json`.
  2. Define 6 signature charcoal-grilled specialties for Katipunan Grill:
     - Bacolod Chicken Inasal Pecho (marinated chicken breast and wing basted in annatto-calamansi oil)
     - Chicken Inasal Paa (succulent chicken thigh and drumstick with sinamak vinegar)
     - Inihaw na Liempo Klasik (charbroiled pork belly slab marinated in garlic, soy, and calamansi)
     - Katipunan Pork BBQ Skewers (3 jumbo bamboo skewers glazed in sweet-savory banana ketchup sauce)
     - Inihaw na Tuna Panga (charcoal-grilled tuna jaw with garlic-soy dipping sauce)
     - Boneless Bangus Inihaw (milkfish stuffed with fresh tomatoes, onions, and ginger)
  3. Include portion sizes, marinades, and centavo prices.
- **Definition of Done (Acceptance Criteria)**:
  - Valid JSON file at `dev/seeds/menu/katipunan-bbq.json`.
  - Reflects the rustic smokehouse persona of Katipunan Grill.
  - Integer centavo pricing formatted accurately.
- **Deliverable Path**: `dev/seeds/menu/katipunan-bbq.json`

---

### TSK-MEN-06: Katipunan Grill Pinoy Street Skewers & Pulutan (6 Items)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.0 hr
- **Prerequisites / Tools Needed**: Text editor, Filipino street food culinary knowledge
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new seed file at `dev/seeds/menu/katipunan-skewers.json`.
  2. Author copy for 6 street skewers and pulutan favorites:
     - Isaw ng Manok (grilled chicken intestines cleaned and charred with spiced vinegar dip)
     - Tenga ng Baboy (crisp-charred pork ears skewered in sweet chili barbecue glaze)
     - Betamax Dugo Skewers (coagulated pork blood cubes grilled over charcoal)
     - Crispy Kwek-Kwek Basket (battered deep-fried quail eggs served with sweet manong sauce)
     - Tokneneng Special (jumbo chicken egg in crispy orange batter with spicy cucumber relish)
     - Balut Penoy Supreme (boiled developing duck egg served with rock salt and sinamak)
  3. Assign accessible price points (PHP 45.00 to PHP 95.00) in centavos.
- **Definition of Done (Acceptance Criteria)**:
  - Valid JSON file at `dev/seeds/menu/katipunan-skewers.json`.
  - Culturally accurate, fun, and engaging street food descriptions.
  - Includes allergen and spice-level tags.
- **Deliverable Path**: `dev/seeds/menu/katipunan-skewers.json`

---

### TSK-MEN-07: Sourcing 25 High-Res Food Photos for Hiraya Bistro (WebP)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 2.0 hr
- **Prerequisites / Tools Needed**: Free stock imagery sources (Unsplash, Pexels, Wikimedia Commons) or camera; image converter (cwebp, Squoosh, or Photoshop)
- **Step-by-Step Instructions ("How to Do It")**:
  1. Source 25 copyright-free, mouth-watering photographs representing the appetizers, main ulam, desserts, and beverages defined for Hiraya Bistro.
  2. Crop and resize all images to a uniform aspect ratio of 4:3 (e.g., 800x600 pixels).
  3. Convert and compress each image to `.webp` format, ensuring file size is strictly under 150 KB per image.
  4. Place all 25 images into `src/public/assets/images/food/hiraya/` matching standard naming conventions (e.g., `sinigang.webp`, `kare-kare.webp`, `halo-halo.webp`).
- **Definition of Done (Acceptance Criteria)**:
  - Exactly 25 WebP image files exist in `src/public/assets/images/food/hiraya/`.
  - Every image is optimized under 150 KB with crisp visual clarity.
  - Image filenames match the item slug identifiers.
- **Deliverable Path**: `src/public/assets/images/food/hiraya/`

---

### TSK-MEN-08: Sourcing 20 High-Res Food Photos for Katipunan Grill (WebP)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 2.0 hr
- **Prerequisites / Tools Needed**: Stock photo search, image editing tool
- **Step-by-Step Instructions ("How to Do It")**:
  1. Source 20 high-quality, appetite-inducing images of charcoal-grilled chicken inasal, pork barbecue, liempo, grilled seafood, and street skewers.
  2. Crop each image to 4:3 ratio (800x600) for uniform rendering on cards and modals.
  3. Convert to `.webp` format with compression target between 80 KB and 140 KB.
  4. Save all 20 images into `src/public/assets/images/food/katipunan/` (e.g., `inasal-pecho.webp`, `isaw.webp`, `kwek-kwek.webp`).
- **Definition of Done (Acceptance Criteria)**:
  - Exactly 20 WebP image files reside in `src/public/assets/images/food/katipunan/`.
  - Uniform dimension and compression standards satisfied.
  - High aesthetic visual appeal matching the rustic smokehouse brand.
- **Deliverable Path**: `src/public/assets/images/food/katipunan/`

---

### TSK-MEN-09: 50 Synthetic Filipino Customer Profiles & 5-Star Reviews
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 2.0 hr
- **Prerequisites / Tools Needed**: Text editor, SQL editor
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new seed script at `dev/seeds/reviews.sql`.
  2. Author SQL INSERT statements generating 50 synthetic, culturally authentic Filipino diner profiles (e.g., Juan Dela Cruz, Maria Santos, Rafael Agoncillo, Bea Valderama) across Cavite and Manila cities.
  3. For each profile, create realistic 4-star and 5-star customer reviews praising specific dishes (e.g., "The Sinigang na Baboy broth had the perfect tamarind sourness!", "Crispy pata crackling stayed crunchy throughout our family dinner!").
  4. Ensure all timestamps are spread across the past 90 days with proper foreign key linking to `restaurant_id = 1` and `restaurant_id = 2`.
- **Definition of Done (Acceptance Criteria)**:
  - Valid SQL file committed at `dev/seeds/reviews.sql`.
  - Exactly 50 customer review rows with realistic Filipino names and thoughtful testimonials.
  - Executes cleanly against both MySQL and SQLite3 databases without syntax errors.
- **Deliverable Path**: `dev/seeds/reviews.sql`

---

### TSK-MEN-10: 100 Historical Reservation Simulation Seed Rows
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 2.0 hr
- **Prerequisites / Tools Needed**: Text editor, SQL editor, review of `reservation_slots` schema
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new seed script at `dev/seeds/reservations_history.sql`.
  2. Generate 100 historical reservation rows spanning lunch (11:00 to 14:00) and dinner (17:00 to 21:00) dining windows over the previous 30 days.
  3. Allocate party sizes from 2 to 8 guests across tables 1 through 16 in Hiraya Bistro and Katipunan Grill.
  4. Distribute statuses across realistic operational outcomes: 82 `confirmed`/`completed`, 12 `cancelled`, and 6 `no_show`.
  5. Ensure corresponding `reservation_slots` entries are populated conforming to 15-minute slot boundary constraints.
- **Definition of Done (Acceptance Criteria)**:
  - Valid SQL file committed at `dev/seeds/reservations_history.sql`.
  - Exactly 100 distinct reservations with realistic status distribution.
  - Enables rich analytics dashboard rendering (turnover rates, peak hours heatmap, no-show rate).
- **Deliverable Path**: `dev/seeds/reservations_history.sql`

```
================================================================================
EPIC 4: CROSS-PLATFORM MANUAL QA & DEVICE LAB (10 TICKETS)
================================================================================
Goal: Empirically prove system resilience across physical hardware, diverse
operating systems, modern web browsers, mobile viewports, and edge-case error states.
```

### TSK-MQA-01: Windows 10 Native XAMPP Clean Installation & Audit Report
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 2.0 hr
- **Prerequisites / Tools Needed**: Windows 10 PC, XAMPP 8.2+ installer (with Apache and MySQL)
- **Step-by-Step Instructions ("How to Do It")**:
  1. Perform a clean extraction of the EIMS repository or XAMPP bundle into `C:\xampp\htdocs\eims`.
  2. Start Apache and MySQL from the XAMPP Control Panel.
  3. Execute database migration via SQLite fallback or MySQL import.
  4. Open `http://localhost/eims/src/public/` in Google Chrome and verify Apache URL rewrite rules (`.htaccess`).
  5. Document your environment specifications, steps taken, test logins, and screenshots in `docs/dev/windows10-qa.md`.
- **Definition of Done (Acceptance Criteria)**:
  - Audit document committed at `docs/dev/windows10-qa.md`.
  - Validates that EIMS operates seamlessly on bare-metal Windows 10 without Docker.
  - Includes confirmation of login for all 5 evaluation accounts.
- **Deliverable Path**: `docs/dev/windows10-qa.md`

---

### TSK-MQA-02: Windows 11 Native XAMPP Clean Installation & Audit Report
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 2.0 hr
- **Prerequisites / Tools Needed**: Windows 11 PC, XAMPP 8.2+ installer
- **Step-by-Step Instructions ("How to Do It")**:
  1. Deploy EIMS into a fresh Windows 11 XAMPP environment.
  2. Test default Windows port 80/3306 conflicts (IIS, Skype, or Hyper-V) and document solutions.
  3. Perform full reservation cycle: log in as customer, book a table for 4, log in as manager, and confirm booking.
  4. Test session persistence across browser restarts.
  5. Write the comprehensive Windows 11 audit report in `docs/dev/windows11-qa.md`.
- **Definition of Done (Acceptance Criteria)**:
  - Audit document committed at `docs/dev/windows11-qa.md`.
  - Captures Windows 11 specific firewall, directory permission, and browser behavior.
  - Confirms end-to-end reservation workflow completes with zero PHP warnings.
- **Deliverable Path**: `docs/dev/windows11-qa.md`

---

### TSK-MQA-03: Desktop Google Chrome Manual Form & Button Walkthrough
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: Desktop PC running Google Chrome (latest version)
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open Chrome DevTools (F12) to the Console and Network tabs.
  2. Navigate through every primary route: `/`, `/login`, `/superadmin/organizations`, `/admin/restaurants/1/tables`, `/discover`, and `/bookings/new`.
  3. Click every button, toggle, and submit every form. Intentionally submit empty forms to verify HTML5 required attributes and server-side 422 error banners.
  4. Verify that zero JavaScript errors or 404 console warnings are emitted.
  5. Log observations, response times, and findings in `docs/dev/audit/chrome-desktop-audit.md`.
- **Definition of Done (Acceptance Criteria)**:
  - Audit report exists at `docs/dev/audit/chrome-desktop-audit.md`.
  - Confirms 100% of forms and action buttons function as expected.
  - Console log confirms zero uncaught JS disruptions or broken asset links.
- **Deliverable Path**: `docs/dev/audit/chrome-desktop-audit.md`

---

### TSK-MQA-04: Desktop Microsoft Edge Manual Walkthrough & CSRF Audit
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: Desktop PC running Microsoft Edge (Chromium based)
- **Step-by-Step Instructions ("How to Do It")**:
  1. Navigate all administrative and customer views in Microsoft Edge.
  2. Inspect every POST form to ensure a hidden `<input type="hidden" name="csrf_token" ...>` is present.
  3. Test CSRF protection by attempting to delete or alter a token in DevTools before submission; verify the server strictly rejects with HTTP 403 Forbidden.
  4. Test tracking prevention settings (Basic, Balanced, Strict) and confirm session cookies survive.
  5. Compile audit findings in `docs/dev/audit/edge-desktop-audit.md`.
- **Definition of Done (Acceptance Criteria)**:
  - Audit report committed at `docs/dev/audit/edge-desktop-audit.md`.
  - Verifies CSRF rejection behavior under tampered token conditions.
  - Confirms layout parity with Chrome.
- **Deliverable Path**: `docs/dev/audit/edge-desktop-audit.md`

---

### TSK-MQA-05: Desktop Mozilla Firefox Layout & Cookie Session Walkthrough
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: Desktop PC running Mozilla Firefox
- **Step-by-Step Instructions ("How to Do It")**:
  1. Launch Mozilla Firefox and inspect the 12x8 floor plan CSS Grid (`/admin/restaurants/1/tables`).
  2. Verify that CSS Grid tracks, subgrid alignment, and sticky navigation headers render identically to Chromium browsers.
  3. Inspect session cookies (`eims_session`, `theme`) to confirm `SameSite=Lax` and `HttpOnly` flags are respected.
  4. Verify the zero inline `style=""` invariant using the Firefox DOM Inspector.
  5. Author the Firefox audit report in `docs/dev/audit/firefox-desktop-audit.md`.
- **Definition of Done (Acceptance Criteria)**:
  - Audit report committed at `docs/dev/audit/firefox-desktop-audit.md`.
  - Confirms Gecko rendering engine compatibility with all CSS3 custom property themes.
  - Documents cookie security posture.
- **Deliverable Path**: `docs/dev/audit/firefox-desktop-audit.md`

---

### TSK-MQA-06: Mobile Safari (iOS) Touch & Viewport Manual Walkthrough
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: Physical iPhone or iPad (or Xcode iOS Simulator)
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open Safari on iOS and connect to the local development server (or hosted preview).
  2. Verify responsive layout at 375px and 414px mobile viewports.
  3. Ensure zero horizontal page overflow or side-scrolling occurs.
  4. Test touch interaction: tap targets for buttons, table selectors, and dropdowns must be at least 44x44 CSS pixels.
  5. Verify that focusing input fields does not trigger undesirable layout clipping or hide the keyboard.
  6. Document observations in `docs/dev/audit/ios-safari-audit.md`.
- **Definition of Done (Acceptance Criteria)**:
  - Audit report committed at `docs/dev/audit/ios-safari-audit.md`.
  - Mobile Safari touch responsiveness verified firsthand.
  - Confirms viewport meta tag `<meta name="viewport" content="width=device-width, initial-scale=1.0">` functions smoothly.
- **Deliverable Path**: `docs/dev/audit/ios-safari-audit.md`

---

### TSK-MQA-07: Mobile Chrome (Android) Touch & 15-Min Snapping Walkthrough
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: Physical Android smartphone running Google Chrome
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open Chrome on Android and navigate to the Customer Reservation booking page (`/bookings/new`).
  2. Test time-picker selection: confirm slots only permit discrete 15-minute intervals (:00, :15, :30, :45).
  3. Complete a reservation for 2 guests on a mobile touchscreen.
  4. Verify toast notifications, confirmation modals, and table status badges render cleanly without wrapping or text overlap.
  5. Log audit findings in `docs/dev/audit/android-chrome-audit.md`.
- **Definition of Done (Acceptance Criteria)**:
  - Audit report committed at `docs/dev/audit/android-chrome-audit.md`.
  - Confirms mobile booking journey completes from home screen to reservation token.
  - Validates zero visual clipping on mobile Chrome.
- **Deliverable Path**: `docs/dev/audit/android-chrome-audit.md`

---

### TSK-MQA-08: Tablet Viewport (iPad / Galaxy Tab) Grid Inspection
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: Physical tablet or desktop browser responsive mode (768px to 1024px width)
- **Step-by-Step Instructions ("How to Do It")**:
  1. Inspect application views at standard tablet resolutions (768x1024 portrait, 1024x768 landscape).
  2. Verify that two-column dashboard layouts reflow intelligently without overlapping card components.
  3. Test the interactive floor plan: tables must remain legible and touch-selectable.
  4. Test orientation flip from portrait to landscape to ensure CSS Grid transitions smoothly.
  5. Author the tablet audit report in `docs/dev/audit/tablet-viewport-audit.md`.
- **Definition of Done (Acceptance Criteria)**:
  - Audit report committed at `docs/dev/audit/tablet-viewport-audit.md`.
  - Verifies mid-breakpoint CSS responsiveness across both orientations.
  - Documents tablet usability findings.
- **Deliverable Path**: `docs/dev/audit/tablet-viewport-audit.md`

---

### TSK-MQA-09: Theme Switcher Visual Contrast Audit (Lyra / Hiraya / Katipunan)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: Browser contrast checker (e.g., WCAG Color Contrast Checker extension, Lighthouse)
- **Step-by-Step Instructions ("How to Do It")**:
  1. Navigate to any page and use the global navigation theme switcher to cycle through all 3 themes:
     - Lyra Personal Platform (`lyra.css`)
     - Hiraya Bistro Heritage (`hiraya.css`)
     - Katipunan Grill Smokehouse (`katipunan.css`)
  2. Measure text-to-background contrast on headings, body text, buttons, and alert badges across both light and dark modes.
  3. Verify that all core text meets WCAG AA standard (minimum 4.5:1 for normal text, 3:1 for large text).
  4. Document contrast ratios, color hex codes, and pass/fail ratings in `docs/dev/audit/theme-contrast-audit.md`.
- **Definition of Done (Acceptance Criteria)**:
  - Audit report committed at `docs/dev/audit/theme-contrast-audit.md`.
  - All 3 theme stylesheets tested empirically with zero contrast violations.
  - Confirms theme persistence across page navigations via `localStorage` and cookie.
- **Deliverable Path**: `docs/dev/audit/theme-contrast-audit.md`

---

### TSK-MQA-10: Error Boundary & Empty State UI Verification
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: Web browser, local development server
- **Step-by-Step Instructions ("How to Do It")**:
  1. Test HTTP error views by navigating to:
     - Non-existent route: `/invalid-url-path` (verify custom 404 view)
     - Unauthorized route: access `/superadmin` as a customer (verify 403 Forbidden)
  2. Test empty states across data rosters:
     - Search for a non-existent reservation or menu item (verify clear "No items match your criteria" message).
     - Test empty waitlist display.
  3. Confirm that no raw PHP stack traces, file paths, or database passwords leak into error views.
  4. Compile report in `docs/dev/audit/error-empty-states-audit.md`.
- **Definition of Done (Acceptance Criteria)**:
  - Audit report committed at `docs/dev/audit/error-empty-states-audit.md`.
  - Confirms Defense Kernel intercepts all exceptions and presents user-friendly error views.
  - Zero sensitive internal server paths exposed to the client.
- **Deliverable Path**: `docs/dev/audit/error-empty-states-audit.md`

```
================================================================================
EPIC 5: END-USER MANUAL & TECHNICAL DOCUMENTATION (8 TICKETS)
================================================================================
Goal: Produce illustrated user guides, cheat sheets, LaTeX monograph proofreading,
and a comprehensive academic glossary for evaluation panel submission.
```

### TSK-DOC-01: Diner Portal Illustrated Step-by-Step User Guide
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 2.0 hr
- **Prerequisites / Tools Needed**: Markdown editor, browser screenshot tool
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new guide at `docs/user-guides/diner-portal.md`.
  2. Author a beginner-friendly, illustrated walkthrough for a dining customer:
     - Step 1: Navigating to the discovery portal and searching by cuisine or dietary preference.
     - Step 2: Selecting a restaurant, viewing operating hours, and browsing the live menu.
     - Step 3: Choosing party size, date, and 15-minute arrival time slot.
     - Step 4: Confirming reservation and saving the secure reservation tracking token.
     - Step 5: Joining the remote waitlist and monitoring queue position in real time.
  3. Include cropped UI screenshots illustrating each step.
- **Definition of Done (Acceptance Criteria)**:
  - User guide committed at `docs/user-guides/diner-portal.md`.
  - Written in clear, non-technical English suitable for everyday restaurant diners.
  - Every step accompanied by a high-resolution screenshot.
- **Deliverable Path**: `docs/user-guides/diner-portal.md`

---

### TSK-DOC-02: Restaurant Manager Illustrated Step-by-Step User Guide
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 2.0 hr
- **Prerequisites / Tools Needed**: Markdown editor, browser screenshot tool
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new guide at `docs/user-guides/manager-portal.md`.
  2. Write an operational manual for restaurant managers (`manager@hirayabistro.ph`):
     - Section 1: Dashboard overview, daily reservation counts, and turnover KPI cards.
     - Section 2: Managing dining zones and creating/repositioning dining tables.
     - Section 3: Handling reservation check-ins, cancellations, and marking no-shows.
     - Section 4: Operating the 86-list toggle to immediately mark depleted menu items unavailable.
     - Section 5: Moderating customer reviews and managing staff role delegations.
  3. Provide concrete examples and screenshots for each administrative workflow.
- **Definition of Done (Acceptance Criteria)**:
  - User guide committed at `docs/user-guides/manager-portal.md`.
  - Comprehensive operational coverage for restaurant management staff.
  - Step-by-step instructions with visual callouts for all 8 ERP modules.
- **Deliverable Path**: `docs/user-guides/manager-portal.md`

---

### TSK-DOC-03: SuperAdmin Multi-Tenant Illustrated User Guide
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 2.0 hr
- **Prerequisites / Tools Needed**: Markdown editor, browser screenshot tool
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new guide at `docs/user-guides/superadmin-portal.md`.
  2. Author the governance guide for university IT administrators and platform operators (`superadmin@cvsu.edu.ph`):
     - Section 1: Provisioning a new restaurant tenant organization.
     - Section 2: Configuring multi-tenant feature flags (`enable_deposits`, `enable_loyalty`, `enable_waitlist`).
     - Section 3: System-wide user administration and role bitmask assignments.
     - Section 4: Inspecting immutable audit logs and reviewing administrative mutation trails.
  3. Detail security safeguards preventing cross-tenant data leakage.
- **Definition of Done (Acceptance Criteria)**:
  - User guide committed at `docs/user-guides/superadmin-portal.md`.
  - Complete walkthrough of platform governance capabilities.
  - Clear explanations of multi-tenant isolation and security controls.
- **Deliverable Path**: `docs/user-guides/superadmin-portal.md`

---

### TSK-DOC-04: Evaluator Quickstart 1-Page Cheat Sheet (PDF/Poster Format)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: Canva, Markdown editor, or Word/InDesign
- **Step-by-Step Instructions ("How to Do It")**:
  1. Design a clean, high-impact 1-page reference cheat sheet formatted for standard Letter / A4 paper.
  2. Prominently display:
     - URL / Localhost Port (`http://localhost:8080` or `http://localhost/eims/src/public/`)
     - Pre-configured evaluation accounts table (SuperAdmin, Admin, Customer, Staff with password `Password123!`)
     - Quick terminal commands (`just setup`, `just up`, `just test`, `just doctor`)
     - Key feature checklist mapped to the DCIT 24 project rubric.
  3. Save text outline at `docs/user-guides/evaluator-cheat-sheet.md` and generate print-ready PDF at `docs/media/evaluator-cheat-sheet.pdf`.
- **Definition of Done (Acceptance Criteria)**:
  - Markdown reference committed at `docs/user-guides/evaluator-cheat-sheet.md`.
  - Print-ready 1-page PDF produced in `docs/media/evaluator-cheat-sheet.pdf`.
  - Visually compelling layout in CvSU green/gold branding.
- **Deliverable Path**: `docs/user-guides/evaluator-cheat-sheet.md`

---

### TSK-DOC-05: 63-Page LaTeX Monograph Typographical & Spelling Proofread
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 2.5 hr
- **Prerequisites / Tools Needed**: Text editor, review of `paper/sections/*.tex`
- **Step-by-Step Instructions ("How to Do It")**:
  1. Carefully read through the LaTeX source files in `paper/sections/`:
     - `01-introduction.tex`
     - `02-theoretical-background-and-related-work.tex`
     - `03-system-architecture-and-design.tex`
     - `04-relational-data-model-and-concurrency-engine.tex`
     - `05-security-threat-model-and-mutation-guards.tex`
     - `06-experimental-evaluation-and-adversarial-verification.tex`
     - `07-conclusion-and-future-work.tex`
  2. Flag and correct any typographical errors, grammatical inconsistencies, awkward phrasings, or LaTeX escape syntax mistakes.
  3. Verify mathematical equation formatting (e.g., bitmask RBAC equations and time-slot discretization functions).
  4. Document all suggested edits or corrections in `docs/dev/audit/latex-proofread-notes.md`.
- **Definition of Done (Acceptance Criteria)**:
  - Proofreading log committed at `docs/dev/audit/latex-proofread-notes.md`.
  - All 7 LaTeX section files verified for flawless English grammar and technical precision.
  - Paper compiles cleanly via `latexmk -xelatex` with zero fatal syntax errors.
- **Deliverable Path**: `docs/dev/audit/latex-proofread-notes.md`

---

### TSK-DOC-06: Bibliography Citation Cross-Check against References (`references.bib`)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: Text editor, Google Scholar / ACM Digital Library
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open `paper/references.bib`.
  2. Audit every BibTeX citation key used in `paper/sections/*.tex` (e.g., `\cite{...}`) to ensure a matching entry exists in `references.bib`.
  3. Verify bibliographic completeness: each entry must have author names, publication title, journal/conference name, year, and DOI/URL where applicable.
  4. Re-compile the document using Biber/XeLaTeX to verify zero undefined citation warnings (`LaTeX Warning: Citation ... undefined`).
  5. Log audit results in `docs/dev/audit/bib-audit.md`.
- **Definition of Done (Acceptance Criteria)**:
  - Audit report committed at `docs/dev/audit/bib-audit.md`.
  - 100% of in-text citations resolved against `paper/references.bib`.
  - Zero missing citation warnings emitted during LaTeX compilation.
- **Deliverable Path**: `docs/dev/audit/bib-audit.md`

---

### TSK-DOC-07: Physical Deliverable Print Preparation & Bookbinding Checklist
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.0 hr
- **Prerequisites / Tools Needed**: CvSU Thesis and Manuscript Manual guidelines
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new document at `docs/dev/print-preparation-checklist.md`.
  2. Review CvSU formal academic submission standards:
     - 1.5-inch left margin (for bookbinding gutter), 1.0-inch top, bottom, and right margins.
     - Double-spaced or 1.5-spaced body text, 12pt Times New Roman or serif typeface.
     - Preliminary pages: Title Page, Approval Sheet, Acknowledgment, Table of Contents, List of Tables, List of Figures, Abstract.
  3. Compile a physical printing and bookbinding checklist (paper weight: Substance 24 / 80 GSM, hardbound cover in dark green with gold hot-stamped lettering).
- **Definition of Done (Acceptance Criteria)**:
  - Checklist committed at `docs/dev/print-preparation-checklist.md`.
  - Contains exact print specifications matching CvSU Department of IT requirements.
  - Includes local print shop preparation instructions and turn-around schedule.
- **Deliverable Path**: `docs/dev/print-preparation-checklist.md`

---

### TSK-DOC-08: Comprehensive Glossary of Information Management & Restaurant ERP Terms
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: Text editor, review of codebase and academic paper
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new glossary document at `docs/user-guides/glossary.md`.
  2. Author clear, academic definitions for at least 35 key terms used throughout EIMS:
     - Multi-tenancy, RBAC (Role-Based Access Control), Bitwise Flags, Concurrency Control,
     - Double-Booking Prevention, Discrete Time Snapping, Pessimistic Locking, Optimistic UI,
     - Failover, SQLite Fallback, CSRF Token, Air-gapped Deployment, Clean-room Implementation,
     - 86-List, Front-of-House (FOH), Back-of-House (BOH), Turn-over Rate, Bill of Materials (BOM),
     - Audit Trail, Foreign Key Cascade, ANSI-SQL, PDO Driver, FastCGI, Defense Kernel.
  3. Organize terms alphabetically with cross-references to relevant project files and modules.
- **Definition of Done (Acceptance Criteria)**:
  - Glossary committed at `docs/user-guides/glossary.md`.
  - Contains minimum 35 precise technical and operational terms.
  - Serves as a reference guide for team members during oral panel defense.
- **Deliverable Path**: `docs/user-guides/glossary.md`

```
================================================================================
EPIC 6: ORAL DEFENSE PREPARATION & LIVE DEMO COORDINATION (8 TICKETS)
================================================================================
Goal: Rehearse, orchestrate, and bulletproof the team for the live oral defense
before the Cavite State University faculty evaluation panel.
```

### TSK-DEF-01: Top 25 Toughest Defense Questions & Formulated Answers Guide
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 2.5 hr
- **Prerequisites / Tools Needed**: Text editor, consultation with technical lead
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new document at `docs/dev/defense-qa-guide.md`.
  2. Brainstorm the 25 most challenging technical, architectural, and operational questions an academic panel could ask:
     - "Why build from scratch in vanilla PHP instead of using Laravel?"
     - "How does the system prevent two customers from booking Table 4 simultaneously?"
     - "What happens if MySQL crashes midway through a high-traffic dinner service?"
     - "How is data isolated between Hiraya Bistro and Katipunan Grill?"
     - "Why use bitmask RBAC instead of standard relational permission lookup tables?"
  3. Write exhaustive, impressive answers grounding every reply in computer science theory, exact line numbers, and architectural decision records.
- **Definition of Done (Acceptance Criteria)**:
  - Guide committed at `docs/dev/defense-qa-guide.md`.
  - Contains exactly 25 rigorous questions paired with authoritative answers.
  - Categorized into Architecture, Database, Security, UI/UX, and Operations.
- **Deliverable Path**: `docs/dev/defense-qa-guide.md`

---

### TSK-DEF-02: Member-by-Member Defense Answer Assignment Sheet
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.0 hr
- **Prerequisites / Tools Needed**: Text editor, Q&A guide from TSK-DEF-01, team roster
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new document at `docs/dev/defense-roster-assignment.md`.
  2. Assign specialized subject-matter defense domains across all 8 group members:
     - Member 1: System Scope, Problem Background, High-Level Value Proposition
     - Member 2: Clean-Room Vanilla Stack Justification, Cold-Start Benchmarks
     - Member 3: Relational Database Schema, Normalization (3NF), Foreign Keys
     - Member 4: Concurrency Engine, 15-Minute Slot Boundary Snapping Algorithm
     - Member 5: Security Architecture, Bitwise RBAC, CSRF Protection, Password Hashing
     - Member 6: Multi-Tenant Tenant Isolation, Feature Flag Governance
     - Member 7: Front-of-House Restaurant Operations, 12x8 Floor Plan CSS Grid
     - Member 8: Testing Strategy, Adversarial Sabotage Defense, Disaster Recovery
  3. Map each of the 25 questions from TSK-DEF-01 to the designated primary and backup respondent.
- **Definition of Done (Acceptance Criteria)**:
  - Assignment sheet committed at `docs/dev/defense-roster-assignment.md`.
  - Every group member has dedicated technical territory to defend.
  - Eliminates awkward pauses or members talking over each other during panel questioning.
- **Deliverable Path**: `docs/dev/defense-roster-assignment.md`

---

### TSK-DEF-03: Live Demo Script & Step-by-Step Operator Checklist (5-Min Timebox)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: Stopwatch, web browser, review of `docs/DEMO-RUNBOOK.md`
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new document at `docs/dev/live-demo-script.md`.
  2. Distill the 15-minute comprehensive walkthrough into a high-octane 5-minute time-boxed live demo script for panel situations with tight time constraints.
  3. Script exact operator actions with stopwatch intervals:
     - `00:00 - 00:45`: Public landing page, customer discovery, dynamic menu display.
     - `00:45 - 01:45`: Instant reservation creation, slot reservation conflict prevention.
     - `01:45 - 02:45`: Restaurant Manager dashboard, interactive floor plan table status change.
     - `02:45 - 03:45`: SuperAdmin tenant provisioning and audit log verification.
     - `03:45 - 05:00`: Live terminal test execution showing 0 failures.
  4. Rehearse the operator actions repeatedly until execution is completely muscle-memorized.
- **Definition of Done (Acceptance Criteria)**:
  - Script committed at `docs/dev/live-demo-script.md`.
  - Operator workflow verified to complete within 4:45 minutes.
  - Includes emergency skip points if the panel interrupts with immediate questions.
- **Deliverable Path**: `docs/dev/live-demo-script.md`

---

### TSK-DEF-04: Panel Sabotage Drill Demonstration Script
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.5 hr
- **Prerequisites / Tools Needed**: Terminal running bash, `dev/scripts/sh/test-panel-sabotage.sh`
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new document at `docs/dev/sabotage-drill-script.md`.
  2. Create an exhilarating script designed to stun the evaluation panel by inviting them to "break" the system live.
  3. Script 3 live sabotage demonstrations:
     - Drill A: SQL Injection Attempt (injecting `' OR 1=1 --` into login and search inputs, showing parameterized PDO protection).
     - Drill B: Concurrent Double-Booking Clash (firing two simultaneous reservation requests for Table 4 at 19:00, showing one succeed and one receive HTTP 409 Conflict).
     - Drill C: Instant Database Pull-the-Plug (terminating MySQL container and showing EIMS seamlessly failover to local SQLite3 without dropping user sessions).
  4. Write exact narrator lines accompanying each live terminal drill.
- **Definition of Done (Acceptance Criteria)**:
  - Script committed at `docs/dev/sabotage-drill-script.md`.
  - All 3 sabotage drills verified empirically to succeed with 100% reliability.
  - Contains exact shell commands and expected exit codes.
- **Deliverable Path**: `docs/dev/sabotage-drill-script.md`

---

### TSK-DEF-05: Offline Emergency Backup USB Flash Drive Setup
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.0 hr
- **Prerequisites / Tools Needed**: 16GB+ USB 3.0 Flash Drive, `just package-xampp`
- **Step-by-Step Instructions ("How to Do It")**:
  1. Format a high-speed USB flash drive with exFAT or NTFS labeled `EIMS-DEFENSE-BACKUP`.
  2. Run `just package-xampp` to generate the standalone, zero-docker evaluation ZIP package.
  3. Copy onto the flash drive:
     - Standalone EIMS distribution (`dist/package/eims-xampp-portable.zip`)
     - Complete source repository with `.git` history
     - Standalone XAMPP 8.2+ Windows installer (`xampp-windows-x64-8.2.12-0-VS16-installer.exe`)
     - Google Chrome offline standalone installer
     - 63-page compiled academic paper (`paper/main.pdf`)
     - Presentation slide deck (`.pptx` and `.pdf`)
     - Demonstration video MP4
  4. Write a setup runbook on the flash drive root at `docs/dev/usb-backup-runbook.md`.
- **Definition of Done (Acceptance Criteria)**:
  - Runbook committed at `docs/dev/usb-backup-runbook.md`.
  - Physical flash drive prepared with complete air-gapped evaluation payload.
  - Verified on a disconnected, secondary laptop with zero internet access.
- **Deliverable Path**: `docs/dev/usb-backup-runbook.md`

---

### TSK-DEF-06: Presentation Day Logistics & Hardware Check (HDMI, Audio, Clicker)
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.0 hr
- **Prerequisites / Tools Needed**: Presentation laptop, backup laptop, HDMI adapters, wireless presenter clicker
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new checklist at `docs/dev/presentation-day-logistics.md`.
  2. Itemize all physical hardware needed for oral defense day:
     - Primary presentation laptop (fully charged, power brick included)
     - Secondary backup laptop with pre-loaded offline USB environment
     - HDMI cable and USB-C to HDMI multi-port dongles
     - Wireless presentation clicker with fresh AAA batteries
     - 3.5mm audio aux cable or portable Bluetooth presentation speaker
     - 8 formal group member attire checks (CvSU formal / semi-formal dress code)
  3. Establish exact arrival timing (minimum 45 minutes prior to scheduled defense slot for room AV testing).
- **Definition of Done (Acceptance Criteria)**:
  - Logistics document committed at `docs/dev/presentation-day-logistics.md`.
  - Hardware redundancy plan established with verified backup hardware.
  - Team room setup protocol documented step-by-step.
- **Deliverable Path**: `docs/dev/presentation-day-logistics.md`

---

### TSK-DEF-07: Peer Evaluation Form & Contribution Log Verification
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 1.0 hr
- **Prerequisites / Tools Needed**: Text editor, CvSU peer evaluation rubric
- **Step-by-Step Instructions ("How to Do It")**:
  1. Open a new document at `docs/dev/peer-evaluation-matrix.md`.
  2. Create an individualized peer evaluation tracking sheet documenting the contributions of each of the 8 members.
  3. Include quantitative scoring criteria across 5 dimensions: Attendance & Punctuality, Task Completion & Reliability, Quality of Deliverables, Teamwork & Communication, Technical Knowledge Demonstrated.
  4. Verify that every group member has claimed and fulfilled at least 4 atomic tickets in this queue, ensuring 100% fair score distribution.
- **Definition of Done (Acceptance Criteria)**:
  - Matrix document committed at `docs/dev/peer-evaluation-matrix.md`.
  - Complete contribution log prepared for submission to course instructor.
  - Signed off by all team members.
- **Deliverable Path**: `docs/dev/peer-evaluation-matrix.md`

---

### TSK-DEF-08: Final Group Full-Dress Rehearsal Coordination & Timing Run
- **Target Assignee**: `[ ] Unassigned (Volunteer)`
- **Estimated Effort**: 2.0 hr
- **Prerequisites / Tools Needed**: Video call or physical meeting room, stopwatch, slide deck, demo environment
- **Step-by-Step Instructions ("How to Do It")**:
  1. Coordinate a mandatory 2-hour full-dress rehearsal session with all 8 group members.
  2. Execute a continuous, timed mock defense run:
     - Slide Presentation (15 minutes strict cap, with all 8 members speaking)
     - Live Software Demonstration (5 minutes strict cap)
     - Mock Panel Cross-Examination (10 minutes, using TSK-DEF-01 questions)
  3. Record total elapsed time and speaker transition bottlenecks.
  4. Log feedback, timing improvements, and rehearsal sign-off in `docs/dev/rehearsal-timing-sheet.md`.
- **Definition of Done (Acceptance Criteria)**:
  - Rehearsal timing sheet committed at `docs/dev/rehearsal-timing-sheet.md`.
  - At least two full dry runs executed with all 8 members in attendance.
  - Final timed run completes within university defense limits.
- **Deliverable Path**: `docs/dev/rehearsal-timing-sheet.md`
