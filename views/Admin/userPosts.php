<?php
require $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';

$userId = $_GET['user_id'];
$stmt = $conn->prepare("SELECT * FROM posts WHERE user_id = ?");
$stmt->execute([$userId]);
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>

<head>
  <title>用户帖子</title>
</head>

<body>
  <h1>用户发布的帖子</h1>
  <table>
    <?php foreach ($posts as $post): ?>
      <tr>
        <td><?= htmlspecialchars($post['title']) ?></td>
        <td><?= substr(htmlspecialchars($post['content']), 0, 50) ?>...</td>
        <td><?= $post['created_at'] ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</body>

</html>