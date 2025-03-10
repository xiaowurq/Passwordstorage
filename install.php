<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 生成随机数据库名称
    // $db_name = 'password_storage_' . bin2hex(random_bytes(8));
    $db_name = bin2hex(random_bytes(8));
    $secret_key = $_POST['secret_key'];

    // 检查 SQLite3 扩展是否已启用
    if (!class_exists('SQLite3')) {
        die("Error: SQLite3 extension is not enabled. Please enable it in your PHP configuration.");
    }

    // 创建 SQLite3 数据库连接
    $conn = new SQLite3($db_name . '.db');
    if (!$conn) {
        die("Connection failed: " . $conn->lastErrorMsg());
    }

    // 创建用户和密码表
    $sql_users = "CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL,
        password TEXT NOT NULL,
        role TEXT DEFAULT 'user'
    )";

    $sql_passwords = "CREATE TABLE IF NOT EXISTS passwords (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        platform_name TEXT,
        platform_address TEXT,
        account TEXT,
        password TEXT,
        other_info TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    $conn->exec($sql_users);
    $conn->exec($sql_passwords);

    // 创建管理员账户
    $admin_username = $_POST['admin_username'];
    $admin_password = password_hash($_POST['admin_password'], PASSWORD_BCRYPT);
    $conn->exec("INSERT INTO users (username, password, role) VALUES ('$admin_username', '$admin_password', 'admin')");

    // 将数据库配置写入文件
    $config_content = "<?php\n"
        . "\$db_name = '$db_name';\n"
        . "\$secret_key = '$secret_key';\n"
        . "\$conn = new SQLite3(\$db_name . '.db');\n"
        . "if (!\$conn) {\n"
        . "    die(\"Connection failed: \" . \$conn->lastErrorMsg());\n"
        . "}\n"
        . "?>\n";

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
    <?php
    if (file_exists('config.php')) {
        echo "<div class='alert alert-warning'>配置文件已存在，如需重新安装，请先手动移除配置文件。</div>";
    } else {
    ?>
    <form method="post" class="mt-4">
        <div class="form-group">
            <label for="secret_key">秘钥(盐)</label>
            <input type="text" class="form-control" id="secret_key" name="secret_key" required>
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
    <?php
    }
    ?>
</div>
</body>
</html>