<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// 处理添加用户
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];
    $conn->query("INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')");
}

// 处理删除用户
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $userId = intval($_POST['user_id']);
    
    // 查询用户角色
    $result = $conn->query("SELECT role FROM users WHERE id = $userId");
    $user = $result->fetch_assoc();

    // 如果角色不是管理员，执行删除
    if ($user && $user['role'] !== 'admin') {
        $conn->query("DELETE FROM users WHERE id = $userId");
    }
}

// 处理修改密码
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'change_password') {
    $userId = intval($_POST['user_id']);
    $newPassword = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
    
    // 更新密码
    $conn->query("UPDATE users SET password = '$newPassword' WHERE id = $userId");
}

// 获取用户列表
$users = $conn->query("SELECT * FROM users");
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>管理员页面</title>
</head>
<body class="bg-light">
<div class="container mt-5">
    <h1 class="mb-4">管理员页面</h1>
    <form method="post" class="mb-4">
        <div class="form-group">
            <label for="username">用户名</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="password">密码</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="role">角色</label>
            <select class="form-control" id="role" name="role">
                <option value="user">用户</option>
                <option value="admin">管理员</option>
            </select>
        </div>
        <input type="hidden" name="action" value="add">
        <button type="submit" class="btn btn-primary">添加用户</button>
    </form>

    <h2 class="mb-4">用户列表</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>用户名</th>
                <th>角色</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $users->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['username']; ?></td>
                <td><?php echo $row['role']; ?></td>
                <td>
                    <?php if ($row['role'] !== 'admin'): ?>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn btn-danger btn-sm">删除</button>
                        </form>
                        <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#changePasswordModal<?php echo $row['id']; ?>">修改密码</button>

                        <!-- 修改密码的模态框 -->
                        <div class="modal fade" id="changePasswordModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="changePasswordModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="changePasswordModalLabel<?php echo $row['id']; ?>">修改密码</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <form method="post">
                                        <div class="modal-body">
                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="change_password">
                                            <div class="form-group">
                                                <label for="new_password">新密码</label>
                                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
                                            <button type="submit" class="btn btn-primary">保存修改</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <span class="text-muted">无法删除管理员</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
