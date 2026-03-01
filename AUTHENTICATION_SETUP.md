# To-Do App Authentication System - Setup & Testing Guide

## Overview

This guide walks you through implementing a complete user authentication system for your to-do app. The system includes:

- User registration with password hashing
- Secure login with session tokens
- User-specific task management
- Session protection on all CRUD operations
- Logout functionality

---

## Part 1: Database Setup

### Step 1: Initialize the Database

1. **Open your browser and navigate to:**

   ```
   http://localhost/to-do-app-by-ag-golosino/database_setup.php
   ```

2. **You should see a green confirmation message** indicating:
   - ✓ Users table created successfully
   - ✓ Tasks table modified with user_id column

3. **If errors occur**, check:
   - MySQL is running in XAMPP
   - You have proper permissions
   - The database name is `test` (or adjust in `db.php`)

### Database Schema

**Users Table:**

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username)
);
```

**Tasks Table (Modified):**

- Added `user_id` column linking to users.id
- Added FOREIGN KEY constraint with ON DELETE CASCADE
- All queries now filter by user_id

---

## Part 2: File Structure Overview

### New Files Created:

1. **`config.php`** - Configuration constants
2. **`auth_check.php`** - Authentication helper functions
3. **`register.html`** - Registration form
4. **`register.php`** - Registration handler
5. **`login.html`** - Login form
6. **`login.php`** - Login handler
7. **`logout.php`** - Logout handler
8. **`toggle_task.php`** - Mark task complete/pending
9. **`database_setup.php`** - Database initialization

### Modified Files:

1. **`add_task.php`** - Added auth check & user_id
2. **`get_tasks.php`** - Added auth check & user filtering
3. **`edit_task.php`** - Added auth check & ownership verification
4. **`delete_task.php`** - Added auth check & ownership verification
5. **`index.php`** - Added auth check & user info bar
6. **`tasks.php`** - Added auth check & user info bar
7. **`style.css`** - Added auth UI styles

---

## Part 3: Authentication Flow

### Registration Flow

```
1. User fills register.html form
   ↓
2. JavaScript validates input
   ↓
3. POST to register.php
   ↓
4. PHP validates username/password
   ↓
5. Check if username exists
   ↓
6. Hash password with password_hash()
   ↓
7. Insert user into database
   ↓
8. Return success/error JSON
   ↓
9. Redirect to login.html
```

### Login Flow

```
1. User fills login.html form
   ↓
2. POST to login.php
   ↓
3. Find user by username
   ↓
4. Verify password with password_verify()
   ↓
5. Generate random token (bin2hex(random_bytes(32)))
   ↓
6. Store in $_SESSION:
   - $_SESSION['token']
   - $_SESSION['user_id']
   - $_SESSION['username']
   ↓
7. Return success JSON with token
   ↓
8. Redirect to index.php
```

### CRUD Protection Flow

```
For any CRUD operation (add, edit, delete, view):
   ↓
1. Include auth_check.php
   ↓
2. Call checkAuth() or requireAuth()
   ↓
3. Get $user_id from $_SESSION['user_id']
   ↓
4. Filter queries with WHERE user_id = ?
   ↓
5. Verify ownership before delete/edit
   ↓
6. Return results only for current user
```

---

## Part 4: Step-by-Step Testing Guide

### Test 1: Database Setup

**Action:** Visit `http://localhost/to-do-app-by-ag-golosino/database_setup.php`

**Expected Result:**

- Green checkmarks for all operations
- See links to "Register Account", "Login", and "Go to App"

---

### Test 2: User Registration

**Action:**

1. Click "Register Account" link
2. Fill in form:
   - Username: `testuser1`
   - Password: `password123`
   - Confirm Password: `password123`
3. Click "Register"

**Expected Results:**

- ✓ Success message appears
- ✓ Redirects to login page after 2 seconds
- ✓ Username and password stored in database

**Test Failure Cases:**

| Scenario              | Input              | Expected                                                                      |
| --------------------- | ------------------ | ----------------------------------------------------------------------------- |
| Too short username    | `ab`               | Error: "Username must be at least 3 characters"                               |
| Too short password    | `123`              | Error: "Password must be at least 6 characters"                               |
| Passwords don't match | Different values   | Error: "Passwords do not match"                                               |
| Username exists       | Duplicate username | Error: "Username already exists"                                              |
| Invalid characters    | `user@name!`       | Error: "Username can only contain letters, numbers, underscores, and hyphens" |

---

### Test 3: User Login

**Action:**

1. From login.html, enter:
   - Username: `testuser1`
   - Password: `password123`
2. Click "Login"

**Expected Results:**

- ✓ Success message
- ✓ Redirected to index.php
- ✓ User bar shows "Welcome, testuser1!"
- ✓ Session created with token

**Test Failure Cases:**

| Scenario          | Input                        | Expected                              |
| ----------------- | ---------------------------- | ------------------------------------- |
| Wrong password    | Correct user, wrong password | Error: "Invalid username or password" |
| Non-existent user | Non-existent username        | Error: "Invalid username or password" |
| Missing fields    | Blank username or password   | Error message                         |

---

### Test 4: Add Task (Authenticated)

**Action:**

1. Already logged in as testuser1
2. Fill task form on index.php:
   - Title: "Buy groceries"
   - Description: "Milk, eggs, bread"
3. Click "Add Task"

**Expected Results:**

- ✓ Task appears in list
- ✓ Success notification
- ✓ Task stored with user_id=1
- ✓ Task created_at timestamp

**Query Behind the Scenes:**

```php
INSERT INTO test.tasks (user_id, title, description, status)
VALUES (1, 'Buy groceries', 'Milk, eggs, bread', 'pending')
```

---

### Test 5: View All Tasks

**Action:**

1. Click "View All Tasks" button
2. Navigate to tasks.php

**Expected Results:**

- ✓ Only testuser1's task is visible
- ✓ Task shows title and description
- ✓ Created timestamp displays
- ✓ Action buttons visible (Mark Complete, Edit, Delete)

---

### Test 6: Mark Task Complete

**Action:**

1. On tasks.php, click "Mark Complete" button

**Expected Results:**

- ✓ Task text becomes strikethrough
- ✓ Button text changes to "Mark Pending"
- ✓ Status updated in database to 'completed'

**Query:**

```php
UPDATE test.tasks SET status = 'completed'
WHERE id = ? AND user_id = 1
```

---

### Test 7: Edit Task

**Action:**

1. Click "Edit" button on a task
2. Modify form:
   - Title: "Buy groceries and cook dinner"
   - Description: "Milk, eggs, bread, chicken"
3. Click "Save"

**Expected Results:**

- ✓ Edit form disappears
- ✓ Task list refreshes
- ✓ Updated content displays
- ✓ No other user can edit this task

---

### Test 8: Delete Task

**Action:**

1. Click "Delete" button on a task
2. Confirm in dialog

**Expected Results:**

- ✓ Confirmation prompt appears
- ✓ Task is removed from list
- ✓ Database record deleted

**Query:**

```php
DELETE FROM test.tasks
WHERE id = ? AND user_id = 1
```

---

### Test 9: Logout

**Action:**

1. Click "Logout" button (top right)

**Expected Results:**

- ✓ Session destroyed
- ✓ Redirected to login.html
- ✓ Browser back button shows login page (not protected pages)

---

### Test 10: Session Protection (Direct Access)

**Action:**

1. Logout (delete session)
2. Try to access `http://localhost/to-do-app-by-ag-golosino/index.php` directly

**Expected Results:**

- ✗ Redirected to login.html
- ✗ Cannot access protected pages without session

**Code Protection:**

```php
require_auth(); // At top of protected files
```

---

### Test 11: Data Isolation (Multi-User Test)

**Action:**

1. Register second user:
   - Username: `testuser2`
   - Password: `password456`

2. Login as testuser2

3. Add task: "Workout"

4. Logout

5. Login as testuser1

**Expected Results:**

- ✓ Can see only "Buy groceries" task
- ✗ Cannot see testuser2's "Workout" task
- ✓ Each user sees only their own tasks

**Protection:**

```php
WHERE user_id = ? // All queries filtered by current user
```

---

### Test 12: Ownership Verification

**Action:**

1. Login as testuser1
2. Get task_id from their task (e.g., ID=5)
3. Logout
4. Login as testuser2
5. Manually edit URL or use browser dev tools to attempt deleting testuser1's task

**Expected Results:**

- ✗ Edit/Delete fails
- ✗ No task record updated/deleted
- NULL response or error message
- Activity log should show unauthorized attempt

---

### Test 13: SQL Injection Prevention

**Action:**

1. During registration, try username:
   ```
   admin' OR '1'='1
   ```

**Expected Results:**

- ✓ Treated as regular string
- ✓ No SQL injection
- ✓ Stored safely via prepared statements

**Protection:**

```php
$stmt->bind_param("s", $username); // Type-safe binding
```

---

## Part 5: Security Features Implemented

### 1. Password Security

- ✓ **Bcrypt Hashing:** `password_hash($password, PASSWORD_DEFAULT)`
- ✓ **Verification:** `password_verify($plaintext, $hash)`
- ✓ **Not stored in plain text**

### 2. Session Management

- ✓ **Unique tokens:** `bin2hex(random_bytes(32))`
- ✓ **Server-side sessions:** `$_SESSION` storage
- ✓ **Secure logout:** `session_destroy()`

### 3. Data Protection

- ✓ **SQL Injection prevention:** Prepared statements
- ✓ **User isolation:** All queries filtered by user_id
- ✓ **Ownership verification:** WHERE clause checks

### 4. Authorization

- ✓ **Protected pages:** `requireAuth()` checks
- ✓ **Protected operations:** Ownership verification
- ✓ **Cross-user prevention:** user_id in all queries

---

## Part 6: Troubleshooting

### "Database connection failed"

- **Check:** MySQL is running in XAMPP
- **Check:** Database credentials in db.php are correct
- **Check:** Database `test` exists

### "Username already exists"

- **Solution:** Choose a different username
- **Check:** Database constraints are working

### "Invalid username or password"

- **Check:** Spelling and case sensitivity
- **Check:** User exists in database
- **Check:** Password is correct

### Session not persisting

- **Check:** Cookies enabled in browser
- **Check:** `session_start()` called in auth_check.php
- **Check:** No output before session_start()

### Task not visible after login

- **Check:** Task has correct user_id
- **Check:** Logged in as correct user
- **Check:** Query has WHERE user_id = ? filter

---

## Part 7: Optional Enhancements

### Add JWT (JSON Web Tokens)

Install firebase/php-jwt:

```bash
composer require firebase/php-jwt
```

Example JWT implementation:

```php
<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secret = 'your-secret-key';
$token = JWT::encode($payload, $secret, 'HS256');
$decoded = JWT::decode($token, new Key($secret, 'HS256'));
?>
```

### Add Email Verification

```php
// Send verification email after registration
mail($email, 'Verify Account', $verification_link);
```

### Add Password Reset

```php
// Generate reset token
$reset_token = bin2hex(random_bytes(32));
// Store token and expiry in database
// Send reset link via email
```

### Add Two-Factor Authentication (2FA)

```php
// Generate TOTP code after login
// Send via SMS or email
// Verify before granting access
```

---

## Code Quality Notes

All PHP code includes:

- ✓ **Comments:** Explaining purpose of each function
- ✓ **Error handling:** Graceful failure messages
- ✓ **Input validation:** On both client and server
- ✓ **Type hinting:** Prepared statements
- ✓ **Security checks:** Authentication and authorization
- ✓ **HTTP status codes:** 401, 403, 405, 500 where appropriate
- ✓ **JSON responses:** Consistent format for AJAX

---

## Summary

Your to-do app now has:

1. ✓ **Secure registration** - Password hashing with bcrypt
2. ✓ **Secure login** - Session-based authentication
3. ✓ **Data isolation** - Each user sees only their tasks
4. ✓ **Protected CRUD** - Ownership verification on all operations
5. ✓ **Session protection** - Automatic redirect on unauthorized access
6. ✓ **Logout** - Complete session cleanup
7. ✓ **Responsive design** - Works on mobile and desktop

The system is production-ready for a small to medium-scale application.

For enterprise needs, consider adding JWT tokens, database query logging, and more sophisticated access control.

---

## Quick Start Checklist

- [ ] Run `database_setup.php` in browser
- [ ] Register a test user via `register.html`
- [ ] Login via `login.html`
- [ ] Add a task on `index.php`
- [ ] View all tasks on `tasks.php`
- [ ] Edit and delete tasks to verify ownership
- [ ] Register a second user and verify data isolation
- [ ] Test logout functionality
- [ ] Test direct access to protected pages (should redirect)

**You're all set!** 🎉
