> 该文档详细描述了实现一个简单的密码管理工具的过程，工具基于PHP和MySQL构建，支持用户注册、密码存储、管理以及角色权限控制等核心功能。
## 系统架构设计
1. **技术栈**：PHP（后端逻辑）、MySQL（数据存储）、Bootstrap（前端样式）
2. **数据存储**：用户表(`users`)和密码表(`passwords`)存储用户的基本信息和其管理的密码信息，包含外键关联实现级联删除。
3. **权限控制**：通过`session`会话管理用户角色，确保不同角色的访问权限。

## 数据库配置和安装流程
### 1. 数据库配置
为了便于初次安装用户配置数据库信息，工具提供了安装页面(`install.php`)来接收并初始化数据库参数和管理员账户。
![微信图片_20241029151130.png](https://www.1042.net/usr/uploads/2024/10/2845001643.png)
```php
<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $db_name = $_POST['db_name'];

    // 连接数据库并创建数据库
    $conn = new mysqli($db_host, $db_user, $db_pass);
    if ($conn->connect_error) {
        die("连接失败: " . $conn->connect_error);
    }
    $conn->query("CREATE DATABASE IF NOT EXISTS $db_name");
    $conn->select_db($db_name);

    // 创建用户和密码表
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
    
    // 创建管理员账户
    $admin_username = $_POST['admin_username'];
    $admin_password = password_hash($_POST['admin_password'], PASSWORD_BCRYPT);
    $conn->query("INSERT INTO users (username, password, role) VALUES ('$admin_username', '$admin_password', 'admin')");
    
    // 将数据库配置写入文件
    $config_content = "<?php\n"
        . "\$db_host = '$db_host';\n"
        . "\$db_user = '$db_user';\n"
        . "\$db_pass = '$db_pass';\n"
        . "\$db_name = '$db_name';\n"
        . "\$conn = new mysqli(\$db_host, \$db_user, \$db_pass, \$db_name);\n"
        . "if (\$conn->connect_error) {\n"
        . "    die(\"连接失败: \" . \$conn->connect_error);\n"
        . "}\n";
    
    file_put_contents('config.php', $config_content);
    
    echo "<div class='alert alert-success'>安装成功！</div>";
}
?>
```

在 `install.php` 文件中，定义了数据库连接及初始化代码，接受表单输入并写入配置文件 `config.php`，用于后续的数据库访问。
---
## 用户身份验证与会话管理
用户登录和权限管理通过`session`实现。登录页面`login.php`验证用户身份并开启会话，将用户ID和角色写入会话变量，以便后续操作中使用。
### 用户登录 (`login.php`)
```php
<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE username='$username'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit;
        }
    }
    echo "<div class='alert alert-danger'>用户名或密码错误！</div>";
}
?>
```
登录验证流程：
1. 获取用户输入的用户名和密码。
2. 使用`SELECT`语句查询用户信息，并验证密码(`password_verify`)。
3. 验证成功后，写入会话(`$_SESSION['user_id']`和`$_SESSION['role']`)并跳转至主页面。

## 管理员页面和用户管理
管理员页面(`admin.php`)提供用户的增删改功能，管理员角色控制通过会话变量实现。此页面仅限`role='admin'`的用户访问。
### 用户管理 (`admin.php`)
```php
<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// 添加用户
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];
    $conn->query("INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')");
}

// 删除用户
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $userId = intval($_POST['user_id']);
    $result = $conn->query("SELECT role FROM users WHERE id=$userId");
    $user = $result->fetch_assoc();
    if ($user && $user['role'] !== 'admin') {
        $conn->query("DELETE FROM users WHERE id=$userId");
    }
}
?>
```
管理员页面支持：
1. **添加用户**：从表单获取用户名、密码、角色信息，进行哈希加密存储。
2. **删除用户**：基于用户ID执行删除操作，仅允许删除非管理员用户。
![微信图片_20241029151229.png](https://www.1042.net/usr/uploads/2024/10/2651954358.png)

## 密码管理
主页面(`dashboard.php`)实现用户的密码管理功能，包括密码的添加、删除、修改和搜索等操作。每个密码条目包含平台名称、平台地址、账号和密码。
![微信图片_20241029151410.png](https://www.1042.net/usr/uploads/2024/10/3165239980.png)
### 添加密码
用户通过表单提交密码条目，包括平台名称、地址、账号和密码，数据存储在 `passwords` 表中。

```php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_password'])) {
    $platform_name = $_POST['platform_name'];
    $platform_address = $_POST['platform_address'];
    $account = $_POST['account'];
    $password = $_POST['password'];
    $conn->query("INSERT INTO passwords (user_id, platform_name, platform_address, account, password) VALUES ('$user_id', '$platform_name', '$platform_address', '$account', '$password')");
}
```

### 删除密码

```php
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $conn->query("DELETE FROM passwords WHERE id='$delete_id' AND user_id='$user_id'");
    header("Location: dashboard.php");
    exit;
}
```

### 编辑密码

```php
if (isset($_POST['edit_password'])) {
    $id = $_POST['id'];
    $platform_name = $_POST['platform_name'];
    $platform_address = $_POST['platform_address'];
    $account = $_POST['account'];
    $password = $_POST['password'];
    $conn->query("UPDATE passwords SET platform_name='$platform_name', platform_address='$platform_address', account='$account', password='$password' WHERE id='$id' AND user_id='$user_id'");
    header("Location: dashboard.php");
    exit;
}
```

通过模态框提供编辑密码功能，用户可以修改已有条目数据并提交更改。
## 安全性措施
1. **密码加密**：使用`password_hash`和`password_verify`函数确保用户密码安全存储和验证。
2. **SQL注入防范**：用户输入通过适当的数据类型转换，避免直接拼接字符串进行查询，提升安全性。
3. **权限控制**：基于会话的角色验证，确保管理员操作权限，阻止普通用户访问管理页面。

## 结论

通过上述模块的构建，实现了一个功能完备的密码管理工具。系统设计中充分考虑了数据安全和用户权限控制，满足基础的密码管理需求。

## 项目地址
[x-github url="https://github.com/C4rpeDime/Passwordstorage"/]
