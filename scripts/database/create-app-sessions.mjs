import { Client } from 'pg';

async function main() {
  const client = new Client({
    connectionString: process.env.DB_URL || undefined,
    host: process.env.PGHOST,
    port: process.env.PGPORT ? Number(process.env.PGPORT) : undefined,
    database: process.env.PGDATABASE,
    user: process.env.PGUSER,
    password: process.env.PGPASSWORD,
    ssl: { rejectUnauthorized: false },
  });

  await client.connect();
  await client.query('create extension if not exists pgcrypto');
  await client.query(`
    create table if not exists app_sessions (
      id varchar(255) primary key,
      uuid uuid not null default gen_random_uuid() unique,
      user_id bigint null,
      ip_address varchar(64) null,
      user_agent text null,
      payload text not null,
      last_activity integer not null,
      created_by bigint null,
      updated_by bigint null,
      deleted_by bigint null,
      created_at timestamptz null,
      updated_at timestamptz null,
      deleted_at timestamptz null
    )
  `);
  await client.query('create index if not exists app_sessions_user_id_index on app_sessions (user_id)');
  await client.query('create index if not exists app_sessions_last_activity_index on app_sessions (last_activity)');
  await client.query(`
    insert into migrations (migration, batch)
    select '2026_07_10_080000_create_app_sessions_table', coalesce((select max(batch) from migrations), 1)
    where not exists (
      select 1 from migrations where migration = '2026_07_10_080000_create_app_sessions_table'
    )
  `);
  console.log('app_sessions ready');
  await client.end();
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
