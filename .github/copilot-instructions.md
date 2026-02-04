# GitHub Copilot Instructions for Composer Stager

## Purpose

This document is for Copilot. It provides meta-guidance for code changes, reviews, and suggestions in this repository. For project-specific details, consult the `docs/` directory when needed.

## General Guidance

- Prioritize correctness, maintainability, and simplicity.
- Only change code, comments, or documentation if directly relevant to the request. Avoid drive-by refactoring or style changes unless explicitly requested.
- Call out unused code, variables, and files when encountered.
- Avoid clever or convoluted solutions. Prefer clear, idiomatic, and robust code.
- When suggesting large or architectural changes, request additional context or documentation if not already provided.

## Code Quality Priorities

1. Correctness: Code must work as intended and meet requirements.
2. Maintainability: Code should be easy to read, understand, and modify.
3. Simplicity: Avoid unnecessary complexity and overengineering.
4. Testability: Code should be easy to test, with clear separation of concerns.
5. Security: Follow PHP security best practices.
6. Robustness: Handle edge cases and errors gracefully, but avoid over-protecting against unlikely scenarios.
7. Performance: Consider performance only after other quality attributes.
8. Documentation: Write clear, concise comments for non-obvious code.

## When Reviewing or Suggesting Changes

- Ensure all changes align with these priorities and the project’s documented standards.
- If a request is ambiguous, ask for clarification or reference the relevant documentation.
- If a change affects documentation or standards, suggest updates to the appropriate files in `docs/`.
