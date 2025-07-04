# SOC 2 Compliant Release Workflow for Magento 2 application

This repository provides a template for setting up a SOC 2 compliant process using `release-please-action` on GitHub Actions.  
The workflow automates version control, changelog generation, pre-release => release creation, deployment and PR comments.

## Key Concepts

The release process is divided into two stages:
- **Pre-release:** Generates pre-release (alpha) for testing.
- **Release:** Publishes the final release after successful completion of all tests.

To maintain SOC 2 compliance, this template assumes all related testing and publishing workflows also adhere to compliance requirements, and that branch protection rules are properly configured.

### Compliance Features

- Works with protected main branches (no direct pushes)
- Maintains a complete audit log of all changes
- Standardizes the release process across your team
- Automates version updates and changelog entries
- Links code changes to releases for full traceability
- Enforces separation between development and release steps
- Supports controlled pre-release testing environments
- Documents all tests conducted before a final release
- Manages comprehensive change documentation automatically
- Integrates with PR approval workflows for access control
- Keeps all the wworkflow messages and comments in PR

## Workflow Highlights

1. Open PR with code changes to the dev branch using Conventional Commit messages.
2. PR and changed files validation. [Review](https://github.com/magenx/Magento-2-deployment-pipeline/pull/215) and merge.
3. The workflow opens a [pre-release PR](https://github.com/magenx/Magento-2-deployment-pipeline/pull/206) automatically.
4. Starts Magento 2 build and deployment.
5. PR updated with job outcome status.
6. Test your deployment on staging server.
7. Merging the pre-release PR generates a release PR.
8. Run additional tests on staging.
9. Merging the release PR publishes the production release.
10. Deployment starts to production.

## SOC 2 Requirements

- using secrets
- only allow squash merging PR
- check "Allow squash merging" and set the below dropdown to "Default to pull request title"
- uncheck "Allow merge commits" & "Allow rebase merging"
- setup branch protection rules
  - "Require a pull request before merging"
  - "Dismiss stale pull request approvals when new commits are pushed"
  - "Require status checks to pass before merging"
  - "Require branches to be up to date before merging"
  - "Require conversation resolution before merging"
  - "Require linear history"
  - make sure "Do not allow bypassing the above settings" is unchecked, else the automatic release commits won't work


## A Better Deployments Pattern  
Use remote storage (like S3 or container registries).  
Trigger deployments through event systems (S3 events, RabbitMQ, Redis).  
Pass only scoped tokens and minimal payloads.  
  
### Problems With the Traditional Approach  
Master tokens expose the entire system if leaked  
Secrets are scattered across repositories, environments, and configs  
SSH keys are hard to rotate and prone to leakage  
Changing IPs or ports often break pipelines  
Infrastructure and deployment logic are tightly coupled  
  
### Benefits of Storage + Event-Based Deployment  
Use short-lived, minimal-scope tokens  
Event-driven model removes the need for client-side coordination  
No exposed ports or hardcoded IPs to manage  
Storage and queues provide built-in logging and auditability  
Works across cloud, hybrid, and secure/offline environments  




---

