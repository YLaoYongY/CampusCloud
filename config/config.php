<?php
$db_config = [
  'host' => 'localhost',
  'dbname' => 'campus',
  'username' => 'root',
  'password' => ''
];

// 创建数据库连接
try {
  $conn = new PDO(
    "mysql:host={$db_config['host']};dbname={$db_config['dbname']}",
    $db_config['username'],
    $db_config['password']
  );
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("数据库连接失败: " . $e->getMessage());
}
