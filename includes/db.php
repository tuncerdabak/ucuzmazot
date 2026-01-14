<?php
/**
 * Veritabanı Bağlantı Sınıfı
 * PDO kullanarak güvenli veritabanı işlemleri
 */

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        } catch (PDOException $e) {
            // Üretimde detaylı hata gösterme
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                die("Veritabanı bağlantı hatası: " . $e->getMessage());
            }
            die("Veritabanı bağlantısı kurulamadı. Lütfen daha sonra tekrar deneyin.");
        }
    }

    /**
     * Singleton instance al
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * PDO bağlantısını al
     */
    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * Hazırlanmış sorgu çalıştır
     */
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Tek satır getir
     */
    public function fetchOne($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Tüm satırları getir
     */
    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Satır ekle ve ID döndür
     */
    public function insert($table, $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);

        return $this->pdo->lastInsertId();
    }

    /**
     * Satır güncelle
     */
    public function update($table, $data, $where, $whereParams = [])
    {
        $set = [];
        $values = [];
        foreach ($data as $column => $value) {
            $set[] = "{$column} = ?";
            $values[] = $value;
        }
        $setStr = implode(', ', $set);

        $sql = "UPDATE {$table} SET {$setStr} WHERE {$where}";
        $params = array_merge($values, $whereParams);

        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Satır sil
     */
    public function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Sayı getir (COUNT, SUM vb.)
     */
    public function fetchColumn($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }

    /**
     * Transaction başlat
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Transaction onayla
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * Transaction geri al
     */
    public function rollback()
    {
        return $this->pdo->rollBack();
    }

    /**
     * Transaction aktif mi?
     */
    public function inTransaction()
    {
        return $this->pdo->inTransaction();
    }
}

/**
 * Veritabanı instance'ına kolay erişim
 */
function db()
{
    return Database::getInstance();
}

/**
 * PDO bağlantısına kolay erişim
 */
function pdo()
{
    return Database::getInstance()->getConnection();
}
