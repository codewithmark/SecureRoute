<?php 
    require 'SecureRoute.php'; 

    $router = new SecureRoute([], '/secure-route-app'); // ✅ BASE PATH SET

    $router->map('GET', '/', function () {
        echo "Welcome to the homepage!";

          echo phpinfo();
    });

    $router->map('GET|POST', '/users/[i:id]', function ($id) {
        echo "User ID: " . htmlspecialchars($id);
    });

    //quick dispatch
    $router->dispatch();
/* 

    //for advance dispatch > custom 404 error handling
    $match = $router->match();
    if ($match) {
        call_user_func_array($match['target'], $match['params']);
    } else {
        http_response_code(404);
        echo "404 Not Found - No route matched.";
    }

*/
?>
