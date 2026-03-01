<?php
/**
 * All Tasks Page - To-Do List App
 * View and manage all personal tasks
 * Requires authentication
 */

include 'auth_check.php';

// Check authentication
requireAuth();

// Get current user info
$user = getCurrentUser();
$username = $user ? htmlspecialchars($user['username']) : 'User';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Tasks - To-Do List App</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div id="notificationContainer"></div>

    <!-- User Info Bar -->
    <div class="user-bar">
        <div class="user-info">
            <span class="username">Welcome, <strong><?php echo $username; ?></strong>!</span>
        </div>
        <div class="user-actions">
            <a href="index.php" class="nav-link">Add New Task</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <h1>All Tasks</h1>
        <div id="loading" style="display: none;">Loading tasks...</div>
        <ul id="taskList">
            <!-- Tasks will be loaded here -->
        </ul>
    </div>
    <script src="script.js"></script>
    <script>
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

        // Load tasks on page load
        document.addEventListener('DOMContentLoaded', function () {
            loadTasks();
        });

        function loadTasks() {
            const loading = document.getElementById('loading');
            loading.style.display = 'block';
            console.log('Starting to load tasks...');
            fetch('get_tasks.php')
                .then(response => {
                    console.log('Response status:', response.status);
                    if (response.status === 401) {
                        // Unauthorized - redirect to login
                        window.location.href = 'login.html';
                        return;
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Data received:', data);
                    loading.style.display = 'none';
                    if (!data) return;
                    if (data.error) {
                        alert('Error loading tasks: ' + data.error);
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
                    loading.style.display = 'none';
                    console.error('Error loading tasks:', error);
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
                        loadTasks();
                    } else {
                        alert('Error updating task: ' + (result.error || 'Unknown error'));
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function showEditForm(id) {
            const form = document.getElementById(`editForm${id}`);
            form.style.display = 'block';
            // Remove any existing listener to avoid duplicates
            form.removeEventListener('submit', form.submitHandler);
            form.submitHandler = function (e) {
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
                alert('Task title is required');
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
                        hideEditForm(id);
                        loadTasks();
                    } else {
                        alert('Error updating task: ' + (result.error || 'Unknown error'));
                    }
                })
                .catch(error => console.error('Error:', error));
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
                            loadTasks();
                        } else {
                            alert('Error deleting task: ' + (result.error || 'Unknown error'));
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        }
    </script>
</body>

</html>