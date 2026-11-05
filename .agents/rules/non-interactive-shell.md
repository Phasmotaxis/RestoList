# Agent Rule: Non-Interactive Shell Operations

- **Root Navigation**: [Project Overview](../../README.md)
- **Architecture Context**: [Architecture Decision Records](../../docs/dev/specifications/architecture.md)

---

## Directives
1. Never invoke interactive flags or commands (`-i`, `git rebase -i`, interactive pagers).
2. Set non-interactive environment defaults (`GIT_PAGER=cat`, `PAGER=cat`, `CI=true`).
3. Ensure all scripts run without terminal prompt waiting or TTY requirements.
