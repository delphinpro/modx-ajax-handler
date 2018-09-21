<?php
/**
 * ModX Ajax
 *
 * @author      delphinpro <delphinpro@gmail.com>
 * @copyright   copyright © 2018 delphinpro
 * @license     licensed under the MIT license
 *
 * @var DocumentParser $modx
 */

define('MODX_API_MODE', true);
define('ACTIONS_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'actions');
define('TEST', isset($_GET['test']));

define('CFG_PREFIX_PARAM', 'client_');
define('CFG_EMAILS_DELIMITER', "\n");

function exception_error_handler($severity, $message, $file, $line)
{
    if (!(error_reporting() & $severity)) {
        // Этот код ошибки не входит в error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
}

set_error_handler("exception_error_handler");


mb_internal_encoding("UTF-8");

$action = isset($_GET['action'])
    ? ucfirst(preg_replace('/[^a-zA-Z]/', '', $_GET['action']))
    : null;

include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'index.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'AjaxResponse.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'AjaxMailer.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'AjaxAction.php';

$modx->db->connect();

if (empty($modx->config)) $modx->getSettings();

$modx->invokeEvent("OnWebPageInit");


if (TEST) {
    ob_start();
    echo '<code>' . 'Action: ' . $action . '</code><br>';
    pre('$_POST', $_POST);
    pre('$_FILES', $_FILES);
    pre('Config', filterTvParams($modx->config, CFG_PREFIX_PARAM));
//    pre($modx->config);
}

try {

    if (empty($action)) {
        throw new Exception('Invalid ajax action: empty');
    }

    if ($action !== ucfirst($_GET['action'])) {
        throw new Exception('Invalid ajax action');
    }

    $actionFileName = ACTIONS_DIR . DIRECTORY_SEPARATOR . $action . '.php';
    if (TEST) echo '<tt>' . 'File:   ' . $actionFileName . '</tt><br>';
    if (!file_exists($actionFileName)) {
        throw new Exception('Invalid ajax action: ' . $action);
    }

    /** @noinspection PhpIncludeInspection */
    require_once $actionFileName;
    if (!class_exists($actionClassName = $action . 'AjaxAction')) {
        throw new Exception('Class not found ' . $actionClassName);
    }

    $config = filterTvParams($modx->config, CFG_PREFIX_PARAM);
    AjaxAction::getInstance($actionClassName, $modx, $config)->exec();

} catch (Exception $e) {

    $base = str_replace('/', DIRECTORY_SEPARATOR, MODX_BASE_PATH);
    $file = str_replace($base, '', $e->getFile());
    $message =  $e->getMessage() . ' <br>in <code>\\' . $file . ':' . $e->getLine()  . '</code>';
    AjaxResponse::getInstance()->sendError($message);

}
