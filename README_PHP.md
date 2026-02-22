# Cardinal Stage - PHP Version

This is the PHP conversion of the Cardinal Stage application. The application maintains the same functionality as the original React version while using PHP with Supabase for the backend.

## Setup Instructions

### 1. Install Dependencies

```bash
composer install
```

### 2. Configure Environment

Copy `.env.example` to `.env` and update with your Supabase credentials:

```bash
cp .env.example .env
```

Edit `.env`:
```
SUPABASE_URL=https://your-project-id.supabase.co
SUPABASE_PUBLIC_ANON_KEY=your-public-anon-key
SUPABASE_SECRET_KEY=your-secret-key
APP_ENV=development
```

### 3. Set Up Web Server

#### Using PHP Built-in Server (Development):
```bash
cd /path/to/CardinalStage
php -S localhost:8000
```

Then visit: `http://localhost:8000`

#### Using Apache:
1. Configure your Apache vhost to point to the project root
2. Enable `mod_rewrite`:
   ```bash
   sudo a2enmod rewrite
   ```
3. Ensure `.htaccess` is in the root directory
4. Restart Apache:
   ```bash
   sudo systemctl restart apache2
   ```

#### Using Nginx:
Add this to your nginx configuration:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 4. File Structure

```
CardinalStage/
├── app/
│   ├── config/              # Configuration files
│   ├── controllers/         # Request handlers
│   ├── models/             # Database models (optional)
│   ├── utils/              # Utility classes (Supabase SDK)
│   ├── views/              # PHP templates
│   │   ├── components/     # Reusable components
│   │   ├── layout/         # Layout templates
│   │   ├── pages/          # Page templates
│   │   └── error/          # Error pages
│   ├── helpers.php         # Helper functions
│   └── routes.php          # Route definitions
├── vendor/                 # Composer dependencies
├── public/                 # Static assets (if needed)
├── .env                    # Environment variables
├── .env.example           # Example env file
├── .htaccess              # Apache rewrite rules
├── composer.json          # PHP dependencies
└── index.php              # Application entry point
```

## Key Differences from React Version

### Routing
Instead of client-side routing with React Router, we use PHP file routing:
- Routes are defined in `app/routes.php`
- Each route maps to a controller method
- URL rewriting is handled via `.htaccess` or nginx config

### Authentication
- Uses `$_SESSION` for server-side session management
- Login tokens are stored in `$_SESSION`
- Helper functions: `auth_check()`, `get_user()`, `require_auth()`

### Views
- All components are now PHP templates in `app/views/`
- Layout wrapping is handled through `include` statements
- Echoing values with `htmlspecialchars()` for security

### Database
- Supabase is still used for the backend database
- PHP SDK communicates with Supabase REST API
- See `app/utils/Supabase.php` for database methods

## Routes

- `GET /` - Home page
- `GET|POST /signin` - Sign in form and handler
- `GET|POST /signup` - Sign up form and handler
- `GET /signout` - Sign out
- `GET /dashboard` - User dashboard (requires auth)
- `GET /admin` - Admin dashboard (requires admin role)
- `GET /org-admin` - Org admin dashboard (requires org_admin role)
- `GET /organizer` - Organizer dashboard (requires organizer role)
- `GET /directory` - Talent directory
- `GET /account` - Account settings (requires auth)

## Helper Functions

### Authentication
- `auth_check()` - Check if user is logged in
- `get_user()` - Get current user data
- `require_auth()` - Require authentication, redirect to signin if not
- `user_has_role($role)` - Check if user has a specific role

### Session Management
- `session_set($key, $value)` - Set session variable
- `session_get($key, $default)` - Get session variable
- `session_has($key)` - Check if session key exists
- `session_flush()` - Clear all session data

### View Rendering
- `view($name, $data)` - Render a view template
- `redirect($path)` - Redirect to URL
- `json_response($data, $status)` - Return JSON response

### Utility
- `env($key, $default)` - Get environment variable
- `csrf_token()` - Get CSRF token
- `verify_csrf($token)` - Verify CSRF token

## Controller Example

```php
namespace App\Controllers;

use App\Utils\Supabase;

class PagesController
{
    public function home($params = [])
    {
        return view('pages/home', [
            'isAuthenticated' => auth_check(),
            'user' => get_user(),
        ]);
    }
}
```

## View Example

```php
<?php
$title = 'Home - Cardinal Stage';
$content = <<<'HTML'
<div class="container">
    <?php if ($isAuthenticated): ?>
        <p>Welcome, <?php echo htmlspecialchars($user['email']); ?></p>
    <?php else: ?>
        <p>Welcome, guest!</p>
    <?php endif; ?>
</div>
HTML;

include app_path('views/layout/app.php');
```

## Security Notes

1. **CSRF Protection**: Implement CSRF token verification for POST requests
2. **XSS Prevention**: Always use `htmlspecialchars()` when echoing user data
3. **SQL Injection**: Supabase provides parameterized queries, so SQL injection is not a concern
4. **Session Security**: 
   - Use HTTPS in production
   - Set secure session cookies in php.ini
   - Use HttpOnly flag for cookies

## Deployment

### Production Setup

1. Set `APP_ENV=production` in `.env`
2. Use a proper web server (Apache/Nginx) with PHP-FPM
3. Enable HTTPS/SSL
4. Store `.env` file outside web root if possible
5. Set proper file permissions:
   ```bash
   chmod 755 -R .
   chmod 644 index.php app/routes.php
   ```

### Environment Variables

```bash
SUPABASE_URL=your-supabase-url
SUPABASE_PUBLIC_ANON_KEY=your-public-key
SUPABASE_SECRET_KEY=your-secret-key
APP_NAME=Cardinal Stage
APP_ENV=production
SESSION_TIMEOUT=3600
```

## Troubleshooting

### 404 on all pages except /index.php
- Enable `mod_rewrite` in Apache: `a2enmod rewrite`
- Restart Apache: `systemctl restart apache2`
- Check `.htaccess` exists in root directory

### Cannot connect to Supabase
- Verify `SUPABASE_URL` and `SUPABASE_PUBLIC_ANON_KEY` in `.env`
- Check if PHP can make HTTP requests (some hosts block this)
- Enable `allow_url_fopen` in php.ini if disabled

### Session not persisting
- Ensure `session_start()` is called at the beginning of `index.php`
- Check PHP session storage path has write permissions
- Verify session cookies are enabled

## License

Same as the original project.
