<?php
if (file_exists('config.php')) {
    header('Location: login.php');
} else {
    header('Location: install.php');
}
exit;
?>