<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli-server') {
    // safe guard against unwanted execution
    throw new Exception("You cannot run this script directly, it's a fixture for TestHttpServer.");
}

$vars = [];

if (! $_POST) {
    $_POST = json_decode(file_get_contents('php://input'), true);
    $_POST['content-type'] = $_SERVER['HTTP_CONTENT_TYPE'] ?? '?';
}

foreach ($_SERVER as $k => $v) {
    switch ($k) {
        default:
            if (strpos($k, 'HTTP_') !== 0) {
                continue 2;
            }

        // no break
        case 'SERVER_NAME':
        case 'SERVER_PROTOCOL':
        case 'REQUEST_URI':
        case 'REQUEST_METHOD':
        case 'PHP_AUTH_USER':
        case 'PHP_AUTH_PW':
            $vars[$k] = $v;
    }
}

$json = json_encode($vars, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

switch ($vars['REQUEST_URI']) {
    default:
        exit;

    case '/':
        header('Content-Type: application/json');
        break;

    case '/404':
        header('Content-Type: application/json', true, 404);
        break;

    case '/302':
        header('Location: http://localhost:8057/', true, 302);
        break;

    case '/307':
        header('Location: http://localhost:8057/post', true, 307);
        break;

    case '/post':
        $output = json_encode($_POST + ['REQUEST_METHOD' => $vars['REQUEST_METHOD']], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        header('Content-Type: application/json', true);
        header('Content-Length: ' . strlen($output));
        echo $output;
        exit;
}

header('Content-Type: application/json', true);

echo $json;
