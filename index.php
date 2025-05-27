<?php

// 数据库配置
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "campus";

// 创建数据库连接
try {
    // 第一步：连接到MySQL服务器
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 第二步：创建数据库（如果不存在）
    $conn->exec("CREATE DATABASE IF NOT EXISTS $dbname");

    // 第三步：连接到指定数据库
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 第四步：创建数据表
    $conn->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(7) UNIQUE NOT NULL,
        -- 学号唯一且不为空     且没有符号属性  int类型长度只有10位 需要bigint类型
        student_id BIGINT(11) UNSIGNED UNIQUE NOT NULL, 
        raw_password VARCHAR(20) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
    ");
} catch (PDOException $e) {
    die("连接失败: " . $e->getMessage());
}


// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 注册处理
    if (isset($_POST['register'])) {
        $username = trim($_POST['username']);
        $student_id = trim($_POST['student_id']);
        $raw_password = $_POST['password'];
        // 新增用户名长度校验
        if (strlen($username) < 1 || strlen($username) > 7) {
            $error = "用户名长度需在1-7位之间";
        }
        // 先验证学号格式
        if (!preg_match('/^\d{11}$/', $student_id)) {
            $error = "学号必须是11位数字";
        }
        // 验证密码长度
        elseif (strlen($raw_password) > 20) {
            $error = "密码长度不能超过20个字符";
        } elseif (strlen($raw_password) < 6) {
            $error = "密码长度不能少于6个字符";  // 新增密码最小长度验证
        } else {
            // 检查学号是否已存在
            $stmt = $conn->prepare("SELECT id FROM users WHERE student_id = ?");
            $stmt->execute([$student_id]);
            if ($stmt->rowCount() > 0) {
                $error = "学号已被注册";
            } else {
                $stmt = $conn->prepare("INSERT INTO users (username, student_id, raw_password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $student_id, $raw_password]);
                $success = "注册成功！";
            }
        }
    }
    // 登录处理
    elseif (isset($_POST['login'])) {
        if (empty($_POST['password'])) {
            $error = "密码不能为空";
        } else {
            $account = trim($_POST['username']);
            $password = $_POST['password'];

            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR student_id = ?");
            $stmt->execute([$account, $account]);

            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch();
                // 明文密码比对
                if ($password === $user['raw_password']) {
                    session_start();
                    $_SESSION['user'] = $user['username'];
                    header("Location: home.php");
                } else {
                    $error = "密码错误！";
                }
            } else {
                $error = "用户不存在！";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>校园通 - 登录注册</title>
    <style>
        /* 基础样式 */
        :root {
            --primary-color: #ff6b6b;
            --secondary-color: #4ecdc4;
        }

        body {
            margin: 0;
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        /* 登录容器样式 */
        .auth-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 400px;
            transform-style: preserve-3d;
            transition: all 0.3s;
        }

        /* 选项卡样式 */
        .tabs {
            display: flex;
            margin-bottom: 30px;
        }

        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }

        .tab.active {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        /* 输入框动画 */
        .input-group {
            margin-bottom: 25px;
            position: relative;
        }

        .input-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .input-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(255, 107, 107, 0.2);
        }

        /* 按钮样式 */
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
        }

        .login-btn {
            background: var(--primary-color);
            color: white;
        }

        .register-btn {
            background: var(--secondary-color);
            color: white;
            margin-top: 15px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* 忘记密码链接 */
        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }

        .forgot-password a {
            color: #666;
            text-decoration: none;
            transition: color 0.3s;
        }

        .forgot-password a:hover {
            color: var(--primary-color);
        }

        /* 提示信息样式 */
        @keyframes alertFade {
            0% {
                opacity: 1;
            }

            70% {
                opacity: 1;
            }

            100% {
                opacity: 0;
                transform: translate(-50%, -100%);
            }
        }

        .success-message {
            color: #ddd;
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <?php if (isset($error)): ?>
            <div class="global-alert" style="position: fixed; top: -220px; left: 50%; transform: translateX(-50%); 
           padding: 15px 30px; background: #ff6b6b; color: white; border-radius: 8px;
           box-shadow: 0 4px 15px rgba(255,107,107,0.3); z-index: 1000;
           animation: alertFade 2.5s forwards;">
                <?php echo $error ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="success-message" style="position: fixed; top: -220px; left: 50%; transform: translateX(-50%); 
           padding: 15px 30px; background:rgb(88, 220, 95); color: white; border-radius: 8px;
           box-shadow: 0 4px 15px rgba(255,107,107,0.3); z-index: 1000;
           animation: alertFade 2.5s forwards;">
                <?php echo $success ?>
            </div>
        <?php endif; ?>
        <div class=" tabs">
            <div class="tab active" onclick="switchTab('login')">登录</div>
            <div class="tab" onclick="switchTab('register')">注册</div>
        </div>

        <!-- 登录表单 -->
        <form id="loginForm" action="./layout/frame.php" method="POST" style="display: block;">
            <div class="input-group">
                <input type="text"
                    name="username"
                    placeholder="用户名/学号"
                    title="请输入用户名或11位学号"
                    minlength="1"
                    maxlength="11"
                    required>
            </div>
            <div class="input-group">
                <input type="password"
                    name="password"
                    placeholder="密码"
                    maxlength="20"
                    oninput="checkPasswordLength(this)"
                    title="请输入密码"
                    required>
                <div class="length-hint" style="display:none; color:#ff6b6b; font-size:12px; margin-top:5px;">
                    密码已达最大长度 (20 字符)
                </div>
            </div>
            <button type="submit" name="login" class="btn login-btn">立即登录</button>
            <div class="forgot-password">
                <a href="forgot_password.php">忘记密码？</a>
            </div>
        </form>

        <!-- 注册表单 -->
        <form id="registerForm" method="POST" style="display: none;">
            <div class="input-group">
                <input type="text"
                    name="username"
                    placeholder="创建用户名"
                    minlength="1"
                    maxlength="7"
                    title="请输入1-7位用户名"
                    required>
            </div>
            <div class="input-group">
                <input type="text"
                    name="student_id"
                    placeholder="输入学号"
                    pattern="\d{11}"
                    title="请输入11位数字学号"

                    required>
            </div>
            <div class="input-group">
                <div class="input-group">
                    <input type="password"
                        name="password"
                        placeholder="设置密码"
                        minlength="6"
                        maxlength="20"
                        oninput="checkPasswordLength(this)"
                        title="请输入6-20位密码"
                        required>
                    <div class="length-hint" style="display:none; color:#ff6b6b; font-size:12px; margin-top:5px;">
                        密码已达最大长度 (20 字符)
                    </div>
                </div>
                <button type="submit" name="register" class="btn register-btn">立即注册</button>
        </form>
    </div>

    <script>
        // 选项卡切换功能
        function switchTab(tabName) {
            const tabs = document.querySelectorAll('.tab');
            const forms = document.querySelectorAll('form');

            tabs.forEach(tab => tab.classList.remove('active'));
            forms.forEach(form => form.style.display = 'none');

            document.querySelector(`#${tabName}Form`).style.display = 'block';
            document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('active');
        }
        // 在现有脚本中添加功能
        function checkPasswordLength(input) {
            const hint = input.parentElement.querySelector('.length-hint');
            hint.style.display = input.value.length >= 20 ? 'block' : 'none';
        }

        // 初始化时绑定事件（在文档加载完成后）
        document.querySelectorAll('input[type="password"]').forEach(input => {
            input.addEventListener('input', function() {
                checkPasswordLength(this);
            });
        });
        // 提示消息隐藏
        document.querySelectorAll('.global-alert').forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 1s, transform 1s';
                alert.style.opacity = '0';
                alert.style.transform = 'translate(-50%, -100%)';
            }, 2000);
        });
    </script>
</body>

</html>