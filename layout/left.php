<!DOCTYPE html>
<html lang="zh-CN">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>管理导航</title>
  <style>
    html,
    body {
      margin: 0;
      padding: 0;
      height: 100%;
    }

    :root {
      --primary-color: #2c3e50;
      --accent-color: #3498db;
      --hover-bg: rgba(255, 255, 255, 0.05);
    }

    .admin-sidebar {
      width: 200px;
      height: 100vh;
      background: var(--primary-color);
      position: fixed;
      box-shadow: 5px 0 15px rgba(0, 0, 0, 0.2);
    }

    .sidebar-logo {
      display: flex;
      flex-direction: column;
      /* 改为垂直排列 */
      align-items: center;
      /* 水平居中 */
      padding: 20px 0;
      /* 调整上下间距 */
      text-align: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar-logo img {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      margin: 0 0 10px 0;
      /* 下边距10px */
    }


    .platform-name {
      color: #ecf0f1;
      font-size: 16px;
      font-weight: bold;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
      line-height: 1.2;
      /* 恢复默认行高 */
    }

    .nav-menu {
      padding: 15px 0;
      height: calc(100vh - 100px);
      overflow-y: auto;
    }

    .menu-item {
      position: relative;
      margin: 8px 0;
    }

    .menu-link {
      display: flex;
      align-items: center;
      padding: 14px 20px;
      color: #ecf0f1;
      text-decoration: none;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .menu-link:hover {
      background: var(--hover-bg);
      transform: translateX(10px);
    }

    .sub-menu {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease-out;
    }

    .menu-item.active .sub-menu {
      max-height: 500px;
    }

    .menu-link i {
      margin-right: 12px;
      transition: transform 0.3s;
    }

    .menu-item.active .menu-link i {
      transform: rotate(90deg);
    }
  </style>
</head>

<body>
  <nav class="admin-sidebar">
    <div class="sidebar-logo">
      <img src="../img/logo.jpeg" alt="管理后台">
      <span class="platform-name">农职大管理平台</span>
    </div>

    <ul class="nav-menu">
      <li class="menu-item">
        <a href="../views/Admin/managementPlatform.php" target="mainFrame" class="menu-link">
          <i>👤</i>首页
        </a>
      </li>
      <!-- 角色管理 -->
      <li class="menu-item">
        <a href="#" class="menu-link">
          <i>👤</i>角色管理
        </a>
      </li>

      <!-- 校园墙管理 -->
      <li class="menu-item">
        <a href="#" class="menu-link" onclick="toggleSubMenu(this)">
          <i>📋</i>校园墙管理
          <span style="margin-left:auto">▶</span>
        </a>
        <ul class="sub-menu">
          <li><a href="../views/Admin/admin.php" target="mainFrame" class="menu-link">帖子审核</a></li>
          <li><a href="#" class="menu-link">内容管理</a></li>
          <li><a href="#" class="menu-link">评论管理</a></li>
        </ul>
      </li>

      <!-- 其他菜单项 -->
      <li class="menu-item">
        <a href="#" class="menu-link">
          <i>🍴</i>食堂管理
        </a>
      </li>
      <li class="menu-item">
        <a href="#" class="menu-link">
          <i>♻️</i>闲置管理
        </a>
      </li>
      <li class="menu-item">
        <a href="#" class="menu-link">
          <i>🎉</i>活动管理
        </a>
      </li>
      <li class="menu-item">
        <a href="#" class="menu-link">
          <i>💼</i>兼职管理
        </a>
      </li>
      <li class="menu-item">
        <a href="#" class="menu-link">
          <i>📢</i>发布公告
        </a>
      </li>
    </ul>
  </nav>

  <script>
    function toggleSubMenu(link) {
      const parent = link.parentElement;
      parent.classList.toggle('active');

      // 关闭其他展开的菜单
      document.querySelectorAll('.menu-item').forEach(item => {
        if (item !== parent) {
          item.classList.remove('active');
        }
      });
    }
  </script>
</body>

</html>