# Repo Template – Automation & CI/CD Tooling

A reusable, agnostic repository template for modern development workflows.  
This repository focuses on automation, CI/CD, and release management, not application code.

## What’s Inside

This template comes preconfigured with:

- GitHub Actions
  - Pull request checks
  - Linting workflow
  - Automated releases
- Semantic Release
  - Conventional commits
  - Automatic versioning and changelog generation
- Dependabot
  - Automated dependency updates
- Issue and Pull Request Templates
  - Bug reports
  - Feature requests
  - Content questions
- Docker Support
  - Agnostic container for CI tooling
- Repository Hygiene
  - CODEOWNERS
  - Security policy
  - Label automation

This repository is tooling-only and intentionally does not assume any specific framework or runtime.

---

## Repo Stats

![Alt](https://repobeats.axiom.co/api/embed/e1583995f59c14c6cee7a5075f4bcd0e9a399580.svg "Repobeats analytics image")

[![Star History Chart](https://api.star-history.com/svg?repos=armandwipangestu/repo-template&type=date&legend=top-left)](https://www.star-history.com/#armandwipangestu/repo-template&type=date&legend=top-left)

## Contributors

<a href="https://github.com/armandwipangestu/repo-template/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=armandwipangestu/repo-template" />
</a>

---

## Repository Structure

```

.
├── .dockerignore          # Docker ignore rules for CI/container usage
├── .github/
│   ├── workflows/         # CI/CD pipelines (lint, PR checks, release)
│   ├── ISSUE_TEMPLATE/    # Issue templates
│   ├── CODEOWNERS         # Code ownership rules
│   ├── dependabot.yml     # Dependency update automation
│   ├── FUNDING.yml        # Funding and sponsorship config
│   ├── labeler.yml        # Issue and PR label automation
│   ├── PULL_REQUEST_TEMPLATE.md
│   └── SECURITY.md        # Security policy
├── .gitignore             # Git ignore rules
├── bun.lock               # Bun lockfile
├── CONTRIBUTING.md        # Contribution guidelines
├── Dockerfile             # Agnostic CI/tooling container
├── package.json           # Tooling dependencies and scripts
├── README.md              # Repository documentation
└── release.config.cjs     # Semantic release configuration

````

---

## Philosophy

- Framework agnostic: works for backend, frontend, library, or tooling repositories
- Automation-first: minimize manual release and maintenance tasks
- CI-friendly: designed to run consistently in GitHub Actions
- Composable: use only what you need and remove the rest

---

## Docker Usage

This repository includes a Dockerfile intended for CI/CD and automation usage, not as an application runtime.

### Build the image

```bash
docker build --platform linux/amd64 -f Dockerfile -t repo-template .
````

---

## GitHub Actions

The workflows are designed to:

* Validate pull requests
* Enforce linting and commit conventions
* Automatically release on merge to `main` and `staging` with suffix `rc` as release candidate version.

No application-specific steps are included by default.

---

## Release Management

Releases are handled using semantic-release with conventional commits.

Example commit messages:

```txt
feat: add new automation workflow
fix: correct release pipeline configuration
chore: update dependencies
```

Tags, releases, and changelogs are generated automatically.

---

## Security

Please refer to `SECURITY.md` for vulnerability reporting guidelines.

---

## Customization Guide

You can safely customize:

* `.github/workflows/*` to adjust CI logic
* `release.config.cjs` to tweak release rules
* `package.json` to add or remove tooling
* `Dockerfile` to adapt the runtime or package manager

All other components are optional and can be removed if not needed.

---

## License

MIT License. You are free to use this template for personal or commercial projects.

---

## Contributing

This repository is primarily intended as a starter template, but improvements and suggestions are welcome.

---

## Why Use This Template

Use this template if you want to:

* Bootstrap repositories without recreating CI/CD pipelines
* Enforce consistent commit and release practices
* Focus on building features instead of infrastructure setup
