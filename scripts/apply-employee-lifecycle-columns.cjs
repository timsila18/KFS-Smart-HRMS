const fs = require('fs');
const path = require('path');
const { Client } = require('pg');

function readEnvFile(file) {
  if (!fs.existsSync(file)) {
    return {};
  }

  return Object.fromEntries(
    fs.readFileSync(file, 'utf8')
      .split(/\r?\n/)
      .map((line) => line.trim())
      .filter((line) => line && !line.startsWith('#'))
      .map((line) => {
        const index = line.indexOf('=');
        return [line.slice(0, index), line.slice(index + 1)];
      })
  );
}

async function main() {
  const env = { ...readEnvFile(path.join(process.cwd(), '.env.production.local')), ...process.env };
  const client = new Client({
    host: env.DB_HOST,
    port: Number(env.DB_PORT || 5432),
    database: env.DB_DATABASE,
    user: env.DB_USERNAME,
    password: env.DB_PASSWORD,
    ssl: { rejectUnauthorized: false },
  });

  await client.connect();
  await client.query(`
    alter table employees add column if not exists payroll_status varchar(60) not null default 'live';
    alter table employees add column if not exists account_status varchar(60) not null default 'active';
    alter table employees add column if not exists separated_at timestamptz null;
    alter table employees add column if not exists reinstated_at timestamptz null;
    create index if not exists idx_employees_payroll_status on employees (payroll_status) where deleted_at is null;
    create index if not exists idx_employees_account_status on employees (account_status) where deleted_at is null;
    update employees
      set payroll_status = 'stopped'
      where employment_status in ('separated', 'exited', 'inactive') and payroll_status = 'live';
    update employees
      set account_status = 'suspended'
      where employment_status in ('separated', 'exited') and account_status = 'active';
  `);

  const { rows } = await client.query(`
    select
      count(*) filter (where payroll_status = 'stopped') as stopped_salary,
      count(*) filter (where account_status = 'suspended') as suspended_accounts
    from employees
    where deleted_at is null
  `);
  await client.end();
  console.log(`Employee lifecycle columns ready. Stopped salaries: ${rows[0].stopped_salary}; suspended accounts: ${rows[0].suspended_accounts}.`);
}

main().catch((error) => {
  console.error(error);
  process.exit(1);
});
