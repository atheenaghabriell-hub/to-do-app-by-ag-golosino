<?php
if (isset($_GET['error'])) {
    echo "Connection failed: " . htmlspecialchars($_GET['error']);
} else {
    echo "Unknown error.";
}
?>