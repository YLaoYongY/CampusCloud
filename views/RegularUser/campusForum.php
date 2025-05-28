<?php
session_start();

// 数据库连接配置
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "campus";

try {
  $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("数据库连接失败: " . $e->getMessage());
}


// 处理AJAX请求
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
  header('Content-Type: application/json');

  try {
    switch ($_POST['action']) {
      case 'add_post':
        if (!isset($_SESSION['user_id'])) {
          throw new Exception("请先登录");
        }

        $title = $_POST['title'];
        $content = $_POST['content'];
        $category = $_POST['category'];
        $user_id = $_SESSION['user_id'];
        $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;

        if ($is_anonymous) {
          $user_id = null; // 匿名发布
        }

        $stmt = $conn->prepare("INSERT INTO posts (title, content, user_id, category) 
                                      VALUES (:title, :content, :user_id, :category)");
        $stmt->execute([
          ':title' => $title,
          ':content' => $content,
          ':user_id' => $user_id,
          ':category' => $category
        ]);

        echo json_encode(['success' => true, 'message' => '帖子发布成功']);
        break;

      case 'add_comment':
        if (!isset($_SESSION['user_id'])) {
          throw new Exception("请先登录");
        }

        $post_id = $_POST['post_id'];
        $content = $_POST['content'];
        $user_id = $_SESSION['user_id'];

        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) 
                                      VALUES (:post_id, :user_id, :content)");
        $stmt->execute([
          ':post_id' => $post_id,
          ':user_id' => $user_id,
          ':content' => $content
        ]);

        echo json_encode(['success' => true, 'message' => '评论成功']);
        break;

      case 'toggle_like':
        if (!isset($_SESSION['user_id'])) {
          throw new Exception("请先登录");
        }

        $post_id = $_POST['post_id'];
        $user_id = $_SESSION['user_id'];

        // 检查是否已经点赞
        $stmt = $conn->prepare("SELECT id FROM likes WHERE post_id = :post_id AND user_id = :user_id");
        $stmt->execute([
          ':post_id' => $post_id,
          ':user_id' => $user_id
        ]);

        if ($stmt->rowCount() > 0) {
          // 取消点赞
          $conn->beginTransaction();

          $stmt = $conn->prepare("DELETE FROM likes WHERE post_id = :post_id AND user_id = :user_id");
          $stmt->execute([
            ':post_id' => $post_id,
            ':user_id' => $user_id
          ]);

          $stmt = $conn->prepare("UPDATE posts SET likes = likes - 1 WHERE id = :post_id");
          $stmt->execute([':post_id' => $post_id]);

          $conn->commit();

          echo json_encode(['success' => true, 'action' => 'unlike', 'message' => '已取消点赞']);
        } else {
          // 点赞
          $conn->beginTransaction();

          $stmt = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (:post_id, :user_id)");
          $stmt->execute([
            ':post_id' => $post_id,
            ':user_id' => $user_id
          ]);

          $stmt = $conn->prepare("UPDATE posts SET likes = likes + 1 WHERE id = :post_id");
          $stmt->execute([':post_id' => $post_id]);

          $conn->commit();

          echo json_encode(['success' => true, 'action' => 'like', 'message' => '点赞成功']);
        }
        break;

      default:
        throw new Exception("未知操作");
    }
  } catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '数据库错误: ' . $e->getMessage()]);
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  }
  exit;
}

// 获取热门帖子排行
$stmt = $conn->prepare("SELECT p.*, u.username, COUNT(l.id) as likes_count 
                      FROM posts p 
                      LEFT JOIN users u ON p.user_id = u.id
                      LEFT JOIN likes l ON p.id = l.post_id
                      GROUP BY p.id 
                      ORDER BY likes_count DESC 
                      LIMIT 5");
$stmt->execute();
$hot_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 获取最新帖子
$stmt = $conn->prepare("SELECT p.*, u.username, COUNT(l.id) as likes_count 
                      FROM posts p 
                      LEFT JOIN users u ON p.user_id = u.id
                      LEFT JOIN likes l ON p.id = l.post_id
                      GROUP BY p.id 
                      ORDER BY p.created_at DESC 
                      LIMIT 10");
$stmt->execute();
$new_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>校园墙 - 青春记忆的分享平台</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Tailwind 配置 -->
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#4F46E5', // 主色调：深紫色，代表青春活力与智慧
            secondary: '#EC4899', // 辅助色：粉色，代表活力与温暖
            accent: '#10B981', // 强调色：绿色，代表希望与成长
            dark: '#1F2937', // 深色：深灰色，用于标题和重要文本
            light: '#F9FAFB', // 浅色：接近白色，用于背景
          },
          fontFamily: {
            inter: ['Inter', 'sans-serif'],
          },
          animation: {
            'float': 'float 3s ease-in-out infinite',
            'bounce-slow': 'bounce 3s infinite',
            'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
          },
          keyframes: {
            float: {
              '0%, 100%': {
                transform: 'translateY(0)'
              },
              '50%': {
                transform: 'translateY(-10px)'
              },
            }
          }
        },
      }
    }
  </script>

  <style type="text/tailwindcss">
    @layer utilities {
            .content-auto {
                content-visibility: auto;
            }
            .text-shadow {
                text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .text-shadow-lg {
                text-shadow: 0 4px 8px rgba(0,0,0,0.12), 0 2px 4px rgba(0,0,0,0.08);
            }
            .backdrop-blur {
                backdrop-filter: blur(8px);
            }
            .scrollbar-hide::-webkit-scrollbar {
                display: none;
            }
            .scrollbar-hide {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }
            .card-hover {
                @apply transition-all duration-300 hover:shadow-xl hover:-translate-y-1;
            }
            .gradient-text {
                @apply bg-clip-text text-transparent bg-gradient-to-r from-primary to-secondary;
            }
        }
    </style>
</head>

<body class="font-inter bg-light text-dark antialiased min-h-screen flex flex-col">
  <!-- 英雄区域 -->
  <section class="pt-16 pb-8 md:pt-20 md:pb-12 bg-gradient-to-br from-primary/5 to-secondary/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center">
        <h1 class="text-[clamp(2rem,4vw,3rem)] font-bold leading-tight text-shadow-lg mb-4">
          <span class="gradient-text">校园墙</span>
        </h1>
        <p class="mt-4 text-[clamp(1rem,2vw,1.25rem)] text-gray-600 max-w-3xl mx-auto">
          分享校园生活，记录青春回忆
        </p>
      </div>
    </div>
  </section>

  <!-- 热门帖子区域 -->
  <section id="hot" class="py-8 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
          <h2 class="text-2xl font-bold mb-2">热门<span class="gradient-text">帖子排行</span></h2>
          <p class="text-gray-600 max-w-2xl">这些是当前最受关注的帖子，参与讨论，分享你的看法</p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-2">
          <button class="px-4 py-2 rounded-md bg-primary text-white text-sm font-medium">今日</button>
          <button class="px-4 py-2 rounded-md bg-white text-gray-600 text-sm font-medium hover:bg-gray-100">本周</button>
          <button class="px-4 py-2 rounded-md bg-white text-gray-600 text-sm font-medium hover:bg-gray-100">本月</button>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($hot_posts as $post): ?>
          <!-- 热门帖子卡片 -->
          <div class="bg-white rounded-xl overflow-hidden shadow-md card-hover">
            <div class="relative">
              <img src="https://picsum.photos/id/<?= rand(1, 100) ?>/800/400" alt="校园活动照片" class="w-full h-48 object-cover">
              <div class="absolute top-4 left-4 bg-primary text-white text-xs font-semibold px-2.5 py-0.5 rounded">热门</div>
            </div>
            <div class="p-5">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                  <img src="https://picsum.photos/id/<?= rand(60, 70) ?>/100/100" alt="用户头像" class="w-8 h-8 rounded-full object-cover">
                  <div class="ml-2">
                    <h4 class="font-medium text-sm"><?= $post['username'] ?: '匿名用户' ?></h4>
                    <p class="text-gray-500 text-xs"><?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></p>
                  </div>
                </div>
                <div class="flex items-center space-x-3">
                  <button class="like-btn text-gray-400 hover:text-red-500 transition-colors text-sm" data-post-id="<?= $post['id'] ?>">
                    <i class="fa fa-heart-o"></i>
                    <span class="ml-1"><?= $post['likes_count'] ?></span>
                  </button>
                  <button class="text-gray-400 hover:text-primary transition-colors text-sm comment-btn" data-post-id="<?= $post['id'] ?>">
                    <i class="fa fa-comment-o"></i>
                    <span class="ml-1">查看评论</span>
                  </button>
                </div>
              </div>
              <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($post['title']) ?></h3>
              <p class="text-gray-600 mb-3 text-sm line-clamp-2"><?= htmlspecialchars(substr($post['content'], 0, 100)) ?>...</p>
              <div class="flex items-center justify-between">
                <span class="text-xs px-2 py-1 bg-primary/10 text-primary rounded-full"><?= htmlspecialchars($post['category']) ?: '综合' ?></span>
                <button class="text-primary text-xs font-medium hover:underline">阅读全文</button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- 帖子列表区域 -->
  <section id="posts" class="py-8 bg-white flex-grow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
          <h2 class="text-2xl font-bold mb-2">最新<span class="gradient-text">帖子</span></h2>
          <p class="text-gray-600 max-w-2xl">浏览最新发布的帖子，参与校园话题讨论</p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-2">
          <div class="relative">
            <select id="sort-select" class="appearance-none bg-gray-50 border border-gray-300 text-gray-700 py-2 pl-3 pr-10 rounded-md leading-tight focus:outline-none focus:bg-white focus:border-primary text-sm">
              <option value="newest">最新发布</option>
              <option value="most_liked">最多点赞</option>
              <option value="most_commented">最多评论</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
              <i class="fa fa-chevron-down text-xs"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- 帖子列表 -->
      <div class="space-y-5" id="posts-container">
        <?php foreach ($new_posts as $post): ?>
          <!-- 帖子 -->
          <div class="bg-white rounded-xl shadow-sm overflow-hidden card-hover border border-gray-100" data-post-id="<?= $post['id'] ?>">
            <div class="p-5">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                  <img src="https://picsum.photos/id/<?= rand(60, 70) ?>/100/100" alt="用户头像" class="w-8 h-8 rounded-full object-cover">
                  <div class="ml-2">
                    <h4 class="font-medium text-sm"><?= $post['username'] ?: '匿名用户' ?></h4>
                    <p class="text-gray-500 text-xs"><?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></p>
                  </div>
                </div>
              </div>
              <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($post['title']) ?></h3>
              <p class="text-gray-600 mb-3 text-sm"><?= htmlspecialchars($post['content']) ?></p>
              <div class="flex flex-wrap gap-1 mb-3">
              </div>
              <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                <div class="flex items-center space-x-5">
                  <button class="like-btn text-gray-400 hover:text-red-500 transition-colors flex items-center text-sm" data-post-id="<?= $post['id'] ?>">
                    <i class="fa fa-heart-o"></i>
                    <span class="ml-1"><?= $post['likes_count'] ?></span>
                  </button>
                  <button class="comment-btn text-gray-400 hover:text-primary transition-colors flex items-center text-sm" data-post-id="<?= $post['id'] ?>">
                    <i class="fa fa-comment-o"></i>
                    <span class="ml-1">查看评论</span>
                  </button>
                  <button class="text-gray-400 hover:text-gray-600 transition-colors flex items-center text-sm">
                    <i class="fa fa-share-alt"></i>
                    <span class="ml-1">分享</span>
                  </button>
                </div>
                <button class="text-gray-400 hover:text-gray-600 transition-colors text-sm">
                  <i class="fa fa-bookmark-o"></i>
                </button>
              </div>

              <!-- 评论区 -->
              <div class="comments-container mt-4 hidden">
                <div class="bg-gray-50 p-4 rounded-lg">
                  <h4 class="font-medium mb-3">评论</h4>
                  <div class="comments-list space-y-3">
                    <!-- 评论会通过AJAX加载 -->
                  </div>
                  <div class="mt-3">
                    <div class="flex">
                      <input type="text" class="flex-1 border border-gray-300 rounded-l-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" placeholder="发表评论...">
                      <button class="add-comment-btn bg-primary text-white px-4 py-2 rounded-r-lg hover:bg-primary/90 transition-colors" data-post-id="<?= $post['id'] ?>">
                        发送
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- 加载更多 -->
      <div class="mt-8 text-center">
        <button id="load-more-btn" class="inline-flex items-center px-5 py-2 border border-primary text-base font-medium rounded-md text-primary bg-white hover:bg-primary/5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all duration-200">
          <i class="fa fa-refresh mr-2"></i>
          加载更多
        </button>
      </div>
    </div>
  </section>

  <!-- 页脚 -->
  <footer class="bg-dark text-white pt-12 pb-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div>
          <div class="flex items-center mb-3">
            <i class="fa fa-commenting-o text-primary text-3xl mr-2"></i>
            <span class="text-xl font-bold">校园墙</span>
          </div>
          <p class="text-gray-400 mb-3 text-sm">分享校园生活，记录青春回忆</p>
          <div class="flex space-x-3">
            <a href="#" class="text-gray-400 hover:text-primary transition-colors">
              <i class="fa fa-weibo text-lg"></i>
            </a>
            <a href="#" class="text-gray-400 hover:text-primary transition-colors">
              <i class="fa fa-wechat text-lg"></i>
            </a>
            <a href="#" class="text-gray-400 hover:text-primary transition-colors">
              <i class="fa fa-instagram text-lg"></i>
            </a>
            <a href="#" class="text-gray-400 hover:text-primary transition-colors">
              <i class="fa fa-twitter text-lg"></i>
            </a>
          </div>
        </div>
        <div>
          <h3 class="text-lg font-semibold mb-3">快速链接</h3>
          <ul class="space-y-2">
            <li><a href="#" class="text-gray-400 hover:text-white transition-colors text-sm">首页</a></li>
            <li><a href="#posts" class="text-gray-400 hover:text-white transition-colors text-sm">帖子</a></li>
            <li><a href="#hot" class="text-gray-400 hover:text-white transition-colors text-sm">热门</a></li>
            <li><a href="#" class="text-gray-400 hover:text-white transition-colors text-sm">关于我们</a></li>
          </ul>
        </div>
        <div>
          <h3 class="text-lg font-semibold mb-3">联系我们</h3>
          <ul class="space-y-2">
            <li class="flex items-center text-gray-400 text-sm">
              <i class="fa fa-envelope-o mr-2"></i>
              <span>contact@campus-wall.com</span>
            </li>
            <li class="flex items-center text-gray-400 text-sm">
              <i class="fa fa-phone mr-2"></i>
              <span>400-123-4567</span>
            </li>
            <li class="flex items-center text-gray-400 text-sm">
              <i class="fa fa-map-marker mr-2"></i>
              <span>北京市海淀区中关村大街1号</span>
            </li>
          </ul>
        </div>
        <div>
          <h3 class="text-lg font-semibold mb-3">下载我们的APP</h3>
          <p class="text-gray-400 mb-3 text-sm">随时随地查看校园动态</p>
          <div class="flex space-x-3">
            <a href="#" class="bg-gray-800 hover:bg-gray-700 transition-colors p-2 rounded">
              <i class="fa fa-apple text-lg"></i>
            </a>
            <a href="#" class="bg-gray-800 hover:bg-gray-700 transition-colors p-2 rounded">
              <i class="fa fa-android text-lg"></i>
            </a>
          </div>
        </div>
      </div>
      <div class="pt-6 border-t border-gray-800 text-center text-gray-500 text-xs">
        <p>&copy; 2025 校园墙 All Rights Reserved. 京ICP备12345678号-1</p>
      </div>
    </div>
  </footer>

  <!-- 发布帖子模态框 -->
  <div id="post-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
          <h3 class="text-xl font-semibold">发布新帖子</h3>
          <button id="close-modal" class="text-gray-400 hover:text-gray-600">
            <i class="fa fa-times text-xl"></i>
          </button>
        </div>
      </div>
      <div class="p-6">
        <form id="post-form">
          <div class="mb-4">
            <label for="post-title" class="block text-sm font-medium text-gray-700 mb-1">标题</label>
            <input type="text" id="post-title" name="title" placeholder="请输入帖子标题" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
          </div>
          <div class="mb-4">
            <label for="post-content" class="block text-sm font-medium text-gray-700 mb-1">内容</label>
            <textarea id="post-content" name="content" rows="5" placeholder="请输入帖子内容" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"></textarea>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">分类</label>
            <div class="grid grid-cols-3 gap-2">
              <button type="button" class="category-btn px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:border-primary hover:text-primary" data-category="学习交流">学习交流</button>
              <button type="button" class="category-btn px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:border-primary hover:text-primary" data-category="美食推荐">美食推荐</button>
              <button type="button" class="category-btn px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:border-primary hover:text-primary" data-category="实习就业">实习就业</button>
              <button type="button" class="category-btn px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:border-primary hover:text-primary" data-category="兴趣爱好">兴趣爱好</button>
              <button type="button" class="category-btn px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:border-primary hover:text-primary" data-category="活动通知">活动通知</button>
              <button type="button" class="category-btn px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:border-primary hover:text-primary" data-category="失物招领">失物招领</button>
            </div>
            <input type="hidden" id="post-category" name="category" value="">
          </div>
          <div class="mb-4">
            <label class="flex items-center">
              <input type="checkbox" id="is-anonymous" name="is_anonymous" class="rounded border-gray-300 text-primary focus:ring-primary">
              <span class="ml-2 text-sm text-gray-600">匿名发布</span>
            </label>
          </div>
          <div class="flex justify-end space-x-3">
            <button type="button" id="cancel-post" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">取消</button>
            <button type="submit" class="px-4 py-2 bg-primary border border-transparent rounded-lg text-sm font-medium text-white hover:bg-primary/90">发布</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- 右下角发布按钮 -->
  <button id="create-post-btn" class="fixed bottom-8 right-8 bg-primary text-white rounded-full w-14 h-14 shadow-xl flex items-center justify-center z-40 hover:bg-primary/90 transition-all duration-300 transform hover:scale-110">
    <i class="fa fa-plus text-xl"></i>
  </button>

  <!-- 返回顶部按钮 -->
  <button id="back-to-top" class="fixed bottom-24 right-8 bg-gray-800 text-white rounded-full p-3 shadow-lg opacity-0 invisible transition-all duration-300 hover:bg-gray-700">
    <i class="fa fa-arrow-up"></i>
  </button>

  <!-- JavaScript -->
  <script>
    // 模态框控制
    const postModal = document.getElementById('post-modal');
    const createPostBtn = document.getElementById('create-post-btn');
    const closeModal = document.getElementById('close-modal');
    const cancelPost = document.getElementById('cancel-post');

    function openModal() {
      postModal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }

    function closeModalFunc() {
      postModal.classList.add('hidden');
      document.body.style.overflow = '';
    }

    createPostBtn.addEventListener('click', openModal);
    closeModal.addEventListener('click', closeModalFunc);
    cancelPost.addEventListener('click', closeModalFunc);

    // 点击模态框外部关闭
    postModal.addEventListener('click', (e) => {
      if (e.target === postModal) {
        closeModalFunc();
      }
    });

    // 返回顶部按钮
    const backToTopBtn = document.getElementById('back-to-top');

    window.addEventListener('scroll', () => {
      if (window.scrollY > 500) {
        backToTopBtn.classList.remove('opacity-0', 'invisible');
        backToTopBtn.classList.add('opacity-100', 'visible');
      } else {
        backToTopBtn.classList.add('opacity-0', 'invisible');
        backToTopBtn.classList.remove('opacity-100', 'visible');
      }
    });

    backToTopBtn.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });

    // 平滑滚动
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        e.preventDefault();

        const targetId = this.getAttribute('href');
        if (targetId === '#') return;

        const targetElement = document.querySelector(targetId);
        if (targetElement) {
          targetElement.scrollIntoView({
            behavior: 'smooth'
          });
        }
      });
    });

    // 加载更多按钮
    const loadMoreBtn = document.getElementById('load-more-btn');
    loadMoreBtn.addEventListener('click', () => {
      loadMoreBtn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i> 加载中...';

      // 模拟加载延迟
      setTimeout(() => {
        loadMoreBtn.innerHTML = '<i class="fa fa-refresh mr-2"></i> 加载更多';
        // 这里可以添加实际的加载逻辑
        alert('已加载全部内容');
      }, 1500);
    });

    // 表单提交
    const postForm = document.getElementById('post-form');
    postForm.addEventListener('submit', (e) => {
      e.preventDefault();

      const title = document.getElementById('post-title').value;
      const content = document.getElementById('post-content').value;
      const category = document.getElementById('post-category').value;
      const isAnonymous = document.getElementById('is-anonymous').checked;

      if (!title || !content || !category) {
        alert('请填写完整信息并选择分类');
        return;
      }

      const submitBtn = postForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i> 发布中...';
      submitBtn.disabled = true;

      // 发送AJAX请求
      fetch(window.location.href, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'add_post',
            title: title,
            content: content,
            category: category,
            is_anonymous: isAnonymous ? '1' : '0'
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert(data.message);
            closeModalFunc();
            postForm.reset();

            // 清除分类选择状态
            categoryBtns.forEach(b => {
              b.classList.remove('bg-primary/10', 'border-primary', 'text-primary');
              b.classList.add('border-gray-300', 'text-gray-700');
            });

            // 刷新页面或添加新帖子到列表
            location.reload();
          } else {
            alert(data.message);
          }
        })
        .catch(error => {
          alert('发布失败: ' + error.message);
        })
        .finally(() => {
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
        });
    });

    // 点赞功能
    document.querySelectorAll('.like-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const postId = this.dataset.postId;
        const likeCount = this.querySelector('span');
        const icon = this.querySelector('i');

        fetch(window.location.href, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
              action: 'toggle_like',
              post_id: postId
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              if (data.action === 'like') {
                likeCount.textContent = parseInt(likeCount.textContent) + 1;
                icon.classList.remove('fa-heart-o');
                icon.classList.add('fa-heart');
                this.classList.add('text-red-500');
              } else {
                likeCount.textContent = parseInt(likeCount.textContent) - 1;
                icon.classList.remove('fa-heart');
                icon.classList.add('fa-heart-o');
                this.classList.remove('text-red-500');
              }
            } else {
              alert(data.message);
            }
          })
          .catch(error => {
            alert('操作失败: ' + error.message);
          });
      });
    });

    // 评论功能
    document.querySelectorAll('.comment-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const postId = this.dataset.postId;
        const postElement = document.querySelector(`[data-post-id="${postId}"]`);
        const commentsContainer = postElement.querySelector('.comments-container');

        commentsContainer.classList.toggle('hidden');

        if (!commentsContainer.classList.contains('hidden')) {
          // 加载评论
          const commentsList = commentsContainer.querySelector('.comments-list');
          commentsList.innerHTML = '<div class="text-center py-3 text-gray-500">加载中...</div>';

          fetch(window.location.href, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
              },
              body: new URLSearchParams({
                action: 'load_comments',
                post_id: postId
              })
            })
            .then(response => response.text())
            .then(data => {
              commentsList.innerHTML = data;
            })
            .catch(error => {
              commentsList.innerHTML = '<div class="text-center py-3 text-red-500">加载失败</div>';
            });
        }
      });
    });

    // 添加评论
    document.querySelectorAll('.add-comment-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const postId = this.dataset.postId;
        const postElement = document.querySelector(`[data-post-id="${postId}"]`);
        const commentInput = postElement.querySelector('.comments-container input');
        const commentContent = commentInput.value.trim();

        if (!commentContent) {
          alert('请输入评论内容');
          return;
        }

        const commentsList = postElement.querySelector('.comments-list');
        const originalContent = commentsList.innerHTML;
        commentsList.innerHTML = '<div class="text-center py-3 text-gray-500">发送中...</div>';

        fetch(window.location.href, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
              action: 'add_comment',
              post_id: postId,
              content: commentContent
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // 刷新评论列表
              fetch(window.location.href, {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                  },
                  body: new URLSearchParams({
                    action: 'load_comments',
                    post_id: postId
                  })
                })
                .then(response => response.text())
                .then(data => {
                  commentsList.innerHTML = data;
                  commentInput.value = '';
                })
                .catch(error => {
                  commentsList.innerHTML = '<div class="text-center py-3 text-red-500">加载失败</div>';
                });
            } else {
              commentsList.innerHTML = originalContent;
              alert(data.message);
            }
          })
          .catch(error => {
            commentsList.innerHTML = originalContent;
            alert('评论失败: ' + error.message);
          });
      });
    });

    // 排序筛选
    const sortSelect = document.getElementById('sort-select');

    function applyFilters() {
      const sort = sortSelect.value;

      // 这里可以添加实际的筛选逻辑
      alert(`筛选条件：排序=${sort}`);
    }

    sortSelect.addEventListener('change', applyFilters);
  </script>
</body>

</html>