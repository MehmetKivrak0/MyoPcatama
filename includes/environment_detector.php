<?php
/**
 * Ortam Tespit Fonksiyonu
 * Free hosting ve normal sunucu arasında otomatik geçiş yapar
 */

function detectEnvironment() {
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    $httpHost = $_SERVER['HTTP_HOST'] ?? '';
    
    // Free hosting göstergeleri
    $freeHostingIndicators = [
        'epizy.com', 'infinityfree.net', '000webhost.com', 'freehostia.com',
        'byet.org', 'hostinger.com', 'awardspace.com', 'freehosting.com',
        'x10hosting.com'
    ];
    
    // Okul sunucusu göstergeleri
    $schoolServerIndicators = [
        'mcbu.edu.tr', 'xrlab.mcbu.edu.tr', // Özel domain'ler - öncelikli
        'edu.tr', 'edu', 'university', 'college', 'school', 'academy',
        'k12', 'univ', 'uni', 'okul', 'universite', 'kolej'
    ];
    
    $environment = [
        'isFreeHosting' => false,
        'isSchoolServer' => false,
        'isNormalServer' => false,
        'serverType' => 'unknown'
    ];
    
    // Free hosting tespiti
    foreach ($freeHostingIndicators as $indicator) {
        if (strpos(strtolower($serverName), strtolower($indicator)) !== false || 
            strpos(strtolower($httpHost), strtolower($indicator)) !== false) {
            $environment['isFreeHosting'] = true;
            $environment['serverType'] = 'free_hosting';
            break;
        }
    }
    
    // Okul sunucusu tespiti
    if (!$environment['isFreeHosting']) {
        foreach ($schoolServerIndicators as $indicator) {
            if (strpos(strtolower($serverName), strtolower($indicator)) !== false || 
                strpos(strtolower($httpHost), strtolower($indicator)) !== false) {
                $environment['isSchoolServer'] = true;
                $environment['serverType'] = 'school_server';
                break;
            }
        }
    }
    
    // Normal sunucu tespiti
    if (!$environment['isFreeHosting'] && !$environment['isSchoolServer']) {
        $environment['isNormalServer'] = true;
        $environment['serverType'] = 'normal_server';
    }
    
    return $environment;
}

/**
 * Ortam bazlı güvenlik kontrolü
 */
function checkSecurityByEnvironment($environment) {
    // Free hosting'de güvenlik kontrolü atla
    if ($environment['isFreeHosting']) {
        return true;
    }
    
    // School server için özel güvenlik kuralları
    if ($environment['isSchoolServer']) {
        // xrlab.mcbu.edu.tr için güvenlik kontrolü
        if (strpos($_SERVER['HTTP_HOST'] ?? '', 'xrlab.mcbu.edu.tr') !== false) {
            // Okul sunucusunda güvenlik kontrolü yap
            if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
                exit();
            }
        }
        return true;
    }
    
    // Normal sunucu için güvenlik kontrolü
    if ($environment['isNormalServer']) {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
            exit();
        }
    }
    
    return true;
}

/**
 * Ortam bazlı method kontrolü
 */
function checkMethodByEnvironment($method, $environment) {
    // Free hosting'de POST method kısıtlı
    if ($environment['isFreeHosting'] && $method === 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'POST method free hosting\'de desteklenmiyor, GET kullanın']);
        return false;
    }
    
    return true;
}

/**
 * Ortam bilgilerini logla (debug için)
 */
function logEnvironmentInfo($environment) {
    // Production ortamında debug modunu kapat
    $isProduction = strpos($_SERVER['HTTP_HOST'] ?? '', 'xrlab.mcbu.edu.tr') !== false;
    
    if (!$isProduction && isset($_GET['debug']) && $_GET['debug'] === '1') {
        error_log("Environment detected: " . json_encode($environment));
    }
}
?>
