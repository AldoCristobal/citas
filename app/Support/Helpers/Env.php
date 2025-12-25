<?php
// app/Support/Helpers/Env.php
declare(strict_types=1);

namespace App\Support\Helpers;

final class Env
{
   private static bool $loaded = false;

   public static function load(string $path): void
   {
      if (self::$loaded) return;
      if (!is_file($path)) {
         self::$loaded = true;
         return;
      }

      $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
      foreach ($lines as $line) {
         $line = trim($line);
         if ($line === '' || str_starts_with($line, '#')) continue;

         $pos = strpos($line, '=');
         if ($pos === false) continue;

         $key = trim(substr($line, 0, $pos));
         $val = trim(substr($line, $pos + 1));

         // quitar comillas
         if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
            (str_starts_with($val, "'") && str_ends_with($val, "'"))
         ) {
            $val = substr($val, 1, -1);
         }

         if ($key !== '' && getenv($key) === false) {
            putenv("$key=$val");
            $_ENV[$key] = $val;
         }
      }

      self::$loaded = true;
   }

   public static function get(string $key, ?string $default = null): ?string
   {
      $v = getenv($key);
      if ($v === false) return $default;
      return $v;
   }
}
