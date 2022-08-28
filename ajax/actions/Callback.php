<?php
/*
 * Evolution CMS AJAX Handler
 * Copyright (c) 2018-2022 delphinpro
 * Licensed under the MIT license
 */

/**
 * Class CallbackAjaxAction
 */
class CallbackAjaxAction extends AjaxAction
{
    public function exec()
    {
        $mailer = $this->getMailer($this->postData);
        $mailer->sendMail();
        AjaxResponse::getInstance()->send(null, 'Спасибо. Наши менеджеры вам скоро перезвонят.');
    }
}
