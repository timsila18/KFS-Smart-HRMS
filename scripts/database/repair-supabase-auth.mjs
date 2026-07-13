import bcrypt from 'bcryptjs';
import pg from 'pg';

const { Client } = pg;

const requiredEnv = ['PGHOST', 'PGPORT', 'PGDATABASE', 'PGUSER', 'PGPASSWORD'];
const missing = requiredEnv.filter((key) => !process.env[key]);

if (missing.length > 0) {
  throw new Error(`Missing required environment variables: ${missing.join(', ')}`);
}

const accounts = [
  ['hr.admin@kfs.go.ke', 'KfsAdmin@2026'],
  ['hr.manager@kfs.go.ke', 'KfsManager@2026'],
  ['payroll.operator@kfs.go.ke', 'KfsPayroll@2026'],
];

const client = new Client({
  host: process.env.PGHOST,
  port: Number(process.env.PGPORT),
  database: process.env.PGDATABASE,
  user: process.env.PGUSER,
  password: process.env.PGPASSWORD,
  ssl: { rejectUnauthorized: false },
});

await client.connect();

try {
  await client.query('begin');

  await client.query('alter table roles add column if not exists station_id bigint null');
  await client.query('create index if not exists idx_roles_station_id on roles (station_id)');

  const constraint = await client.query('select 1 from pg_constraint where conname = $1', [
    'roles_station_id_fk',
  ]);

  if (constraint.rowCount === 0) {
    await client.query(
      'alter table roles add constraint roles_station_id_fk foreign key (station_id) references stations(id) on delete set null',
    );
  }

  const phpBcryptPrefix = '$2y$';

  for (const [email, password] of accounts) {
    const hash = phpBcryptPrefix + (await bcrypt.hash(password, 12)).slice(4);

    await client.query('update users set password = $1, updated_at = now() where email = $2', [
      hash,
      email,
    ]);
  }

  await client.query('commit');

  const { rows } = await client.query(
    `select email, left(password, 4) as password_prefix
     from users
     where email = any($1::text[])
     order by email`,
    [accounts.map(([email]) => email)],
  );

  console.log(JSON.stringify({ ok: true, accounts: rows }, null, 2));
} catch (error) {
  await client.query('rollback');
  throw error;
} finally {
  await client.end();
}
