<?php
header('Content-Type: application/json');



$card_code = $_POST['card_code'] ?? '';
$machine_code = $_POST['machine_code'] ?? '';
$type = $_POST['type'] ?? 'all'; // 新增 type 参数



// 模拟公告内容
$title_announcements = [
    '服务器将于今晚12点进行维护'
];

$popup_announcements = [
    ['title' => '售后QQ群', 'content' => '欢迎加入售后QQ群~ 249674160']
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
