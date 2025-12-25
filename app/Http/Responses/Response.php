<?php
// app/Http/Responses/Response.php
declare(strict_types=1);

namespace App\Http\Responses;

final class Response
{
   public static function json(bool $ok, $data = null, ?array $error = null, int $status = 200): void
   {
      http_response_code($status);
      header('Content-Type: application/json; charset=utf-8');

      $payload = ['ok' => $ok];
      if ($ok) {
         $payload['data'] = $data;
      } else {
         $payload['error'] = $error ?? ['code' => 'SERVER', 'message' => 'Error'];
      }

      echo json_encode($payload, JSON_UNESCAPED_UNICODE);
   }

   public static function badRequest(string $message, string $code = 'VALIDATION'): void
   {
      self::json(false, null, ['code' => $code, 'message' => $message], 400);
   }
}
