# Project Guidance for Claude Code

Refer to [@AGENTS.md](AGENTS.md) for master architecture invariants, execution safety rules, and engineering standards.

## Mandatory Invariants Summary
- Non-interactive shell execution (`GIT_PAGER=cat`, no interactive flags).
- Explicit Git staging only (stage exact relative paths, never `git add .` or `git commit -a`).
- Native PHP 8.3 with `declare(strict_types=1);` and zero external backend frameworks.
- Documentation Triad: Maintain `docs/dev/specifications/architecture.md`, `docs/dev/ai/tasks.md`, and `docs/CHANGELOG.md` in lockstep.
- Zero ticket pollution in code or commit messages.
- Terminal circuit breaker: Halt and escalate after two consecutive command timeouts or hangs.
