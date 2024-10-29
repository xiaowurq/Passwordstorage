<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $db_name = $_POST['db_name'];

    // 创建数据库连接
    $conn = new mysqli($db_host, $db_user, $db_pass);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // 创建数据库
    $conn->query("CREATE DATABASE IF NOT EXISTS $db_name");
    $conn->select_db($db_name);

    // 创建表
    $sql_users = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') DEFAULT 'user'
    )";

    $sql_passwords = "CREATE TABLE IF NOT EXISTS passwords (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        platform_name VARCHAR(100),
        platform_address VARCHAR(255),
        account VARCHAR(100),
        password VARCHAR(255),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    $conn->query($sql_users);
    $conn->query($sql_passwords);

    // 添加管理员
    $admin_username = $_POST['admin_username'];
    $admin_password = password_hash($_POST['admin_password'], PASSWORD_BCRYPT);
    $conn->query("INSERT INTO users (username, password, role) VALUES ('$admin_username', '$admin_password', 'admin')");

    // 保存数据库配置
    $config_content = "<?php\n"
        . "\$db_host = '$db_host';\n"
        . "\$db_user = '$db_user';\n"
        . "\$db_pass = '$db_pass';\n"
        . "\$db_name = '$db_name';\n"
        . "\$conn = new mysqli(\$db_host, \$db_user, \$db_pass, \$db_name);\n"
        . "if (\$conn->connect_error) {\n"
        . "    die(\"Connection failed: \" . \$conn->connect_error);\n"
        . "}\n";

    file_put_contents('config.php', $config_content);
    
    echo "<div class='alert alert-success'>安装成功！请访问 <a href='login.php'>登录页面</a></div>";
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>安装</title>
</head>
<body class="bg-light">
<div class="container">
    <h1 class="mt-5">安装数据库</h1>
    <form method="post" class="mt-4">
        <div class="form-group">
            <label for="db_host">数据库主机</label>
            <input type="text" class="form-control" id="db_host" name="db_host" required>
        </div>
        <div class="form-group">
            <label for="db_user">数据库用户名</label>
            <input type="text" class="form-control" id="db_user" name="db_user" required>
        </div>
        <div class="form-group">
            <label for="db_pass">数据库密码</label>
            <input type="password" class="form-control" id="db_pass" name="db_pass">
        </div>
        <div class="form-group">
            <label for="db_name">数据库名称</label>
            <input type="text" class="form-control" id="db_name" name="db_name" required>
        </div>
        <div class="form-group">
            <label for="admin_username">管理员用户名</label>
            <input type="text" class="form-control" id="admin_username" name="admin_username" required>
        </div>
        <div class="form-group">
            <label for="admin_password">管理员密码</label>
            <input type="password" class="form-control" id="admin_password" name="admin_password" required>
        </div>
        <button type="submit" class="btn btn-primary">安装</button>
    </form>
</div>
</body>
</html>
