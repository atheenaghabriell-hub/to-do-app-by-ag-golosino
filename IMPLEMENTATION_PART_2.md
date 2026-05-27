## 11. UPDATED DATABASE SETUP - Add Archive Tables & Roles

**Location**: `database_setup.php`  
**Purpose**: Create archive_tasks table and add role column to users

This file should be run once to set up the complete database schema. Add this code:

```php
<?php
/**
 * Database Setup Script
 * Creates all necessary tables for the To-Do App
 * Run this once after initial setup
 */

// Include database connection
include 'db.php';

// ========== CREATE USERS TABLE WITH ROLE COLUMN ==========
// Create users table if it doesn't exist
$users_table_sql = "CREATE TABLE IF NOT EXISTS test.users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Execute users table creation
if (!$conn->query($users_table_sql)) {
    // If table creation fails, show error
    echo "Error creating users table: " . $conn->error . "\n";
} else {
    // Success message
    echo "Users table created/verified successfully\n";
}

// ========== CREATE TASKS TABLE ==========
// Create tasks table if it doesn't exist
$tasks_table_sql = "CREATE TABLE IF NOT EXISTS test.tasks (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending', 'completed', 'in_progress', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES test.users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Execute tasks table creation
if (!$conn->query($tasks_table_sql)) {
    // If table creation fails, show error
    echo "Error creating tasks table: " . $conn->error . "\n";
} else {
    // Success message
    echo "Tasks table created/verified successfully\n";
}

// ========== CREATE ARCHIVE_TASKS TABLE ==========
// Create archive_tasks table if it doesn't exist
// This table stores deleted/archived tasks for recovery
$archive_table_sql = "CREATE TABLE IF NOT EXISTS test.archive_tasks (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending', 'completed', 'in_progress', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_archived_at (archived_at),
    FOREIGN KEY (user_id) REFERENCES test.users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Execute archive table creation
if (!$conn->query($archive_table_sql)) {
    // If table creation fails, show error
    echo "Error creating archive_tasks table: " . $conn->error . "\n";
} else {
    // Success message
    echo "Archive tasks table created/verified successfully\n";
}

// ========== ADD ROLE COLUMN TO EXISTING USERS TABLE ==========
// Check if role column exists in users table
$check_role = $conn->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='role'");

// If role column doesn't exist, add it
if ($check_role->num_rows === 0) {
    // Add role column with default value
    $add_role_sql = "ALTER TABLE test.users ADD COLUMN role ENUM('admin', 'user') DEFAULT 'user' AFTER password_hash";
    
    // Execute ALTER TABLE
    if (!$conn->query($add_role_sql)) {
        // Error adding column
        echo "Error adding role column: " . $conn->error . "\n";
    } else {
        // Success message
        echo "Role column added to users table\n";
    }
} else {
    // Role column already exists
    echo "Role column already exists\n";
}

// ========== CREATE DEFAULT ADMIN USER (OPTIONAL) ==========
// For testing purposes, create an admin user if it doesn't exist
$check_admin = $conn->query("SELECT id FROM test.users WHERE username='admin'");

// If no admin user exists, create one
if ($check_admin->num_rows === 0) {
    // Hash password 'admin123' for testing
    // IMPORTANT: Change this password before deployment!
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT, ['cost' => 10]);
    
    // Prepare INSERT statement for admin user
    $admin_stmt = $conn->prepare("INSERT INTO test.users (username, password_hash, role) VALUES (?, ?, ?)");
    
    // Set role to 'admin'
    $admin_role = 'admin';
    
    // Bind parameters
    $admin_stmt->bind_param("sss", $username, $admin_password, $admin_role);
    
    // Set username
    $username = 'admin';
    
    // Execute INSERT
    if ($admin_stmt->execute()) {
        echo "Default admin user created (username: admin, password: admin123)\n";
        echo "WARNING: Change admin password before production deployment!\n";
    } else {
        echo "Error creating admin user: " . $admin_stmt->error . "\n";
    }
    
    $admin_stmt->close();
} else {
    // Admin user already exists
    echo "Admin user already exists\n";
}

// ========== DISPLAY SUCCESS MESSAGE ==========
echo "\n=== Database Setup Complete ===\n";
echo "Tables created/verified:\n";
echo "  - users (with role column)\n";
echo "  - tasks\n";
echo "  - archive_tasks\n";
echo "\nYou can now use the application!\n";

// Close database connection
$conn->close();

?>
```

---

## 12. UPDATED script.js - Fix UI & Add Archive Support

**Location**: `script.js`  
**Purpose**: Completely rewrite to fix refresh issues and add archive functionality

Replace entire file with:

```javascript
/**
 * To-Do App Main JavaScript
 * Handles task management, notifications, and UI updates
 * Optimized for low-resource systems (2GB RAM)
 */

// ========== TASK CACHE SYSTEM ==========
// Cache for storing tasks in memory to reduce API calls
const TaskCache = {
    // Store task data in memory
    data: null,
    // Timestamp of last cache update (milliseconds since epoch)
    timestamp: null,
    // Cache duration in milliseconds (30 seconds)
    duration: 30000,
    
    /**
     * Check if cached data is still valid
     * Returns true if data exists and cache hasn't expired
     */
    isValid() {
        // Check if data exists AND time since cache is less than duration
        return this.data !== null && (Date.now() - this.timestamp) < this.duration;
    },
    
    /**
     * Get cached data if valid, otherwise return null
     */
    get() {
        // Return data only if cache is valid, otherwise null
        return this.isValid() ? this.data : null;
    },
    
    /**
     * Store new data in cache with current timestamp
     */
    set(data) {
        // Store task data
        this.data = data;
        // Record current time for expiration checking
        this.timestamp = Date.now();
    },
    
    /**
     * Clear cache completely
     */
    clear() {
        // Set data to null
        this.data = null;
        // Reset timestamp
        this.timestamp = null;
    }
};

// Variable to store setTimeout ID for debouncing loadTasks
let loadTasksTimeout;

// ========== PAGE INITIALIZATION ==========
/**
 * Initialize page when DOM is loaded
 * Loads tasks and attaches event listeners
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get task form element by ID
    const taskForm = document.getElementById('taskForm');
    // Check if form exists on this page
    if (taskForm) {
        // Load tasks from server
        loadTasks();
        // Attach submit event listener to form
        taskForm.addEventListener('submit', function(e) {
            // Prevent default form submission
            e.preventDefault();
            // Call addTask function
            addTask();
        });
    }
    
    // Initialize archive section if it exists
    const archiveBtn = document.getElementById('viewArchiveBtn');
    if (archiveBtn) {
        // Attach click listener to archive button
        archiveBtn.addEventListener('click', toggleArchiveView);
    }
});

// ========== NOTIFICATION SYSTEM ==========
/**
 * Display notification message to user
 * Parameters:
 *   message (string) - text to display
 *   type (string) - 'success', 'error', or 'info'
 *   duration (number) - milliseconds to show (default 4000)
 */
function showNotification(message, type = 'info', duration = 4000) {
    // Get notification container element
    const container = document.getElementById('notificationContainer');
    // If container doesn't exist, exit
    if (!container) return;
    
    // Create new div element for notification
    const notification = document.createElement('div');
    // Add classes for styling: notification base class + type class
    notification.className = `notification ${type}`;
    // Set message text
    notification.textContent = message;
    // Add notification to container
    container.appendChild(notification);

    // Set timer to hide notification after duration
    setTimeout(() => {
        // Add hide class to trigger fade out
        notification.classList.add('hide');
        // Set timer to remove element from DOM after animation
        setTimeout(() => {
            // Try to remove notification, catch error if already removed
            try { 
                container.removeChild(notification); 
            } catch(e) {}
        }, 300); // 300ms matches CSS animation duration
    }, duration);
}

// ========== LOAD AND DISPLAY TASKS ==========
/**
 * Fetch tasks from server and display them
 * Uses cache to minimize API calls
 */
function loadTasks() {
    // Get loading indicator element
    const loading = document.getElementById('loading');
    
    // ========== CHECK CACHE FIRST ==========
    // Get cached task data if still valid
    const cachedData = TaskCache.get();
    // If cache is valid, use cached data
    if (cachedData !== null) {
        // Render tasks from cache
        renderTasks(cachedData);
        // Hide loading indicator
        if (loading) loading.style.display = 'none';
        // Exit function early (no API call needed)
        return;
    }
    
    // ========== FETCH FROM SERVER ==========
    // Show loading indicator
    if (loading) loading.style.display = 'block';
    
    // Make fetch request to get_tasks.php
    fetch('get_tasks.php')
        // Handle response
        .then(response => {
            // Check if HTTP response is ok (200-299)
            if (!response.ok) throw new Error('Network response was not ok');
            // Parse response as JSON
            return response.json();
        })
        // Handle parsed JSON data
        .then(data => {
            // Hide loading indicator
            if (loading) loading.style.display = 'none';
            // Check if data is valid and has no error
            if (data && !data.error) {
                // Store data in cache
                TaskCache.set(data);
                // Render tasks to page
                renderTasks(data);
            } else {
                // Show error notification
                showNotification('✗ Error loading tasks', 'error');
            }
        })
        // Handle network errors
        .catch(error => {
            // Hide loading indicator
            if (loading) loading.style.display = 'none';
            // Show error notification
            showNotification('✗ Network error', 'error');
        });
}

/**
 * Render tasks to the task list
 * Clears current list and displays all tasks
 * Parameters: data (array) - array of task objects
 */
function renderTasks(data) {
    // Get task list element by ID
    const taskList = document.getElementById('taskList');
    // If element doesn't exist, exit
    if (!taskList) return;
    
    // Clear all existing tasks from list
    taskList.innerHTML = '';
    
    // ========== HANDLE EMPTY LIST ==========
    // Check if data is empty or null
    if (!data || data.length === 0) {
        // Create list item element
        const li = document.createElement('li');
        // Set message text
        li.textContent = 'No tasks found. Add one to get started!';
        // Add to list
        taskList.appendChild(li);
        // Exit function
        return;
    }
    
    // ========== RENDER EACH TASK ==========
    // Loop through each task in data array
    data.forEach(task => {
        // Call function to create task element
        createTaskElement(task, taskList);
    });
}

/**
 * Create HTML elements for a single task and add to list
 * Parameters:
 *   task (object) - task data
 *   taskList (element) - list to append to
 */
function createTaskElement(task, taskList) {
    // Create list item element for task
    const li = document.createElement('li');
    // Set class to 'completed' if task is done
    li.className = task.status === 'completed' ? 'completed' : '';

    // ========== TASK DISPLAY SECTION ==========
    // Create container for task details
    const taskDiv = document.createElement('div');
    
    // Create and add task title (bold)
    const strong = document.createElement('strong');
    // Set title with HTML escape for security
    strong.textContent = escapeHtml(task.title);
    // Add to task div
    taskDiv.appendChild(strong);
    
    // Create and add task description
    const p = document.createElement('p');
    // Set description text with HTML escape
    p.textContent = escapeHtml(task.description);
    // Add to task div
    taskDiv.appendChild(p);
    
    // Create and add task creation date
    const small = document.createElement('small');
    // Format date and time for display
    small.textContent = 'Created: ' + new Date(task.created_at).toLocaleString();
    // Add to task div
    taskDiv.appendChild(small);

    // ========== ACTION BUTTONS SECTION ==========
    // Create container for action buttons
    const actionsDiv = document.createElement('div');
    // Set class name for styling
    actionsDiv.className = 'task-actions';
    
    // ========== TOGGLE BUTTON ==========
    // Create button to mark complete/pending
    const toggleBtn = document.createElement('button');
    // Set button text based on current status
    toggleBtn.textContent = task.status === 'completed' ? 'Mark Pending' : 'Mark Complete';
    // Attach click handler
    toggleBtn.onclick = () => toggleTask(task.id, task.status);
    // Add button to actions
    actionsDiv.appendChild(toggleBtn);
    
    // ========== EDIT BUTTON ==========
    // Create edit button
    const editBtn = document.createElement('button');
    // Set button text
    editBtn.textContent = 'Edit';
    // Attach click handler to show edit form
    editBtn.onclick = () => showEditForm(task.id);
    // Add to actions
    actionsDiv.appendChild(editBtn);
    
    // ========== DELETE/ARCHIVE BUTTON ==========
    // Create delete button (actually archives the task)
    const deleteBtn = document.createElement('button');
    // Set button text
    deleteBtn.textContent = 'Archive';
    // Attach click handler
    deleteBtn.onclick = () => deleteTask(task.id);
    // Add to actions
    actionsDiv.appendChild(deleteBtn);

    // ========== EDIT FORM (HIDDEN) ==========
    // Create form for editing (initially hidden)
    const editForm = document.createElement('form');
    // Set class for styling
    editForm.className = 'edit-form';
    // Set unique ID for this task's edit form
    editForm.id = 'editForm' + task.id;
    // Hide form by default
    editForm.style.display = 'none';
    
    // ========== EDIT FORM INPUTS ==========
    // Create input field for editing title
    const editInput = document.createElement('input');
    // Set input type to text
    editInput.type = 'text';
    // Pre-fill with current title
    editInput.value = escapeHtml(task.title);
    // Mark as required field
    editInput.required = true;
    // Add to form
    editForm.appendChild(editInput);
    
    // Create textarea for editing description
    const editTextarea = document.createElement('textarea');
    // Pre-fill with current description
    editTextarea.textContent = escapeHtml(task.description);
    // Add to form
    editForm.appendChild(editTextarea);
    
    // ========== EDIT FORM BUTTONS ==========
    // Create save button
    const saveBtn = document.createElement('button');
    // Set type to submit
    saveBtn.type = 'submit';
    // Set button text
    saveBtn.textContent = 'Save';
    // Add to form
    editForm.appendChild(saveBtn);
    
    // Create cancel button
    const cancelBtn = document.createElement('button');
    // Set type to button (not submit)
    cancelBtn.type = 'button';
    // Set button text
    cancelBtn.textContent = 'Cancel';
    // Attach click handler to hide form
    cancelBtn.onclick = () => hideEditForm(task.id);
    // Add to form
    editForm.appendChild(cancelBtn);

    // ========== ASSEMBLE TASK ELEMENT ==========
    // Add task display section to list item
    li.appendChild(taskDiv);
    // Add action buttons to list item
    li.appendChild(actionsDiv);
    // Add edit form to list item
    li.appendChild(editForm);
    // Add complete task element to task list
    taskList.appendChild(li);
}

// ========== ADD TASK FUNCTION ==========
/**
 * Add new task to database
 * Gets input from form, validates, and sends to server
 */
function addTask() {
    // Get title input element and extract trimmed value
    const title = document.getElementById('title').value.trim();
    // Get description input element and extract trimmed value
    const description = document.getElementById('description').value.trim();

    // ========== VALIDATION ==========
    // Check if title is empty
    if (!title) {
        // Show error notification
        showNotification('Task title is required', 'error');
        // Exit function
        return;
    }

    // ========== DISABLE BUTTON ==========
    // Get submit button element
    const submitBtn = document.querySelector('#taskForm button[type="submit"]');
    // Disable button to prevent double submission
    submitBtn.disabled = true;
    // Change button text to show loading state
    submitBtn.textContent = 'Adding...';

    // ========== SEND REQUEST ==========
    // Send POST request to add_task.php
    fetch('add_task.php', {
        // Set request method to POST
        method: 'POST',
        // Set content type header
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        // Encode form data (URL encoded format)
        body: `title=${encodeURIComponent(title)}&description=${encodeURIComponent(description)}`
    })
    // Parse response as JSON
    .then(response => response.json())
    // Handle JSON data
    .then(result => {
        // Re-enable submit button
        submitBtn.disabled = false;
        // Reset button text
        submitBtn.textContent = 'Add Task';
        // Check if request was successful
        if (result.success) {
            // Show success notification
            showNotification('✓ Task saved successfully!', 'success');
            // Clear form inputs
            document.getElementById('taskForm').reset();
            // Clear cache so fresh data is loaded
            TaskCache.clear();
            // Cancel any pending timeout
            clearTimeout(loadTasksTimeout);
            // Reload tasks after 100ms delay to ensure server is updated
            loadTasksTimeout = setTimeout(loadTasks, 100);
        } else {
            // Show error notification with error message
            showNotification('✗ Error: ' + (result.error || 'Failed'), 'error');
        }
    })
    // Handle network errors
    .catch(error => {
        // Re-enable submit button
        submitBtn.disabled = false;
        // Reset button text
        submitBtn.textContent = 'Add Task';
        // Show error notification
        showNotification('✗ Network error', 'error');
    });
}

// ========== TOGGLE TASK STATUS ==========
/**
 * Mark task as complete or pending
 * Parameters:
 *   id (number) - task ID
 *   currentStatus (string) - 'completed' or 'pending'
 */
function toggleTask(id, currentStatus) {
    // Determine new status (opposite of current)
    const newStatus = currentStatus === 'completed' ? 'pending' : 'completed';

    // Send POST request to toggle_task.php
    fetch('toggle_task.php', {
        // Set request method to POST
        method: 'POST',
        // Set content type header
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        // Send task ID and new status
        body: `id=${id}&status=${newStatus}`
    })
    // Parse response as JSON
    .then(response => response.json())
    // Handle JSON data
    .then(result => {
        // Check if request was successful
        if (result.success) {
            // Show success notification with new status
            showNotification('✓ Task marked ' + newStatus + '!', 'success');
            // Clear task cache
            TaskCache.clear();
            // Cancel pending timeout
            clearTimeout(loadTasksTimeout);
            // Reload tasks after 100ms
            loadTasksTimeout = setTimeout(loadTasks, 100);
        } else {
            // Show error notification
            showNotification('✗ Error: ' + (result.error || 'Failed'), 'error');
        }
    })
    // Handle network errors
    .catch(error => showNotification('✗ Network error', 'error'));
}

// ========== EDIT TASK FUNCTIONS ==========
/**
 * Show edit form for a task
 * Parameters: id (number) - task ID
 */
function showEditForm(id) {
    // Get edit form by ID (editForm + task ID)
    const form = document.getElementById(`editForm${id}`);
    // Check if form exists
    if (form) {
        // Show form by setting display to block
        form.style.display = 'block';
        // Attach submit handler to form
        form.onsubmit = function(e) {
            // Prevent default form submission
            e.preventDefault();
            // Call saveEdit function
            saveEdit(id);
        };
    }
}

/**
 * Hide edit form for a task
 * Parameters: id (number) - task ID
 */
function hideEditForm(id) {
    // Get edit form by ID
    const form = document.getElementById(`editForm${id}`);
    // Check if form exists
    if (form) 
        // Hide form
        form.style.display = 'none';
}

/**
 * Save task edits to database
 * Parameters: id (number) - task ID
 */
function saveEdit(id) {
    // Get edit form by ID
    const form = document.getElementById(`editForm${id}`);
    // Exit if form doesn't exist
    if (!form) return;
    
    // Get updated title from form input and trim
    const title = form.querySelector('input').value.trim();
    // Get updated description from form textarea and trim
    const description = form.querySelector('textarea').value.trim();

    // ========== VALIDATION ==========
    // Check if title is empty
    if (!title) {
        // Show error notification
        showNotification('Task title is required', 'error');
        // Exit function
        return;
    }

    // ========== SEND UPDATE REQUEST ==========
    // Send POST request to edit_task.php
    fetch('edit_task.php', {
        // Set request method to POST
        method: 'POST',
        // Set content type header
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        // Send task ID, title, and description
        body: `id=${id}&title=${encodeURIComponent(title)}&description=${encodeURIComponent(description)}`
    })
    // Parse response as JSON
    .then(response => response.json())
    // Handle JSON data
    .then(result => {
        // Check if request was successful
        if (result.success) {
            // Show success notification
            showNotification('✓ Task updated!', 'success');
            // Hide edit form
            hideEditForm(id);
            // Clear cache
            TaskCache.clear();
            // Cancel pending timeout
            clearTimeout(loadTasksTimeout);
            // Reload tasks after 100ms
            loadTasksTimeout = setTimeout(loadTasks, 100);
        } else {
            // Show error notification
            showNotification('✗ Error: ' + (result.error || 'Failed'), 'error');
        }
    })
    // Handle network errors
    .catch(error => showNotification('✗ Network error', 'error'));
}

// ========== DELETE/ARCHIVE TASK ==========
/**
 * Delete (archive) a task
 * Parameters: id (number) - task ID
 */
function deleteTask(id) {
    // Ask user to confirm deletion
    if (confirm('Are you sure you want to archive this task?')) {
        // Send POST request to delete_task.php (actually archives)
        fetch('delete_task.php', {
            // Set request method to POST
            method: 'POST',
            // Set content type header
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            // Send task ID to delete
            body: `id=${id}`
        })
        // Parse response as JSON
        .then(response => response.json())
        // Handle JSON data
        .then(result => {
            // Check if request was successful
            if (result.success) {
                // Show success notification
                showNotification('✓ Task archived!', 'success');
                // Clear cache
                TaskCache.clear();
                // Cancel pending timeout
                clearTimeout(loadTasksTimeout);
                // Reload tasks after 100ms
                loadTasksTimeout = setTimeout(loadTasks, 100);
            } else {
                // Show error notification
                showNotification('✗ Error: ' + (result.error || 'Failed'), 'error');
            }
        })
        // Handle network errors
        .catch(error => showNotification('✗ Network error', 'error'));
    }
}

// ========== ARCHIVE VIEW (PLACEHOLDER) ==========
/**
 * Toggle archive view (for future implementation)
 * Currently a placeholder for archive functionality
 */
function toggleArchiveView() {
    // This function can be implemented to show archived tasks
    // For now, just show a message
    showNotification('Archive view coming soon!', 'info');
}

// ========== SECURITY: XSS PREVENTION ==========
/**
 * Escape HTML special characters to prevent XSS attacks
 * Converts dangerous characters to safe HTML entities
 * Parameters: text (string) - text to escape
 * Returns: escaped HTML safe string
 */
function escapeHtml(text) {
    // Check if text is empty or null
    if (!text) return '';
    // Create temporary div element
    const div = document.createElement('div');
    // Set textContent (automatically escapes HTML)
    div.textContent = text;
    // Return escaped HTML from innerHTML
    return div.innerHTML;
}
```

---

## 13. XML SCHEMA FILES

### tasks.xsd

**Location**: `data/tasks.xsd`  
**Purpose**: Validate tasks.xml structure

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!-- XML Schema for tasks.xml backup file -->
<!-- Validates structure and allowed values -->
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
  <!-- Define root element: tasks -->
  <xs:element name="tasks">
    <xs:complexType>
      <!-- Tasks element contains multiple task children -->
      <xs:sequence>
        <!-- Allow zero or more task elements -->
        <xs:element name="task" type="taskType" minOccurs="0" maxOccurs="unbounded"/>
      </xs:sequence>
    </xs:complexType>
  </xs:element>

  <!-- Define task structure -->
  <xs:complexType name="taskType">
    <xs:sequence>
      <!-- User ID (required) -->
      <xs:element name="user_id" type="xs:integer"/>
      <!-- Task title (required) -->
      <xs:element name="title" type="xs:string"/>
      <!-- Task description (optional) -->
      <xs:element name="description" type="xs:string" minOccurs="0"/>
      <!-- Task status (required) -->
      <xs:element name="status" type="statusType"/>
      <!-- Creation timestamp (required) -->
      <xs:element name="created_at" type="xs:string"/>
    </xs:sequence>
    <!-- Task must have id attribute -->
    <xs:attribute name="id" type="xs:integer" use="required"/>
  </xs:complexType>

  <!-- Define allowed task statuses -->
  <xs:simpleType name="statusType">
    <xs:restriction base="xs:string">
      <!-- Allowed values -->
      <xs:enumeration value="pending"/>
      <xs:enumeration value="completed"/>
      <xs:enumeration value="in_progress"/>
      <xs:enumeration value="cancelled"/>
    </xs:restriction>
  </xs:simpleType>
</xs:schema>
```

### archive_tasks.xsd

**Location**: `data/archive_tasks.xsd`  
**Purpose**: Validate archive_tasks.xml structure

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!-- XML Schema for archive_tasks.xml backup file -->
<!-- Validates archived task structure -->
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
  <!-- Define root element: archived_tasks -->
  <xs:element name="archived_tasks">
    <xs:complexType>
      <!-- Archived_tasks element contains multiple archived_task children -->
      <xs:sequence>
        <!-- Allow zero or more archived_task elements -->
        <xs:element name="archived_task" type="archivedTaskType" minOccurs="0" maxOccurs="unbounded"/>
      </xs:sequence>
    </xs:complexType>
  </xs:element>

  <!-- Define archived task structure -->
  <xs:complexType name="archivedTaskType">
    <xs:sequence>
      <!-- User ID (required) -->
      <xs:element name="user_id" type="xs:integer"/>
      <!-- Task title (required) -->
      <xs:element name="title" type="xs:string"/>
      <!-- Task description (optional) -->
      <xs:element name="description" type="xs:string" minOccurs="0"/>
      <!-- Task status (required) -->
      <xs:element name="status" type="statusType"/>
      <!-- Original creation timestamp (required) -->
      <xs:element name="created_at" type="xs:string"/>
      <!-- Archive timestamp (required) -->
      <xs:element name="archived_at" type="xs:string"/>
    </xs:sequence>
    <!-- Archived task must have id attribute -->
    <xs:attribute name="id" type="xs:integer" use="required"/>
  </xs:complexType>

  <!-- Define allowed task statuses -->
  <xs:simpleType name="statusType">
    <xs:restriction base="xs:string">
      <!-- Allowed values -->
      <xs:enumeration value="pending"/>
      <xs:enumeration value="completed"/>
      <xs:enumeration value="in_progress"/>
      <xs:enumeration value="cancelled"/>
    </xs:restriction>
  </xs:simpleType>
</xs:schema>
```

---

## IMPLEMENTATION CHECKLIST

### Phase 1: Core Fixes (Do First)
- [ ] Update `config.php` with DEV_MODE flag
- [ ] Update `auth_check.php` with rate limiting & isAdmin()
- [ ] Update `script.js` with cache-based loading
- [ ] Run `database_setup.php` to add archive table + role column
- [ ] Create `xml_sync_helper.php` with sync functions
- [ ] Update `add_task.php` with XML sync

### Phase 2: Feature Completion
- [ ] Update `edit_task.php` with XML sync
- [ ] Update `delete_task.php` to archive instead of delete
- [ ] Create `restore_task.php` to move tasks from archive
- [ ] Create `data/tasks.xsd` schema
- [ ] Create `data/archive_tasks.xsd` schema
- [ ] Create `data/` directory (mkdir data/ in XAMPP htdocs)

### Phase 3: Testing
- [ ] Test adding task → XML sync
- [ ] Test editing task → XML sync
- [ ] Test deleting task → moves to archive, XML sync
- [ ] Test restoring task → moves back from archive
- [ ] Test with DEV_MODE=true → rate limiting disabled
- [ ] Verify no manual refresh needed after operations

---

## TROUBLESHOOTING

**Problem**: "Network error" on task operations
- **Solution**: Check browser console (F12), verify `Content-Type: application/json` response in PHP files

**Problem**: XML files not created
- **Solution**: Manually create `data/` directory with 755 permissions: `mkdir -p data/ && chmod 755 data/`

**Problem**: Rate limiting keeps blocking you
- **Solution**: Set `DEV_MODE = true` in config.php for testing

**Problem**: Tasks show then disappear
- **Solution**: Cache is 30 seconds old. Clear it manually: `TaskCache.clear()` in browser console

**Problem**: Archived tasks not visible
- **Solution**: Create view for archived tasks (future enhancement)

```

