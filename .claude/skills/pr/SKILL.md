---
name: pr
description: Turn the working tree's uncommitted and unpushed changes into clean Conventional-Commit commits on a feature branch, push them, and open a Pull Request (via gh, or a compare URL if gh is absent). Use when the user asks to "commit and push", "make a PR", "raise a pull request", "conventional commits", or ship the current work.
---

# Conventional-Commit PR skill

Ship the current work as tidy Conventional-Commit commits on a branch, push, and open a PR.
Follow these steps in order. Prefer the Bash tool; keep the user informed of each decision.

## 1. Survey the work

Run these and read the output before doing anything else:

```
git status --short
git branch --show-current
git remote -v
git log --oneline -8
git diff --stat            # unstaged
git diff --stat --cached   # staged
```

- If there is **nothing to commit and nothing unpushed**, stop and tell the user.
- Note the default branch (usually `main`). Note whether `gh` exists: `command -v gh`.

## 2. Choose a branch

- If the current branch **is** the default branch (`main`/`master`), create a feature branch first so the
  work can become a PR: `git checkout -b <type>/<short-slug>` (uncommitted changes carry over).
  Derive `<type>` from the dominant change (feat, fix, docs, refactor, chore) and a 2-4 word kebab slug.
- If already on a non-default branch, stay on it.
- Never commit straight to the default branch when a PR is requested.

## 3. Group changes into logical commits

Inspect what changed (`git status`, targeted `git diff`) and split into **coherent units** — one concern per
commit. Do not lump unrelated features together. For each unit, stage exactly its files with
`git add <paths>` (never blind `git add -A` when the tree mixes concerns).

## 4. Write Conventional-Commit messages

Format: `type(scope): subject`

- **type**: feat | fix | docs | style | refactor | perf | test | build | ci | chore
- **scope**: optional, lowercase area (e.g. `income`, `auth`, `api`, `security`)
- **subject**: imperative, lowercase, no trailing period, <= 72 chars
- Add a body (blank line, then `- ` bullets) when the change needs the "why".
- Breaking change: add `!` after type/scope and a `BREAKING CHANGE:` footer.

End every commit message with this trailer (this repo's rule):

```
Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>
```

Commit each group. Use a heredoc so multi-line messages keep their formatting:

```
git commit -F - <<'MSG'
feat(income): add recurring income templates

- one-click monthly posting with idempotent reference guard
- scheduler auto-posts active auto_post templates on the 1st

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>
MSG
```

## 5. Push

```
git push -u origin <branch>
```

If the push is rejected (non-fast-forward), stop and report — do not force-push unless the user says so.

## 6. Open the Pull Request

Derive the **PR title** from the commits: if one commit, reuse its subject; if several, write a
Conventional-Commit summary line covering them. Build a body with a `## Summary` bullet list and a
`## Test plan` section (mention `php artisan test` for this Laravel repo).

- **If `gh` is available:**
  ```
  gh pr create --base <default> --head <branch> --title "<title>" --body "<body>"
  ```
  Report the PR URL it prints.

- **If `gh` is NOT available:** push is enough to open a PR from the web. Print the compare URL for the
  user to click (convert the remote to https and strip `.git`):
  ```
  https://github.com/<owner>/<repo>/compare/<default>...<branch>?expand=1
  ```
  Give them the ready-made title and body to paste, and mention they can `winget install GitHub.cli`
  (or `choco install gh`) to have future PRs opened automatically.

## 7. Report

Tell the user: the branch name, each commit (hash + subject), push result, and the PR/compare URL.
Never run destructive git operations (reset --hard, force-push, branch -D) unless explicitly asked.
