document.addEventListener('DOMContentLoaded', function() {
    loadTasks();

    document.getElementById('taskForm').addEventListener('submit', function(e) {
        e.preventDefault();
        addTask();
    });
});

// Notification system
function showNotification(message, type = 'info', duration = 4000) {
    const container = document.getElementById('notificationContainer');
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    container.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('hide');
        setTimeout(() => {
            container.removeChild(notification);
        }, 300);
    }, duration);
}

function loadTasks() {
    const loading = document.getElementById('loading');
    if (loading) loading.style.display = 'block';
    console.log('Starting to load tasks...');
    fetch('get_tasks.php')
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Data received:', data);
            if (loading) loading.style.display = 'none';
            if (data.error) {
                showNotification('✗ Error: ' + data.error, 'error');
                console.error('Error:', data.error);
                return;
            }
            const taskList = document.getElementById('taskList');
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

                const taskDiv = document.createElement('div');
                const strong = document.createElement('strong');
                strong.textContent = task.title;
                const p = document.createElement('p');
                p.textContent = task.description;
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
                editInput.value = task.title;
                editInput.required = true;
                const editTextarea = document.createElement('textarea');
                editTextarea.textContent = task.description;
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
            });
        })
        .catch(error => {
            if (loading) loading.style.display = 'none';
            console.error('Error loading tasks:', error);
            showNotification('✗ Network error: Could not load tasks', 'error');
        });
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
            loadTasks();
        } else {
            showNotification('✗ Error: ' + (result.error || 'Failed to add task'), 'error');
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Add Task';
        console.error('Error:', error);
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
            loadTasks();
        } else {
            showNotification('✗ Error: ' + (result.error || 'Failed to update task'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('✗ Network error: Could not update task', 'error');
    });
}

function showEditForm(id) {
    const form = document.getElementById(`editForm${id}`);
    form.style.display = 'block';
    form.removeEventListener('submit', form.submitHandler);
    form.submitHandler = function(e) {
        e.preventDefault();
        saveEdit(id);
    };
    form.addEventListener('submit', form.submitHandler);
}

function hideEditForm(id) {
    document.getElementById(`editForm${id}`).style.display = 'none';
}

function saveEdit(id) {
    const form = document.getElementById(`editForm${id}`);
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
            loadTasks();
        } else {
            showNotification('✗ Error: ' + (result.error || 'Failed to update task'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
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
                loadTasks();
            } else {
                showNotification('✗ Error: ' + (result.error || 'Failed to delete task'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('✗ Network error: Could not delete task', 'error');
        });
    }
}