# Optional: JWT (JSON Web Tokens) Authentication

This guide shows how to replace simple session tokens with JWT for a production-ready authentication system.

**Why JWT?**

- Stateless authentication (no server-side session storage needed)
- Works across multiple servers/domains
- Can include user claims/metadata
- Standard for REST APIs
- Industry best practice

---

## Installation

### Step 1: Install Composer (If Not Installed)

Download from: https://getcomposer.org/download/

### Step 2: Install Firebase PHP-JWT

```bash
# In your project root directory
composer require firebase/php-jwt
```

This creates:

- `composer.json` - Project dependencies
- `composer.lock` - Locked versions
- `vendor/` - Library code

### Step 3: Include in Your Project

```php
<?php
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
?>
```

---

## JWT Implementation

### Config File Update

Add to `config.php`:

```php
<?php
// ... existing config ...

// JWT Configuration
define('JWT_SECRET', 'your-very-secure-secret-key-change-this!');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRY', 3600); // 1 hour in seconds

?>
```

**⚠️ IMPORTANT:** Change `JWT_SECRET` to a truly random, secure key!

```bash
# Generate a secure key:
# On Linux/Mac: openssl rand -hex 32
# On Windows: Use https://www.random.org/bytes/
```

---

## JWT Login Implementation

### Updated `login.php` with JWT

```php
<?php
/**
 * Login Handler - JWT Version
 */

require 'vendor/autoload.php';
use Firebase\JWT\JWT;

include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Username and password required']);
    exit();
}

// Find user
$stmt = $conn->prepare("SELECT id, username, password_hash FROM test.users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
    $stmt->close();
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Verify password
if (!password_verify($password, $user['password_hash'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
    exit();
}

// Create JWT Token
$issuedAt = time();
$expire = $issuedAt + 3600; // 1 hour

$payload = [
    'iat' => $issuedAt,           // Issued at
    'exp' => $expire,              // Expiration time
    'user_id' => $user['id'],      // User ID
    'username' => $user['username'], // Username
    'iat_human' => date('Y-m-d H:i:s', $issuedAt),
    'exp_human' => date('Y-m-d H:i:s', $expire)
];

$jwt = JWT::encode(
    $payload,
    'your-very-secure-secret-key-change-this!',
    'HS256'
);

echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'token' => $jwt,
    'user_id' => $user['id'],
    'username' => $user['username'],
    'expires_in' => 3600
]);

$conn->close();
?>
```

---

## JWT Frontend Update

### Updated `login.html` JavaScript

```javascript
// Store token in localStorage after successful login
fetch("login.php", {
  method: "POST",
  body: formData,
})
  .then((response) => response.json())
  .then((data) => {
    if (data.success) {
      // Store JWT token
      localStorage.setItem("auth_token", data.token);
      localStorage.setItem("user_id", data.user_id);
      localStorage.setItem("username", data.username);

      // Redirect
      window.location.href = "index.php";
    } else {
      showMessage(data.error, "error");
    }
  });
```

---

## JWT Verification Middleware

### Create `auth_check_jwt.php`

```php
<?php
/**
 * JWT Authentication Check
 * Include this in protected pages
 */

require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\InvalidTokenException;

include 'db.php';

/**
 * Verify JWT Token from Authorization header
 */
function verifyJWT() {
    // Get token from Authorization header
    $headers = getallheaders();

    if (!isset($headers['Authorization'])) {
        return ['valid' => false, 'error' => 'No authorization header'];
    }

    $authHeader = $headers['Authorization'];

    // Extract token from "Bearer <token>"
    if (strpos($authHeader, 'Bearer ') !== 0) {
        return ['valid' => false, 'error' => 'Invalid authorization header format'];
    }

    $token = substr($authHeader, 7);

    try {
        $decoded = JWT::decode(
            $token,
            new Key('your-very-secure-secret-key-change-this!', 'HS256')
        );

        return [
            'valid' => true,
            'user_id' => $decoded->user_id,
            'username' => $decoded->username,
            'decoded' => $decoded
        ];

    } catch (ExpiredException $e) {
        return ['valid' => false, 'error' => 'Token expired'];
    } catch (InvalidTokenException $e) {
        return ['valid' => false, 'error' => 'Invalid token'];
    } catch (Exception $e) {
        return ['valid' => false, 'error' => 'Token verification failed'];
    }
}

/**
 * Require JWT authentication
 */
function requireJWTAuth() {
    $result = verifyJWT();

    if (!$result['valid']) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
        exit();
    }

    return $result;
}

?>
```

---

## API Endpoint Example with JWT

### Updated `add_task.php` for JWT

```php
<?php
/**
 * Add Task - JWT Protected
 */

require 'vendor/autoload.php';
include 'auth_check_jwt.php';

header('Content-Type: application/json');

// Verify JWT
$auth = requireJWTAuth();
$user_id = $auth['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

if (empty($title)) {
    echo json_encode(['success' => false, 'error' => 'Task title required']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO test.tasks (user_id, title, description, status)
                        VALUES (?, ?, ?, 'pending')");
$stmt->bind_param("iss", $user_id, $title, $description);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'task_id' => $stmt->insert_id,
        'message' => 'Task added successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to add task']);
}

$stmt->close();
$conn->close();

?>
```

---

## Frontend API Calls with JWT

### JavaScript Helper for API Calls

Create `api-client.js`:

```javascript
/**
 * API Client with JWT Authentication
 */

const API_BASE = "/to-do-app-by-ag-golosino/";

async function apiCall(endpoint, method = "GET", data = null) {
  const token = localStorage.getItem("auth_token");

  if (!token) {
    // Redirect to login
    window.location.href = API_BASE + "login.html";
    return null;
  }

  const options = {
    method: method,
    headers: {
      Authorization: `Bearer ${token}`,
      "Content-Type": "application/json",
    },
  };

  if (data && method !== "GET") {
    options.body = JSON.stringify(data);
  }

  try {
    const response = await fetch(API_BASE + endpoint, options);

    // Check if token expired
    if (response.status === 401) {
      localStorage.clear();
      window.location.href = API_BASE + "login.html";
      return null;
    }

    return await response.json();
  } catch (error) {
    console.error("API Error:", error);
    return null;
  }
}

// Usage:
// const tasks = await apiCall('get_tasks.php', 'GET');
// const result = await apiCall('add_task.php', 'POST', {title: 'New Task', description: ''});
```

---

## Using JWT with Frontend

Update your JavaScript calls:

```javascript
// Get tasks with JWT
async function loadTasks() {
  const token = localStorage.getItem("auth_token");

  fetch("get_tasks.php", {
    method: "GET",
    headers: {
      Authorization: `Bearer ${token}`,
      "Content-Type": "application/json",
    },
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.length) {
        // Display tasks
      }
    });
}

// Logout
function logout() {
  localStorage.clear(); // Remove token
  window.location.href = "login.html";
}
```

---

## JWT Advantages

| Feature         | Session           | JWT            |
| --------------- | ----------------- | -------------- |
| Server Storage  | Yes (memory/DB)   | No (stateless) |
| Scalability     | Limited           | Excellent      |
| Mobile Friendly | Cookies only      | Headers        |
| Microservices   | Difficult         | Easy           |
| Single Sign-On  | Hard to implement | Built-in       |
| Token Expiry    | Automatic         | Explicit       |
| Cross-Domain    | CORS issues       | Simple         |

---

## JWT Best Practices

### 1. Secure Secret Key

```php
// Generate and store in environment variable
$jwt_secret = getenv('JWT_SECRET');
if (!$jwt_secret) {
    die('JWT_SECRET environment variable not set');
}
```

### 2. Use HTTPS Only

```php
if ($_SERVER['SCHEME'] !== 'https' && $_SERVER['HTTP_HOST'] !== 'localhost') {
    die('JWT requires HTTPS');
}
```

### 3. Short Expiry + Refresh Tokens

```php
'access_token_expiry' => 900,      // 15 minutes
'refresh_token_expiry' => 604800,  // 7 days
```

### 4. Token Validation

```php
// Always verify:
// - Signature
// - Expiration
// - Claims (user_id, permissions)
// - Not blacklisted
```

### 5. Revoke Tokens (Logout)

```php
// Store revoked tokens in blacklist table
CREATE TABLE token_blacklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(500) UNIQUE,
    revoked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

// Check on each request
$stmt = $conn->prepare("SELECT id FROM token_blacklist WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    // Token is blacklisted
}
```

---

## Troubleshooting JWT

### "No authorization header"

- Check: Client sends `Authorization: Bearer <token>`
- Check: Token not null in localStorage

### "Token expired"

- Solution: Implement refresh token flow
- Request new token automatically

### "Invalid signature"

- Check: Secret key matches on encode/decode
- Check: Not modified in transit

---

## Migration Path

To migrate from sessions to JWT:

1. **Phase 1:** Support both session and JWT
2. **Phase 2:** Accept JWT in Authorization header
3. **Phase 3:** Deprecate session-based auth
4. **Phase 4:** Remove session code

```php
<?php
// Support both session and JWT
function getAuthUser() {
    // Try JWT first
    $jwt_result = verifyJWT();
    if ($jwt_result['valid']) {
        return $jwt_result['user_id'];
    }

    // Fall back to session
    session_start();
    if (isset($_SESSION['user_id'])) {
        return $_SESSION['user_id'];
    }

    return null;
}
?>
```

---

## Summary

JWT provides:

- ✓ Stateless authentication
- ✓ Scalability across servers
- ✓ Industry standard
- ✓ Mobile-friendly
- ✓ API-first design

**Recommended:** Use JWT for production APIs, keep sessions for traditional web apps.
