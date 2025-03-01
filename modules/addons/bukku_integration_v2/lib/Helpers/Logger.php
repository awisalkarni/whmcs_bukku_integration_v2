<?php

namespace GBNetwork\BukkuIntegration\Helpers;

use WHMCS\Database\Capsule;

class Logger
{
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_DEBUG = 'debug';
    
    /**
     * Log a message
     *
     * @param string $message
     * @param string $level
     * @param array $context
     * @return bool
     */
    public static function log(string $message, string $level = self::LEVEL_INFO, array $context = []): bool
    {
        try {
            // Insert into our custom logs table
            Capsule::table('mod_bukku_integration_logs')->insert([
                'level' => $level,
                'message' => $message,
                'context' => !empty($context) ? json_encode($context) : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            // Also log to WHMCS activity log for errors
            if ($level === self::LEVEL_ERROR) {
                logActivity("Bukku Integration Error: {$message}");
            }
            
            return true;
        } catch (\Exception $e) {
            // Fallback to WHMCS activity log if our table insert fails
            logActivity("Bukku Integration {$level}: {$message}");
            return false;
        }
    }
    
    /**
     * Log an info message
     *
     * @param string $message
     * @param array $context
     * @return bool
     */
    public static function info(string $message, array $context = []): bool
    {
        return self::log($message, self::LEVEL_INFO, $context);
    }
    
    /**
     * Log a warning message
     *
     * @param string $message
     * @param array $context
     * @return bool
     */
    public static function warning(string $message, array $context = []): bool
    {
        return self::log($message, self::LEVEL_WARNING, $context);
    }
    
    /**
     * Log an error message
     *
     * @param string $message
     * @param array $context
     * @return bool
     */
    public static function error(string $message, array $context = []): bool
    {
        return self::log($message, self::LEVEL_ERROR, $context);
    }
    
    /**
     * Log a debug message
     *
     * @param string $message
     * @param array $context
     * @return bool
     */
    public static function debug(string $message, array $context = []): bool
    {
        return self::log($message, self::LEVEL_DEBUG, $context);
    }
    
    /**
     * Get all logs
     *
     * @param int $limit
     * @param int $offset
     * @param string|null $level
     * @return array
     */
    public static function getLogs(int $limit = 100, int $offset = 0, ?string $level = null): array
    {
        try {
            $query = Capsule::table('mod_bukku_integration_logs')
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit);
            
            if ($level) {
                $query->where('level', $level);
            }
            
            return $query->get()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Clear logs
     *
     * @param string|null $level
     * @param int|null $daysOld
     * @return bool
     */
    public static function clearLogs(?string $level = null, ?int $daysOld = null): bool
    {
        try {
            $query = Capsule::table('mod_bukku_integration_logs');
            
            if ($level) {
                $query->where('level', $level);
            }
            
            if ($daysOld) {
                $date = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
                $query->where('created_at', '<', $date);
            }
            
            $query->delete();
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}