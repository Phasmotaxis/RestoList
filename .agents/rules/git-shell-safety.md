# Agent Rule: Git and Shell Safety

- **Root Navigation**: [Project Overview](../../README.md)
- **Architecture Context**: [Architecture Decision Records](../../docs/dev/specifications/architecture.md)

---

## Directives
1. **Explicit Path Staging**: Never run `git add .`, `git add -A`, or `git commit -a`. Stage exact paths explicitly.
2. **Push Restrictions**: Never run raw `git push`. Use sanctioned wrapper `~/.config/agent-policy/bin/agent-push`.
3. **Commit Formatting**: Format commit messages as `type(scope): imperative summary`.
4. **Terminal Circuit Breaker**: If any command hangs or times out twice consecutively, halt execution immediately and escalate to the operator.
