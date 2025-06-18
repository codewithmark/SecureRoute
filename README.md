# SecureRoute 🛡️

**SecureRoute** is a simple, standalone PHP router with clean syntax and zero dependencies.  
Inspired by AltoRouter — but modernized, secure, and easier to use.  
No Composer required. Just one file: `SecureRoute.php`.

---

## 🚀 Features

- Route with dynamic URL patterns like `/users/[i:id]/`
- Use closures or controller functions as targets
- Named routes for easy URL generation
- Supports multiple HTTP methods (`GET|POST`)
- Works without Composer or frameworks
- Optional base path for apps in subdirectories

---

## 📦 Installation

1. **Download** `SecureRoute.php`
2. **Include it** in your project:

```php
require 'SecureRoute.php';
```

---

## 📄 Basic Example

```php
$router = new SecureRoute();

// Home page
$router->map('GET', '/', function () {
    echo "Welcome to the homepage!";
});

// Dynamic user page
$router->map('GET|POST', '/users/[i:id]/', function ($id) {
    echo "User ID: " . htmlspecialchars($id);
}, 'user-details');

// Generate a URL to a named route
echo $router->generate('user-details', ['id' => 42]); // Output: /users/42

// Handle the current request
$router->dispatch();
```

---

## 🔧 Supported Patterns

You can use special patterns inside routes:

| Pattern     | Description              | Example URL        |
|-------------|--------------------------|--------------------|
| `[i:id]`    | Integer parameter         | `/users/123/`      |
| `[a:slug]`  | Alphanumeric              | `/posts/hello123/` |
| `[h:key]`   | Hexadecimal               | `/color/ff00cc/`   |
| `[*:path]`  | Wildcard (one segment)    | `/files/docs/`     |
| `[**:path]` | Wildcard (multi-segment)  | `/files/a/b/c/`    |

You can also define **optional** parameters using `?`:

```php
$router->map('GET', '/blog/[a:slug]?/', function ($slug = null) {
    echo $slug ? "Post: $slug" : "Blog index";
});
```

---

## 🛠 Advanced Example

```php
$router = new SecureRoute([], '/myapp'); // Base path if app lives in /myapp

$router->map('GET', '/profile/[a:username]/', function ($username) {
    echo "Hello, $username!";
}, 'user-profile');

$url = $router->generate('user-profile', ['username' => 'alice']);
echo $url; // Output: /myapp/profile/alice/
```

---

## 🧪 Testing Locally

Use PHP’s built-in web server to test your routes:

```bash
php -S localhost:8000
```

Make sure your router is in `index.php` and handles all requests.

---

## 📁 Project Structure Suggestion

```
/project-root
  ├── views/
  │   └── home.php
  ├── SecureRoute.php
  └── index.php
```

---

## 📄 License

MIT © [Your Name]

---

## 🙋‍♂️ Questions?

Open an issue or pull request — contributions are welcome!
