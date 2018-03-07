<?php
/**
 * ModX Ajax action example
 *
 * @author      delphinpro <delphinpro@gmail.com>
 * @copyright   copyright © 2018 delphinpro
 * @license     licensed under the MIT license
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
