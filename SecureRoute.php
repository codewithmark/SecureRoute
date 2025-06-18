<?php

class SecureRoute
{
    protected array $routes = [];
    protected array $namedRoutes = [];
    protected string $basePath = '';
    protected array $matchTypes = [
        'i'  => '[0-9]++',
        'a'  => '[0-9A-Za-z]++',
        'h'  => '[0-9A-Fa-f]++',
        '*'  => '.+?',
        '**' => '.++',
        ''   => '[^/\.]++'
    ];

    public function __construct(array $routes = [], string $basePath = '', array $matchTypes = [])
    {
        $this->setBasePath($basePath);
        $this->addMatchTypes($matchTypes);
        $this->addRoutes($routes);
    }

    public function addRoutes(array $routes): void
    {
        foreach ($routes as $route) {
            $this->map(...$route);
        }
    }

    public function map(string $method, string $route, callable $target, ?string $name = null): void
    {
        $this->routes[] = [$method, $route, $target, $name];

        if ($name) {
            if (isset($this->namedRoutes[$name])) {
                throw new LogicException("Cannot redeclare route '$name'");
            }
            $this->namedRoutes[$name] = $route;
        }
    }

    public function setBasePath(string $basePath): void
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function addMatchTypes(array $matchTypes): void
    {
        $this->matchTypes = array_merge($this->matchTypes, $matchTypes);
    }

    public function generate(string $routeName, array $params = []): string
    {
        if (!isset($this->namedRoutes[$routeName])) {
            throw new RuntimeException("Route '$routeName' does not exist.");
        }

        $route = $this->namedRoutes[$routeName];
        $url = $this->basePath . $route;

        if (preg_match_all('`(/|\.|)\[([^:\]]*)(?::([^:\]]+))?\](\?|)?`', $route, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                [$block, $pre, , $param, $optional] = array_pad($match, 5, '');

                $value = $params[$param] ?? null;

                if ($value !== null) {
                    $url = str_replace($block, $value, $url);
                } elseif ($optional) {
                    $url = str_replace($pre . $block, '', $url);
                } else {
                    $url = str_replace($block, '', $url);
                }
            }
        }

        return $url;
    }

    public function match(string $uri = null, string $method = null): array|false
    {
        $method = strtoupper($method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = rawurldecode($uri ?? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

        if ($this->basePath && str_starts_with($uri, $this->basePath)) {
            $uri = substr($uri, strlen($this->basePath));
        }

        $uri = '/' . ltrim($uri, '/');
        $lastChar = substr($uri, -1);

        foreach ($this->routes as [$methods, $route, $target, $name]) {
            if (!preg_match("~^($methods)$~i", $method)) {
                continue;
            }

            $params = [];
            $match = false;

            if ($route === '*') {
                $match = true;
            } elseif ($route[0] === '@') {
                $pattern = '`' . substr($route, 1) . '`u';
                $match = preg_match($pattern, $uri, $params) === 1;
            } elseif (!str_contains($route, '[')) {
                $match = strcmp($uri, $route) === 0;
            } else {
                $pos = strpos($route, '[');
                if (strncmp($uri, $route, $pos) !== 0 &&
                    ($lastChar === '/' || $route[$pos - 1] !== '/')) {
                    continue;
                }

                $regex = $this->compileRoute($route);
                $match = preg_match($regex, $uri, $params) === 1;
            }

            if ($match) {
                $params = array_filter($params, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);
                return ['target' => $target, 'params' => $params, 'name' => $name];
            }
        }

        return false;
    }

    public function dispatch(): void
    {
        $match = $this->match();

        if (!$match) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $target = $match['target'];
        $params = array_values($match['params']);

        call_user_func_array($target, $params);
    }

    protected function compileRoute(string $route): string
    {
        if (preg_match_all('`(/|\.|)\[([^:\]]*)(?::([^:\]]+))?\](\?|)?`', $route, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                [$block, $pre, $type, $param, $optional] = array_pad($match, 5, '');
                $typeRegex = $this->matchTypes[$type] ?? $type;

                if ($pre === '.') {
                    $pre = '\.';
                }

                $optionalPattern = $optional ? '?' : '';
                $pattern = '(?:' . ($pre !== '' ? $pre : '') .
                    '(?P<' . $param . '>' . $typeRegex . ')' .
                    ')' . $optionalPattern . $optionalPattern;

                $route = str_replace($block, $pattern, $route);
            }
        }

        return "`^$route$`u";
    }
}
?>
