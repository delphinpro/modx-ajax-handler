<?php
/**
 * ModX Ajax
 *
 * @author      delphinpro <delphinpro@gmail.com>
 * @copyright   copyright © 2018 delphinpro
 * @license     licensed under the MIT license
 */

defined('JSON_UNESCAPED_UNICODE') or define('JSON_UNESCAPED_UNICODE', 256);

class AjaxResponse
{
    private static $instance;
    private $res;

    private function __construct()
    {
        $this->res = [
            'status'  => true,
            'message' => null,
            'payload' => null,
        ];
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function sendError($message = null)
    {
        $this->res['status'] = false;
        $this->res['message'] = !is_null($message) ? $message : $this->res['message'];
        $this->res['payload'] = null;
        $this->res['$_POST'] = $_POST;
        $this->res['$_GET'] = $_GET;
        if (TEST) {
            $this->res['debug'] = '<div style="text-align:left;">' . ob_get_clean() . '</div>';
        }
        echo json_encode($this->res, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function send($payload, $message = null)
    {
        $this->res['status'] = true;
        $this->res['message'] = !is_null($message) ? $message : $this->res['message'];
        $this->res['payload'] = $payload;
        if (TEST) {
            $this->res['debug'] = '<div style="text-align:left;">' . ob_get_clean() . '</div>';
        }
        echo json_encode($this->res, JSON_UNESCAPED_UNICODE);
        die();
    }
}
