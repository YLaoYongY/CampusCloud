<?php
// 移除顶部的数据库连接代码，直接引入配置文件
require $_SERVER['DOCUMENT_ROOT'] . '/config.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>数据总览</title>
  <style>
    .stats-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      padding: 20px;
    }

    .stat-card {
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      display: flex;
      align-items: center;
    }

    .card-icon {
      font-size: 32px;
      margin-right: 15px;
      padding: 10px;
      border-radius: 8px;
    }

    .card-content {
      flex-grow: 1;
    }

    .card-title {
      color: #666;
      font-size: 14px;
      margin-bottom: 5px;
    }

    .card-value {
      font-size: 24px;
      font-weight: bold;
      color: #333;
    }
  </style>
</head>

<body>
  <div class="stats-container">
    <!-- 待审核帖子 -->
    <div class="stat-card">
      <div class="card-icon" style="background:#ffd70033;color:#ffd700;">📑</div>
      <div class="card-content">
        <div class="card-title">待审核帖子</div>
        <div class="card-value"><?php echo getPendingPosts(); ?></div>
      </div>
    </div>

    <!-- 今日新增 -->
    <div class="stat-card">
      <div class="card-icon" style="background:#4ecdc433;color:#4ecdc4;">🆕</div>
      <div class="card-content">
        <div class="card-title">今日新增</div>
        <div class="card-value"><?php echo getTodayPosts(); ?></div>
      </div>
    </div>

    <!-- 当前在线 -->
    <div class="stat-card">
      <div class="card-icon" style="background:#3498db33;color:#3498db;">👥</div>
      <div class="card-content">
        <div class="card-title">当前在线</div>
        <div class="card-value"><?php echo getOnlineUsers(); ?></div>
      </div>
    </div>

    <!-- 注册用户 -->
    <div class="stat-card">
      <div class="card-icon" style="background:#2ecc7133;color:#2ecc71;">📈</div>
      <div class="card-content">
        <div class="card-title">注册用户</div>
        <div class="card-value"><?php echo getTotalUsers(); ?></div>
      </div>
    </div>
  </div>

  <?php
  // 数据库连接
  require '../../config.php';

  function getPendingPosts()
  {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE status = 'pending'");
    $stmt->execute();
    return $stmt->fetchColumn();
  }

  function getTodayPosts()
  {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    return $stmt->fetchColumn();
  }

  function getOnlineUsers()
  {
    // 根据session记录获取在线用户数（示例）
    return 0; // 需实现具体逻辑
  }

  function getTotalUsers()
  {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    return $stmt->fetchColumn();
  }
  ?>
</body>

</html>