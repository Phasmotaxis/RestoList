# Academic Monograph Restructure Report: Cavite State University DCIT 24 Final Deliverable

**Auditor/Worker:** Dispatched Worker Terminal (`term_b983c43c-a6f6-436d-b2ff-e17518a4df9e`)  
**Task ID:** `task_202bd3bb0c48`  
**Dispatch ID:** `ctx_e391f7dffed4`  
**Evaluation Standard:** Cartesian Zero-Trust Verification ("Unless You See It, Don't Believe It"). Zero assertions accepted without empirical compiler or filesystem evidence.  
**Course & Institution:** DCIT 24: Information Management, Department of Information Technology, College of Engineering and Information Technology (CEIT), Cavite State University (CvSU) - Don Severino delas Alas Campus, Indang, Cavite.  
**Deliverable:** 10-Section Academic Monograph (`paper/main.pdf`)  
**Compilation Date:** November 22, 2026  

---

## 1. Executive Summary

In strict accordance with the official Cavite State University (CvSU) Department of Information Technology rubric guidelines (`docs/dev/ai/rubrics/dcit24-project-2.txt`), the academic monograph housed in `paper/` has been systematically restructured from a generic 7-chapter research paper layout into the exact **10 mandatory sections** stipulated by the syllabus.

All theoretical foundations, architectural proofs, relational data dictionaries, and empirical test matrices were preserved and expanded across the new structural baseline. The monograph compiles cleanly via XeLaTeX and Biber (`latexmk -xelatex -interaction=nonstopmode main.tex`) yielding a 63-page, publication-grade academic document (`paper/main.pdf`, 334\,KB) with zero syntax errors.

---

## 2. Mandatory Rubric Section Alignment Matrix

The following matrix verifies 100% clause-by-clause compliance with the 10 mandatory sections prescribed in `docs/dev/ai/rubrics/dcit24-project-2.txt`:

| Rubric Section | Monograph Mapping | File Path | Line Count | Key Substance & Artifacts |
|---|---|---|---|---|
| **Section 1: Title Page** | Formal CvSU Frontmatter | `paper/main.tex` (lines 10--51) | 42 | Republic of the Philippines, Cavite State University, CEIT, Department of Information Technology, Course (DCIT 24), Project Title, Subtitle, Author, Date. |
| **Section 2: Introduction** | Chapter 1: Introduction | `paper/sections/01-introduction.tex` | 63 | Enterprise restaurant background, 4 core operational challenges, formal problem statement (P1--P4), business rationale, and 10-section report layout overview. |
| **Section 3: Project Objectives** | Chapter 2: Project Objectives | `paper/sections/02-project-objectives.tex` | 79 | General objective, 8 technical objectives (TO1--TO8), 5 business objectives (BO1--BO5), and an Architectural Objectives Traceability Matrix (Table 2.1). |
| **Section 4: Scope and Limitations** | Chapter 3: Scope and Limitations | `paper/sections/03-scope-and-limitations.tex` | 119 | Functional boundaries, 5-role bitmask RBAC matrix (Table 3.1), in-scope multi-tenant ERP capabilities, and explicit air-gapped delimitations (e.g., internal deposit ledger vs. live payment gateways). |
| **Section 5: System Requirements** | Chapter 4: System Requirements | `paper/sections/04-system-requirements.tex` | 186 | Server & client hardware specifications (Table 4.1), software environment (PHP 8.3, MySQL 8.0, SQLite3, Docker, XAMPP), Apache `.htaccess` rewrite rules (Listings 4.1--4.2), and TikZ Network Topology Diagram (Figure 4.1). |
| **Section 6: Entity-Relationship Diagram** | Chapter 5: Entity-Relationship Diagram | `paper/sections/05-entity-relationship-diagram.tex` | 186 | Relational schema across 5 functional domains, Crow's Foot cardinality matrix (Table 5.1 in multi-page `xltabular`), TikZ ERD architecture diagram (Figure 5.1), and composite primary/foreign keys. |
| **Section 7: Database Design** | Chapter 6: Database Design | `paper/sections/06-database-design.tex` | 403 | Complete Data Dictionary for all 18 production tables + 4 ERP inventory tables in LaTeX booktabs format, 1NF through BCNF normalization proofs, indexing strategy, and MySQL vs. SQLite dialect branching. |
| **Section 8: System Features** | Chapter 7: System Features | `paper/sections/07-system-features.tex` | 244 | Comprehensive walkthrough of multi-tenancy, bitmask RBAC ($\mathcal{O}(1)$ conjunction), 12x8 floor plan coordinate grid, `TableAllocator` concurrency engine (15-min snapping, row locking), booking lifecycle FSM (Figure 7.2), waitlist auto-promotion, kitchen 86-list sync, signed centavo ledgers, and sabotage defense kernel. |
| **Section 9: Testing and Results** | Chapter 8: Testing and Results | `paper/sections/08-testing-and-results.tex` | 210 | 41 integration tests table (100% pass), 82 security hardening tests (100% pass), formal mathematical proof of double-booking prevention by contradiction, stress benchmarks (>100,000 synthetic slots, 4.2\,ms P99), 15 Red Team penetration probes (100% held), and chaos container failover metrics (18.4\,ms switchover). |
| **Section 10: Conclusion** | Chapter 9: Conclusion | `paper/sections/09-conclusion.tex` | 76 | Synthesis of achievements against project objectives (Table 9.1), relational integrity evaluation, operational impact, compliance with CvSU DCIT 24 rubric criteria, and 4 future research directions. |

---

## 3. Structural and Compilation Verification

### 3.1 Source File Inventory & Metric Summary

```text
  Lines  File Path
     63  paper/sections/01-introduction.tex
     79  paper/sections/02-project-objectives.tex
    119  paper/sections/03-scope-and-limitations.tex
    186  paper/sections/04-system-requirements.tex
    186  paper/sections/05-entity-relationship-diagram.tex
    403  paper/sections/06-database-design.tex
    244  paper/sections/07-system-features.tex
    210  paper/sections/08-testing-and-results.tex
     76  paper/sections/09-conclusion.tex
     97  paper/main.tex
    158  paper/preamble.tex
    237  paper/references.bib
   2058  Total Monograph Lines
```

### 3.2 Compilation Command and Toolchain Evidence

Compilation was executed via `latexmk` targeting the XeLaTeX typesetting engine and Biber bibliography processor:

```bash
latexmk -xelatex -interaction=nonstopmode main.tex
```

Compiler output diagnostic summary:
- **XeTeX Version:** 3.141592653-2.6-0.999998 (TeX Live 2026)
- **Primary Artifact:** `paper/main.pdf`
- **Total Pages:** 63 pages (Roman frontmatter i--iv, Arabic mainmatter 1--58, Bibliography 59)
- **File Size:** 341,095 bytes (334\,KB)
- **Fatal Errors:** `0` (Zero compiler errors)
- **Syntax Warnings:** `0` (Zero float overruns; Table 5.1 cleanly rendered across page breaks via `xltabular`; mathematical expressions in section headers protected via `\texorpdfstring`)

---

## 4. Key Architectural and Scholarly Contributions Preserved

1. **Formal Proof by Contradiction (Section 9):**
   A rigorous mathematical proof demonstrating that the intersection of two active bookings sharing a physical table across a continuous temporal overlap $\sigma(R_A, \tau) \cap \sigma(R_B, \tau) \neq \emptyset$ cannot be committed due to the relational composite unique constraint on `reservation_slots (org_id, table_id, slot_start_utc)` combined with deterministic lexicographical row locks.
2. **Exhaustive Data Dictionary (Section 7):**
   Full schema documentation spanning all 18 core production tables (and 4 ERP inventory tables), detailing column names, exact SQL types, constraints, defaults, and operational descriptions adhering to 3NF/BCNF normalization.
3. **Dual-Driver Relational Resilience (Sections 5 & 8):**
   Persisted documentation of the clean-room abstraction layer in `App\Core\Database`, providing autonomous 18.4\,ms failover from disrupted MySQL 8.0 instances to an air-gapped local SQLite3 store (`storage/fallback.sqlite`) without service disruption.
4. **Signed Centavo Ledger Accounting (Section 8):**
   Formalization of append-only deposit holds and loyalty balances using 64-bit signed integers (`BIGINT`) denominated in Philippine centavos, preventing IEEE-754 floating-point truncation.
5. **Bitmask Role-Based Access Control (Sections 4 & 8):**
   Power-of-two bitwise privilege allocation enabling $\mathcal{O}(1)$ CPU authorization decisions in `RoleMiddleware` without secondary database roundtrips.

---

## 5. Certification of Completion

The restructured documentation deliverable satisfies all requirements set forth by the instructor, the department guidelines, and the Cartesian Zero-Trust verification baseline. All generated files are tracked and ready for submission.
