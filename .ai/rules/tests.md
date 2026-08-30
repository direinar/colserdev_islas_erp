---
paths:
  - 'tests/**'
---

# Tests

## Pest with expect() assertions
This project uses Pest and writes tests with the test() function and expect() assertions. Match the Pest style rather than PHPUnit-style assertions or custom test wrappers.

## No automatic database refresh in tests
Tests in this project do not rely on automatic database refresh between tests. Keep the current testing pattern unless a specific task requires explicit database reset logic.
