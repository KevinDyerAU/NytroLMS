#!/usr/bin/env node
/**
 * NytroLMS — Send Password Reset Emails to Migrated Users
 * ========================================================
 *
 * Companion script to migrate-auth-users.mjs.
 * Reads the auth_user_mapping.json file and sends password reset emails
 * to all migrated users so they can set their own password.
 *
 * USAGE:
 *   export SUPABASE_URL="https://rshmacirxysfwwyrszes.supabase.co"
 *   export SUPABASE_SERVICE_ROLE_KEY="your-service-role-key"
 *   node scripts/send-reset-emails.mjs [options]
 *
 * OPTIONS:
 *   --dry-run       Preview which emails would be sent
 *   --batch-size N  Emails per batch (default: 10)
 *   --delay N       Milliseconds between batches (default: 2000)
 *   --verbose       Show detailed output
 */

import { createClient } from '@supabase/supabase-js';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const SUPABASE_URL = process.env.SUPABASE_URL;
const SUPABASE_SERVICE_ROLE_KEY = process.env.SUPABASE_SERVICE_ROLE_KEY;

if (!SUPABASE_URL || !SUPABASE_SERVICE_ROLE_KEY) {
  console.error('❌ Missing SUPABASE_URL or SUPABASE_SERVICE_ROLE_KEY');
  process.exit(1);
}

const args = process.argv.slice(2);
const DRY_RUN = args.includes('--dry-run');
const VERBOSE = args.includes('--verbose');
const batchSizeIdx = args.indexOf('--batch-size');
const BATCH_SIZE = batchSizeIdx !== -1 ? parseInt(args[batchSizeIdx + 1], 10) || 10 : 10;
const delayIdx = args.indexOf('--delay');
const DELAY_MS = delayIdx !== -1 ? parseInt(args[delayIdx + 1], 10) || 2000 : 2000;

const supabase = createClient(SUPABASE_URL, SUPABASE_SERVICE_ROLE_KEY, {
  auth: { autoRefreshToken: false, persistSession: false },
});

function log(msg) {
  console.log(`[${new Date().toISOString()}] ${msg}`);
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function main() {
  console.log('');
  console.log('╔══════════════════════════════════════════════════════╗');
  console.log('║   NytroLMS — Send Password Reset Emails             ║');
  console.log('╚══════════════════════════════════════════════════════╝');
  console.log('');

  // Load mapping file
  const mappingPath = path.join(__dirname, 'auth_user_mapping.json');
  if (!fs.existsSync(mappingPath)) {
    console.error('❌ auth_user_mapping.json not found.');
    console.error('   Run migrate-auth-users.mjs first to create it.');
    process.exit(1);
  }

  const mapping = JSON.parse(fs.readFileSync(mappingPath, 'utf-8'));
  const users = mapping.users || [];

  log(`📥 Loaded ${users.length} users from mapping file`);

  if (users.length === 0) {
    log('✅ No users to process.');
    return;
  }

  if (DRY_RUN) {
    log('🔍 DRY RUN — Would send reset emails to:');
    for (const u of users) {
      log(`   • ${u.email} (LMS ID: ${u.lms_user_id})`);
    }
    log(`🔍 Total: ${users.length} emails`);
    return;
  }

  let sent = 0;
  let failed = 0;

  for (let i = 0; i < users.length; i += BATCH_SIZE) {
    const batch = users.slice(i, i + BATCH_SIZE);
    const batchNum = Math.floor(i / BATCH_SIZE) + 1;
    const totalBatches = Math.ceil(users.length / BATCH_SIZE);

    log(`🔄 Batch ${batchNum}/${totalBatches} — sending ${batch.length} emails...`);

    for (const user of batch) {
      try {
        const { error } = await supabase.auth.resetPasswordForEmail(user.email, {
          redirectTo: `${SUPABASE_URL.replace('.supabase.co', '')}/reset-password`,
        });

        if (error) {
          failed++;
          console.error(`   ❌ ${user.email}: ${error.message}`);
        } else {
          sent++;
          if (VERBOSE) log(`   ✅ ${user.email}`);
        }
      } catch (err) {
        failed++;
        console.error(`   ❌ ${user.email}: ${err.message}`);
      }
    }

    if (i + BATCH_SIZE < users.length) {
      log(`   ⏳ Waiting ${DELAY_MS}ms before next batch...`);
      await sleep(DELAY_MS);
    }
  }

  console.log('');
  log('📊 Results:');
  log(`   ✅ Sent:    ${sent}`);
  log(`   ❌ Failed:  ${failed}`);
  log(`   📊 Total:   ${sent + failed}`);
  console.log('');
}

main().catch((err) => {
  console.error('💥 Unhandled error:', err);
  process.exit(1);
});
