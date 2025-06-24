# PostgreSQL Configuration Guide

This document explains the PostgreSQL configuration options used in `postgres-docker-compose.yml` for optimal metrics performance.

## Quick Start

1. Copy `misc/.env.example` to `misc/.env`
2. Update credentials in `misc/.env`
3. Run: `docker-compose -f misc/postgres-docker-compose.yml up -d`

## Configuration Breakdown

### Query Statistics & Monitoring

#### `shared_preload_libraries='pg_stat_statements'`

- **Purpose**: Enables query performance monitoring
- **Pros**: Essential for performance tuning and identifying slow queries
- **Cons**: ~1-2MB memory overhead
- **Recommendation**: ✅ KEEP - Critical for metrics performance tuning

#### `pg_stat_statements.track=all`

- **Purpose**: Tracks all queries including nested ones
- **Pros**: Complete visibility into query performance
- **Cons**: Higher memory usage and slight performance impact
- **Recommendation**: ✅ KEEP for development, consider 'top' for production if memory is tight

#### `pg_stat_statements.max=10000`

- **Purpose**: Maximum number of unique queries to track
- **Pros**: Stores more query patterns for analysis
- **Cons**: Higher memory usage (~10-20MB)
- **Recommendation**: ✅ KEEP for metrics workloads, reduce to 5000 if memory constrained

### Connection Management

#### `max_connections=200`

- **Purpose**: Maximum concurrent database connections
- **Pros**: Supports high concurrency for metrics collection
- **Cons**: Each connection uses ~10MB RAM
- **Recommendation**: ✅ KEEP for production, reduce to 100 for development

### Memory Configuration

#### `shared_buffers=256MB`

- **Purpose**: PostgreSQL's shared buffer cache
- **Pros**: Faster query execution, reduced disk I/O
- **Cons**: Higher memory usage, may cause OOM if too high
- **Recommendation**: ✅ KEEP - Good for 4GB+ RAM systems, reduce to 128MB for smaller systems

#### `effective_cache_size=1GB`

- **Purpose**: Hint to query planner about available cache
- **Pros**: Helps query planner make better decisions
- **Cons**: No actual memory allocation, just a hint
- **Recommendation**: ✅ KEEP - Set to 75% of available RAM

#### `maintenance_work_mem=64MB`

- **Purpose**: Memory for maintenance operations (VACUUM, CREATE INDEX)
- **Pros**: Faster maintenance operations
- **Cons**: Higher memory usage during maintenance
- **Recommendation**: ✅ KEEP for production, reduce to 32MB for development

#### `work_mem=4MB`

- **Purpose**: Memory per query operation (sorting, hashing)
- **Pros**: Faster sorting and hash operations
- **Cons**: Higher memory usage per query
- **Recommendation**: ✅ KEEP for complex queries, reduce to 2MB for simple workloads

### Write-Ahead Log (WAL) Configuration

#### `checkpoint_completion_target=0.9`

- **Purpose**: Spreads checkpoint writes over 90% of checkpoint interval
- **Pros**: Faster checkpoint completion, reduces I/O spikes
- **Cons**: Slightly higher I/O during checkpoints
- **Recommendation**: ✅ KEEP - Good balance for most workloads

#### `wal_buffers=16MB`

- **Purpose**: Memory for WAL data before writing to disk
- **Pros**: Reduces WAL I/O overhead
- **Cons**: Higher memory usage, data loss risk on crash
- **Recommendation**: ✅ KEEP - Good for metrics workloads

#### `min_wal_size=1GB` / `max_wal_size=4GB`

- **Purpose**: Controls WAL file size and checkpoint frequency
- **Pros**: Fewer checkpoints, better write performance
- **Cons**: Higher disk usage, longer recovery time
- **Recommendation**: ✅ KEEP for metrics, reduce to 512MB/2GB for low-write workloads

### Query Planner Optimizations

#### `default_statistics_target=100`

- **Purpose**: Sample size for table statistics
- **Pros**: Better query planning with more accurate statistics
- **Cons**: Slower ANALYZE operations, higher disk usage
- **Recommendation**: ✅ KEEP for metrics, reduce to 50 for simple workloads

#### `random_page_cost=1.1`

- **Purpose**: Cost estimate for random page access
- **Pros**: Better query planning for SSD storage
- **Cons**: May cause suboptimal plans for HDD storage
- **Recommendation**: ✅ KEEP for SSD, increase to 4.0 for HDD

#### `effective_io_concurrency=200`

- **Purpose**: Number of concurrent I/O operations
- **Pros**: Better I/O performance on modern storage
- **Cons**: May overwhelm slower storage systems
- **Recommendation**: ✅ KEEP for SSD/NVMe, reduce to 50 for HDD

### Parallel Query Processing

#### `max_parallel_workers_per_gather=4`

- **Purpose**: Workers per parallel query
- **Pros**: Faster parallel query execution
- **Cons**: Higher CPU and memory usage
- **Recommendation**: ✅ KEEP for metrics aggregation, reduce to 2 for simple queries

#### `max_parallel_workers=8`

- **Purpose**: Total parallel workers
- **Pros**: Better parallel query performance
- **Cons**: Higher resource usage
- **Recommendation**: ✅ KEEP for multi-core systems, reduce to 4 for smaller systems

#### `max_parallel_maintenance_workers=4`

- **Purpose**: Workers for maintenance operations
- **Pros**: Faster maintenance operations
- **Cons**: Higher resource usage during maintenance
- **Recommendation**: ✅ KEEP for large tables, reduce to 2 for small datasets

#### `max_worker_processes=8`

- **Purpose**: Total background worker processes
- **Pros**: Better parallel query performance
- **Cons**: Higher resource usage
- **Recommendation**: ✅ KEEP for multi-core systems, reduce to 4 for smaller systems

## Environment-Specific Recommendations

### Development Environment

```yaml
# Reduce these values for development
max_connections: 50-100
shared_buffers: 128MB
work_mem: 2MB
max_parallel_workers: 2-4
```

### Production Environment

```yaml
# Keep current settings for high-performance metrics
# Consider increasing for 8GB+ RAM systems:
shared_buffers: 512MB-1GB
effective_cache_size: 4-8GB
work_mem: 8-16MB
```

### Resource-Constrained Systems (< 4GB RAM)

```yaml
# Reduce these values
shared_buffers: 128MB
effective_cache_size: 512MB
work_mem: 2MB
max_parallel_workers: 2
pg_stat_statements.max: 5000
```

### High-Performance Metrics Systems (> 16GB RAM)

```yaml
# Increase these values
shared_buffers: 1-2GB
effective_cache_size: 4-8GB
work_mem: 8-16MB
max_parallel_workers: 8-16
# Consider adding connection pooling (pgBouncer)
```

## Monitoring and Tuning

### Key Metrics to Monitor

- `pg_stat_statements` for slow query identification
- Connection count vs `max_connections`
- Buffer hit ratio
- Checkpoint frequency
- WAL generation rate

### Common Tuning Commands

```sql
-- View current settings
SHOW ALL;

-- View query statistics
SELECT * FROM pg_stat_statements ORDER BY mean_time DESC LIMIT 10;

-- View buffer hit ratio
SELECT
  sum(heap_blks_hit) / (sum(heap_blks_hit) + sum(heap_blks_read)) as buffer_hit_ratio
FROM pg_statio_user_tables;
```

## Troubleshooting

### High Memory Usage

- Reduce `shared_buffers`
- Reduce `work_mem`
- Reduce parallel worker settings

### Slow Queries

- Check `pg_stat_statements`
- Increase `work_mem` for sorting operations
- Ensure proper indexing

### Connection Issues

- Monitor connection count
- Increase `max_connections` if needed
- Consider connection pooling

### I/O Bottlenecks

- Increase `effective_io_concurrency` for SSD
- Adjust `random_page_cost` for your storage type
- Monitor checkpoint frequency
