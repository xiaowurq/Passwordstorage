<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$password_update_message = "";

// 修改用户密码
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $new_password = $_POST['new_password'];
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT); // 加密密码
    $conn->query("UPDATE users SET password='$hashed_password' WHERE id='$user_id'");

    // 注销用户会话
    session_destroy();
    header("Location: login.php"); // 重定向到登录页面
    exit;
}

// 添加密码
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_password'])) {
    $platform_name = $_POST['platform_name'];
    $platform_address = $_POST['platform'];
    $account = $_POST['account'];
    $password = $_POST['password'];
    $conn->query("INSERT INTO passwords (user_id, platform_name, platform_address, account, password) VALUES ('$user_id', '$platform_name', '$platform_address', '$account', '$password')");
}

// 删除密码
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $conn->query("DELETE FROM passwords WHERE id='$delete_id' AND user_id='$user_id'");
    header("Location: dashboard.php");
    exit;
}

// 编辑密码
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

// 搜索功能
$search_query = '';
$passwords = [];
$search_result_count = 0;
$current_tab = 'home'; // 默认选项卡

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $search_query = $_POST['search_query'];
    $passwords = $conn->query("SELECT * FROM passwords WHERE user_id='$user_id' AND (platform_name LIKE '%$search_query%' OR platform_address LIKE '%$search_query%' OR account LIKE '%$search_query%')");
    $search_result_count = $passwords->num_rows;
    $current_tab = 'myAccounts'; 
} else {
    $passwords = $conn->query("SELECT * FROM passwords WHERE user_id='$user_id'");
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        .content {
            flex: 1;
        }
    </style>
    <title>用户仪表板</title>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">密码管理器</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">登出</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-4 content">
        <h1 class="text-center">用户仪表板</h1>

        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_tab == 'home' ? 'active' : ''; ?>" data-toggle="tab" href="#home">首页</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_tab == 'addAccount' ? 'active' : ''; ?>" data-toggle="tab" href="#addAccount">添加信息</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_tab == 'myAccounts' ? 'active' : ''; ?>" data-toggle="tab" href="#myAccounts">我的存储</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_tab == 'changePassword' ? 'active' : ''; ?>" data-toggle="tab" href="#changePassword">修改密码</a>
            </li>
        </ul>

        <div class="tab-content mt-3">
            <div id="home" class="tab-pane fade <?php echo $current_tab == 'home' ? 'show active' : ''; ?>">
                <div class="card">
                    <div class="card-header">
                        <h5>欢迎来到密码管理器</h5>
                    </div>
                    <div class="card-body">
                        <p>这是一个简易的密码管理器，帮助你安全地存储和管理你的各种在线账号的登录信息。</p>
                        <p>使用此工具，你可以轻松添加、编辑和删除账号信息，确保所有重要的密码都有条不紊地管理。</p>
                        <p>作者：C4rpeDime，个人博客：www.1042.net</p>
                    </div>
                </div>
            </div>

            <div id="addAccount" class="tab-pane fade <?php echo $current_tab == 'addAccount' ? 'show active' : ''; ?>">
                <div class="card">
                    <div class="card-header">
                        <h5>添加账号</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="platform_name">平台名称</label>
                                <input type="text" class="form-control" id="platform_name" name="platform_name" required>
                            </div>
                            <div class="form-group">
                                <label for="platform_address">平台地址</label>
                                <input type="text" class="form-control" id="platform_address" name="platform_address" required>
                            </div>
                            <div class="form-group">
                                <label for="account">账号</label>
                                <input type="text" class="form-control" id="account" name="account" required>
                            </div>
                            <div class="form-group">
                                <label for="password">密码</label>
                                <input type="text" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" name="add_password" class="btn btn-primary btn-block">添加账号</button>
                        </form>
                    </div>
                </div>
            </div>

            <div id="myAccounts" class="tab-pane fade <?php echo $current_tab == 'myAccounts' ? 'show active' : ''; ?>">
                <div class="card">
                    <div class="card-header">
                        <h5>我的账号信息</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" class="mb-3">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="搜索平台名称、地址或账号" name="search_query" value="<?php echo htmlspecialchars($search_query); ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="submit" name="search">搜索</button>
                                </div>
                            </div>
                        </form>

                        <?php if ($search_query && $search_result_count == 0) : ?>
                            <div class="alert alert-warning" role="alert">
                                未找到相关账号信息。
                            </div>
                        <?php endif; ?>

                        <div style="overflow-x:auto;">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>平台名称</th>
                                        <th>平台地址</th>
                                        <th>账号</th>
                                        <th>密码</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $index = 1; ?>
                                    <?php while ($row = $passwords->fetch_assoc()) : ?>
                                        <tr>
                                            <td><?php echo $index++; ?></td>
                                            <td><?php echo htmlspecialchars($row['platform_name']); ?></td>
                                            <td>
                                                <a href="<?php echo htmlspecialchars($row['platform_address']); ?>" target="_blank" class="btn btn-link">立即跳转</a>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['account']); ?></td>
                                            <td><?php echo htmlspecialchars($row['password']); ?></td>
                                            <td>
                                                <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#editModal<?php echo $row['id']; ?>">编辑</button>
                                                <a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('确定要删除吗？');">删除</a>
                                            </td>
                                        </tr>

                                        <!-- 编辑模态框 -->
                                        <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <form method="post">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editModalLabel">编辑账号信息</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                            <div class="form-group">
                                                                <label for="edit_platform_name">平台名称</label>
                                                                <input type="text" class="form-control" id="edit_platform_name" name="platform_name" value="<?php echo htmlspecialchars($row['platform_name']); ?>" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="edit_platform_address">平台地址</label>
                                                                <input type="text" class="form-control" id="edit_platform_address" name="platform_address" value="<?php echo htmlspecialchars($row['platform_address']); ?>" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="edit_account">账号</label>
                                                                <input type="text" class="form-control" id="edit_account" name="account" value="<?php echo htmlspecialchars($row['account']); ?>" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="edit_password">密码</label>
                                                                <input type="text" class="form-control" id="edit_password" name="password" value="<?php echo htmlspecialchars($row['password']); ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
                                                            <button type="submit" name="edit_password" class="btn btn-primary">保存更改</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div id="changePassword" class="tab-pane fade <?php echo $current_tab == 'changePassword' ? 'show active' : ''; ?>">
                <div class="card">
                    <div class="card-header">
                        <h5>修改密码</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($password_update_message)): ?>
                            <div class="alert alert-success"><?php echo $password_update_message; ?></div>
                        <?php endif; ?>
                        <form method="post">
                            <div class="form-group">
                                <label for="new_password">新密码</label>
                                <input type="text" class="form-control" id="new_password" name="new_password" required> <!-- 明文输入 -->
                            </div>
                            <button type="submit" name="update_password" class="btn btn-primary btn-block">更新密码</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <footer class="bg-light text-center text-lg-start">
        <div class="text-center p-3" style="background-color: rgba(0,0, 0, 0.1);">
            ©2023 密码管理器. 保留所有权利.
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
