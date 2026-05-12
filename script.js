// Cache system for improved performance on low-resource systems
const TaskCache = {
    data: null,
    timestamp: null,
    duration: 30000, // 30 seconds
    
    isValid() {
        return this.data !== null && (Date.now() - this.timestamp) < this.duration;
    },
    
    get() {
        return this.isValid() ? this.data : null;
    },
    
    set(data) {
        this.data = data;
        this.timestamp = Date.now();
    },
    
    clear() {
        this.data = null;
        this.timestamp = null;
    }
};

let loadTasksTimeout;

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

// Notification system
function showNotification(message, type = 'info', duration = 4000) {
    const container = document.getElementById('notificationContainer');
    if (!container) return;
    
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    container.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('hide');
        setTimeout(() => {
            try { container.removeChild(notification); } catch(e) {}
        }, 300);
    }, duration);
}

function loadTasks() {
    const loading = document.getElementById('loading');
    
    // Check cache first
    const cachedData = TaskCache.get();
    if (cachedData !== null) {
        renderTasks(cachedData);
        if (loading) loading.style.display = 'none';
        return;
    }
    
    if (loading) loading.style.display = 'block';
    
    fetch('get_tasks.php')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (loading) loading.style.display = 'none';
            if (data && !data.error) {
                TaskCache.set(data);
                renderTasks(data);
            } else {
                showNotification('✗ Error loading tasks', 'error');
            }
        })
        .catch(error => {
            if (loading) loading.style.display = 'none';
            showNotification('✗ Network error', 'error');
        });
}

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
        createTaskElement(task, taskList);
    });
}

function createTaskElement(task, taskList) {
    const li = document.createElement('li');
    li.className = task.status === 'completed' ? 'completed' : '';

    const taskDiv = document.createElement('div');
    const strong = document.createElement('strong');
    strong.textContent = escapeHtml(task.title);
    const p = document.createElement('p');
    p.textContent = escapeHtml(task.description);
    const small = document.createElement('small');
    small.textContent = 'Created: ' + new Date(task.created_at).toLocaleString();
    taskDiv.appendChild(strong);
    taskDiv.appendChild(p);
    taskDiv.appendChild(small);

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
    
    const editInput = document.createElement('input');
    editInput.type = 'text';
    editInput.value = escapeHtml(task.title);
    editInput.required = true;
    
    const editTextarea = document.createElement('textarea');
    editTextarea.textContent = escapeHtml(task.description);
    
    const saveBtn = document.createElement('button');
    saveBtn.type = 'submit';
    saveBtn.textContent = 'Save';
    
    const cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.textContent = 'Cancel';
    cancelBtn.onclick = () => hideEditForm(task.id);
    
    editForm.appendChild(editInput);
    editForm.appendChild(editTextarea);
    editForm.appendChild(saveBtn);
    editForm.appendChild(cancelBtn);

    li.appendChild(taskDiv);
    li.appendChild(actionsDiv);
    li.appendChild(editForm);
    taskList.appendChild(li);
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
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `title=${encodeURIComponent(title)}&description=${encodeURIComponent(description)}`
    })
    .then(response => response.json())
    .then(result => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Add Task';
        if (result.success) {
            showNotification('✓ Task saved successfully!', 'success');
            document.getElementById('taskForm').reset();
            TaskCache.clear();
            clearTimeout(loadTasksTimeout);
            loadTasksTimeout = setTimeout(loadTasks, 100);
        } else {
            showNotification('✗ Error: ' + (result.error || 'Failed'), 'error');
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Add Task';
        showNotification('✗ Network error', 'error');
    });
}

function toggleTask(id, currentStatus) {
    const newStatus = currentStatus === 'completed' ? 'pending' : 'completed';

    fetch('toggle_task.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('✓ Task marked ' + newStatus + '!', 'success');
            TaskCache.clear();
            clearTimeout(loadTasksTimeout);
            loadTasksTimeout = setTimeout(loadTasks, 100);
        } else {
            showNotification('✗ Error: ' + (result.error || 'Failed'), 'error');
        }
    })
    .catch(error => showNotification('✗ Network error', 'error'));
}

function showEditForm(id) {
    const form = document.getElementById(`editForm${id}`);
    if (form) {
        form.style.display = 'block';
        form.onsubmit = function(e) {
            e.preventDefault();
            saveEdit(id);
        };
    }
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
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}&title=${encodeURIComponent(title)}&description=${encodeURIComponent(description)}`
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('✓ Task updated!', 'success');
            hideEditForm(id);
            TaskCache.clear();
            clearTimeout(loadTasksTimeout);
            loadTasksTimeout = setTimeout(loadTasks, 100);
        } else {
            showNotification('✗ Error: ' + (result.error || 'Failed'), 'error');
        }
    })
    .catch(error => showNotification('✗ Network error', 'error'));
}

function deleteTask(id) {
    if (confirm('Are you sure you want to delete this task?')) {
        fetch('delete_task.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showNotification('✓ Task deleted!', 'success');
                TaskCache.clear();
                clearTimeout(loadTasksTimeout);
                loadTasksTimeout = setTimeout(loadTasks, 100);
            } else {
                showNotification('✗ Error: ' + (result.error || 'Failed'), 'error');
            }
        })
        .catch(error => showNotification('✗ Network error', 'error'));
    }
}

// XSS Prevention - Escape HTML special characters
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}