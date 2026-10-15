<?php
/**
 * Bộ điều phối kiểm thử SQL Injection (SQLi Test Router)
 * Hỗ trợ chuyển hướng hoặc nạp bài test tương ứng:
 *   - test=1: test1.php (Numeric ID & LIKE String Injection trên bảng users)
 *   - test=2: test2.php (Authentication Bypass & ORDER BY/Filter Injection)
 */

$testNum = $_REQUEST['test'] ?? (isset($_REQUEST['scenario']) || isset($_REQUEST['category']) ? '2' : '1');

if ($testNum === '2') {
    require_once __DIR__ . '/test2.php';
} else {
    require_once __DIR__ . '/test1.php';
}
