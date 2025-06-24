# Query Examples for Laravel Metrics

## 1. Error Rate per API (Status Code per Endpoint)

```sql
SELECT
  tags->>'endpoint' AS endpoint,
  tags->>'status_code' AS status_code,
  SUM(value) AS count
FROM metrics
WHERE name = 'http.requests'
  AND $__timeFilter(recorded_at)
GROUP BY endpoint, status_code
ORDER BY endpoint, status_code
```

## 2. Exceptions by Class

```sql
SELECT
  $__timeGroup(recorded_at, '1m') as time,
  tags->>'exception_class' as exception_class,
  SUM(value) as count
FROM metrics
WHERE name = 'exception.occurrence'
  AND $__timeFilter(recorded_at)
GROUP BY time, exception_class
ORDER BY time, exception_class
```

## 3. Top Endpoints by Error

```sql
SELECT
  tags->>'endpoint' as endpoint,
  SUM(value) as error_count
FROM metrics
WHERE name = 'http.requests'
  AND tags->>'status_code' != '200'
  AND $__timeFilter(recorded_at)
GROUP BY endpoint
ORDER BY error_count DESC
LIMIT 10
```

## 4. All Metrics for a Time Range

```sql
SELECT
  recorded_at as time,
  name,
  value,
  tags
FROM metrics
WHERE $__timeFilter(recorded_at)
ORDER BY recorded_at DESC
```
