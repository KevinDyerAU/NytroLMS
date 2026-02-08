# NytroLMS Migration Scripts

## Overview

These scripts migrate existing Laravel LMS users into Supabase Auth so they can log in to the new React frontend.

## Prerequisites

1. **Node.js 18+** installed
2. Install the Supabase client:
   ```bash
   npm install @supabase/supabase-js
   ```
3. Set environment variables:
   ```bash
   export SUPABASE_URL="https://rshmacirxysfwwyrszes.supabase.co"
   export SUPABASE_SERVICE_ROLE_KEY="your-service-role-key-here"
   ```

   The **service role key** can be found in your Supabase Dashboard under **Settings > API > service_role key**. This key has admin privileges and must never be exposed to the client.

---

## Scripts

### 1. `migrate-auth-users.mjs`

Creates Supabase Auth entries for each user in `public.users`.

**How it works:**
1. Reads all users from `public.users`
2. Fetches their roles from `model_has_roles` / `roles`
3. Checks which users already exist in `auth.users` (safe to re-run)
4. Creates new `auth.users` entries with:
   - Email confirmed (auto-verified since these are existing users)
   - Temporary random password (or a default you specify)
   - `user_metadata` containing `lms_user_id`, name, and role
   - `app_metadata` containing role for RLS policies
5. Outputs a mapping file (`auth_user_mapping.json`)

**Usage:**

```bash
# Preview what would happen (no changes made)
node scripts/migrate-auth-users.mjs --dry-run --verbose

# Migrate active users only
node scripts/migrate-auth-users.mjs --active-only --verbose

# Migrate all users
node scripts/migrate-auth-users.mjs --verbose

# Migrate with a shared default password
node scripts/migrate-auth-users.mjs --default-password "TempPass123!" --verbose

# Migrate and immediately send reset emails
node scripts/migrate-auth-users.mjs --send-reset-email --verbose
```

**Options:**

| Flag | Description |
|------|-------------|
| `--dry-run` | Preview only, no users created |
| `--active-only` | Only migrate users where `is_active = 1` |
| `--send-reset-email` | Send password reset emails after creation |
| `--batch-size N` | Users per batch (default: 10) |
| `--default-password PWD` | Use a shared temporary password |
| `--verbose` | Detailed per-user output |

**Output files:**
- `auth_user_mapping.json` — Maps `lms_user_id` to `auth_user_id` (safe to share)
- `auth_user_credentials.json` — Contains temporary passwords (delete after use!)

---

### 2. `send-reset-emails.mjs`

Sends password reset emails to all migrated users. Run this after `migrate-auth-users.mjs` if you didn't use `--send-reset-email`.

**Usage:**

```bash
# Preview which emails would be sent
node scripts/send-reset-emails.mjs --dry-run

# Send reset emails
node scripts/send-reset-emails.mjs --verbose

# Custom batch size and delay
node scripts/send-reset-emails.mjs --batch-size 5 --delay 3000
```

---

## Recommended Migration Workflow

```bash
# Step 1: Preview the migration
node scripts/migrate-auth-users.mjs --dry-run --active-only --verbose

# Step 2: Run the migration (active users first)
node scripts/migrate-auth-users.mjs --active-only --verbose

# Step 3: Verify in Supabase Dashboard → Authentication → Users

# Step 4: Send password reset emails
node scripts/send-reset-emails.mjs --verbose

# Step 5: Clean up credentials file
rm scripts/auth_user_credentials.json

# Step 6 (optional): Migrate inactive users later
node scripts/migrate-auth-users.mjs --verbose
```

## Important Notes

- **Password hashes cannot be migrated.** Laravel uses `$2y$` bcrypt hashes which are not compatible with Supabase Auth's internal format. Users must set new passwords via the reset email flow.
- **The script is idempotent.** It checks for existing `auth.users` by email before creating, so it's safe to run multiple times.
- **Rate limits apply.** The script pauses 1 second between batches to avoid hitting Supabase API rate limits. Adjust `--batch-size` if you encounter issues.
- **Service role key is required.** The `admin.createUser()` API requires the service role key, not the anon key.
