<?php
// app/Http/Router/Request.php
declare(strict_types=1);

namespace App\Http\Router;

final class Request
{
   public string $method;
   public string $path;
   public array $query;
   public array $headers;
   public string $rawBody;

   /** @var array<string,string> */
   public array $params = [];

   private function __construct() {}

   public static function fromGlobals(): self
   {
      $r = new self();
      $r->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

      $uri = $_SERVER['REQUEST_URI'] ?? '/';
      $r->path = parse_url($uri, PHP_URL_PATH) ?: '/';
      $r->query = $_GET ?? [];

      $r->headers = self::getAllHeadersSafe();
      $r->rawBody = file_get_contents('php://input') ?: '';

      return $r;
   }

   public function input(): array
   {
      // 1) JSON
      $ct = strtolower((string)($this->headers['content-type'] ?? ''));
      if (str_contains($ct, 'application/json')) {
         $data = json_decode($this->rawBody, true);
         return is_array($data) ? $data : [];
      }

      // 2) x-www-form-urlencoded o multipart/form-data (Postman form-data)
      if (!empty($_POST) && is_array($_POST)) {
         return $_POST;
      }

      // 3) fallback: intenta JSON aunque no venga header (por si Postman se pone raro)
      $data = json_decode($this->rawBody, true);
      return is_array($data) ? $data : [];
   }

   public function json(): array
   {
      return $this->input();
   }


   private static function getAllHeadersSafe(): array
   {
      // getallheaders() no siempre existe
      $headers = [];
      foreach ($_SERVER as $k => $v) {
         if (str_starts_with($k, 'HTTP_')) {
            $name = str_replace('_', '-', strtolower(substr($k, 5)));
            $headers[$name] = $v;
         }
      }
      // Content-Type/Length
      if (isset($_SERVER['CONTENT_TYPE'])) $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
      if (isset($_SERVER['CONTENT_LENGTH'])) $headers['content-length'] = $_SERVER['CONTENT_LENGTH'];
      return $headers;
   }
}
