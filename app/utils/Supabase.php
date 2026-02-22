<?php

namespace App\Utils;

// Optional GoTrue client — only used when the Supabase PHP SDK is installed.
// We avoid a hard dependency so the app can run without the SDK for simple REST usage.

class Supabase
{
    private static $instance = null;
    private $supabaseUrl;
    private $publicAnonKey;
    private $secretKey;
    private $auth;

    private function __construct()
    {
        $this->supabaseUrl = env('SUPABASE_URL');
        $this->publicAnonKey = env('SUPABASE_PUBLIC_ANON_KEY');
        $this->secretKey = env('SUPABASE_SECRET_KEY');

        if (!$this->supabaseUrl || !$this->publicAnonKey) {
            throw new \Exception('Missing Supabase credentials. Please set SUPABASE_URL and SUPABASE_PUBLIC_ANON_KEY in .env file');
        }

        // Initialize GoTrue for auth only if the SDK is available.
        if (class_exists('\Supabase\\GoTrue\\GoTrue')) {
            try {
                $this->auth = new (\Supabase\GoTrue\GoTrue::class)([
                    'autoRefreshToken' => true,
                    'persistSession' => false,
                    'detectSessionInUrl' => true,
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->publicAnonKey,
                        'Content-Type' => 'application/json',
                    ],
                ]);
            } catch (\Throwable $e) {
                // If instantiation fails, fall back to null and continue using REST calls.
                error_log('Supabase GoTrue init skipped: ' . $e->getMessage());
                $this->auth = null;
            }
        } else {
            $this->auth = null;
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function signUp($email, $password, $metadata = [])
    {
        // If Supabase appears unavailable, use local fallback auth
        if (!$this->isAvailable()) {
            $local = new LocalAuth();
            return $local->signUp($email, $password, $metadata);
        }

        try {
            $response = $this->makeRequest('POST', '/auth/v1/signup', [
                'email' => $email,
                'password' => $password,
                'data' => $metadata,
            ]);

            // Supabase /auth/v1/signup returns the user object directly
            // Wrap it to match signIn response structure
            return [
                'success' => true,
                'data' => [
                    'user' => $response,
                    'access_token' => null,  // signup doesn't return tokens
                    'refresh_token' => null,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function signIn($email, $password)
    {
        if (!$this->isAvailable()) {
            $local = new LocalAuth();
            return $local->signIn($email, $password);
        }

        try {
            $response = $this->makeRequest('POST', '/auth/v1/token?grant_type=password', [
                'email' => $email,
                'password' => $password,
            ]);

            // Make sure we actually got a token back
            if (empty($response['access_token'])) {
                return ['success' => false, 'error' => 'Invalid credentials'];
            }

            return ['success' => true, 'data' => $response];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function signOut()
    {
        try {
            // Clear local session
            session_forget('user');
            session_forget('access_token');
            session_forget('refresh_token');
            return [
                'success' => true,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getUserProfile($accessToken)
    {
        if (!$this->isAvailable()) {
            // LocalAuth does not support token->user mapping; return null user
            return ['success' => false, 'error' => 'Local auth does not provide profile by token'];
        }

        try {
            $response = $this->makeRequest('GET', '/auth/v1/user', [], [
                'Authorization' => 'Bearer ' . $accessToken,
            ]);

            return [
                'success' => true,
                'user' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Heuristic for whether Supabase REST/auth endpoints are configured.
     */
    public function isAvailable()
    {
        if (!$this->supabaseUrl || stripos($this->supabaseUrl, 'your-') !== false) {
            return false;
        }
        return true;
    }

    public function query($table, $select = '*', $filters = [])
    {
        try {
            $url = $this->supabaseUrl . "/rest/v1/{$table}?select={$select}";

            foreach ($filters as $key => $value) {
                $url .= "&{$key}=eq.{$value}";
            }

            $response = $this->makeRequest('GET', $url, [], []);

            return [
                'success' => true,
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function insert($table, $data, $accessToken = null)
    {
        try {
            $response = $this->makeRequest('POST', "/rest/v1/{$table}", $data, [
                'Authorization' => $accessToken ? "Bearer {$accessToken}" : 'Bearer ' . $this->publicAnonKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation',
            ]);

            return [
                'success' => true,
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function update($table, $id, $data, $accessToken = null)
    {
        try {
            $response = $this->makeRequest('PATCH', "/rest/v1/{$table}?id=eq.{$id}", $data, [
                'Authorization' => $accessToken ? "Bearer {$accessToken}" : 'Bearer ' . $this->publicAnonKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation',
            ]);

            return [
                'success' => true,
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function delete($table, $id, $accessToken = null)
    {
        try {
            $this->makeRequest('DELETE', "/rest/v1/{$table}?id=eq.{$id}", [], [
                'Authorization' => $accessToken ? "Bearer {$accessToken}" : 'Bearer ' . $this->publicAnonKey,
            ]);

            return [
                'success' => true,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Make a request using the SERVICE ROLE key.
     * Use this for all admin operations that need to bypass RLS:
     * - Creating auth users (pre-registering presidents)
     * - Reading all users (admin user list)
     * - Updating roles
     */
    public function adminRequest($method, $endpoint, $data = [], $extraHeaders = [])
    {
        if (!$this->secretKey) {
            throw new \Exception('SUPABASE_SECRET_KEY is not set. Admin operations require the service role key.');
        }

        $adminHeaders = array_merge([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'apikey'        => $this->secretKey,
            'Content-Type'  => 'application/json',
            'Prefer'        => 'return=representation',
        ], $extraHeaders);

        return $this->makeRequest($method, $endpoint, $data, $adminHeaders);
    }

    public function makeRequest($method, $endpoint, $data = [], $headers = [])
    {
        $url = $endpoint;
        if (strpos($endpoint, 'http') !== 0) {
            $url = $this->supabaseUrl . $endpoint;
        }

        $defaultHeaders = [
            'apikey' => $this->publicAnonKey,
            'Content-Type' => 'application/json',
        ];

        $allHeaders = array_merge($defaultHeaders, $headers);

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => $this->buildHeaders($allHeaders),
                'content' => $method !== 'GET' && $method !== 'DELETE' ? json_encode($data) : null,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \Exception('Failed to connect to Supabase');
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid response from Supabase');
        }

        if (isset($decoded['error'])) {
            throw new \Exception($decoded['error']['message'] ?? 'Supabase error');
        }

        // Catch Supabase auth error format (code + error_code + msg)
        if (isset($decoded['error_code'])) {
            throw new \Exception($decoded['msg'] ?? $decoded['error_code'] ?? 'Authentication error');
        }

        return $decoded;
    }

    private function buildHeaders($headers)
    {
        $headerLines = [];
        foreach ($headers as $key => $value) {
            $headerLines[] = "{$key}: {$value}";
        }
        return $headerLines;
    }

    public function getUrl()
    {
        return $this->supabaseUrl;
    }

    public function getPublicKey()
    {
        return $this->publicAnonKey;
    }
}