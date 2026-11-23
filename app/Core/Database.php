<?php
declare(strict_types=1);
final class Database  {
    private static ?PDO $pdo = null;
    public static function get(): PDO  {
        if (!self::$pdo)  {
            $c = require ROOT . '/config/database.php';
            if (is_file(ROOT . '/config/database.local.php')) $c = array_replace($c, require ROOT . '/config/database.local.php');
            self::$pdo = new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4", $c['user'], $c['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]);
            self::$pdo->exec("SET time_zone = '+07:00'");
        }
        return self::$pdo;
    }
    public static function query(string $sql, array $values = []): PDOStatement  {
        $s=self::get()->prepare($sql);
        $s->execute($values);
        return $s;
    }
    public static function all(string $sql,array $p=[]):array  {
        return self::query($sql,$p)->fetchAll();
    }
    public static function one(string $sql,array $p=[]):?array  {
        return self::query($sql,$p)->fetch() ?: null;
    }
    public static function value(string $sql,array $p=[]):mixed  {
        return self::query($sql,$p)->fetchColumn();
    }
    public static function transaction(callable $fn):mixed  {
        $db=self::get();
        $db->beginTransaction();
        try  {
            $r=$fn();
            $db->commit();
            return $r;
        }
        catch(Throwable $e)  {
            if($db->inTransaction())$db->rollBack();
            throw $e;
        }
    }
}
