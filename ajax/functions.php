<?php
/**
 * ModX Ajax
 *
 * @author      delphinpro <delphinpro@gmail.com>
 * @copyright   copyright © 2018 delphinpro
 * @license     licensed under the MIT license
 */

/**
 * Отладочный вывод
 */
function pre()
{
    $args = func_get_args();
    if (count($args) > 1 && is_string($args[0])) {
        $title = array_shift($args);
    } else {
        $title = 'Debug info';
    }
    echo '<details><summary>' . $title . '</summary>';
    foreach ($args as $arg) {
        echo '<pre style="font-size: 11px;line-height:1.1;">';
        if (is_null($arg)) {
            echo 'NULL';
        } elseif (is_bool($arg)) {
            echo $arg ? 'TRUE' : 'FALSE';
        } elseif (is_string($arg)) {
            echo 'string[' . strlen($arg) . '] ' . $arg;
        } else {
            echo htmlspecialchars(print_r($arg, true));
        }
        echo '</pre>';
    }
    if (isset($title)) echo '</details>';
}

/**
 * Фильтрует настройки, оставляя только глобальные TV
 *
 * @param array $config Массив сситемных настроек ModX
 * @param string $prefix Префикс настроек из плагина CfgTv
 * @return array
 */
function filterTvParams($config, $prefix = 'cfg_')
{
    $result = [];
    foreach ($config as $key => $value) {
        if (strpos($key, $prefix) === 0 && strlen($key) > strlen($prefix)) {
            $param = str_replace($prefix, '', $key);
            if ($param == '') continue;
            $result[$param] = $value;
        }
    }
    return $result;
}

/**
 * Немного чистит данные
 *
 * @param array $array
 * @return array
 */
function safetyData($array)
{
    global $modx;

    $result = array();
    foreach ($array as $key => $value) {
        $val = is_array($value) ? $value : $modx->stripTags($value);
        $result[$key] = $key == 'message' ? nl2br($val) : $val;
    }
    return $result;
}

/**
 * @param string $inputString Comma separated string
 * @return array
 */
function commaSeparatedStringToArray($inputString)
{
    return array_map('trim', explode(',', $inputString));
}
