<?php

function env($key, $default = null)
{
    // Check multiple sources for the variable
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?? null;
    
    if ($value === false || $value === null) {
        return $default;
    }

    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'empty':
        case '(empty)':
            return '';
        case 'null':
        case '(null)':
            return null;
    }

    if (strlen($value) > 1 && $value[0] === '"' && $value[-1] === '"') {
        return substr($value, 1, -1);
    }

    // Remove single quotes if present
    if (strlen($value) > 1 && $value[0] === "'" && $value[-1] === "'") {
        return substr($value, 1, -1);
    }

    return $value;
}

function base_path($path = '')
{
    return dirname(__DIR__) . ($path ? DIRECTORY_SEPARATOR . $path : $path);
}

function app_path($path = '')
{
    return base_path('app' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
}

function view($view, $data = [])
{
    extract($data);
    $viewPath = app_path('views') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $view) . '.php';
    
    if (!file_exists($viewPath)) {
        throw new Exception("View not found: {$view} at {$viewPath}");
    }
    
    ob_start();
    include $viewPath;
    return ob_get_clean();
}

function redirect($path)
{
    header("Location: {$path}");
    exit;
}

function back()
{
    $referer = $_SERVER['HTTP_REFERER'] ?? '/';
    redirect($referer);
}

function json_response($data, $status = 200)
{
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function session_set($key, $value)
{
    $_SESSION[$key] = $value;
}

function session_get($key, $default = null)
{
    return $_SESSION[$key] ?? $default;
}

function session_has($key)
{
    return isset($_SESSION[$key]);
}

function session_forget($key)
{
    unset($_SESSION[$key]);
}

function session_flush()
{
    $_SESSION = [];
}

function get_user()
{
    return session_get('user');
}

function auth_check()
{
    return session_has('user') && session_has('access_token');
}

function is_authenticated()
{
    return auth_check();
}

function require_auth()
{
    if (!auth_check()) {
        redirect('/signin');
        exit;
    }
}

function user_has_role($role)
{
    // Check the session variable you set in AuthController
    $sessionRole = session_get('role');
    return $sessionRole === $role;
}

function csrf_token()
{
    if (!session_has('csrf_token')) {
        session_set('csrf_token', bin2hex(random_bytes(32)));
    }
    return session_get('csrf_token');
}

function verify_csrf($token)
{
    return hash_equals(csrf_token(), $token);
}

/**
 * Return a friendly display name for a user array.
 */
function get_display_name($user)
{
    if (!$user || !is_array($user)) {
        return 'User';
    }

    if (!empty($user['full_name'])) {
        return $user['full_name'];
    }

    if (!empty($user['name'])) {
        return $user['name'];
    }

    if (!empty($user['user_metadata']) && !empty($user['user_metadata']['name'])) {
        return $user['user_metadata']['name'];
    }

    if (!empty($user['email'])) {
        return $user['email'];
    }

    return 'User';
}
