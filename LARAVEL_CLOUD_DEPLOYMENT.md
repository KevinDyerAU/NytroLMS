# KeyLMSNytro: Laravel Cloud Deployment Guide

**Version:** 2.0
**Date:** January 29, 2026

This document provides a comprehensive guide for deploying the KeyLMSNytro application to **Laravel Cloud** using its native stack (MySQL, Redis) and integrating with AWS S3 for object storage. This follows the multi-environment setup for `staging` and `production`.

## 1. Prerequisites

1.  **Laravel Cloud Account**: An active Laravel Cloud account with a payment method.
2.  **GitHub Repository**: Administrative access to the `KevinDyerAU/KeyLMSNytro` GitHub repository.
3.  **AWS S3 Bucket**: An AWS account with an S3 bucket created for file storage, along with an IAM user with programmatic access (Access Key ID and Secret Access Key).

## 2. Project Setup in Laravel Cloud

1.  **Log in to Laravel Cloud** and click **Create Project**.
2.  **Connect to GitHub**: Authorize Laravel Cloud to access your GitHub repositories.
3.  **Select Repository**: Choose the `KevinDyerAU/KeyLMSNytro` repository.
4.  **Configure Project**:
    *   **Project Name**: `KeyLMSNytro`
    *   **PHP Version**: **PHP 8.3** (matches `composer.json`).
    *   **Laravel Root Directory**: **`/source`**. This is a critical step because the Laravel application is in a subdirectory.
5.  Click **Create Project**. Laravel Cloud will provision the project and a default `production` environment linked to the `main` branch.

## 3. Multi-Environment Configuration

We will use two environments to ensure a safe deployment workflow.

*   **`production`**: Deploys from the `main` branch (created by default).
*   **`staging`**: Deploys from the `develop` branch.

### Create Staging Environment

1.  In the project dashboard, go to the **Environments** tab.
2.  Click **Create Environment**.
3.  **Name**: `staging`.
4.  **Source Control Branch**: Select the `develop` branch.
5.  Click **Create Environment**.

## 4. Resource Provisioning

For each environment (`production` and `staging`), you need to provision and attach a database and a cache.

### 4.1. Create Database (MySQL)

1.  Navigate to the environment's dashboard (e.g., `production`).
2.  In the **Databases** card, click **Create**.
3.  **Type**: Select `MySQL`.
4.  **Name**: Give it a name (e.g., `keylms-prod-db`).
5.  **Size**: Choose an appropriate size.
6.  Click **Create Database**. The database will be created and automatically attached to the environment.
7.  Repeat this process for the `staging` environment.

### 4.2. Create Cache (Redis)

1.  In the environment's dashboard, find the **Caches** card and click **Create**.
2.  **Type**: Select `Redis`.
3.  **Name**: Give it a name (e.g., `keylms-prod-cache`).
4.  **Size**: Choose an appropriate size.
5.  Click **Create Cache**. It will be automatically attached.
6.  Repeat for the `staging` environment.

## 5. The `laravel-cloud.yml` File

This file tells Laravel Cloud how to build and deploy the application. Create it in the `/source` directory.

**File**: `/source/laravel-cloud.yml`

```yaml
# Build commands run in the /source directory
build:
  - "composer install --no-interaction --prefer-dist --optimize-autoloader"
  - "npm install"
  - "npm run build"

# Deploy commands run after the build is complete
deploy:
  - "php artisan migrate --force"
  - "php artisan config:cache"
  - "php artisan route:cache"
  - "php artisan view:cache"

# Note: `php artisan storage:link` is handled automatically by Laravel Cloud.
```

Commit and push this file to your `develop` and `main` branches.

## 6. Environment Variables

Configure these in **Environments** -> **[Your Environment]** -> **Environment**.

### Variables for Both Environments

These variables are essential for the application to run. Laravel Cloud injects `DB_*`, `REDIS_*`, and `CACHE_*` variables automatically when you attach resources.

| Variable | Value | Notes |
|---|---|---|
| `APP_KEY` | (Generate with `php artisan key:generate`) | **Must be unique for each environment.** |
| `APP_URL` | `https://your-cloud-domain.com` | Provided by Laravel Cloud. |
| `REDIS_CLIENT` | `predis` | KeyLMSNytro uses the `predis/predis` package. |
| `FILESYSTEM_DRIVER` | `s3` | To use AWS S3 for file storage. |
| `AWS_ACCESS_KEY_ID` | (Your S3 Key) | For S3 integration. |
| `AWS_SECRET_ACCESS_KEY` | (Your S3 Secret) | For S3 integration. |
| `AWS_DEFAULT_REGION` | (Your S3 Region) | For S3 integration. |
| `AWS_BUCKET` | (Your S3 Bucket Name) | For S3 integration. |
| `MAIL_MAILER` | `ses` or `smtp` | Configure your preferred mail service. |
| `MAIL_HOST` | (Your mail host) | e.g., `smtp.mailgun.org` |
| `MAIL_PORT` | (Your mail port) | e.g., `587` |
| `MAIL_USERNAME` | (Your mail username) | | 
| `MAIL_PASSWORD` | (Your mail password) | | 
| `MAIL_ENCRYPTION` | `tls` | | 

### Environment-Specific Variables

| Variable | `staging` Value | `production` Value |
|---|---|---|
| `APP_ENV` | `staging` | `production` |
| `APP_DEBUG` | `true` | `false` |
| `LOG_LEVEL` | `debug` | `error` |

## 7. Startup Instructions (Post-Deployment)

After your first deployment, configure the scheduler and queue worker for each environment.

### 7.1. Scheduler

1.  Go to **Environments** -> **[Your Environment]** -> **Scheduler**.
2.  Click **Enable Scheduler**.
3.  The default command `php artisan schedule:run` is correct.

### 7.2. Queue Worker

1.  Go to **Environments** -> **[Your Environment]** -> **Queues**.
2.  Click **Create Worker**.
3.  **Connection**: Select `redis`.
4.  **Queue**: `default`.
5.  **Processes**: Start with `1` and monitor.
6.  **Daemon**: Ensure this is enabled.
7.  Click **Create Worker**.

## 8. Deployment Workflow

With this setup, your deployment process is automated through Git:

1.  **Develop**: Push new features to feature branches and open pull requests against `develop`.
2.  **Test**: Merge pull requests into `develop`. This automatically triggers a deployment to the **`staging`** environment.
3.  **Release**: After testing on staging, merge the `develop` branch into `main`. This automatically triggers a deployment to the **`production`** environment.

This workflow ensures that all code is tested in a production-like environment before reaching your users.
