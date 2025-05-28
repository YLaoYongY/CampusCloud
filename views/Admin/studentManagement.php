<?php


require $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';
// 文件导入处理（添加到PHP代码开头）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
  if (isset($_FILES['excel']) && $_FILES['excel']['error'] == UPLOAD_ERR_OK) {
    $file = $_FILES['excel']['tmp_name'];
    $handle = fopen($file, "r");

    // 跳过标题行
    fgetcsv($handle);

    $successCount = 0;
    $errorLog = [];

    while (($data = fgetcsv($handle)) !== FALSE) {
      // 验证数据完整性（只需要前3列数据）
      if (count($data) < 3) {
        $errorLog[] = "行数据不完整: " . implode(",", $data);
        continue;
      }

      // 处理字段
      $username = trim($data[0]);
      $student_id = trim($data[1]);
      $id_card = isset($data[2]) ? trim($data[2]) : '';

      // 基本验证（用户名、学号、身份证号不能为空）
      if (empty($username) || empty($student_id) || empty($id_card)) {
        $errorLog[] = "存在空字段: " . implode(",", $data);
        continue;
      }
      if (strlen($id_card) !== 18) {
        $errorLog[] = "身份证号长度错误: 用户名 {$username} 身份证号 {$id_card}";
        continue;
      }



      // 生成密码：取身份证号后6位
      $password = strlen($id_card) >= 6 ? substr($id_card, -6) : '';
      if (empty($password)) {
        $errorLog[] = "身份证号长度不足: {$username}";
        continue;
      }
      $checkStmt = $conn->prepare("SELECT id FROM users WHERE student_id = ?");
      $checkStmt->execute([$student_id]);
      if ($checkStmt->fetch()) {
        $errorLog[] = "学号已存在: {$student_id}（用户名 {$username}）";
        continue;
      }

      try {
        $stmt = $conn->prepare("INSERT INTO users 
                    (username, student_id, raw_password, id_card, is_admin, created_at)
                    VALUES (?, ?, ?, ?, 0, NOW())");  // 添加 is_admin 字段
        $stmt->execute([
          $username,
          $student_id,
          $password,
          $id_card
        ]);
        $successCount++;
      } catch (PDOException $e) {
        // 修改错误提示为学号重复
        if ($e->errorInfo[1] == 1062) {
          $errorLog[] = "学号已存在: {$student_id}（用户名 {$username}）";
        } else {
          $errorLog[] = "插入失败: {$student_id} - " . $e->getMessage();
        }
      }
    }
    fclose($handle);

    // 显示导入结果
    $success = "成功导入 {$successCount} 条数据";
    if (!empty($errorLog)) {
      $errorCount = count($errorLog);  // 新增此行计算错误数量
      $error = "失败 {$errorCount} 条: <br>" . implode("<br>", $errorLog);
    }
  }
}
// 下载模板文件处理（添加到PHP代码开头）
if (isset($_GET['download_template'])) {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=student_template.csv');

  $output = fopen('php://output', 'w');

  // CSV头部
  fputcsv($output, ['用户名', '学号', '身份证号']);

  // 示例数据
  $examples = [
    ['张三', '20230200016', '110101200001011234'],
    ['李四', '20210200066', '340304199901016543'],
    ['王五', '20220200333', '510125200008087890']
  ];

  foreach ($examples as $row) {
    fputcsv($output, $row);
  }

  fclose($output);
  exit;
}
// 分页参数处理
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// 搜索条件处理
$search = [
  'student_id' => isset($_GET['student_id']) ? $_GET['student_id'] : '',
  'username' => isset($_GET['username']) ? $_GET['username'] : '',
  'is_admin' => isset($_GET['is_admin']) ? $_GET['is_admin'] : ''
];

// 构建查询条件
$where = [];
$params = [];
if (!empty($search['student_id'])) {
  $where[] = "student_id LIKE ?";
  $params[] = "%{$search['student_id']}%";
}
if (!empty($search['username'])) {
  $where[] = "username LIKE ?";
  $params[] = "%{$search['username']}%";
}
if (isset($search['is_admin']) && $search['is_admin'] !== '') {
  $where[] = "is_admin = ?";
  $params[] = intval($search['is_admin']);
}

// 处理删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
  $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
  $stmt->execute([$_POST['user_id']]);
}

// 获取总记录数
$totalQuery = "SELECT COUNT(*) FROM users" . ($where ? " WHERE " . implode(" AND ", $where) : "");
$stmt = $conn->prepare($totalQuery);
$stmt->execute($params);
$total = $stmt->fetchColumn();

// 获取分页数据
$query = "SELECT * FROM users" . ($where ? " WHERE " . implode(" AND ", $where) : "") . " LIMIT $offset, $perPage";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>学生管理</title>
  <style>
    .student-table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
    }

    .student-table th,
    .student-table td {
      padding: 12px;
      border: 1px solid #ddd;
      text-align: left;
    }

    .student-table th {
      background-color: #f8f9fa;
    }

    .search-form {
      margin: 20px 0;
      padding: 20px;
      background: #f8f9fa;
      border-radius: 8px;
    }

    .pagination {
      margin-top: 20px;
    }

    .action-buttons button {
      margin: 0 5px;
      padding: 5px 10px;
      cursor: pointer;
    }

    .toolbar {
      display: flex;
      gap: 20px;
      align-items: flex-end;
      margin-bottom: 30px;
    }

    .btn-import,
    .btn-download {
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
    }

    .btn-import {
      background: #4CAF50;
      color: white;
    }

    .btn-download {
      background: #2196F3;
      color: white;
      margin-left: 15px;
    }

    .btn-import:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
    }

    .btn-download:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(33, 150, 243, 0.4);
    }

    .file-input {
      padding: 10px;
      border: 2px dashed #ddd;
      border-radius: 8px;
      transition: border-color 0.3s;
    }

    .file-input:hover {
      border-color: #4CAF50;
    }

    @keyframes ripple {
      to {
        transform: scale(4);
        opacity: 0;
      }
    }

    .loading {
      position: absolute;
      background: rgba(255, 255, 255, 0.9);
      padding: 10px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .alert {
      padding: 15px;
      margin: 20px;
      border-radius: 8px;
      font-size: 14px;
    }

    .success {
      background: #dff0d8;
      color: #3c763d;
      border: 1px solid #d6e9c6;
    }

    .error {
      background: #f2dede;
      color: #a94442;
      border: 1px solid #ebccd1;
    }

    .alert {
      padding: 15px;
      margin: 20px;
      border-radius: 8px;
      font-size: 14px;
      transition: all 1s ease;
      opacity: 1;
      visibility: visible;
    }
  </style>
</head>

<body>
  <?php if (isset($success)): ?>
    <div class="alert success"><?= $success ?></div>
  <?php endif; ?>

  <?php if (isset($error)): ?>
    <div class="alert error"><?= $error ?></div>
  <?php endif; ?>

  <!-- 搜索表单 -->
  <form class="search-form" method="get">
    <input type="text" name="student_id" placeholder="学号" value="<?= htmlspecialchars($search['student_id']) ?>">
    <input type="text" name="username" placeholder="用户名" value="<?= htmlspecialchars($search['username']) ?>">
    <select name="is_admin">
      <option value="">全部权限</option>
      <option value="1" <?= (isset($search['is_admin']) && $search['is_admin'] === '1') ? 'selected' : '' ?>>管理员</option>
      <option value="0" <?= (isset($search['is_admin']) && $search['is_admin'] === '0') ? 'selected' : '' ?>>普通用户</option>
    </select>
    <button type="submit">搜索</button>
  </form>
  <div class="import-section">
    <form method="post" enctype="multipart/form-data" class="import-form" onsubmit="showLoading()">
      <input type="file" name="excel" accept=".csv" required class="file-input">
      <button type="submit" name="import" class="btn-import">一键导入</button>
      <a href="?download_template=1" class="btn-download">下载模板</a>
      <div class="loading" style="display:none">导入中...</div>
    </form>
  </div>
  <!-- 学生表格 -->
  <table class="student-table">
    <thead>
      <tr>
        <th>学号</th>
        <th>用户名</th>
        <th>注册时间</th>
        <th>权限</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($students as $student): ?>
        <tr>
          <td><?= htmlspecialchars($student['student_id']) ?></td>
          <td><?= htmlspecialchars($student['username']) ?></td>
          <td><?= $student['created_at'] ?></td>
          <td>
            <form method="post" style="display:inline">
              <input type="hidden" name="user_id" value="<?= $student['id'] ?>">
              <select name="is_admin" onchange="this.form.submit()">
                <option value="0" <?= !$student['is_admin'] ? 'selected' : '' ?>>普通用户</option>
                <option value="1" <?= $student['is_admin'] ? 'selected' : '' ?>>管理员</option>
              </select>
            </form>
          </td>
          <td class="action-buttons">
            <button onclick="viewPosts(<?= $student['id'] ?>)">查看帖子</button>
            <form method="post" style="display:inline">
              <input type="hidden" name="user_id" value="<?= $student['id'] ?>">
              <button type="submit" name="delete">删除</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- 分页导航 -->
  <div class="pagination">
    <?php
    $totalPages = ceil($total / $perPage);
    for ($i = 1; $i <= $totalPages; $i++):
      $active = $i == $page ? 'style="color:red"' : '';
    ?>
      <a href="?page=<?= $i ?>&<?= http_build_query($search) ?>" <?= $active ?>><?= $i ?></a>
    <?php endfor; ?>
  </div>

  <script>
    function viewPosts(userId) {
      window.open(`userPosts.php?user_id=${userId}`, '_blank');
    }
    // 新增加载动画
    function showLoading() {
      document.querySelector('.loading').style.display = 'block';
      setTimeout(() => {
        document.querySelector('.loading').style.display = 'none';
      }, 2000);
    }
    // 新增加载动画
    function showLoading() {
      document.querySelector('.loading').style.display = 'block';
      setTimeout(() => {
        document.querySelector('.loading').style.display = 'none';
      }, 2000);
    }

    // 新增提示自动隐藏
    document.addEventListener('DOMContentLoaded', function() {
      setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
          alert.style.opacity = '0';
          setTimeout(() => alert.remove(), 1000); // 淡出动画结束后移除元素
        });
      }, 10000); // 10秒后开始隐藏
    });
  </script>
</body>

</html>