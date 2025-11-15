<?php
/**
 * Performance Monitoring Utilities
 * MIS Barangay - Performance Tracking
 */

class PerformanceMonitor
{
    private static $startTime;
    private static $queries = [];
    private static $enabled = true;

    /**
     * Start performance monitoring
     */
    public static function start()
    {
        if (!self::$enabled) {
            return;
        }
        
        self::$startTime = microtime(true);
        self::$queries = [];
    }

    /**
     * Log a database query for performance tracking
     */
    public static function logQuery($query, $executionTime)
    {
        if (!self::$enabled) {
            return;
        }
        
        self::$queries[] = [
            'query' => $query,
            'time' => $executionTime,
            'timestamp' => microtime(true)
        ];
    }

    /**
     * Get total execution time
     */
    public static function getExecutionTime()
    {
        if (!self::$startTime) {
            return 0;
        }
        
        return microtime(true) - self::$startTime;
    }

    /**
     * Get all logged queries
     */
    public static function getQueries()
    {
        return self::$queries;
    }

    /**
     * Get slow queries (queries taking more than specified time)
     */
    public static function getSlowQueries($threshold = 0.1)
    {
        return array_filter(self::$queries, function($query) use ($threshold) {
            return $query['time'] > $threshold;
        });
    }

    /**
     * Get performance summary
     */
    public static function getSummary()
    {
        $totalTime = self::getExecutionTime();
        $queryCount = count(self::$queries);
        $totalQueryTime = array_sum(array_column(self::$queries, 'time'));
        $slowQueries = count(self::getSlowQueries());
        
        return [
            'total_execution_time' => round($totalTime, 4),
            'query_count' => $queryCount,
            'total_query_time' => round($totalQueryTime, 4),
            'average_query_time' => $queryCount > 0 ? round($totalQueryTime / $queryCount, 4) : 0,
            'slow_queries' => $slowQueries,
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB'
        ];
    }

    /**
     * Log performance data to file
     */
    public static function logToFile($filename = null)
    {
        if (!self::$enabled) {
            return;
        }
        
        if (!$filename) {
            $logDir = __DIR__ . '/../logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            $filename = $logDir . '/performance_' . date('Y-m-d') . '.log';
        }
        
        $summary = self::getSummary();
        $logEntry = date('Y-m-d H:i:s') . " - " . json_encode($summary) . "\n";
        
        file_put_contents($filename, $logEntry, FILE_APPEND);
    }

    /**
     * Enable/disable performance monitoring
     */
    public static function setEnabled($enabled)
    {
        self::$enabled = $enabled;
    }

    /**
     * Check if monitoring is enabled
     */
    public static function isEnabled()
    {
        return self::$enabled;
    }
}

// Auto-start monitoring if enabled (can be disabled in production)
if (defined('ENABLE_PERFORMANCE_MONITORING') && ENABLE_PERFORMANCE_MONITORING) {
    PerformanceMonitor::start();
}

