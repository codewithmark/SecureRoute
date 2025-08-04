<?php

class SecureRoute
{
    protected array $routes = [];
    protected array $namedRoutes = [];
    protected string $basePath = '';
    protected array $matchTypes = [
        'i'  => '[0-9]+',
        'a'  => '[0-9A-Za-z]+',
        'h'  => '[0-9A-Fa-f]+',
        '*'  => '[^/]+',
        '**' => '.+',
        ''   => '[^/\.]++'
    ];

    public function __construct(array $routes = [], string $basePath = '', array $matchTypes = [])
    {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $scriptDir = dirname($scriptName);
        $route_base_path = rtrim($scriptDir, '/') === '' ? '/' : rtrim($scriptDir, '/') . '/';
 
        $this->setBasePath($route_base_path);
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
            $this->render404Page();
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
                    ')' . $optionalPattern;

                $route = str_replace($block, $pattern, $route);
            }
        }

        return "`^$route$`u";
    }

    protected function render404Page(): void
    { 

        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>404 Page</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                html, body {
                    height: 100%;
                    margin-top: 100px;
                    background-color: #556;
                    font-family: Arial, sans-serif;
                    color: #333;
                }

                .main-container {
                    max-width: 1000px;
                    margin: 35px auto;
                    padding: 20px;
                    background: #FFF;
                    border: 1px dotted #CCC;
                    border-radius: 50px;
                    text-align: center;
                }

                .icon {
                    font-size: 200px;
                    color: #888;
                }

                .title {
                    font-size: 48px;
                    margin-top: 20px;
                }

                .message {
                    font-size: 28px;
                    margin: 30px 0;
                }

                .btn {
                    display: inline-block;
                    background-color: #28a745;
                    color: white;
                    padding: 12px 24px;
                    border: none;
                    border-radius: 8px;
                    text-decoration: none;
                    font-size: 18px;
                    transition: background-color 0.3s ease;
                }

                .btn:hover {
                    background-color: #218838;
                }

                @media (min-width: 768px) {
                    .main-container {
                        width: 100%;
                    }
                }

                @media (min-width: 992px) {
                    .main-container {
                        width: 600px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="main-container">
                <div class="icon">😞</div>
                <div class="title">Oh...No</div>
                <p class="message">Can't Find Your Page....</p>
                <a class="btn" href="
                <?php
                    if (
                        isset($_SERVER['HTTPS']) &&
                        ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
                        isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
                        $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
                    ) {
                        $ssl = 'https';
                    } else {
                        $ssl = 'http';
                    }

                    $app_url = ($ssl)
                        . "://" . $_SERVER['HTTP_HOST']
                        //. $_SERVER["SERVER_NAME"]
                        . (dirname($_SERVER["SCRIPT_NAME"]) == DIRECTORY_SEPARATOR ? "" : "/")
                        . trim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"])), "/");
                    
                    echo  $app_url; 
                ?>">← Go Home</a>
            </div>
        </body>
        </html>
        <?php
    }
}
?>
