---
paths:
  - 'app/Actions/**'
---

# Actions

## Action methods use execute()
Action classes in this project expose business logic through execute(). Keep action classes focused and call them through execute() rather than handle() or __invoke().
