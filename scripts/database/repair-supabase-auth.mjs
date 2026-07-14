import bcrypt from 'bcryptjs';
import pg from 'pg';

const { Client } = pg;

const requiredEnv = ['PGHOST', 'PGPORT', 'PGDATABASE', 'PGUSER', 'PGPASSWORD'];
const missing = requiredEnv.filter((key) => !process.env[key]);

if (missing.length > 0) {
  throw new Error(`Missing required environment variables: ${missing.join(', ')}`);
}

const accounts = [
  ['hr.admin@kfs.go.ke', 'KfsAdmin@2026', 'hr-admin'],
  ['hr.manager@kfs.go.ke', 'KfsManager@2026', 'hr-manager'],
  ['payroll.operator@kfs.go.ke', 'KfsPayroll@2026', 'hr-payroll-operator'],
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
  await client.query('alter table model_has_permissions add column if not exists station_id bigint null');
  await client.query('create index if not exists idx_roles_station_id on roles (station_id)');
  await client.query(
    'create index if not exists idx_model_has_permissions_station_id on model_has_permissions (station_id)',
  );

  const roleConstraint = await client.query('select 1 from pg_constraint where conname = $1', [
    'roles_station_id_fk',
  ]);

  if (roleConstraint.rowCount === 0) {
    await client.query(
      'alter table roles add constraint roles_station_id_fk foreign key (station_id) references stations(id) on delete set null',
    );
  }

  const modelPermissionConstraint = await client.query(
    'select 1 from pg_constraint where conname = $1',
    ['model_has_permissions_station_id_fk'],
  );

  if (modelPermissionConstraint.rowCount === 0) {
    await client.query(
      'alter table model_has_permissions add constraint model_has_permissions_station_id_fk foreign key (station_id) references stations(id) on delete set null',
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

  for (const [email, , roleName] of accounts) {
    const user = await client.query('select id from users where email = $1', [email]);
    const role = await client.query('select id from roles where name = $1 and guard_name = $2', [
      roleName,
      'web',
    ]);

    if (user.rowCount === 0 || role.rowCount === 0) {
      throw new Error(`Cannot assign role ${roleName} to ${email}; missing user or role.`);
    }

    await client.query(
      'delete from model_has_roles where model_type = $1 and model_id = $2 and role_id = $3',
      ['App\\Models\\User', user.rows[0].id, role.rows[0].id],
    );

    await client.query(
      'insert into model_has_roles (role_id, model_type, model_id, station_id) values ($1, $2, $3, null)',
      [role.rows[0].id, 'App\\Models\\User', user.rows[0].id],
    );
  }

  await client.query('commit');

  const { rows } = await client.query(
    `select users.email, left(users.password, 4) as password_prefix, roles.name as role
     from users
     left join model_has_roles on model_has_roles.model_id = users.id
      and model_has_roles.model_type = 'App\\Models\\User'
     left join roles on roles.id = model_has_roles.role_id
     where users.email = any($1::text[])
     order by users.email`,
    [accounts.map(([email]) => email)],
  );

  console.log(JSON.stringify({ ok: true, accounts: rows }, null, 2));
} catch (error) {
  await client.query('rollback');
  throw error;
} finally {
  await client.end();
}
