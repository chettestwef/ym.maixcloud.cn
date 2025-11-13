<?php
// 永久重定向 (301)
header("HTTP/1.1 301 Moved Permanently");
header("Location: /changeLog/");
exit(); // 确保后续代码不会执行
?>