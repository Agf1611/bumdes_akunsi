<?php

declare(strict_types=1);

const ROOT_PATH = __DIR__ . '/..';
const APP_PATH = __DIR__;

$config = [
    'app' => require APP_PATH . '/config/app.php',
    'database' => require APP_PATH . '/config/database.php',
    'auth' => require APP_PATH . '/config/auth.php',
];

$generatedConfigPath = APP_PATH . '/config/generated.php';
if (is_file($generatedConfigPath)) {
    $generatedConfig = require $generatedConfigPath;
    if (is_array($generatedConfig)) {
        foreach (['app', 'database', 'auth'] as $section) {
            if (isset($generatedConfig[$section]) && is_array($generatedConfig[$section])) {
                $config[$section] = array_replace($config[$section], $generatedConfig[$section]);
            }
        }
    }
}

date_default_timezone_set((string) $config['app']['timezone']);

if (!function_exists('mb_strlen')) {
    function mb_strlen(string $string, ?string $encoding = null): int
    {
        return strlen($string);
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string
    {
        return $length === null ? substr($string, $start) : substr($string, $start, $length);
    }
}

if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $string, ?string $encoding = null): string
    {
        return strtolower($string);
    }
}

if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper(string $string, ?string $encoding = null): string
    {
        return strtoupper($string);
    }
}

require APP_PATH . '/helpers/common_helper.php';
require APP_PATH . '/helpers/audit_helper.php';
require APP_PATH . '/helpers/profile_helper.php';
require APP_PATH . '/helpers/upload_helper.php';
require APP_PATH . '/helpers/business_unit_helper.php';
require APP_PATH . '/helpers/report_layout_helper.php';
require APP_PATH . '/helpers/dashboard_helper.php';
require APP_PATH . '/helpers/import_helper.php';
require APP_PATH . '/helpers/period_helper.php';
require APP_PATH . '/helpers/ledger_helper.php';
require APP_PATH . '/helpers/trial_balance_helper.php';
require APP_PATH . '/helpers/profit_loss_helper.php';
require APP_PATH . '/helpers/balance_sheet_helper.php';
require APP_PATH . '/helpers/financial_notes_helper.php';
require APP_PATH . '/helpers/lpj_package_helper.php';
require APP_PATH . '/helpers/cash_flow_helper.php';
require APP_PATH . '/helpers/report_pdf_helper.php';
require APP_PATH . '/helpers/equity_changes_helper.php';
require APP_PATH . '/helpers/coa_helper.php';
require APP_PATH . '/helpers/journal_helper.php';
require APP_PATH . '/helpers/listing_helper.php';
require APP_PATH . '/helpers/workspace_helper.php';
require APP_PATH . '/helpers/asset_helper.php';
require APP_PATH . '/helpers/bank_reconciliation_helper.php';

spl_autoload_register(static function (string $class): void {
    $paths = [
        APP_PATH . '/core/' . $class . '.php',
        APP_PATH . '/middleware/' . $class . '.php',
    ];

    foreach (glob(APP_PATH . '/modules/*/' . $class . '.php') ?: [] as $file) {
        $paths[] = $file;
    }

    foreach ($paths as $file) {
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

set_exception_handler(static function (Throwable $e): void {
    http_response_code(500);
    log_error($e);
    render_error_page(500, 'Sistem sedang mengalami kendala. Silakan coba lagi atau hubungi administrator.', $e);
});

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

Session::start($config['app']);

$router = new App();
require APP_PATH . '/routes/auth.php';
require APP_PATH . '/routes/web.php';

return $router;
