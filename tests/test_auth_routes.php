<?php
/**
 * Automated Test Suite for Authentication & Dashboard Routing
 * Tests:
 * - /pftp-bot/login
 * - /pftp-bot/dashboard
 * - /pftp-bot/logout
 * - Legacy redirects
 * - Authentication guards & sessions
 */

declare(strict_types=1);

$baseUrl = rtrim(getenv('TEST_BASE_URL') ?: 'http://127.0.0.1:8088', '/');
$expectedPrefix = (strpos($baseUrl, '/pftp-bot') !== false) ? '/pftp-bot' : '';
$expectedLogin = $expectedPrefix . '/login';
$expectedDashboard = $expectedPrefix . '/dashboard';
$cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pftp_test_cookie_' . uniqid() . '.txt';

function makeRequest(string $url, string $method = 'GET', array $postData = [], string $cookieJar = '', bool $followRedirects = false): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirects);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if (!empty($cookieJar)) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);

    $headerText = substr($response ?: '', 0, $headerSize);
    $body = substr($response ?: '', $headerSize);

    // Extract Location header if present
    $location = $redirectUrl;
    if (preg_match('/^Location:\s*(.*?)$/mi', $headerText, $matches)) {
        $location = trim($matches[1]);
    }

    curl_close($ch);

    return [
        'code'     => $httpCode,
        'headers'  => $headerText,
        'body'     => $body,
        'location' => $location
    ];
}

$passed = 0;
$failed = 0;

function assertTest(string $name, bool $condition, string $detail = '') {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$name}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$name} - {$detail}\n";
    }
}

echo "======================================================\n";
echo "PFTP AI Chatbot - Auth & Dashboard Routing Test Suite\n";
echo "======================================================\n\n";

// Test 1: Unauthenticated request to /dashboard should redirect to /login
echo "Test 1: Protected Dashboard Authentication Guard\n";
$res = makeRequest("{$baseUrl}/dashboard", 'GET', [], '', false);
assertTest(
    "Unauthenticated /dashboard redirects (302)",
    $res['code'] === 302,
    "Expected HTTP 302, got {$res['code']}"
);
assertTest(
    "/dashboard redirects to /login",
    strpos((string)$res['location'], $expectedLogin) !== false,
    "Expected Location to contain '{$expectedLogin}', got: " . (string)$res['location']
);

// Test 2: Direct GET to /login returns 200 OK
echo "\nTest 2: Dedicated Login Page Rendering\n";
$res = makeRequest("{$baseUrl}/login", 'GET', [], '', false);
assertTest(
    "/login returns HTTP 200 OK",
    $res['code'] === 200,
    "Expected HTTP 200, got {$res['code']}"
);
assertTest(
    "/login contains PFTP Admin Portal branding",
    strpos($res['body'], 'PFTP Admin Portal') !== false,
    "Page does not contain 'PFTP Admin Portal'"
);
assertTest(
    "/login contains username and password form fields",
    strpos($res['body'], 'name="username"') !== false && strpos($res['body'], 'name="password"') !== false,
    "Form fields not found"
);

// Test 3: Failed Login Attempt
echo "\nTest 3: Authentication Failure Handling\n";
$res = makeRequest("{$baseUrl}/login", 'POST', ['username' => 'wrongadmin', 'password' => 'badpassword'], '', false);
assertTest(
    "Invalid login returns 200 with error message",
    $res['code'] === 200 && strpos($res['body'], 'Invalid username/email or password') !== false,
    "Failed to show invalid credential error"
);

// Test 4: Successful Login Attempt
echo "\nTest 4: Authentication Success & Session Creation\n";
$res = makeRequest("{$baseUrl}/login", 'POST', ['username' => 'admin', 'password' => 'AdminPassword123!'], $cookieFile, false);
assertTest(
    "Valid login responds with 302 redirect",
    $res['code'] === 302,
    "Expected HTTP 302, got {$res['code']}"
);
assertTest(
    "Redirect destination is /dashboard",
    strpos((string)$res['location'], $expectedDashboard) !== false,
    "Expected Location '{$expectedDashboard}', got: " . (string)$res['location']
);

// Test 5: Authenticated Access to /dashboard
echo "\nTest 5: Authenticated Dashboard Rendering\n";
$res = makeRequest("{$baseUrl}/dashboard", 'GET', [], $cookieFile, false);
assertTest(
    "Authenticated /dashboard returns HTTP 200",
    $res['code'] === 200,
    "Expected HTTP 200, got {$res['code']}"
);
assertTest(
    "Dashboard contains page heading",
    strpos($res['body'], 'PFTP Admissions AI Dashboard') !== false,
    "Dashboard title not found"
);
assertTest(
    "Dashboard displays logged in admin username",
    strpos($res['body'], 'Logged in as:') !== false && strpos($res['body'], 'admin') !== false,
    "Admin username not found in dashboard topbar"
);
assertTest(
    "Dashboard renders key statistics cards",
    strpos($res['body'], 'Registered Student Leads') !== false && strpos($res['body'], 'Knowledge Base Q&amp;A Entries') !== false,
    "Metrics cards not found"
);

// Test 6: Logging in while already logged in redirects to dashboard
echo "\nTest 6: Redirection when visiting /login while logged in\n";
$res = makeRequest("{$baseUrl}/login", 'GET', [], $cookieFile, false);
assertTest(
    "Visiting /login while logged in redirects (302)",
    $res['code'] === 302,
    "Expected HTTP 302, got {$res['code']}"
);
assertTest(
    "Redirects directly to /dashboard",
    strpos((string)$res['location'], $expectedDashboard) !== false,
    "Expected Location '{$expectedDashboard}', got: " . (string)$res['location']
);

// Test 7: Legacy Route Redirects
echo "\nTest 7: Legacy Route Compatibility\n";
$res = makeRequest("{$baseUrl}/chatbot/admin/login.php", 'GET', [], '', false);
assertTest(
    "Legacy chatbot/admin/login.php redirects to /login",
    $res['code'] === 302 && strpos((string)$res['location'], $expectedLogin) !== false,
    "Legacy login didn't redirect to {$expectedLogin}"
);

$res = makeRequest("{$baseUrl}/chatbot/admin/index.php", 'GET', [], '', false);
assertTest(
    "Legacy chatbot/admin/index.php redirects to /dashboard",
    $res['code'] === 302 && strpos((string)$res['location'], $expectedDashboard) !== false,
    "Legacy index didn't redirect to {$expectedDashboard}"
);

// Test 8: Logout Flow
echo "\nTest 8: Logout & Session Destruction\n";
$res = makeRequest("{$baseUrl}/logout", 'GET', [], $cookieFile, false);
assertTest(
    "/logout redirects to /login (302)",
    $res['code'] === 302 && strpos((string)$res['location'], $expectedLogin) !== false,
    "Logout did not redirect to {$expectedLogin}"
);

// Test 9: Subsequent request to /dashboard after logout is blocked
$res = makeRequest("{$baseUrl}/dashboard", 'GET', [], $cookieFile, false);
assertTest(
    "Accessing /dashboard after logout redirects to /login",
    $res['code'] === 302 && strpos((string)$res['location'], $expectedLogin) !== false,
    "Session was not destroyed after logout"
);

// Test 10: Production Health Diagnostic & Installer Endpoints
echo "\nTest 10: Production Health & Cloud Installer Endpoints\n";
$res = makeRequest("{$baseUrl}/api/health.php", 'GET', [], '', false);
assertTest(
    "/api/health.php returns HTTP 200",
    $res['code'] === 200,
    "Expected HTTP 200, got {$res['code']}"
);
assertTest(
    "/api/health.php reports healthy status",
    strpos($res['body'], '"status": "healthy"') !== false,
    "Health status not healthy"
);

$res = makeRequest("{$baseUrl}/install.php", 'GET', [], '', false);
assertTest(
    "/install.php returns HTTP 200",
    $res['code'] === 200,
    "Expected HTTP 200, got {$res['code']}"
);
assertTest(
    "/install.php renders database setup interface",
    strpos($res['body'], 'PFTP Cloud Database Setup') !== false,
    "Installer interface title not found"
);

// Clean up test cookie file
if (file_exists($cookieFile)) {
    @unlink($cookieFile);
}

echo "\n------------------------------------------------------\n";
echo "SUMMARY: {$passed} Passed, {$failed} Failed\n";
echo "------------------------------------------------------\n";

if ($failed > 0) {
    exit(1);
}

