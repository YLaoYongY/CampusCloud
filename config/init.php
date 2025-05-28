<?php
// 数据库初始化配置
function initializeDatabase()
{
  $servername = "localhost";
  $username = "root";
  $password = "";
  $dbname = "campus";

  try {
    // 第一步：连接到MySQL服务器
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 第二步：创建数据库（如果不存在）
    $conn->exec("CREATE DATABASE IF NOT EXISTS $dbname");

    // 第三步：连接到指定数据库
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 第四步：创建用户表
    $conn->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(7) UNIQUE,
            student_id BIGINT(11) UNSIGNED UNIQUE NOT NULL,
            raw_password VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_admin BOOLEAN DEFAULT 0 NOT NULL,
            id_card VARCHAR(18) UNIQUE NOT NULL
        )");

    // 第五步：创建帖子分类表
    $conn->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(50) UNIQUE NOT NULL,
            description VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

    // 第六步：创建帖子表（修改原表，添加category_id字段）
    $conn->exec("
      CREATE TABLE IF NOT EXISTS posts (
          id INT PRIMARY KEY AUTO_INCREMENT,
          title VARCHAR(255) NOT NULL,
          content TEXT NOT NULL,
          status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          user_id INT,
          likes INT DEFAULT 0,
          category_id INT,
          FOREIGN KEY (user_id) REFERENCES users(id),
          FOREIGN KEY (category_id) REFERENCES categories(id)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 第七步：创建评论表
    $conn->exec("
      CREATE TABLE IF NOT EXISTS comments (
          id INT PRIMARY KEY AUTO_INCREMENT,
          post_id INT,
          user_id INT,
          content TEXT NOT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (post_id) REFERENCES posts(id),
          FOREIGN KEY (user_id) REFERENCES users(id)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 第八步：创建点赞表
    $conn->exec("
      CREATE TABLE IF NOT EXISTS likes (
          id INT PRIMARY KEY AUTO_INCREMENT,
          post_id INT,
          user_id INT,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (post_id) REFERENCES posts(id),
          FOREIGN KEY (user_id) REFERENCES users(id),
          UNIQUE (post_id, user_id)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 第九步：创建举报表
    $conn->exec("
      CREATE TABLE IF NOT EXISTS reports (
          id INT PRIMARY KEY AUTO_INCREMENT,
          post_id INT,
          user_id INT,
          reason TEXT NOT NULL,
          status ENUM('pending', 'processed', 'rejected') DEFAULT 'pending',
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (post_id) REFERENCES posts(id),
          FOREIGN KEY (user_id) REFERENCES users(id)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 第十步：创建公告表
    $conn->exec("
      CREATE TABLE IF NOT EXISTS announcements (
          id INT PRIMARY KEY AUTO_INCREMENT,
          title VARCHAR(255) NOT NULL,
          content TEXT NOT NULL,
          user_id INT,
          is_pinned BOOLEAN DEFAULT 0,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          FOREIGN KEY (user_id) REFERENCES users(id)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 第十一步：创建用户收藏表
    $conn->exec("
      CREATE TABLE IF NOT EXISTS favorites (
          id INT PRIMARY KEY AUTO_INCREMENT,
          post_id INT,
          user_id INT,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (post_id) REFERENCES posts(id),
          FOREIGN KEY (user_id) REFERENCES users(id),
          UNIQUE (post_id, user_id)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 第十二步：初始化分类数据
    $categories = [
      ['name' => '学习交流', 'description' => '关于课程、学习资源和学术讨论的内容'],
      ['name' => '美食推荐', 'description' => '分享校园周边的美食和餐厅'],
      ['name' => '实习就业', 'description' => '求职经验、实习机会和就业信息'],
      ['name' => '兴趣爱好', 'description' => '各种兴趣小组和活动的讨论'],
      ['name' => '活动通知', 'description' => '校园活动和社团招新信息'],
      ['name' => '失物招领', 'description' => '寻找丢失物品或招领遗失物品']
    ];

    // 检查是否已有分类数据
    $checkStmt = $conn->query("SELECT COUNT(*) FROM categories");
    if ($checkStmt->fetchColumn() == 0) {
      foreach ($categories as $category) {
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
        $stmt->execute([
          ':name' => $category['name'],
          ':description' => $category['description']
        ]);
      }
    }

    // 初始化管理员账号
    $adminCheck = $conn->query("SELECT id FROM users WHERE username = 'admin'");
    if ($adminCheck->rowCount() == 0) {
      try {
        $stmt = $conn->prepare("INSERT INTO users 
                (username, student_id, raw_password, is_admin, id_card)
                VALUES (:user, :sid, :pwd, :admin, :idcard)");
        $stmt->execute([
          ':user' => 'admin',
          ':sid' => 00000000000,
          ':pwd' => 'adminpassword',
          ':admin' => 1,
          ':idcard' => '000000'
        ]);
      } catch (PDOException $e) {
        error_log("管理员初始化失败: " . $e->getMessage());
      }
    }

    // 添加校园墙特定的表结构
    $conn->exec("
      CREATE TABLE IF NOT EXISTS campus_wall_config (
          id INT PRIMARY KEY AUTO_INCREMENT,
          site_name VARCHAR(50) DEFAULT '校园墙',
          site_description VARCHAR(255) DEFAULT '分享校园生活，记录青春回忆',
          post_approval BOOLEAN DEFAULT 1 COMMENT '帖子是否需要审核',
          comment_approval BOOLEAN DEFAULT 0 COMMENT '评论是否需要审核',
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 初始化配置
    $configCheck = $conn->query("SELECT id FROM campus_wall_config");
    if ($configCheck->rowCount() == 0) {
      $conn->exec("INSERT INTO campus_wall_config (site_name, site_description) VALUES ('校园墙', '分享校园生活，记录青春回忆')");
    }

    return $conn;
  } catch (PDOException $e) {
    die("初始化失败: " . $e->getMessage());
  }
}
