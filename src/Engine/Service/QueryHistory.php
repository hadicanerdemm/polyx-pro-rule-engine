<?php
declare(strict_types=1);

namespace Polyx\Engine\Service;

/**
 * QueryHistory - Sorgu geçmişi servisi
 * 
 * SQLite veritabanı ile geçmiş sorguları saklar ve yönetir.
 * 
 * @package Polyx\Engine\Service
 */
class QueryHistory
{
    private \PDO $db;
    private string $dbPath;
    private int $maxRecords;

    /**
     * Constructor
     * 
     * @param string|null $dbPath Veritabanı yolu
     * @param int $maxRecords Maksimum kayıt sayısı
     */
    public function __construct(?string $dbPath = null, int $maxRecords = 100)
    {
        $this->maxRecords = $maxRecords;
        $this->dbPath = $dbPath ?? dirname(__DIR__, 3) . '/data/history.db';
        
        // Dizini oluştur
        $dir = dirname($this->dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->initDatabase();
    }

    /**
     * Veritabanını başlat
     */
    private function initDatabase(): void
    {
        $this->db = new \PDO('sqlite:' . $this->dbPath);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS query_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                rule TEXT NOT NULL,
                context_json TEXT,
                result BOOLEAN,
                execution_time REAL,
                memory_used INTEGER,
                token_count INTEGER,
                error_message TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                session_id TEXT,
                ip_address TEXT
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS favorites (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                rule TEXT NOT NULL,
                context_json TEXT,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->db->exec("
            CREATE INDEX IF NOT EXISTS idx_history_created ON query_history(created_at DESC)
        ");
    }

    /**
     * Sorguyu kaydet
     * 
     * @param array $data Sorgu verileri
     * @return int Eklenen kayıt ID'si
     */
    public function save(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO query_history 
            (rule, context_json, result, execution_time, memory_used, token_count, error_message, session_id, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['rule'] ?? '',
            json_encode($data['context'] ?? [], JSON_UNESCAPED_UNICODE),
            $data['result'] ?? null,
            $data['execution_time'] ?? null,
            $data['memory_used'] ?? null,
            $data['token_count'] ?? null,
            $data['error_message'] ?? null,
            $data['session_id'] ?? session_id(),
            $data['ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? null)
        ]);

        $insertedId = (int)$this->db->lastInsertId();

        // Eski kayıtları temizle
        $this->cleanup();

        return $insertedId;
    }

    /**
     * Son sorguları getir
     * 
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function getRecent(int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM query_history 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        
        return array_map(function ($row) {
            $row['context'] = json_decode($row['context_json'], true);
            unset($row['context_json']);
            return $row;
        }, $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * Başarılı sorguları getir
     * 
     * @param int $limit Limit
     * @return array
     */
    public function getSuccessful(int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM query_history 
            WHERE result = 1 AND error_message IS NULL
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Hatalı sorguları getir
     * 
     * @param int $limit Limit
     * @return array
     */
    public function getFailed(int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM query_history 
            WHERE error_message IS NOT NULL
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Favorilere ekle
     * 
     * @param string $name İsim
     * @param string $rule Kural
     * @param array $context Kontekst
     * @param string $description Açıklama
     * @return int
     */
    public function addFavorite(string $name, string $rule, array $context = [], string $description = ''): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO favorites (name, rule, context_json, description)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $name,
            $rule,
            json_encode($context, JSON_UNESCAPED_UNICODE),
            $description
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Favorileri getir
     * 
     * @return array
     */
    public function getFavorites(): array
    {
        $stmt = $this->db->query("SELECT * FROM favorites ORDER BY created_at DESC");
        
        return array_map(function ($row) {
            $row['context'] = json_decode($row['context_json'], true);
            unset($row['context_json']);
            return $row;
        }, $stmt->fetchAll(\PDO::FETCH_ASSOC));
    }

    /**
     * Favori sil
     * 
     * @param int $id Favori ID
     * @return bool
     */
    public function deleteFavorite(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM favorites WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * İstatistikleri getir
     * 
     * @return array
     */
    public function getStats(): array
    {
        $stats = [];

        // Toplam sorgu
        $stats['total_queries'] = (int)$this->db->query("SELECT COUNT(*) FROM query_history")->fetchColumn();

        // Başarılı/Başarısız
        $stats['successful'] = (int)$this->db->query("SELECT COUNT(*) FROM query_history WHERE error_message IS NULL")->fetchColumn();
        $stats['failed'] = $stats['total_queries'] - $stats['successful'];

        // Ortalama süre
        $stats['avg_execution_time'] = (float)$this->db->query("SELECT AVG(execution_time) FROM query_history")->fetchColumn();

        // Ortalama bellek
        $stats['avg_memory_used'] = (int)$this->db->query("SELECT AVG(memory_used) FROM query_history")->fetchColumn();

        // Son 24 saat
        $stats['last_24h'] = (int)$this->db->query("
            SELECT COUNT(*) FROM query_history 
            WHERE created_at > datetime('now', '-24 hours')
        ")->fetchColumn();

        // Favori sayısı
        $stats['favorites_count'] = (int)$this->db->query("SELECT COUNT(*) FROM favorites")->fetchColumn();

        return $stats;
    }

    /**
     * Sorgu ara
     * 
     * @param string $keyword Aranacak kelime
     * @param int $limit Limit
     * @return array
     */
    public function search(string $keyword, int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM query_history 
            WHERE rule LIKE ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute(['%' . $keyword . '%', $limit]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Geçmişi temizle
     * 
     * @return int Silinen kayıt sayısı
     */
    public function clear(): int
    {
        $count = (int)$this->db->query("SELECT COUNT(*) FROM query_history")->fetchColumn();
        $this->db->exec("DELETE FROM query_history");
        return $count;
    }

    /**
     * Eski kayıtları temizle
     */
    private function cleanup(): void
    {
        $this->db->prepare("
            DELETE FROM query_history 
            WHERE id NOT IN (
                SELECT id FROM query_history 
                ORDER BY created_at DESC 
                LIMIT ?
            )
        ")->execute([$this->maxRecords]);
    }

    /**
     * Kayıt getir
     * 
     * @param int $id Kayıt ID
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM query_history WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($row) {
            $row['context'] = json_decode($row['context_json'], true);
            unset($row['context_json']);
        }
        
        return $row ?: null;
    }
}
