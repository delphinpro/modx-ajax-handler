<?php
/*
 * Evolution CMS AJAX Handler
 * Copyright (c) 2018-2022 delphinpro
 * Licensed under the MIT license
 */

/**
 * Class AjaxAction
 */
abstract class AjaxAction
{
    /** @var array Список глобальных настроек из TV-параметров */
    protected $config;

    /** @var array Очищенные данные из массива $_POST */
    protected $postData;

    /** @var DocumentParser */
    protected $modx;

    public function __construct(DocumentParser $modx, $config)
    {
        $this->modx = $modx;
        $this->config = $config;
        $this->postData = safetyData($_POST);
    }

    /**
     * @param array $cfg
     * @return AjaxAction
     */
    public function configure(array $cfg)
    {
        return $this;
    }

    abstract public function exec();

    /**
     * @param $classname
     * @param DocumentParser $modx
     * @param array $config
     * @return AjaxAction
     */
    public static function getInstance($classname, DocumentParser $modx, array $config)
    {
        return new $classname($modx, $config);
    }

    /**
     * @param array $postData Данные для отправки
     * @return AjaxMailer
     */
    public function getMailer($postData)
    {
        $postData['site_name'] = $this->modx->config['site_name'];
        return new AjaxMailer($this->modx, $this->config, $postData);
    }

    /**
     * @param string $name Имя параметра
     * @param int|null $defaultValue
     * @param int|null $minValue
     * @param int|null $maxValue
     * @return int|null
     */
    protected function getIntParam($name, $defaultValue = null, $minValue = null, $maxValue = null)
    {
        $value = isset($_POST[$name]) ? (int)$_POST[$name] : $defaultValue;

        if (!is_null($minValue) && $value < $minValue) $value = $minValue;
        if (!is_null($maxValue) && $value > $maxValue) $value = $maxValue;

        return $value;
    }
}
