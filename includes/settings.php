<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Salon Settings Key-Value Configuration Engine
 * 
 * Strict Brand Theme: Ivory #F5EFE6, Champagne #C5A46D, Charcoal #252321, White #FFFFFF
 */

class Settings
{
    private static ?array $cache = null;

    /**
     * Load all settings from the database into in-memory cache
     */
    public static function loadAll(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        self::$cache = [];
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->query("SELECT setting_key, setting_value, setting_group, description FROM salon_settings");
            $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
                self::$cache[$row['setting_key']] = [
                    'value'       => $row['setting_value'],
                    'group'       => $row['setting_group'],
                    'description' => $row['description']
                ];
            }
        } catch (Exception $e) {
            AppLogger::warning("Could not load salon_settings: " . $e->getMessage());
        }

        return self::$cache;
    }

    /**
     * Retrieve a specific setting value by key
     */
    public static function get(string $key, $default = null): ?string
    {
        self::loadAll();
        return self::$cache[$key]['value'] ?? $default;
    }

    /**
     * Retrieve all settings as a key => value dictionary
     */
    public static function all(): array
    {
        self::loadAll();
        $dict = [];
        foreach (self::$cache as $key => $data) {
            $dict[$key] = $data['value'];
        }
        return $dict;
    }

    /**
     * Retrieve all settings belonging to a group
     */
    public static function getGroup(string $group): array
    {
        self::loadAll();
        $res = [];
        foreach (self::$cache as $key => $data) {
            if ($data['group'] === $group) {
                $res[$key] = $data['value'];
            }
        }
        return $res;
    }

    /**
     * Set or update a single setting
     */
    public static function set(string $key, ?string $value, string $group = 'general', ?string $description = null): bool
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO salon_settings (setting_key, setting_value, setting_group, description, updated_at)
                VALUES (:key, :val, :grp, :desc, NOW())
                ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    setting_group = COALESCE(VALUES(setting_group), setting_group),
                    description   = COALESCE(VALUES(description), description),
                    updated_at    = NOW()
            ");
            $success = $stmt->execute([
                'key'  => $key,
                'val'  => $value,
                'grp'  => $group,
                'desc' => $description
            ]);

            if ($success) {
                if (self::$cache !== null) {
                    self::$cache[$key] = [
                        'value'       => $value,
                        'group'       => $group,
                        'description' => $description
                    ];
                }
                return true;
            }
        } catch (Exception $e) {
            AppLogger::error("Failed to update setting {$key}: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Set multiple settings in a batch
     */
    public static function setMultiple(array $pairs): bool
    {
        try {
            $pdo = Database::getConnection();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                INSERT INTO salon_settings (setting_key, setting_value, updated_at)
                VALUES (:key, :val, NOW())
                ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    updated_at    = NOW()
            ");

            foreach ($pairs as $key => $val) {
                $stmt->execute([
                    'key' => (string)$key,
                    'val' => (string)$val
                ]);
            }

            $pdo->commit();
            self::$cache = null; // Invalidate cache
            return true;
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            AppLogger::error("Batch settings update failed: " . $e->getMessage());
            return false;
        }
    }
}
