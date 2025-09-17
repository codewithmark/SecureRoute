# SecureRoute - PHP Router Class

A lightweight, flexible PHP router class for handling HTTP requests with support for dynamic routes, named parameters, and custom match types.

## Features

- ✅ Simple and intuitive routing syntax
- ✅ Support for all HTTP methods (GET, POST, PUT, DELETE, etc.)
- ✅ Dynamic route parameters with type validation
- ✅ Named routes for URL generation
- ✅ Custom match types
- ✅ Base path support for subdirectory installations
- ✅ Built-in 404 error page
- ✅ Regex route support
- ✅ Catch-all routes

## Installation

Simply include the `SecureRoute.php` file in your project:

```php
<?php
require_once 'SecureRoute.php';

$router = new SecureRoute();
```

## Basic Usage

### Simple Routes

```php
<?php
require_once 'route.php';

$router = new SecureRoute();

// Basic GET route
$router->map('GET', '/', function () {
    echo "Welcome to the homepage!";
});

// Basic POST route
$router->map('POST', '/contact', function () {
    echo "Contact form submitted!";
});

// Multiple HTTP methods
$router->map('GET|POST', '/api/data', function () {
    $method = $_SERVER['REQUEST_METHOD'];
    echo "Data endpoint accessed via $method";
});

// Dispatch the route
$router->dispatch();
```

## Route Parameters

### Parameter Types

The router supports several built-in parameter types:

| Type | Pattern | Description | Example |
|------|---------|-------------|---------|
| `i` | `[0-9]+` | Integer numbers only | `/users/[i:id]` matches `/users/123` |
| `a` | `[0-9A-Za-z]+` | Alphanumeric characters | `/posts/[a:slug]` matches `/posts/abc123` |
| `h` | `[0-9A-Fa-f]+` | Hexadecimal characters | `/colors/[h:hex]` matches `/colors/ff0000` |
| `*` | `[^/]+` | Any characters except `/` | `/files/[*:name]` matches `/files/document.pdf` |
| `**` | `.+` | Any characters including `/` | `/path/[**:rest]` matches `/path/to/anything` |
| `` | `[^/\.]++` | Default: any except `/` and `.` | `/items/[:id]` matches `/items/abc` |

### Simple Parameter Examples

```php
// Integer parameter
$router->map('GET', '/users/[i:id]', function ($id) {
    echo "User ID: " . htmlspecialchars($id);
});
// Matches: /users/123, /users/456
// Doesn't match: /users/abc, /users/123/profile

// Alphanumeric parameter
$router->map('GET', '/posts/[a:slug]', function ($slug) {
    echo "Post slug: " . htmlspecialchars($slug);
});
// Matches: /posts/hello123, /posts/myPost
// Doesn't match: /posts/hello-world (contains hyphen)

// Wildcard parameter (any characters except /)
$router->map('GET', '/files/[*:filename]', function ($filename) {
    echo "Filename: " . htmlspecialchars($filename);
});
// Matches: /files/document.pdf, /files/my-file_v2.txt
// Doesn't match: /files/folder/document.pdf

// Catch-all parameter (includes /)
$router->map('GET', '/assets/[**:path]', function ($path) {
    echo "Asset path: " . htmlspecialchars($path);
});
// Matches: /assets/css/style.css, /assets/js/app.js, /assets/images/logo.png
```

### Multiple Parameters

```php
// Multiple parameters
$router->map('GET', '/users/[i:userId]/posts/[i:postId]', function ($userId, $postId) {
    echo "User $userId, Post $postId";
});

// Mixed parameter types
$router->map('GET', '/blog/[i:year]/[i:month]/[*:slug]', function ($year, $month, $slug) {
    echo "Blog post: $year/$month/$slug";
});
// Matches: /blog/2024/03/my-awesome-post
```

### Optional Parameters

```php
// Optional parameters with ?
$router->map('GET', '/products/[i:categoryId]?', function ($categoryId = null) {
    if ($categoryId) {
        echo "Category: $categoryId";
    } else {
        echo "All products";
    }
});
// Matches: /products (categoryId = null)
// Matches: /products/5 (categoryId = 5)
```

## Named Routes

Named routes allow you to generate URLs programmatically:

```php
// Define named routes
$router->map('GET', '/', function () {
    echo "Homepage";
}, 'home');

$router->map('GET', '/users/[i:id]', function ($id) {
    echo "User profile: $id";
}, 'user.profile');

$router->map('GET', '/blog/[i:year]/[i:month]/[*:slug]', function ($year, $month, $slug) {
    echo "Blog post: $year/$month/$slug";
}, 'blog.post');

// Generate URLs
echo $router->generate('home'); 
// Output: /

echo $router->generate('user.profile', ['id' => 123]);
// Output: /users/123

echo $router->generate('blog.post', [
    'year' => 2024,
    'month' => 3,
    'slug' => 'my-awesome-post'
]);
// Output: /blog/2024/3/my-awesome-post
```

## Advanced Usage

### Base Path

If your application is installed in a subdirectory:

```php
$router = new SecureRoute([], '/myapp');

$router->map('GET', '/users', function () {
    echo "Users page";
});

// This will match: /myapp/users
```

### Custom Match Types

Add your own parameter types:

```php
$router->addMatchTypes([
    'uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
    'email' => '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}'
]);

$router->map('GET', '/users/[uuid:id]', function ($id) {
    echo "User UUID: $id";
});

$router->map('POST', '/newsletter/[email:address]', function ($address) {
    echo "Email: $address";
});
```

### Regex Routes

For complex patterns, use regex routes (prefix with `@`):

```php
// Regex route
$router->map('GET', '@/api/v([1-9])/users/(\d+)', function ($version, $userId) {
    echo "API v$version, User: $userId";
});
// Matches: /api/v1/users/123, /api/v2/users/456
```

### Catch-All Routes

```php
// Catch-all route (must be defined last)
$router->map('GET', '*', function () {
    echo "This catches any unmatched GET request";
});
```

### Route Groups/Bulk Loading

```php
$routes = [
    ['GET', '/', function () { echo 'Home'; }],
    ['GET', '/about', function () { echo 'About'; }],
    ['POST', '/contact', function () { echo 'Contact'; }],
    ['GET', '/users/[i:id]', function ($id) { echo "User: $id"; }]
];

$router = new SecureRoute($routes);
$router->dispatch();
```

### Working with Classes

```php
class UserController {
    public function show($id) {
        echo "Showing user: $id";
    }
    
    public function edit($id) {
        echo "Editing user: $id";
    }
}

$userController = new UserController();

$router->map('GET', '/users/[i:id]', [$userController, 'show']);
$router->map('PUT', '/users/[i:id]', [$userController, 'edit']);
```

### REST API Example

```php
class APIController {
    public function getUsers() {
        header('Content-Type: application/json');
        echo json_encode(['users' => []]);
    }
    
    public function getUser($id) {
        header('Content-Type: application/json');
        echo json_encode(['user' => ['id' => $id]]);
    }
    
    public function createUser() {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode(['created' => true, 'data' => $data]);
    }
}

$api = new APIController();

// REST API routes
$router->map('GET', '/api/users', [$api, 'getUsers']);
$router->map('GET', '/api/users/[i:id]', [$api, 'getUser']);
$router->map('POST', '/api/users', [$api, 'createUser']);
$router->map('PUT', '/api/users/[i:id]', [$api, 'updateUser']);
$router->map('DELETE', '/api/users/[i:id]', [$api, 'deleteUser']);

$router->dispatch();
```

### Error Handling

```php
$router->map('GET', '/users/[i:id]', function ($id) {
    if ($id < 1) {
        http_response_code(400);
        echo "Invalid user ID";
        return;
    }
    
    // Your logic here
    echo "User: $id";
});

// The router automatically handles 404 errors with a built-in error page
$router->dispatch();
```

### Middleware-like Functionality

```php
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo "Unauthorized";
        exit;
    }
}

$router->map('GET', '/dashboard', function () {
    requireAuth();
    echo "Dashboard content";
});

$router->map('GET', '/profile', function () {
    requireAuth();
    echo "User profile";
});
```

## Complete Example

Here's a complete example of a simple blog application:

```php
<?php
session_start();
require_once 'route.php';

class BlogController {
    public function home() {
        echo "<h1>Blog Homepage</h1>";
    }
    
    public function showPost($year, $month, $slug) {
        echo "<h1>Blog Post</h1>";
        echo "<p>Year: $year, Month: $month, Slug: $slug</p>";
    }
    
    public function showCategory($categoryId = null) {
        if ($categoryId) {
            echo "<h1>Category: $categoryId</h1>";
        } else {
            echo "<h1>All Categories</h1>";
        }
    }
    
    public function api($path) {
        header('Content-Type: application/json');
        echo json_encode(['path' => $path]);
    }
}

$blog = new BlogController();
$router = new SecureRoute();

// Routes
$router->map('GET', '/', [$blog, 'home'], 'home');
$router->map('GET', '/blog/[i:year]/[i:month]/[*:slug]', [$blog, 'showPost'], 'blog.post');
$router->map('GET', '/categories/[i:id]?', [$blog, 'showCategory'], 'categories');
$router->map('GET', '/api/[**:path]', [$blog, 'api'], 'api');

// Add some navigation
echo '<nav>';
echo '<a href="' . $router->generate('home') . '">Home</a> | ';
echo '<a href="' . $router->generate('blog.post', ['year' => 2024, 'month' => 3, 'slug' => 'hello-world']) . '">Sample Post</a> | ';
echo '<a href="' . $router->generate('categories') . '">All Categories</a> | ';
echo '<a href="' . $router->generate('categories', ['id' => 5]) . '">Category 5</a>';
echo '</nav><hr>';

$router->dispatch();
```

## License

This project is open source. Feel free to use it in your projects.

## Contributing

Feel free to submit issues and pull requests to improve this router class.
