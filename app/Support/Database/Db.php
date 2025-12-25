<?php
// app/Support/Database/Db.php
declare(strict_types=1);

namespace App\Support\Database;

use PDO;
use PDOException;
use App\Support\Helpers\Env;

final class Db
{
   private static ?PDO $pdo = null;

   public static function pdo(): PDO
   {
      if (self::$pdo) return self::$pdo;

      $host = Env::get('DB_HOST', '127.0.0.1');
      $port = Env::get('DB_PORT', '3306');
      $name = Env::get('DB_NAME', 'citas');
      $user = Env::get('DB_USER', 'root');
      $pass = Env::get('DB_PASS', '');

      $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

      try {
         self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
         ]);
      } catch (PDOException $e) {
         // No exponer detalles sensibles
         http_response_code(500);
         header('Content-Type: application/json; charset=utf-8');
         echo json_encode([
            'ok' => false,
            'error' => ['code' => 'DB', 'message' => 'No se pudo conectar a la base de datos']
         ], JSON_UNESCAPED_UNICODE);
         exit;
      }

      // Timezone
      $tz = Env::get('APP_TZ', 'America/Mexico_City');
      @self::$pdo->exec("SET time_zone = '" . addslashes(self::phpTzToMySqlOffset($tz)) . "'");

      return self::$pdo;
   }

   private static function phpTzToMySqlOffset(string $tz): string
   {
      // MySQL soporta nombres si tiene tablas de zona cargadas; como no siempre,
      // ponemos offset actual para MX. (Luego lo hacemos mejor si quieres.)
      // America/Mexico_City suele ser -06:00 (puede variar por DST).
      try {
         $dt = new \DateTimeImmutable('now', new \DateTimeZone($tz));
         return $dt->format('P'); // -06:00
      } catch (\Throwable) {
         return '-06:00';
      }
   }
}
