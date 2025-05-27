<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>校园导航</title>
  <style>
    /* 新增动效样式 */
    body {
      margin: 0;
      font-family: 'Segoe UI', Arial, sans-serif;
    }
    nav {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 15px 30px;
      background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      position: relative;
    }
    .nav-links {
      display: flex;
      gap: 40px;
      position: relative;
      left: -60px;
    }
    .nav-links a {
      color: #2c3e50;
      font-weight: 600;
      text-decoration: none;
      padding: 10px 15px;
      border-radius: 25px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
    }
    .nav-links a:hover {
      background: rgba(255,255,255,0.3);
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    .nav-links a::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 50%;
      width: 0;
      height: 3px;
      background: #e74c3c;
      transition: all 0.3s;
    }
    .nav-links a:hover::after {
      width: 80%;
      left: 10%;
    }
    .profile {
      margin-left: auto;
      position: absolute;
      right: 30px;
    }
    .avatar {
      width: 55px;
      height: 55px;
      border-radius: 50%;
      border: 3px solid white;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
      transition: all 0.3s;
      cursor: pointer;
    }
    .avatar:hover {
      transform: scale(1.1) rotate(5deg);
      box-shadow: 0 8px 20px rgba(0,0,0,0.3);
    }
  </style>
</head>
<body>
  <nav>
    <div class="nav-links">
      <a href="./index.php">首页</a>
      <a href="./campus-wall.php">校园墙</a>
      <a href="./canteen.php">食堂评价</a>
      <a href="./second-hand.php">出闲置</a>
      <a href="./part-time.php">兼职</a>
      <a href="./help.php">求助</a>
    </div>
    <div class="profile">
      <a href="./profile.php">
        <img src="../img/tx.png" alt="个人头像" class="avatar">
      </a>
    </div>
  </nav>
</body>
</html>