<?php
// rate_limit.php

class RateLimiter {
    private $limit;
    private $window;
    private $storageDir;
    
    public function __construct($limit = 10, $window = 10, $storageDir = null) {
        $this->limit = $limit;    // 允许的请求次数
        $this->window = $window;  // 时间窗口(秒)
        $this->storageDir = $storageDir ?: sys_get_temp_dir() . '/rate_limit/';
        
        if (!file_exists($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }
    
    public function getClientIP() {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'invalid_ip';
    }
    
    public function checkRateLimit() {
        $ip = $this->getClientIP();
        $filename = $this->storageDir . md5($ip) . '.json';
        
        $now = time();
        $data = [
            'count' => 1,
            'start' => $now
        ];
        
        if (file_exists($filename)) {
            $fileData = json_decode(file_get_contents($filename), true);
            if ($now - $fileData['start'] <= $this->window) {
                $data['count'] = $fileData['count'] + 1;
                $data['start'] = $fileData['start'];
            }
        }
        
        file_put_contents($filename, json_encode($data));
        
        if ($data['count'] > $this->limit) {
            $this->sendRateLimitResponse($data);
        }
    }
    
    private function sendRateLimitResponse($data) {
        // 在加15秒
        $resetTime = $data['start'] + $this->window + 15;
        $remaining = $this->limit - $data['count'];
        
        header('HTTP/1.1 429 Too Many Requests');
        header('Content-Type: application/json');
        header('X-RateLimit-Limit: ' . $this->limit);
        header('X-RateLimit-Remaining: ' . max(0, $remaining));
        header('X-RateLimit-Reset: ' . $resetTime);
        
        echo json_encode([
            'success' => false,
            'message' => '请求过于频繁，请稍后再试',
            'retry_after' => $resetTime - time()
        ]);
        exit;
    }
}

// 使用示例 (可根据需要调整参数)
$rateLimiter = new RateLimiter(
    60,     // 每分钟最多60次请求
    60,     // 60秒时间窗口
    __DIR__ . '/../../storage/rate_limit/'  // 存储目录
);

$rateLimiter->checkRateLimit();