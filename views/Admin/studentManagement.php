<?php
require $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';

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
  </style>
</head>

<body>


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
  </script>
</body>

</html>