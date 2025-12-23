<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="notificationContainer"></div>
    <div class="container">
        <h1>My To-Do List</h1>
        <a href="tasks.php" class="view-btn">View All Tasks</a>
        <form id="taskForm">
            <input type="text" id="title" placeholder="Task Title" required>
            <textarea id="description" placeholder="Task Description"></textarea>
            <button type="submit">Add Task</button>
        </form>
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
    </script>
</body>
</html>