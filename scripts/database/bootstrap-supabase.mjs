import bcrypt from 'bcryptjs';
import fs from 'node:fs';
import path from 'node:path';
import { Client } from 'pg';

const root = process.cwd();
const now = () => new Date();

class PhpArrayParser {
  constructor(input) {
    this.input = input;
    this.i = 0;
  }

  parse() {
    this.skip();
    return this.value();
  }

  value() {
    this.skip();
    const char = this.input[this.i];
    if (char === '[') return this.array();
    if (char === "'" || char === '"') return this.string();
    if (/[0-9-]/.test(char)) return this.number();
    const ident = this.ident();
    if (ident === 'true') return true;
    if (ident === 'false') return false;
    if (ident === 'null') return null;
    throw new Error(`Unexpected token near ${this.input.slice(this.i, this.i + 40)}`);
  }

  array() {
    this.expect('[');
    const entries = [];
    let associative = false;

    while (true) {
      this.skip();
      if (this.peek(']')) {
        this.expect(']');
        break;
      }

      const first = this.value();
      this.skip();
      if (this.peek('=>')) {
        this.i += 2;
        associative = true;
        entries.push([first, this.value()]);
      } else {
        entries.push([entries.length, first]);
      }

      this.skip();
      if (this.peek(',')) this.i++;
    }

    if (!associative) return entries.map(([, value]) => value);
    return Object.fromEntries(entries);
  }

  string() {
    const quote = this.input[this.i++];
    let out = '';
    while (this.i < this.input.length) {
      const char = this.input[this.i++];
      if (char === '\\') {
        out += this.input[this.i++] ?? '';
        continue;
      }
      if (char === quote) return out;
      out += char;
    }
    throw new Error('Unterminated string');
  }

  number() {
    const start = this.i;
    while (/[0-9.-]/.test(this.input[this.i] ?? '')) this.i++;
    const raw = this.input.slice(start, this.i);
    return raw.includes('.') ? Number.parseFloat(raw) : Number.parseInt(raw, 10);
  }

  ident() {
    const start = this.i;
    while (/[A-Za-z_]/.test(this.input[this.i] ?? '')) this.i++;
    return this.input.slice(start, this.i);
  }

  skip() {
    while (this.i < this.input.length) {
      if (/\s/.test(this.input[this.i])) {
        this.i++;
        continue;
      }
      if (this.input[this.i] === '/' && this.input[this.i + 1] === '/') {
        while (this.i < this.input.length && this.input[this.i] !== '\n') this.i++;
        continue;
      }
      break;
    }
  }

  peek(token) {
    return this.input.slice(this.i, this.i + token.length) === token;
  }

  expect(token) {
    if (!this.peek(token)) throw new Error(`Expected ${token}`);
    this.i += token.length;
  }
}

function extractDefinitions() {
  const migration = fs.readFileSync(path.join(root, 'database/migrations/2026_07_10_000000_create_kfs_hrms_database.php'), 'utf8');
  const marker = 'private function definitions(): array';
  const start = migration.indexOf('return [', migration.indexOf(marker));
  if (start === -1) throw new Error('Could not find definitions array.');
  let i = migration.indexOf('[', start);
  let depth = 0;
  let quote = null;
  for (; i < migration.length; i++) {
    const char = migration[i];
    const previous = migration[i - 1];
    if (quote) {
      if (char === quote && previous !== '\\') quote = null;
      continue;
    }
    if (char === "'" || char === '"') quote = char;
    if (char === '[') depth++;
    if (char === ']') {
      depth--;
      if (depth === 0) {
        return new PhpArrayParser(migration.slice(migration.indexOf('[', start), i + 1)).parse();
      }
    }
  }
  throw new Error('Definitions array was not closed.');
}

const ident = (value) => `"${String(value).replaceAll('"', '""')}"`;
const hash = (value) => {
  let h = 2166136261;
  for (const char of value) h = Math.imul(h ^ char.charCodeAt(0), 16777619);
  return (h >>> 0).toString(36);
};
const nameFor = (prefix, parts) => `${prefix}_${parts.join('_')}`.replace(/[^a-zA-Z0-9_]/g, '_').slice(0, 48) + '_' + hash(parts.join('_'));

function sqlType(type, args) {
  switch (type) {
    case 'string': return `varchar(${args ?? 255})`;
    case 'text':
    case 'longText': return 'text';
    case 'integer': return 'integer';
    case 'bigInteger': return 'bigint';
    case 'boolean': return 'boolean';
    case 'date': return 'date';
    case 'dateTimeTz': return 'timestamptz';
    case 'time': return 'time';
    case 'decimal': return `numeric(${args[0]},${args[1]})`;
    case 'jsonb': return 'jsonb';
    case 'foreignId': return 'bigint';
    default: throw new Error(`Unsupported type ${type}`);
  }
}

function literal(value) {
  if (value === null || value === undefined) return 'null';
  if (typeof value === 'boolean') return value ? 'true' : 'false';
  if (typeof value === 'number') return String(value);
  return `'${String(value).replaceAll("'", "''")}'`;
}

function columnSql(column) {
  const [type, name, args, options = {}] = column;
  const nullable = options.nullable ?? true;
  const parts = [ident(name), sqlType(type, args)];
  if (!nullable) parts.push('not null');
  if (Object.hasOwn(options, 'default')) parts.push(`default ${literal(options.default)}`);
  return parts.join(' ');
}

function baseColumns() {
  return [
    'id bigserial primary key',
    'uuid uuid not null default gen_random_uuid()',
    'created_by bigint null',
    'updated_by bigint null',
    'deleted_by bigint null',
    'created_at timestamptz null',
    'updated_at timestamptz null',
    'deleted_at timestamptz null',
  ];
}

function addIndex(sql, table, columns, unique = false, where = '') {
  const indexName = nameFor(unique ? 'uniq' : 'idx', [table, ...columns, where]);
  sql.push(`create ${unique ? 'unique ' : ''}index if not exists ${ident(indexName)} on ${ident(table)} (${columns.map(ident).join(', ')}) ${where};`);
}

function addFk(sql, table, column, refTable, refColumn = 'id', onDelete = 'set null') {
  const constraint = nameFor('fk', [table, column, refTable, refColumn]);
  sql.push(`do $$ begin if not exists (select 1 from pg_constraint where conname = ${literal(constraint)}) then alter table ${ident(table)} add constraint ${ident(constraint)} foreign key (${ident(column)}) references ${ident(refTable)}(${ident(refColumn)}) on delete ${onDelete}; end if; end $$;`);
}

function createTable(sql, table, columns) {
  sql.push(`create table if not exists ${ident(table)} (${columns.join(', ')});`);
  addIndex(sql, table, ['uuid'], true);
  for (const column of ['created_by', 'updated_by', 'deleted_by']) addIndex(sql, table, [column]);
}

function addColumn(sql, table, column, type) {
  sql.push(`alter table ${ident(table)} add column if not exists ${ident(column)} ${type};`);
}

function migrationSql() {
  const definitions = extractDefinitions();
  const sql = ['create extension if not exists pgcrypto;', 'create extension if not exists pg_trgm;'];

  for (const [table, definition] of Object.entries(definitions)) {
    createTable(sql, table, [...baseColumns(), ...(definition.columns ?? []).map(columnSql)]);
    for (const column of definition.columns ?? []) {
      const [, name, , options = {}] = column;
      if (options.unique) addIndex(sql, table, [name], true);
      if (options.index || column[0] === 'foreignId') addIndex(sql, table, [name]);
    }
    for (const columns of definition.unique ?? []) addIndex(sql, table, columns, true);
    for (const columns of definition.index ?? []) addIndex(sql, table, columns);
  }

  for (const [table, definition] of Object.entries(definitions)) {
    for (const audit of ['created_by', 'updated_by', 'deleted_by']) addFk(sql, table, audit, 'users');
    for (const [column, refColumn = 'id', refTable] of definition.foreign ?? []) addFk(sql, table, column, refTable, refColumn);
  }

  addColumn(sql, 'users', 'two_factor_secret', 'text null');
  addColumn(sql, 'users', 'two_factor_recovery_codes', 'jsonb null');
  addColumn(sql, 'users', 'two_factor_enabled', 'boolean not null default false');
  addColumn(sql, 'users', 'two_factor_confirmed_at', 'timestamptz null');
  addColumn(sql, 'roles', 'station_id', 'bigint null');
  addFk(sql, 'roles', 'station_id', 'stations');
  addIndex(sql, 'roles', ['station_id']);
  addColumn(sql, 'model_has_permissions', 'station_id', 'bigint null');
  addFk(sql, 'model_has_permissions', 'station_id', 'stations');
  addIndex(sql, 'model_has_permissions', ['station_id']);
  addColumn(sql, 'employees', 'photo_path', 'varchar(500) null');
  addColumn(sql, 'report_catalogs', 'is_schedulable', 'boolean not null default false');
  addColumn(sql, 'report_catalogs', 'schedule_frequency', 'varchar(40) null');
  addColumn(sql, 'report_catalogs', 'schedule_recipients', 'jsonb null');
  addColumn(sql, 'report_catalogs', 'next_run_at', 'timestamptz null');

  addColumn(sql, 'pay_codes', 'component_group', 'varchar(80) null');
  addColumn(sql, 'pay_codes', 'component_subtype', 'varchar(100) null');
  addColumn(sql, 'pay_codes', 'calculation_method', "varchar(80) not null default 'fixed'");
  addColumn(sql, 'pay_codes', 'default_amount', 'numeric(36,18) null');
  addColumn(sql, 'pay_codes', 'default_rate', 'numeric(24,12) null');
  addColumn(sql, 'pay_codes', 'is_recurring', 'boolean not null default true');
  addColumn(sql, 'pay_codes', 'requires_membership', 'boolean not null default false');
  addColumn(sql, 'pay_codes', 'is_active', 'boolean not null default true');
  addColumn(sql, 'pay_codes', 'sort_order', 'integer not null default 0');
  addIndex(sql, 'pay_codes', ['pay_code_type', 'component_group']);
  addIndex(sql, 'pay_codes', ['component_subtype', 'is_active']);

  for (const [table, columns] of Object.entries({
    payroll_run_items: { amount: [36, 18], quantity: [24, 12], rate: [24, 12] },
    payroll_adjustments: { amount: [36, 18] },
    payroll_runs: { gross_total: [36, 18], deduction_total: [36, 18], net_total: [36, 18] },
    payslips: { gross_pay: [36, 18], total_deductions: [36, 18], net_pay: [36, 18] },
    pay_codes: { default_amount: [36, 18], default_rate: [24, 12] },
  })) {
    for (const [column, [precision, scale]] of Object.entries(columns)) {
      sql.push(`alter table ${ident(table)} alter column ${ident(column)} type numeric(${precision},${scale}) using ${ident(column)}::numeric;`);
    }
  }

  createTable(sql, 'user_login_histories', [
    ...baseColumns(),
    'user_id bigint null',
    'email varchar(190) not null',
    'ip_address varchar(64) null',
    'user_agent text null',
    'status varchar(40) not null',
    'failure_reason varchar(120) null',
    'logged_in_at timestamptz not null',
    'logged_out_at timestamptz null',
    'metadata jsonb null',
  ]);
  addFk(sql, 'user_login_histories', 'user_id', 'users');
  addIndex(sql, 'user_login_histories', ['email']);
  addIndex(sql, 'user_login_histories', ['status']);
  addIndex(sql, 'user_login_histories', ['user_id', 'logged_in_at']);
  addIndex(sql, 'user_login_histories', ['email', 'status']);

  createTable(sql, 'employee_next_of_kin', [
    ...baseColumns(),
    'employee_id bigint not null',
    'full_name varchar(190) not null',
    'relationship varchar(80) not null',
    'phone varchar(40) null',
    'email varchar(190) null',
    'address varchar(255) null',
    'is_primary boolean not null default false',
  ]);
  addFk(sql, 'employee_next_of_kin', 'employee_id', 'employees', 'id', 'cascade');
  addIndex(sql, 'employee_next_of_kin', ['employee_id', 'is_primary']);

  createTable(sql, 'employee_medical_records', [
    ...baseColumns(),
    'employee_id bigint not null',
    'blood_group varchar(10) null',
    'medical_scheme varchar(160) null',
    'medical_membership_number varchar(120) null',
    'allergies text null',
    'conditions text null',
    'disabilities text null',
    'last_medical_exam_on date null',
    'next_medical_exam_on date null',
    'metadata jsonb null',
  ]);
  addFk(sql, 'employee_medical_records', 'employee_id', 'employees', 'id', 'cascade');
  addIndex(sql, 'employee_medical_records', ['medical_scheme', 'medical_membership_number']);

  createTable(sql, 'payroll_institutions', [
    ...baseColumns(),
    'institution_type varchar(80) not null',
    'code varchar(80) not null',
    'name varchar(190) not null',
    'registration_number varchar(120) null',
    'contact_person varchar(160) null',
    'phone varchar(40) null',
    'email varchar(190) null',
    'configuration jsonb null',
    'is_active boolean not null default true',
  ]);
  addIndex(sql, 'payroll_institutions', ['code'], true);
  addIndex(sql, 'payroll_institutions', ['institution_type', 'is_active']);

  createTable(sql, 'payroll_institution_products', [
    ...baseColumns(),
    'payroll_institution_id bigint not null',
    'pay_code_id bigint null',
    'product_type varchar(80) not null',
    'code varchar(80) not null',
    'name varchar(190) not null',
    "calculation_method varchar(80) not null default 'fixed'",
    'default_amount numeric(36,18) null',
    'default_rate numeric(24,12) null',
    'rules jsonb null',
    'is_active boolean not null default true',
  ]);
  addIndex(sql, 'payroll_institution_products', ['code'], true);
  addFk(sql, 'payroll_institution_products', 'payroll_institution_id', 'payroll_institutions', 'id', 'cascade');
  addFk(sql, 'payroll_institution_products', 'pay_code_id', 'pay_codes');
  addIndex(sql, 'payroll_institution_products', ['product_type', 'is_active']);

  addColumn(sql, 'payroll_runs', 'approved_by', 'bigint null');
  addColumn(sql, 'payroll_runs', 'approved_at', 'timestamptz null');
  addColumn(sql, 'payroll_runs', 'locked_by', 'bigint null');
  addColumn(sql, 'payroll_runs', 'locked_at', 'timestamptz null');
  addColumn(sql, 'payroll_runs', 'reversed_by', 'bigint null');
  addColumn(sql, 'payroll_runs', 'reversed_at', 'timestamptz null');
  addColumn(sql, 'payroll_runs', 'reversal_of_run_id', 'bigint null');
  addColumn(sql, 'payroll_runs', 'reversal_reason', 'text null');
  for (const column of ['approved_by', 'locked_by', 'reversed_by']) addFk(sql, 'payroll_runs', column, 'users');
  addFk(sql, 'payroll_runs', 'reversal_of_run_id', 'payroll_runs');

  createTable(sql, 'payroll_output_files', [
    ...baseColumns(),
    'payroll_run_id bigint not null',
    'output_type varchar(80) not null',
    'file_name varchar(255) not null',
    'file_path varchar(500) not null',
    'mime_type varchar(120) null',
    'metadata jsonb null',
  ]);
  addFk(sql, 'payroll_output_files', 'payroll_run_id', 'payroll_runs', 'id', 'cascade');
  addIndex(sql, 'payroll_output_files', ['payroll_run_id', 'output_type']);

  createTable(sql, 'bank_branches', [
    ...baseColumns(),
    'bank_code varchar(20) not null',
    'branch_code varchar(20) not null',
    'bank_name varchar(190) not null',
    'branch_name varchar(190) not null',
    'is_active boolean not null default true',
    'metadata jsonb null',
  ]);
  addIndex(sql, 'bank_branches', ['bank_code', 'branch_code'], true);
  addIndex(sql, 'bank_branches', ['bank_name', 'branch_name']);
  addColumn(sql, 'employee_bank_accounts', 'bank_branch_id', 'bigint null');
  addColumn(sql, 'employee_bank_accounts', 'bank_code', 'varchar(20) null');
  addColumn(sql, 'employee_bank_accounts', 'branch_code', 'varchar(20) null');
  addColumn(sql, 'employee_bank_accounts', 'metadata', 'jsonb null');
  addFk(sql, 'employee_bank_accounts', 'bank_branch_id', 'bank_branches');
  addIndex(sql, 'employee_bank_accounts', ['bank_code', 'branch_code']);

  createTable(sql, 'cache', [...baseColumns(), 'key varchar(255) not null', 'value text not null', 'expiration integer not null']);
  createTable(sql, 'cache_locks', [...baseColumns(), 'key varchar(255) not null', 'owner varchar(255) not null', 'expiration integer not null']);
  addIndex(sql, 'cache', ['key'], true);
  addIndex(sql, 'cache_locks', ['key'], true);
  createTable(sql, 'jobs', [
    'id bigserial primary key',
    'uuid uuid not null default gen_random_uuid()',
    'queue varchar(255) not null',
    'payload text not null',
    'attempts smallint not null',
    'reserved_at integer null',
    'available_at integer not null',
    'created_at integer not null',
    'created_by bigint null',
    'updated_by bigint null',
    'deleted_by bigint null',
    'updated_at timestamptz null',
    'deleted_at timestamptz null',
  ]);
  for (const column of ['queue', 'reserved_at', 'available_at', 'created_at']) addIndex(sql, 'jobs', [column]);
  sql.push('create table if not exists job_batches (id varchar(255) primary key, uuid uuid not null default gen_random_uuid(), name varchar(255) not null, total_jobs integer not null, pending_jobs integer not null, failed_jobs integer not null, failed_job_ids text not null, options text null, cancelled_at integer null, created_at integer not null, finished_at integer null, created_by bigint null, updated_by bigint null, deleted_by bigint null, updated_at timestamptz null, deleted_at timestamptz null);');
  createTable(sql, 'failed_jobs', [...baseColumns(), 'connection text not null', 'queue text not null', 'payload text not null', 'exception text not null', 'failed_at timestamptz not null default now()']);
  createTable(sql, 'personal_access_tokens', [
    ...baseColumns(),
    'tokenable_type varchar(255) not null',
    'tokenable_id bigint not null',
    'name varchar(255) not null',
    'token varchar(64) not null',
    'abilities text null',
    'last_used_at timestamptz null',
    'expires_at timestamptz null',
  ]);
  addIndex(sql, 'personal_access_tokens', ['token'], true);
  addIndex(sql, 'personal_access_tokens', ['tokenable_type', 'tokenable_id']);
  addIndex(sql, 'personal_access_tokens', ['expires_at']);

  sql.push("create index if not exists idx_employees_department_status on employees (department_id, employment_status) where deleted_at is null;");
  sql.push("create index if not exists idx_employees_station_status on employees (station_id, employment_status) where deleted_at is null;");
  sql.push("create index if not exists idx_employees_search_name on employees using gin (to_tsvector('simple', coalesce(employee_number,'') || ' ' || coalesce(first_name,'') || ' ' || coalesce(middle_name,'') || ' ' || coalesce(last_name,''))) where deleted_at is null;");
  sql.push("create index if not exists idx_payroll_runs_period_status on payroll_runs (payroll_period_id, status) where deleted_at is null;");
  sql.push("create index if not exists idx_payroll_run_items_run_employee on payroll_run_items (payroll_run_id, employee_id) where deleted_at is null;");
  sql.push("create index if not exists idx_report_runs_report_created on report_runs (report_catalog_id, created_at desc) where deleted_at is null;");
  sql.push("create index if not exists idx_audit_logs_event_created on audit_logs (event, created_at desc) where deleted_at is null;");
  sql.push("create index if not exists idx_audit_logs_subject on audit_logs (auditable_type, auditable_id, created_at desc) where deleted_at is null;");
  sql.push("create index if not exists idx_user_login_history_email_created on user_login_histories (email, created_at desc) where deleted_at is null;");

  sql.push('create table if not exists migrations (id serial primary key, migration varchar(255) not null, batch integer not null);');
  for (const migration of fs.readdirSync(path.join(root, 'database/migrations')).filter((file) => file.endsWith('.php'))) {
    sql.push(`insert into migrations (migration, batch) select ${literal(migration.replace('.php', ''))}, 1 where not exists (select 1 from migrations where migration = ${literal(migration.replace('.php', ''))});`);
  }

  return sql;
}

async function upsert(client, table, conflictColumns, data) {
  const row = { ...data, updated_at: now(), created_at: data.created_at ?? now() };
  const columns = Object.keys(row);
  const values = Object.values(row).map((value) => {
    if (Array.isArray(value)) return JSON.stringify(value);
    if (value && value.constructor === Object) return JSON.stringify(value);
    return value;
  });
  const updates = columns
    .filter((column) => !conflictColumns.includes(column) && column !== 'created_at')
    .map((column) => `${ident(column)} = excluded.${ident(column)}`)
    .join(', ');
  const sql = `insert into ${ident(table)} (${columns.map(ident).join(', ')}) values (${columns.map((_, i) => `$${i + 1}`).join(', ')}) on conflict (${conflictColumns.map(ident).join(', ')}) do update set ${updates};`;
  await client.query(sql, values);
}

async function value(client, sql, params = []) {
  const result = await client.query(sql, params);
  return result.rows[0] ? Object.values(result.rows[0])[0] : null;
}

function reportDefinitions() {
  return {
    PAYROLL_REGISTER: ['Payroll Register', 'payroll'],
    PAYROLL_SUMMARY: ['Payroll Summary', 'payroll'],
    EMPLOYEE_REGISTER: ['Employee Register', 'employees'],
    DEPARTMENT_PAYROLL: ['Department Payroll', 'payroll'],
    P9: ['P9', 'payroll'],
    PAYE: ['PAYE', 'statutory'],
    SHA: ['SHA', 'statutory'],
    NSSF: ['NSSF', 'statutory'],
    HOUSING_LEVY: ['Housing Levy', 'statutory'],
    HELB: ['HELB', 'deductions'],
    KEFSSWA: ['KEFSSWA', 'deductions'],
    ASILI_SACCO: ['Asili Sacco', 'deductions'],
    BANK_SCHEDULE: ['Bank Schedule', 'payroll'],
    HRISKE_WAGEBILL: ['Wagebill', 'payroll'],
    HRISKE_EARNINGS_DEDUCTIONS: ['Earnings & Deductions', 'payroll'],
    HRISKE_DEDUCTION_POSTINGS: ['Monthly Deduction Postings', 'payroll'],
    HRISKE_INDIVIDUAL_PAYMENT_BREAKDOWN: ['Individual Payment Breakdown', 'payroll'],
    HRISKE_BANK_SUMMARY: ['Bank Summary', 'payroll'],
    BANK_BRANCH_REGISTER: ['Bank Branch Register', 'payroll'],
    VARIANCE_REPORT: ['Variance Report', 'payroll'],
    CONTRACT_EXPIRY: ['Contract Expiry', 'contracts'],
    LEAVE_REPORT: ['Leave Report', 'leave'],
    PERFORMANCE_REPORT: ['Performance Report', 'performance'],
    TRAINING_REPORT: ['Training Report', 'training'],
  };
}

function payrollComponents() {
  const nssf = '1080.000000000000000000';
  return [
    ['BASIC', 'Basic Salary', 'earning', 'salary', 'basic_salary', true, true, 'formula', null, null, { method: 'formula', expression: 'basic_salary', prorate: true }],
    ['HOUSE', 'House Allowance', 'earning', 'allowance', 'house_allowance', true, false, 'formula', null, null, { method: 'formula', expression: 'house_allowance', prorate: true }],
    ['COMMUTER', 'Commuter Allowance', 'earning', 'allowance', 'commuter', true, false],
    ['EXTRANEOUS', 'Extraneous Allowance', 'earning', 'allowance', 'extraneous', true, false],
    ['SPECIAL_SALARY', 'Special Salary', 'earning', 'salary', 'special_salary', true, true],
    ['ACTING', 'Acting Allowance', 'earning', 'allowance', 'acting_allowance', true, false],
    ['LEAVE_ALLOWANCE', 'Leave Allowance', 'earning', 'allowance', 'leave_allowance', true, false, 'manual', null, null, {}, false],
    ['TRANSFER', 'Transfer Allowance', 'earning', 'allowance', 'transfer_allowance', true, false, 'manual', null, null, {}, false],
    ['RESPONSIBILITY', 'Responsibility Allowance', 'earning', 'allowance', 'responsibility_allowance', true, false],
    ['RISK', 'Risk Allowance', 'earning', 'allowance', 'risk_allowance', true, false],
    ['BONUS', 'Bonus', 'earning', 'bonus', 'bonus', true, false, 'manual', null, null, {}, false],
    ['ARREARS', 'Arrears', 'earning', 'arrears', 'arrears', true, false, 'manual', null, null, {}, false],
    ['OVERTIME', 'Overtime', 'earning', 'overtime', 'overtime', true, false, 'manual', null, null, {}, false],
    ['PAYE', 'PAYE', 'deduction', 'statutory', 'paye', false, false],
    ['SHA', 'SHA', 'deduction', 'statutory', 'sha', false, false],
    ['NSSF', 'NSSF', 'deduction', 'statutory', 'nssf', false, false, 'fixed', nssf, null, { method: 'fixed', amount: nssf }],
    ['AHL', 'Affordable Housing Levy', 'deduction', 'statutory', 'affordable_housing_levy', false, false],
    ['HELB', 'HELB', 'deduction', 'departmental', 'helb', false, false],
    ['GOK_HOUSING', 'GOK Housing', 'deduction', 'departmental', 'gok_housing', false, false],
    ['OVERPAYMENT', 'Overpayment Recovery', 'deduction', 'departmental', 'overpayment', false, false],
    ['IMPREST', 'Imprest Recovery', 'deduction', 'departmental', 'imprest_recovery', false, false],
    ['SALARY_ADVANCE', 'Salary Advance', 'deduction', 'departmental', 'salary_advance', false, false],
    ['MEDICAL_COVER_RECOVERY', 'Additional Medical Cover Recovery', 'deduction', 'departmental', 'additional_medical_cover_recovery', false, false],
    ['PENSION', 'Pension', 'deduction', 'pension', 'pension', false, false],
    ['VOL_PENSION', 'Voluntary Pension', 'deduction', 'pension', 'voluntary_pension', false, false],
    ['NSSF_VOL', 'NSSF Voluntary', 'deduction', 'pension', 'nssf_voluntary', false, false],
    ['KEFSSWA', 'KEFSSWA', 'deduction', 'welfare', 'kefsswa', false, false, 'manual', null, null, {}, true, true],
    ['SACCO', 'SACCO', 'deduction', 'sacco', 'sacco', false, false, 'manual', null, null, {}, true, true],
    ['LOAN', 'Loan', 'deduction', 'loan', 'loan', false, false, 'manual', null, null, {}, true, true],
    ['INSURANCE', 'Insurance', 'deduction', 'insurance', 'insurance', false, false, 'manual', null, null, {}, true, true],
    ['BANK_LOAN', 'Bank Loan', 'deduction', 'bank_loan', 'bank_loan', false, false, 'manual', null, null, {}, true, true],
    ['MORTGAGE', 'Mortgage', 'deduction', 'bank_loan', 'mortgage', false, false, 'manual', null, null, {}, true, true],
    ['CAR_LOAN', 'Car Loan', 'deduction', 'loan', 'car_loan', false, false, 'manual', null, null, {}, true, true],
    ['EMERGENCY_LOAN', 'Emergency Loan', 'deduction', 'loan', 'emergency_loan', false, false, 'manual', null, null, {}, true, true],
  ];
}

async function seed(client) {
  const modules = ['dashboard', 'users', 'roles', 'permissions', 'departments', 'stations', 'employees', 'contracts', 'payroll', 'leave', 'attendance', 'performance', 'training', 'ess', 'reports', 'audit', 'notifications', 'settings'];
  const actions = ['view', 'create', 'update', 'approve', 'export', 'delete'];
  const roles = ['super-admin', 'hr-admin', 'hr-manager', 'hr-payroll-operator', 'hr-director', 'hr-officer', 'payroll-manager', 'station-manager', 'employee'];

  for (const group of [
    ['organization', 'Organization', 10],
    ['payroll', 'Payroll', 20],
    ['leave', 'Leave', 30],
    ['security', 'Security', 40],
    ['notifications', 'Notifications', 50],
  ]) await upsert(client, 'setting_groups', ['code'], { code: group[0], name: group[1], sort_order: group[2] });

  for (const role of roles) {
    await upsert(client, 'roles', ['name', 'guard_name'], { name: role, guard_name: 'web', scope: 'system', description: role.replaceAll('-', ' '), is_system: true });
  }

  for (const module of modules) {
    for (const action of actions) {
      await upsert(client, 'permissions', ['name', 'guard_name'], { name: `${module}.${action}`, guard_name: 'web', module, description: `${action} ${module}` });
    }
  }

  const permissionRows = (await client.query('select id, name from permissions')).rows;
  const permissionByName = new Map(permissionRows.map((row) => [row.name, row.id]));
  const roleByName = new Map((await client.query('select id, name from roles')).rows.map((row) => [row.name, row.id]));
  const permissionsFor = (profile) => Object.entries(profile).flatMap(([module, profileActions]) => profileActions.map((action) => `${module}.${action}`));
  const manager = permissionsFor({
    dashboard: ['view'], departments: ['view', 'create', 'update', 'approve', 'export'], stations: ['view', 'create', 'update', 'approve', 'export'], employees: ['view', 'create', 'update', 'approve', 'export'], contracts: ['view', 'create', 'update', 'approve', 'export'], payroll: ['view', 'create', 'update', 'approve', 'export'], leave: ['view', 'create', 'update', 'approve', 'export'], attendance: ['view', 'create', 'update', 'approve', 'export'], performance: ['view', 'create', 'update', 'approve', 'export'], training: ['view', 'create', 'update', 'approve', 'export'], ess: ['view', 'approve'], reports: ['view', 'create', 'update', 'approve', 'export'], audit: ['view', 'export'], notifications: ['view', 'create', 'update', 'approve'],
  });
  const supervisor = permissionsFor({
    dashboard: ['view'], employees: ['view', 'update', 'export'], contracts: ['view', 'update', 'export'], payroll: ['view', 'create', 'update', 'export'], leave: ['view', 'update', 'approve', 'export'], attendance: ['view', 'create', 'update', 'approve', 'export'], performance: ['view', 'create', 'update', 'export'], training: ['view', 'create', 'update', 'export'], ess: ['view', 'approve'], reports: ['view', 'export'], notifications: ['view', 'create', 'update'],
  });
  const profiles = { 'super-admin': [...permissionByName.keys()], 'hr-admin': [...permissionByName.keys()], 'hr-manager': manager, 'hr-payroll-operator': supervisor, 'hr-director': manager, 'payroll-manager': manager, 'hr-officer': supervisor };
  for (const [role, permissions] of Object.entries(profiles)) {
    for (const permission of permissions) {
      const roleId = roleByName.get(role);
      const permissionId = permissionByName.get(permission);
      if (roleId && permissionId) await upsert(client, 'role_has_permissions', ['permission_id', 'role_id'], { role_id: roleId, permission_id: permissionId });
    }
  }

  await upsert(client, 'departments', ['code'], { code: 'HR', name: 'Human Resource Management', type: 'directorate', is_active: true });
  await upsert(client, 'departments', ['code'], { code: 'FIN', name: 'Finance and Accounts', type: 'directorate', is_active: true });
  await upsert(client, 'stations', ['code'], { code: 'HQ', name: 'KFS Headquarters', station_type: 'headquarters', county: 'Nairobi', region: 'Nairobi', is_active: true });
  await upsert(client, 'stations', ['code'], { code: 'CFA-NRB', name: 'Nairobi Conservancy', station_type: 'conservancy', county: 'Nairobi', region: 'Central', is_active: true });
  await upsert(client, 'job_grades', ['code'], { code: 'KFS-1', name: 'KFS Grade 1', rank_order: 1, is_active: true });
  await upsert(client, 'job_grades', ['code'], { code: 'KFS-2', name: 'KFS Grade 2', rank_order: 2, is_active: true });
  await upsert(client, 'job_positions', ['code'], { code: 'HRM', job_grade_id: await value(client, "select id from job_grades where code = 'KFS-1'"), title: 'Human Resource Manager', description: 'Leads HR operations.', is_active: true });
  await upsert(client, 'cost_centres', ['code'], { code: 'HQ-HR', station_id: await value(client, "select id from stations where code = 'HQ'"), name: 'Headquarters HR', is_active: true });

  for (const type of [['PERM', 'Permanent and Pensionable', true], ['CONTRACT', 'Fixed Term Contract', false], ['CASUAL', 'Casual', false]]) {
    await upsert(client, 'employment_types', ['code'], { code: type[0], name: type[1], is_pensionable: type[2] });
  }
  for (const type of [['OPEN', 'Open Ended', null, false], ['FIXED-12', 'Fixed 12 Months', 12, true], ['FIXED-36', 'Fixed 36 Months', 36, true]]) {
    await upsert(client, 'contract_types', ['code'], { code: type[0], name: type[1], default_months: type[2], requires_end_date: type[3] });
  }
  await upsert(client, 'pay_groups', ['code'], { code: 'MONTHLY', name: 'Monthly Payroll', frequency: 'monthly' });

  for (const component of payrollComponents()) {
    const [code, name, pay_code_type, component_group, component_subtype, is_taxable, is_pensionable, calculation_method = 'manual', default_amount = null, default_rate = null, rules = {}, is_recurring = true, requires_membership = false] = component;
    await upsert(client, 'pay_codes', ['code'], { code, name, pay_code_type, component_group, component_subtype, calculation_method, default_amount, default_rate, calculation_rules: rules, is_taxable, is_pensionable, is_recurring, requires_membership, is_active: true, sort_order: 0 });
  }
  for (const deduction of [['PAYE', 'Pay As You Earn', 'tax'], ['NSSF', 'National Social Security Fund', 'social_security'], ['SHIF', 'Social Health Insurance Fund', 'health']]) {
    await upsert(client, 'statutory_deductions', ['code'], { code: deduction[0], name: deduction[1], deduction_type: deduction[2], rules: {}, effective_from: '2026-01-01' });
  }
  await upsert(client, 'pension_schemes', ['code'], { code: 'KFS-PENSION', name: 'KFS Pension Scheme', employee_rate: 0, employer_rate: 0, rules: {} });
  await upsert(client, 'payroll_institutions', ['code'], { institution_type: 'sacco', code: 'ASILI_SACCO', name: 'Asili Sacco', configuration: {}, is_active: true });
  await upsert(client, 'payroll_institutions', ['code'], { institution_type: 'welfare', code: 'KEFSSWA', name: 'KEFSSWA', configuration: {}, is_active: true });

  for (const type of [['ANNUAL', 'Annual Leave', true, false], ['SICK', 'Sick Leave', true, true], ['MATERNITY', 'Maternity Leave', true, true], ['PATERNITY', 'Paternity Leave', true, true]]) {
    await upsert(client, 'leave_types', ['code'], { code: type[0], name: type[1], is_paid: type[2], requires_attachment: type[3] });
  }
  await upsert(client, 'holiday_calendars', ['code'], { code: 'KEN_PUBLIC', name: 'Kenya Public Holidays', country_code: 'KEN', is_active: true });
  await upsert(client, 'shifts', ['code'], { code: 'DAY', name: 'Day Shift', starts_at: '08:00:00', ends_at: '17:00:00', grace_minutes: 15 });
  await upsert(client, 'shift_patterns', ['code'], { code: 'MON-FRI', name: 'Monday to Friday', pattern_rules: { days: [1, 2, 3, 4, 5] } });

  for (const [code, [name, module]] of Object.entries(reportDefinitions())) {
    await upsert(client, 'report_catalogs', ['code'], { code, name, module, parameters_schema: { filters: ['period_id', 'department_id', 'date_from', 'date_to'], outputs: ['preview', 'excel', 'pdf', 'chart'] }, is_active: true, is_schedulable: false, schedule_recipients: [] });
  }
  for (const template of [
    ['LEAVE_REQUEST_SUBMITTED', 'mail', 'Leave request submitted', 'A leave request has been submitted for approval.'],
    ['PAYSLIP_PUBLISHED', 'mail', 'Payslip published', 'Your payslip is available in ESS.'],
  ]) await upsert(client, 'notification_templates', ['code'], { code: template[0], channel: template[1], subject: template[2], body: template[3], variables: {}, is_active: true });
  for (const type of [
    ['EMPLOYEE_FILE', 'Employee File', ['application/pdf', 'image/jpeg', 'image/png'], 5120],
    ['PAYROLL_IMPORT', 'Payroll Import', ['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'], 10240],
  ]) await upsert(client, 'attachment_types', ['code'], { code: type[0], name: type[1], allowed_mime_types: type[2], max_size_kb: type[3] });

  const accounts = [
    ['HR Admin', 'hr.admin@kfs.go.ke', 'KfsAdmin@2026', 'hr-admin'],
    ['HR Manager', 'hr.manager@kfs.go.ke', 'KfsManager@2026', 'hr-manager'],
    ['HR Payroll Operator', 'payroll.operator@kfs.go.ke', 'KfsPayroll@2026', 'hr-payroll-operator'],
  ];
  for (const [name, email, password, role] of accounts) {
    const hash = '$2y$' + bcrypt.hashSync(password, 10).slice(4);
    await upsert(client, 'users', ['email'], { name, email, password: hash, status: 'active', email_verified_at: now() });
    const userId = await value(client, 'select id from users where email = $1', [email]);
    await upsert(client, 'model_has_roles', ['role_id', 'model_type', 'model_id', 'station_id'], { role_id: roleByName.get(role), model_type: 'App\\Models\\User', model_id: userId, station_id: null });
  }

  const branchCsv = path.join(root, 'database/seeders/data/hriske_bank_branches_2025.csv');
  if (fs.existsSync(branchCsv)) {
    const lines = fs.readFileSync(branchCsv, 'utf8').trim().split(/\r?\n/);
    const headers = lines.shift().split(',');
    for (const line of lines) {
      const cells = line.match(/("([^"]|"")*"|[^,]*)/g).filter((_, i) => i % 2 === 0).map((cell) => cell.replace(/^"|"$/g, '').replaceAll('""', '"'));
      const row = Object.fromEntries(headers.map((header, i) => [header, cells[i] ?? '']));
      if (!row.BankCode || !row.BranchAreaCode) continue;
      await upsert(client, 'bank_branches', ['bank_code', 'branch_code'], { bank_code: row.BankCode.trim(), branch_code: row.BranchAreaCode.trim(), bank_name: row.BankName?.trim() || '', branch_name: row.BranchName?.trim() || '', is_active: true, metadata: { source: 'hriske_branchreportexport2025' } });
    }
  }
}

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
  try {
    await client.query('begin');
    for (const statement of migrationSql()) await client.query(statement);
    await seed(client);
    await client.query('commit');
    const tables = await value(client, "select count(*) from information_schema.tables where table_schema = 'public'");
    const users = await value(client, 'select count(*) from users');
    const branches = await value(client, 'select count(*) from bank_branches');
    console.log(JSON.stringify({ ok: true, tables: Number(tables), users: Number(users), bankBranches: Number(branches) }));
  } catch (error) {
    await client.query('rollback');
    throw error;
  } finally {
    await client.end();
  }
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
