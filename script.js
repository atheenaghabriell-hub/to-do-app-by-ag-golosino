/**
 * Optimized Task Management Script
 * Fixes hash/refresh issues and reduces memory usage for 2GB RAM systems
 */

let taskCache = null;
let cacheTimestamp = 0;
const CACHE_DURATION = 30000; // Cache for 30 seconds

document.addEventListener('DOMContentLoaded', function() {
    const taskForm = document.getElementById('taskForm');
    if (taskForm) {
        loadTasks();
        taskForm.addEventListener('submit', function(e) {
            e.preventDefault();
            addTask();
        });
    }
});

// Notification system - optimized for memory
function showNotification(message, type = 'info', duration = 4000) {
    const container = document.getElementById('notificationContainer');
    if (!container) return;
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    container.appendChild(notification);

    const timeoutId = setTimeout(() => {
        notification.classList.add('hide');
        setTimeout(() => {
            if (container.contains(notification)) {
                container.removeChild(notification);
            }
        }, 300);
    }, duration);
}

// Load tasks with caching for low-memory systems
function loadTasks() {
    const loading = document.getElementById('loading');
    const now = Date.now();
    
    // Use cache if available and fresh
    if (taskCache && (now - cacheTimestamp) < CACHE_DURATION) {
        renderTasks(taskCache);
        return;
    }
    
    if (loading) loading.style.display = 'block';
    
    fetch('get_tasks.php')
        .then(response => {
            if (response.status === 401) {
                window.location.href = 'login.html';
                return null;
            }
            return response.json();
        })
        .then(data => {
            if (loading) loading.style.display = 'none';
            if (!data) return;
            
            if (data.error) {
                showNotification('✗ Error: ' + data.error, 'error');
                return;
            }
            
            taskCache = data;
            cacheTimestamp = Date.now();
            renderTasks(data);
        })
        .catch(error => {
            if (loading) loading.style.display = 'none';
            showNotification('✗ Network error: Could not load tasks', 'error');
        });
}

// Separate rendering logic to reduce memory overhead
function renderTasks(data) {
    const taskList = document.getElementById('taskList');
    if (!taskList) return;
    
    taskList.innerHTML = '';
    
    if (!data || data.length === 0) {
        const li = document.createElement('li');
        li.textContent = 'No tasks found. Add one to get started!';
        taskList.appendChild(li);
        return;
    }
    
    data.forEach(task => {
        const li = document.createElement('li');
        li.className = task.status === 'completed' ? 'completed' : '';
        li.innerHTML = '';

        const taskDiv = document.createElement('div');
        taskDiv.innerHTML = `
            <strong>${escapeHtml(task.title)}</strong>
            <p>${escapeHtml(task.description)}</p>
            <small>Created: ${new Date(task.created_at).toLocaleString()}</small>
        `;

        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'task-actions';
        
        const toggleBtn = document.createElement('button');
        toggleBtn.textContent = task.status === 'completed' ? 'Mark Pending' : 'Mark Complete';
        toggleBtn.onclick = () => toggleTask(task.id, task.status);
        
        const editBtn = document.createElement('button');
        editBtn.textContent = 'Edit';
        editBtn.onclick = () => showEditForm(task.id);
        
        const deleteBtn = document.createElement('button');
        deleteBtn.textContent = 'Delete';
        deleteBtn.onclick = () => deleteTask(task.id);
        
        actionsDiv.appendChild(toggleBtn);
        actionsDiv.appendChild(editBtn);
        actionsDiv.appendChild(deleteBtn);

        const editForm = document.createElement('form');
        editForm.className = 'edit-form';
        editForm.id = 'editForm' + task.id;
        editForm.style.display = 'none';
        editForm.innerHTML = `
            <input type="text" value="${escapeHtml(task.title)}" required>
            <textarea>${escapeHtml(task.description)}</textarea>
            <button type="submit">Save</button>
            <button type="button">Cancel</button>
        `;
        
        editForm.querySelector('button[type="button"]').onclick = () => hideEditForm(task.id);
        editForm.addEventListener('submit', (e) => {
            e.preventDefault();
            saveEdit(task.id);
        });

        li.appendChild(taskDiv);
        li.appendChild(actionsDiv);
        li.appendChild(editForm);
        taskList.appendChild(li);
    });
}

// Security function to prevent XSS
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function addTask() {
    const title = document.getElementById('title').value.trim();
    const description = document.getElementById('description').value.trim();

    if (!title) {
        showNotification('Task title is required', 'error');
        return;
    }

    const submitBtn = document.querySelector('#taskForm button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Adding...';

    fetch('add_task.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `title=${encodeURIComponent(title)}&description=${encodeURIComponent(description)}`
    })
    .then(response => response.json())
    .then(result => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Add Task';
        if (result.success) {
            showNotification('✓ Task saved successfully!', 'success');
            document.getElementById('taskForm').reset();
            taskCache = null; // Clear cache
            loadTasks();
        } else {
            showNotification('✗ Error: ' + (result.error || 'Failed to add task'), 'error');
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Add Task';
        showNotification('✗ Network error: Could not save task', 'error');
    });
}

function toggleTask(id, currentStatus) {
    const newStatus = currentStatus === 'completed' ? 'pending' : 'completed';

    fetch('toggle_task.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('✓ Task marked ' + newStatus + '!', 'success');
            taskCache = null; // Clear cache
            loadTasks();
        } else {
            showNotification('✗ Error: ' + (result.error || 'Failed to update task'), 'error');
        }
    })
    .catch(error => {
        showNotification('✗ Network error: Could not update task', 'error');
    });
}

function showEditForm(id) {
    const form = document.getElementById(`editForm${id}`);
    if (form) form.style.display = 'block';
}

function hideEditForm(id) {
    const form = document.getElementById(`editForm${id}`);
    if (form) form.style.display = 'none';
}

function saveEdit(id) {
    const form = document.getElementById(`editForm${id}`);
    if (!form) return;
    
    const title = form.querySelector('input').value.trim();
    const description = form.querySelector('textarea').value.trim();

    if (!title) {
        showNotification('Task title is required', 'error');
        return;
    }

    fetch('edit_task.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}&title=${encodeURIComponent(title)}&description=${encodeURIComponent(description)}`
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('✓ Task updated successfully!', 'success');
            hideEditForm(id);
            taskCache = null; // Clear cache
            loadTasks();
        } else {
            showNotification('✗ Error: ' + (result.error || 'Failed to update task'), 'error');
        }
    })
    .catch(error => {
        showNotification('✗ Network error: Could not update task', 'error');
    });
}

function deleteTask(id) {
    if (confirm('Are you sure you want to delete this task?')) {
        fetch('delete_task.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showNotification('✓ Task deleted successfully!', 'success');
                taskCache = null; // Clear cache
                loadTasks();
            } else {
                showNotification('✗ Error: ' + (result.error || 'Failed to delete task'), 'error');
            }
        })
        .catch(error => {
            showNotification('✗ Network error: Could not delete task', 'error');
        });
    }
}
