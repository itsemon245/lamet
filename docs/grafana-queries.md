# Grafana Query Examples

## Time Series Graph

```sql
-- Response time over time
SELECT
    recorded_at as time,
    value,
    name as metric
FROM metrics
WHERE name = 'api.response_time'
  AND recorded_at >= $__timeFrom()
  AND recorded_at <= $__timeTo()
ORDER BY recorded_at;
```

## Counter Graph

```sql
-- Request count over time
SELECT
    recorded_at as time,
    value,
    name as metric
FROM metrics
WHERE name = 'api.requests'
  AND recorded_at >= $__timeFrom()
  AND recorded_at <= $__timeTo()
ORDER BY recorded_at;
```

## Tag-based Filtering

```sql
-- Filter by tags (PostgreSQL jsonb)
SELECT
    recorded_at as time,
    value,
    tags->>'endpoint' as endpoint
FROM metrics
WHERE name = 'api.response_time'
  AND tags @> '{"endpoint": "/users"}'
  AND recorded_at >= $__timeFrom()
  AND recorded_at <= $__timeTo()
ORDER BY recorded_at;
```

## Multi-metric Dashboard

```sql
-- Multiple metrics in one query
SELECT
    recorded_at as time,
    value,
    name as metric
FROM metrics
WHERE name IN ('api.response_time', 'api.requests', 'memory.usage')
  AND recorded_at >= $__timeFrom()
  AND recorded_at <= $__timeTo()
ORDER BY recorded_at, name;
```

# Dashboard Variables

Create dynamic dashboards with variables:

## Metric Names Variable

```sql
SELECT DISTINCT name FROM metrics ORDER BY name;
```

## Tag Values Variable

```sql
-- For PostgreSQL
SELECT DISTINCT jsonb_object_keys(tags) as tag_key FROM metrics;

-- For MySQL/SQLite
SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(tags, '$.endpoint')) as endpoint
FROM metrics
WHERE JSON_EXTRACT(tags, '$.endpoint') IS NOT NULL;
```
