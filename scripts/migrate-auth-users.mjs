#!/usr/bin/env node
/**
 * NytroLMS — Supabase Auth Users Migration Script
 * =================================================
 *
 * Reads all users from public.users and creates corresponding entries in
 * Supabase Auth (auth.users) using supabase.auth.admin.createUser().
 *
 * KEY DETAILS:
 * - Existing bcrypt password hashes ($2y$) from Laravel are NOT directly
 *   compatible with Supabase Auth. This script sets a temporary password
 *   for each user and optionally triggers a password-reset email so users
 *   can set their own password on first login.
 * - The script stores the public.users.id in auth.users.user_metadata as
 *   `lms_user_id` so the two records can be linked.
 * - Duplicate emails are skipped (idempotent — safe to re-run).
 * - A mapping JSON file is produced linking public.users.id ↔ auth.users.id.
 *
 * PREREQUISITES:
 *   1. Node.js 18+ installed
 *   2. npm install @supabase/supabase-js
 *   3. Set environment variables (see below)
 *
 * USAGE:
 *   export SUPABASE_URL="https://rshmacirxysfwwyrszes.supabase.co"
 *   export SUPABASE_SERVICE_ROLE_KEY="your-service-role-key"
 *   node scripts/migrate-auth-users.mjs [options]
 *
 * OPTIONS:
 *   --dry-run           Preview what would happen without creating users
 *   --active-only       Only migrate active users (is_active = 1)
 *   --send-reset-email  Send password reset emails after creating users
 *   --batch-size N      Number of users to process per batch (default: 10)
 *   --default-password  Set a default temporary password (default: auto-generated per user)
 *   --verbose           Show detailed output for each user
 */

import { createClient } from '@supabase/supabase-js';
import crypto from 'crypto';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// ─────────────────────────────────────────────
// Configuration
// ─────────────────────────────────────────────

const SUPABASE_URL = process.env.SUPABASE_URL;
const SUPABASE_SERVICE_ROLE_KEY = process.env.SUPABASE_SERVICE_ROLE_KEY;

if (!SUPABASE_URL || !SUPABASE_SERVICE_ROLE_KEY) {
  console.error('');
  console.error('❌ Missing required environment variables:');
  console.error('');
  console.error('   SUPABASE_URL              — Your Supabase project URL');
  console.error('   SUPABASE_SERVICE_ROLE_KEY  — Service role key (NOT the anon key)');
  console.error('');
  console.error('Example:');
  console.error('  export SUPABASE_URL="https://rshmacirxysfwwyrszes.supabase.co"');
  console.error('  export SUPABASE_SERVICE_ROLE_KEY="eyJhbGciOiJIUzI1NiIs..."');
  console.error('');
  process.exit(1);
}

// Parse CLI arguments
const args = process.argv.slice(2);
const DRY_RUN = args.includes('--dry-run');
const ACTIVE_ONLY = args.includes('--active-only');
const SEND_RESET_EMAIL = args.includes('--send-reset-email');
const VERBOSE = args.includes('--verbose');
const batchSizeIdx = args.indexOf('--batch-size');
const BATCH_SIZE = batchSizeIdx !== -1 ? parseInt(args[batchSizeIdx + 1], 10) || 10 : 10;
const defaultPwIdx = args.indexOf('--default-password');
const DEFAULT_PASSWORD = defaultPwIdx !== -1 ? args[defaultPwIdx + 1] : null;

// ─────────────────────────────────────────────
// Supabase Admin Client (uses service_role key)
// ─────────────────────────────────────────────

const supabase = createClient(SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY, {
  auth: {
    autoRefreshToken: false,
    persistSession: false,
  },
});

// ─────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────

function generateTempPassword() {
  return crypto.randomBytes(18).toString('base64url');
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function log(msg) {
  console.log(`[${new Date().toISOString()}] ${msg}`);
}

// ─────────────────────────────────────────────
// Data Fetching
// ─────────────────────────────────────────────

async function fetchLmsUsers() {
  log('📥 Fetching users from public.users...');

  let query = supabase
    .from('users')
    .select('id, first_name, last_name, email, username, is_active, is_archived, created_at, email_verified_at')
    .order('id', { ascending: true });

  if (ACTIVE_ONLY) {
    query = query.eq('is_active', 1);
  }

  const { data, error } = await query;

  if (error) {
    console.error('❌ Failed to fetch users:', error.message);
    process.exit(1);
  }

  log(`   Found ${data.length} users${ACTIVE_ONLY ? ' (active only)' : ''}`);
  return data;
}

async function fetchUserRoles(userIds) {
  log('📥 Fetching user roles from model_has_roles...');

  // Supabase .in() has a limit, so batch if needed
  const allRoles = [];
  for (let i = 0; i < userIds.length; i += 100) {
    const batch = userIds.slice(i, i + 100);
    const { data, error } = await supabase
      .from('model_has_roles')
      .select('model_id, role_id, roles(name)')
      .eq('model_type', 'App\\Models\\User')
      .in('model_id', batch);

    if (error) {
      console.error('⚠️  Failed to fetch roles batch:', error.message);
      continue;
    }
    allRoles.push(...data);
  }

  const roleMap = {};
  for (const row of allRoles) {
    roleMap[row.model_id] = row.roles?.name || 'Student';
  }

  log(`   Found roles for ${Object.keys(roleMap).length} users`);
  return roleMap;
}

async function fetchExistingAuthEmails() {
  log('📥 Checking existing auth.users...');

  const allEmails = new Set();
  let page = 1;
  const perPage = 1000;

  while (true) {
    const { data, error } = await supabase.auth.admin.listUsers({ page, perPage });

    if (error) {
      console.error('⚠️  Failed to list auth users:', error.message);
      break;
    }

    for (const u of data.users) {
      if (u.email) allEmails.add(u.email.toLowerCase());
    }

    if (data.users.length < perPage) break;
    page++;
  }

  log(`   Found ${allEmails.size} existing auth users`);
  return allEmails;
}

// ─────────────────────────────────────────────
// File Output
// ─────────────────────────────────────────────

function saveMappingsToFile(mappings) {
  const outputPath = path.join(__dirname, 'auth_user_mapping.json');

  // Save without temp passwords for the "safe" version
  const safeMapping = mappings.map((m) => ({
    lms_user_id: m.lms_user_id,
    auth_user_id: m.auth_user_id,
    email: m.email,
    role: m.role,
  }));

  fs.writeFileSync(
    outputPath,
    JSON.stringify(
      {
        migrated_at: new Date().toISOString(),
        supabase_url: SUPABASE_URL,
        total_migrated: mappings.length,
        send_reset_email: SEND_RESET_EMAIL,
        users: safeMapping,
      },
      null,
      2
    )
  );

  log(`💾 Mapping saved to: ${outputPath}`);

  // Save a separate credentials file (contains temp passwords — handle with care)
  if (!SEND_RESET_EMAIL) {
    const credsPath = path.join(__dirname, 'auth_user_credentials.json');
    fs.writeFileSync(
      credsPath,
      JSON.stringify(
        {
          WARNING: 'This file contains temporary passwords. Delete after users have reset their passwords.',
          migrated_at: new Date().toISOString(),
          users: mappings.map((m) => ({
            lms_user_id: m.lms_user_id,
            email: m.email,
            temp_password: m.temp_password,
            role: m.role,
          })),
        },
        null,
        2
      )
    );
    log(`🔐 Credentials saved to: ${credsPath}`);
    log('   ⚠️  This file contains temporary passwords — store securely and delete after use!');
  }
}

// ─────────────────────────────────────────────
// Main Migration
// ─────────────────────────────────────────────

async function migrateUsers() {
  console.log('');
  console.log('╔══════════════════════════════════════════════════════╗');
  console.log('║   NytroLMS → Supabase Auth Users Migration          ║');
  console.log('╚══════════════════════════════════════════════════════╝');
  console.log('');

  if (DRY_RUN) {
    log('🔍 DRY RUN MODE — No users will be created');
    console.log('');
  }

  // Step 1: Fetch all LMS users
  const lmsUsers = await fetchLmsUsers();
  if (lmsUsers.length === 0) {
    log('✅ No users to migrate.');
    return;
  }

  // Step 2: Fetch roles for all users
  const userIds = lmsUsers.map((u) => u.id);
  const roleMap = await fetchUserRoles(userIds);

  // Step 3: Check which auth users already exist (skip duplicates)
  const existingEmails = await fetchExistingAuthEmails();

  // Step 4: Filter out already-migrated users
  const toMigrate = lmsUsers.filter((u) => {
    const emailLower = u.email.toLowerCase();
    if (existingEmails.has(emailLower)) {
      if (VERBOSE) log(`   ⏭️  Skipping ${u.email} — already exists in auth.users`);
      return false;
    }
    return true;
  });

  console.log('');
  log('📊 Migration Summary:');
  log(`   Total LMS users:     ${lmsUsers.length}`);
  log(`   Already in auth:     ${lmsUsers.length - toMigrate.length}`);
  log(`   To migrate:          ${toMigrate.length}`);
  log(`   Batch size:          ${BATCH_SIZE}`);
  log(`   Send reset emails:   ${SEND_RESET_EMAIL ? 'Yes' : 'No'}`);
  log(`   Active only:         ${ACTIVE_ONLY ? 'Yes' : 'No'}`);
  console.log('');

  if (toMigrate.length === 0) {
    log('✅ All users are already migrated. Nothing to do.');
    return;
  }

  // DRY RUN: just list users
  if (DRY_RUN) {
    log('🔍 Users that would be created:');
    console.log('');
    console.log('   ID    | Role       | Email');
    console.log('   ------+------------+----------------------------------');
    for (const user of toMigrate) {
      const role = (roleMap[user.id] || 'Student').padEnd(10);
      console.log(`   ${String(user.id).padEnd(5)} | ${role} | ${user.email}`);
    }
    console.log('');
    log(`🔍 DRY RUN complete. ${toMigrate.length} users would be created.`);
    log('   Remove --dry-run to execute the migration.');
    return;
  }

  // Step 5: Migrate in batches
  const results = { created: 0, failed: 0, errors: [] };
  const mappings = [];

  for (let i = 0; i < toMigrate.length; i += BATCH_SIZE) {
    const batch = toMigrate.slice(i, i + BATCH_SIZE);
    const batchNum = Math.floor(i / BATCH_SIZE) + 1;
    const totalBatches = Math.ceil(toMigrate.length / BATCH_SIZE);

    log(`🔄 Batch ${batchNum}/${totalBatches} — processing ${batch.length} users...`);

    for (const user of batch) {
      const role = roleMap[user.id] || 'Student';
      const tempPassword = DEFAULT_PASSWORD || generateTempPassword();

      try {
        const { data: authUser, error: createError } = await supabase.auth.admin.createUser({
          email: user.email,
          password: tempPassword,
          email_confirm: true,
          user_metadata: {
            lms_user_id: user.id,
            first_name: user.first_name,
            last_name: user.last_name,
            full_name: `${user.first_name} ${user.last_name}`.trim(),
            role: role,
            migrated_from: 'laravel_lms',
            migrated_at: new Date().toISOString(),
          },
          app_metadata: {
            role: role.toLowerCase(),
            lms_user_id: user.id,
          },
        });

        if (createError) {
          results.failed++;
          results.errors.push({ userId: user.id, email: user.email, error: createError.message });
          console.error(`   ❌ [${user.id}] ${user.email} — ${createError.message}`);
          continue;
        }

        results.created++;
        mappings.push({
          lms_user_id: user.id,
          auth_user_id: authUser.user.id,
          email: user.email,
          temp_password: tempPassword,
          role: role,
        });

        if (VERBOSE) {
          log(`   ✅ [${user.id}] ${user.email} → ${authUser.user.id} (${role})`);
        }

        // Optionally send password reset email
        if (SEND_RESET_EMAIL) {
          try {
            const { error: resetError } = await supabase.auth.admin.generateLink({
              type: 'recovery',
              email: user.email,
            });

            if (resetError) {
              console.error(`   ⚠️  Reset email failed for ${user.email}: ${resetError.message}`);
            } else if (VERBOSE) {
              log(`   📧 Reset link generated for ${user.email}`);
            }
          } catch (resetErr) {
            console.error(`   ⚠️  Reset email error for ${user.email}: ${resetErr.message}`);
          }
        }
      } catch (err) {
        results.failed++;
        results.errors.push({ userId: user.id, email: user.email, error: err.message });
        console.error(`   ❌ [${user.id}] ${user.email} — ${err.message}`);
      }
    }

    // Rate limiting pause between batches
    if (i + BATCH_SIZE < toMigrate.length) {
      await sleep(1000);
    }
  }

  // Step 6: Save mapping files
  if (mappings.length > 0) {
    console.log('');
    saveMappingsToFile(mappings);
  }

  // Step 7: Print final summary
  console.log('');
  console.log('╔══════════════════════════════════════════════════════╗');
  console.log('║   Migration Complete                                 ║');
  console.log('╚══════════════════════════════════════════════════════╝');
  console.log('');
  log(`   ✅ Created:  ${results.created}`);
  log(`   ❌ Failed:   ${results.failed}`);
  log(`   📊 Total:    ${results.created + results.failed}`);

  if (results.errors.length > 0) {
    console.log('');
    log('⚠️  Failed users:');
    for (const err of results.errors) {
      log(`   [${err.userId}] ${err.email}: ${err.error}`);
    }
  }

  if (!SEND_RESET_EMAIL && results.created > 0) {
    console.log('');
    log('📝 NEXT STEPS:');
    log('   1. Users were created with temporary passwords.');
    log('   2. Send password reset emails so users can set their own:');
    log('      node scripts/send-reset-emails.mjs');
    log('   3. Or re-run this script with --send-reset-email');
    log('   4. Delete auth_user_credentials.json after passwords are reset.');
  }

  console.log('');
}

// ─────────────────────────────────────────────
// Execute
// ─────────────────────────────────────────────

migrateUsers().catch((err) => {
  console.error('');
  console.error('💥 Unhandled error:', err);
  process.exit(1);
});
