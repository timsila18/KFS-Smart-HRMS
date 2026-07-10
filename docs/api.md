# KFS Smart HRMS API

The API is versioned under `/api/v1` and protected with Laravel Sanctum plus Spatie permissions.

## Conventions

- Authentication: `Authorization: Bearer <token>`
- Rate limit: `KFS_API_RATE_LIMIT_PER_MINUTE`
- Errors: `application/problem+json`
- Correlation: every response returns `X-Request-Id`

## Employees

`GET /api/v1/employees`

Filters:

- `search`
- `status`
- `station_id`
- `department_id`
- `job_position_id`
- `per_page` between 5 and 100

`GET /api/v1/employees/{uuid}`

Requires `employees.view`.
