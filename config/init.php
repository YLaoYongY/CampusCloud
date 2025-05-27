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
            username VARCHAR(7) UNIQUE NOT NULL,
            student_id BIGINT(11) UNSIGNED UNIQUE NOT NULL,
            raw_password VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_admin BOOLEAN DEFAULT 0 NOT NULL,
            id_card VARCHAR(6) NOT NULL
        )");
    $conn->exec("
      CREATE TABLE IF NOT EXISTS posts (
          id INT PRIMARY KEY AUTO_INCREMENT,
          title VARCHAR(255) NOT NULL,
          content TEXT NOT NULL,
          status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          user_id INT,
          FOREIGN KEY (user_id) REFERENCES users(id)
        )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    // 新增管理员初始化代码
    $adminCheck = $conn->query("SELECT id FROM users WHERE username = 'admin'");
    if ($adminCheck->rowCount() == 0) {
      $conn->exec("INSERT INTO users 
        (username, student_id, raw_password, is_admin, id_card)
        VALUES
        ('admin', 00000000000, 'adminpassword', 1, '000000')");
    }
    try {
      $stmt = $conn->prepare("REPLACE INTO users 
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
    return $conn;
  } catch (PDOException $e) {
    die("初始化失败: " . $e->getMessage());
  }
}
