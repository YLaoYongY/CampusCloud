<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>校园云</title>
  <style>
    html,
    body {
      margin: 0;
      padding: 0;
      height: 100%;
    }
  </style>
</head>
<frameset rows="72,*" cols="*" frameborder="no" border="0" framespacing="0">
  <!-- 这里src写的是导航栏的页面地址，如果需要改导航栏，到layout文件夹下修改top.html即可 -->
  <frame src="./top.php" name="topFrame" scrolling="No" noresize="noresize" id="topFrame" title="topFrame" />
  <frameset rows="*" cols="*" framespacing="0" frameborder="no" border="0">
    <!-- 这里src写的是主框架的地址 -->
    <frame src="../views/RegularUser/home.php" name="mainFrame" id="mainFrame" title="mainFrame" />
  </frameset>
</frameset>
<noframes>

  <body></body>
</noframes>

</html>