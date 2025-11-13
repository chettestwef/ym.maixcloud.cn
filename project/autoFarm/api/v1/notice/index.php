<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../functions.php';

// require_post_and_check_user_agent();

$card_code = $_POST['card_code'] ?? '';
$machine_code = $_POST['machine_code'] ?? '';
$type = $_POST['type'] ?? 'all'; // 新增 type 参数

# 权限检查
#verify_authorization($card_code, $machine_code);

// 模拟公告内容
$title_announcements = [
    '服务器将于今晚12点进行维护，请注意保存进度。'
];

// $popup_announcements = [
//     ['title' => '紧急通知', 'content' => '检测到异常活动，请立即修改密码。'],
//     ['title' => '活动预告', 'content' => '五一活动即将开启，敬请期待！']
// ];

$popup_announcements = [
    ['title' => '服务器故障公告', 'content' => '由于服务器更新等因素, 目前全部服务器的连接均将出现不稳定的情况, 请几分钟重新尝试']
];

// 构造返回体
$response = ['status' => 'success'];

switch ($type) {
    case 'title':
        $response['title_announcements'] = $title_announcements;
        break;

    case 'popup':
        $response['popup_announcements'] = $popup_announcements;
        break;

    case 'popup_title':
        $response['popup_titles'] = array_column($popup_announcements, 'title');
        break;

    case 'popup_content':
        $response['popup_contents'] = array_column($popup_announcements, 'content');
        break;

    case 'all':
    default:
        $response['title_announcements'] = $title_announcements;
        $response['popup_announcements'] = $popup_announcements;
        break;
}

echo json_encode($response);
