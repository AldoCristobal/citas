<?php
// app/Http/Router/Router.php
declare(strict_types=1);

namespace App\Http\Router;

final class Router
{
   /** @var array<int,array{method:string,pattern:string,regex:string,keys:array,handler:callable}> */
   private array $routes = [];

   private string $groupPrefix = '';

   public function group(string $prefix, callable $fn): void
   {
      $prev = $this->groupPrefix;
      $this->groupPrefix = rtrim($prev . '/' . trim($prefix, '/'), '/');
      if ($this->groupPrefix === '') $this->groupPrefix = '';
      $fn($this);
      $this->groupPrefix = $prev;
   }

   public function get(string $pattern, callable $handler): void
   {
      $this->add('GET', $pattern, $handler);
   }
   public function post(string $pattern, callable $handler): void
   {
      $this->add('POST', $pattern, $handler);
   }
   public function put(string $pattern, callable $handler): void
   {
      $this->add('PUT', $pattern, $handler);
   }
   public function patch(string $pattern, callable $handler): void
   {
      $this->add('PATCH', $pattern, $handler);
   }
   public function delete(string $pattern, callable $handler): void
   {
      $this->add('DELETE', $pattern, $handler);
   }

   private function add(string $method, string $pattern, callable $handler): void
   {
      $full = '/' . ltrim($this->groupPrefix . '/' . ltrim($pattern, '/'), '/');
      $full = rtrim($full, '/') ?: '/';

      [$regex, $keys] = $this->compile($full);

      $this->routes[] = [
         'method'  => $method,
         'pattern' => $full,
         'regex'   => $regex,
         'keys'    => $keys,
         'handler' => $handler,
      ];
   }

   /** @return array{0:string,1:array<int,string>} */
   private function compile(string $pattern): array
   {
      // /api/v1/public/citas/{folio}
      $keys = [];
      $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($m) use (&$keys) {
         $keys[] = $m[1];
         return '([^\/]+)';
      }, $pattern);

      // match exact path with optional trailing slash
      $regex = '#^' . rtrim($regex, '/') . '/?$#';

      return [$regex, $keys];
   }

   /** @return array{handler:callable,params:array<string,string>}|null */
   public function match(Request $req): ?array
   {
      foreach ($this->routes as $r) {
         if ($r['method'] !== $req->method) continue;
         if (!preg_match($r['regex'], $req->path, $m)) continue;

         array_shift($m); // full match
         $params = [];
         foreach ($r['keys'] as $i => $key) {
            $params[$key] = (string)($m[$i] ?? '');
         }
         return ['handler' => $r['handler'], 'params' => $params];
      }
      return null;
   }
}
