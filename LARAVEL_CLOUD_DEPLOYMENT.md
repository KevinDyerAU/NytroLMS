# KeyLMSNytro: Laravel Cloud Deployment Guide

**Version:** 1.0
**Date:** January 29, 2026

This document provides a comprehensive guide for deploying the KeyLMSNytro application to Laravel Cloud with a multi-environment setup (staging and production).

## 1. Prerequisites

1.  **Laravel Cloud Account**: You must have an active Laravel Cloud account.
2.  **GitHub Repository**: The KeyLMSNytro code should be in a GitHub repository that you have administrative access to.
3.  **Repository Structure**: This guide assumes the Laravel application is in the `/source` subdirectory of the repository, as is the case with the current KeyLMSNytro setup.

## 2. Project Setup in Laravel Cloud

1.  **Log in to Laravel Cloud**.
2.  Click **Create Project**.
3.  Select **GitHub** as the source control provider and authorize Laravel Cloud to access your repositories.
4.  Choose the **`KevinDyerAU/KeyLMSNytro`** repository.
5.  **Project Name**: Give your project a descriptive name (e.g., `KeyLMSNytro`).
6.  **PHP Version**: Select **PHP 8.3**.
7.  **Laravel Root Directory**: Set this to `/source`. This is a critical step.
8.  Click **Create Project**.

Laravel Cloud will now provision the necessary infrastructure for your project.

## 3. Multi-Environment & GitHub Configuration

We will set up two environments: `production` and `staging`.

*   **`production`**: Deploys from the `main` branch.
*   **`staging`**: Deploys from the `develop` branch (or any other branch you designate for staging).

### 3.1. Production Environment

By default, Laravel Cloud creates a `production` environment linked to the `main` branch. No changes are needed here.

### 3.2. Staging Environment

1.  In your project dashboard, go to the **Environments** tab.
2.  Click **Create Environment**.
3.  **Name**: Enter `staging`.
4.  **Source Control Branch**: Select the `develop` branch (or your preferred staging branch).
5.  Click **Create Environment**.

Laravel Cloud will now create a completely separate environment for staging, with its own database, Redis cache, and domain.

## 4. The `laravel-cloud.yml` File

Create a `laravel-cloud.yml` file in the `/source` directory of your repository. This file instructs Laravel Cloud on how to build and deploy your application.

**File**: `/source/laravel-cloud.yml`

```yaml
build:
  - "composer install --no-interaction --prefer-dist --optimize-autoloader"
  - "npm install"
  - "npm run build"

deploy:
  - "php artisan migrate --force"
  - "php artisan config:cache"
  - "php artisan route:cache"
  - "php artisan view:cache"

# Note: `php artisan storage:link` is handled automatically by Laravel Cloud.
```

Commit and push this file to your repository.

## 5. Environment Variables

For each environment (`production` and `staging`), you must configure the necessary environment variables. Go to **Environments** -> **[Your Environment]** -> **Environment**.

### Key Variables for Both Environments

| Variable | Value | Notes |
|---|---|---|
| `APP_KEY` | (Generate with `php artisan key:generate`) | **Must be unique for each environment.** |
| `APP_URL` | `https://your-cloud-domain.com` | Provided by Laravel Cloud. |
| `DB_CONNECTION` | `mysql` | Managed by Laravel Cloud. |
| `CACHE_DRIVER` | `redis` | Recommended for production/staging. |
| `QUEUE_CONNECTION` | `redis` | Use Redis for reliable queue processing. |
| `SESSION_DRIVER` | `redis` | Use Redis for scalable session handling. |
| `REDIS_CLIENT` | `predis` | KeyLMSNytro uses the `predis/predis` package. |
| `FILESYSTEM_DRIVER` | `s3` | **Highly Recommended**. Use S3 for user uploads. |
| `AWS_ACCESS_KEY_ID` | (Your S3 Key) | Required if using S3. |
| `AWS_SECRET_ACCESS_KEY` | (Your S3 Secret) | Required if using S3. |
| `AWS_DEFAULT_REGION` | (Your S3 Region) | Required if using S3. |
| `AWS_BUCKET` | (Your S3 Bucket Name) | Required if using S3. |
| `MAIL_MAILER` | `ses` or `smtp` | Configure your preferred mail service. |

### Environment-Specific Variables

| Variable | `staging` Value | `production` Value |
|---|---|---|
| `APP_ENV` | `staging` | `production` |
| `APP_DEBUG` | `true` | `false` |
| `LOG_LEVEL` | `debug` | `error` |

## 6. Startup Instructions (Post-Deployment)

After your first successful deployment, you need to configure the scheduler and queue worker.

### 6.1. Scheduler

1.  Go to **Environments** -> **[Your Environment]** -> **Scheduler**.
2.  Click **Enable Scheduler**.
3.  The default command `php artisan schedule:run` is correct. Laravel Cloud will run this every minute.

### 6.2. Queue Worker

1.  Go to **Environments** -> **[Your Environment]** -> **Queues**.
2.  Click **Create Worker**.
3.  **Connection**: Select `redis`.
4.  **Queue**: Enter `default` (or your specific queue names if you use them).
5.  **Processes**: Start with `1` and adjust based on workload.
6.  **Daemon**: Ensure this is enabled.
7.  Click **Create Worker**.

## 7. Triggering Your First Deployment

Once you have pushed the `laravel-cloud.yml` file and configured your environment variables, you can trigger your first deployment.

1.  Go to your project dashboard in Laravel Cloud.
2.  Click **Deploy Now** for the environment you want to deploy.

Laravel Cloud will pull the latest code from the configured branch, execute the build and deploy steps, and make your application live.

## 8. GitHub Settings & Workflow

Your deployment workflow will now be based on your Git branching strategy:

*   **Push to `develop`**: Automatically triggers a deployment to the **`staging`** environment.
*   **Merge to `main`**: Automatically triggers a deployment to the **`production`** environment.

This setup allows you to test all changes in a production-like staging environment before they are released to your users.
