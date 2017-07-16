<?php

if (!defined('BASE_PATH')) {
    define('BASE_PATH', getcwd());
}

require_once BASE_PATH . '/cms/tests/bootstrap/mysite.php';

copy(__DIR__ . '/fixtures/theme.yml.fixture', $projectPath . '/_config/dummytheme.yml');
