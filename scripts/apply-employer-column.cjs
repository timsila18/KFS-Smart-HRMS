const fs = require('fs');
const { Client } = require('pg');

for (const raw of fs.readFileSync('.env.production.local', 'utf8').split(/\r?\n/)) {
  const index = raw.indexOf('=');

  if (index > 0) {
    process.env[raw.slice(0, index)] = raw.slice(index + 1).trim();
  }
}

const client = new Client({
  host: process.env.DB_HOST,
  port: Number(process.env.DB_PORT || 5432),
  database: process.env.DB_DATABASE,
  user: process.env.DB_USERNAME,
  password: process.env.DB_PASSWORD,
  ssl: { rejectUnauthorized: false },
});

(async () => {
  await client.connect();
  await client.query("alter table employees add column if not exists employer varchar(120) not null default 'KFS'");
  await client.query("create index if not exists idx_employees_employer on employees (employer) where deleted_at is null");

  const result = await client.query(`
    select employer, count(*)::int as staff
    from employees
    where deleted_at is null
    group by employer
    order by employer
  `);

  console.log(JSON.stringify(result.rows, null, 2));
  await client.end();
})().catch((error) => {
  console.error(error.stack || error.message || error);
  process.exit(1);
});
