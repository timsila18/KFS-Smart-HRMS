const fs = require('fs');
const path = require('path');
const { Client } = require('pg');

const envPath = path.resolve(process.cwd(), '.env.production.local');
const stationPath = process.argv[2] || path.resolve(process.cwd(), 'storage/app/kfs_stations.json');

if (!fs.existsSync(envPath)) {
  throw new Error('Missing .env.production.local. Pull production env before running this script.');
}

if (!fs.existsSync(stationPath)) {
  throw new Error(`Missing station JSON file: ${stationPath}`);
}

for (const raw of fs.readFileSync(envPath, 'utf8').split(/\r?\n/)) {
  const line = raw.trim();

  if (!line || line.startsWith('#')) {
    continue;
  }

  const index = line.indexOf('=');

  if (index < 0) {
    continue;
  }

  const key = line.slice(0, index);
  let value = line.slice(index + 1).trim();

  if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
    value = value.slice(1, -1);
  }

  process.env[key] = value;
}

const rows = JSON.parse(fs.readFileSync(stationPath, 'utf8'));
console.log(`Loaded ${rows.length} station records.`);

const client = new Client({
  host: process.env.DB_HOST,
  port: Number(process.env.DB_PORT || 5432),
  database: process.env.DB_DATABASE,
  user: process.env.DB_USERNAME,
  password: process.env.DB_PASSWORD,
  ssl: { rejectUnauthorized: false },
});

(async () => {
  console.log('Connecting to database...');
  await client.connect();
  console.log('Connected. Syncing stations...');
  await client.query('begin');

  const parent = { conservancy: new Map(), county: new Map() };
  let upserted = 0;

  for (const row of rows) {
    let parentId = null;

    if (row.station_type === 'county') {
      parentId = parent.conservancy.get(row.region) || null;
    }

    if (row.station_type === 'forest_station') {
      parentId = parent.county.get(`${row.region}|${row.county}`) || null;
    }

    const result = await client.query(`
      insert into stations (code, name, station_type, county, region, parent_id, is_active, created_at, updated_at, deleted_at)
      values ($1,$2,$3,$4,$5,$6,true,now(),now(),null)
      on conflict (code) do update set
        name = excluded.name,
        station_type = excluded.station_type,
        county = excluded.county,
        region = excluded.region,
        parent_id = excluded.parent_id,
        is_active = true,
        updated_at = now(),
        deleted_at = null
      returning id
    `, [row.code, row.name, row.station_type, row.county, row.region, parentId]);

    const id = result.rows[0].id;

    if (row.station_type === 'conservancy') {
      parent.conservancy.set(row.region, id);
    }

    if (row.station_type === 'county') {
      parent.county.set(`${row.region}|${row.county}`, id);
    }

    upserted++;
  }

  await client.query('commit');

  const counts = await client.query(`
    select station_type, count(*)::int as count
    from stations
    where deleted_at is null
    group by station_type
    order by station_type
  `);

  console.log(JSON.stringify({ upserted, counts: counts.rows }, null, 2));
  await client.end();
})().catch(async (error) => {
  try {
    await client.query('rollback');
  } catch (_) {
    // The connection may have failed before a transaction was opened.
  }

  console.error(error.stack || error.message || error);
  process.exit(1);
});
