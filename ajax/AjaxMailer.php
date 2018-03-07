<?php
/**
 * ModX Ajax
 *
 * @author      delphinpro <delphinpro@gmail.com>
 * @copyright   copyright © 2018 delphinpro
 * @license     licensed under the MIT license
 */

/**
 * Class AjaxMailer
 */
class AjaxMailer
{
    /** @var array Список адресов для отправки почты */
    protected $emailsTo;

    /** @var string Адрес «От кого» */
    protected $emailFrom;

    /** @var array Список глобальных настроек из TV-параметров */
    protected $config;

    /* Настройки имён полей */
    protected $fSubject;
    protected $fEmpty;
    protected $fMail;
    protected $fMailTpl;
    protected $fUserMailTpl;

    /** @var array Данные для отправки */
    protected $data;

    /** @var DocumentParser */
    protected $modx;

    /** @var MODxMailer */
    protected $mail;

    public function __construct(DocumentParser $modx, array $config, $data = [])
    {
        $this->fSubject = 'subject';
        $this->fEmpty = 'email';
        $this->fMail = 'mail';
        $this->fMailTpl = 'mail_tmpl';
        $this->fUserMailTpl = 'mail_user_tmpl';

        $this->data = $data;
        $this->config = $config;
        $this->modx = $modx;

        if (!array_key_exists($this->fEmpty, $this->data) or !empty($this->data[$this->fEmpty])) {
            throw new Exception('Spam detected');
        }

        /** @noinspection PhpUndefinedFieldInspection */
        if (is_null($this->modx->mail)) {
            if (false === $this->modx->loadExtension('MODxMailer')) {
                throw new Exception('Can\'t load extension: MODxMailer');
            }
        }
        /** @noinspection PhpUndefinedFieldInspection */
        $this->mail = $this->modx->mail;

        $this->emailsTo = commaSeparatedStringToArray($this->config['EmailsTo']);
        $this->emailFrom = $this->config['EmailFrom'];

        if (!array_key_exists($this->fMailTpl, $this->data)) {
            throw new Exception('Не задан шаблон для письма. Добавьте в форму поле с именем «' . $this->fMailTpl . '» и названием чанка с шаблоном в качестве значения.');
        }
    }

    public function sendMailWithAttach()
    {
        $fileTypes = $this->getAllowedFileTypes();
        $fileSize = $this->getMaxFileSize();
        $attach = $this->prepareAttachedFiles($fileTypes, $fileSize);

        $fields = [];

        if (!empty($attach)) {
            foreach ($attach as $file) {
                $this->mail->addAttachment($file['tmp_name'], $file['name'], "base64", $file['type']);
                $fields['attach'][] = $file['name'];
            }
        }

        $fields['attach'] = (count($fields['attach']) > 0) ? implode(', ', $fields['attach']) : 'нет файлов';
        $this->send($fields);
    }

    public function getMailSubject()
    {
        return (array_key_exists($this->fSubject, $this->data))
            ? $this->data[$this->fSubject]
            : 'Письмо с сайта ' . $this->modx->config['site_name'];
    }

    public function sendMail()
    {
        if (!$this->send()) {
            throw new Exception('Сообщение не отправлено, повторите попытку.');
        }
    }

    public function sendUserMail()
    {
        if (!$this->isRequiredSendToUser()) return false;

        if (TEST) {
            echo 'Ключ шаблона письма юзеру — <tt>' . $this->fUserMailTpl . '</tt>';
            echo 'Шаблон письма юзеру — <tt>' . $this->data[$this->fUserMailTpl] . '</tt>';
        }

        $this->mail->clearAddresses();
        $this->mail->clearAttachments();

        $userMailTpl = $this->data[$this->fUserMailTpl];
        $mailBody = $this->modx->parseChunk($userMailTpl, $this->data, '[+', '+]');

        if (empty($mailBody)) {
            return false;
        }

        $this->mail->addAddress($this->data[$this->fMail]);
        $this->mail->From = $this->emailFrom;
        $this->mail->Subject = $this->getMailSubject();
        $this->mail->Body = $mailBody;
        $this->mail->IsHTML(true);
        $this->mail->AltBody = $this->modx->stripTags($mailBody);

        if (TEST) {
            echo '<div style="border:1px solid;padding:1rem;background:#eee;">';
            echo 'USER EMAIL' . '<br>';
            echo 'Subject: ' . $this->mail->Subject . '<br>';
            echo 'To: ' . join(', ', $this->emailsTo) . '<br>';
            echo 'From: ' . $this->emailFrom . '<br>';
            echo 'Body: ' . '<br>';
            pre(htmlspecialchars($mailBody));
            echo '</div>';
        }

        return $this->mail->send();
    }

    /**
     * @return array
     */
    protected function getAllowedFileTypes()
    {
        $fileTypes = array(
            'image/png',
            'image/jpeg',
            'image/jpg',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'application/msword'
        );
        return $fileTypes;
    }

    /**
     * @return int
     */
    protected function getMaxFileSize()
    {
//        $fileSize = 6;
//        $fileSize = $fileSize > 0 ? ($fileSize * 1024 * 1024) : (2 * 1024 * 1024);
        $fileSize = (int)$this->modx->config['upload_maxsize'];
        return $fileSize;
    }

    /**
     * @param $fileTypes
     * @param $maxFileSize
     * @return array
     */
    protected function prepareAttachedFiles($fileTypes, $maxFileSize)
    {
        $files = [];
        if (!empty($_FILES)) {
            foreach ($_FILES as $fieldName => $values) {
                if (is_array($values['name'])) {
                    $count = count($values['name']);
                    for ($i = 0; $i < $count; $i++) {
                        $files[] = [
                            'name'     => $values['name'][$i],
                            'type'     => $values['type'][$i],
                            'tmp_name' => $values['tmp_name'][$i],
                            'error'    => $values['error'][$i],
                            'size'     => $values['size'][$i],
                        ];
                    }
                } else {
                    $files[] = $values;
                }
            }
        }

        //подготовим файлы для отправки
        $attach = array();
        foreach ($files as $file) {
            //Здесь можем проверить расширения файлов, их длину и т.п.
            if (!$file['error']
                && in_array($file['type'], $fileTypes)
                && ($file['size'] < $maxFileSize)
            ) {
                $name = $this->modx->stripAlias($file["name"]);
                $file['name'] = $name;
                $attach[] = $file;
            }
        }
        return $attach;
    }

    protected function addAddresses()
    {
        foreach ($this->emailsTo as $mail) {
            $this->mail->addAddress($mail);
        }
    }

    /**
     * Определяет, нужно отправлять пользователю уведомление о его действии
     * Для отправки нужно что бы в POST данных
     * @return bool
     */
    private function isRequiredSendToUser()
    {
        // если есть инпут с названием чанка и если есть мыло и оно корректно
        return array_key_exists($this->fUserMailTpl, $this->data)
            && !empty($this->data[$this->fMail])
            && filter_var($this->data[$this->fMail], FILTER_VALIDATE_EMAIL);
    }

    private function send($fields = [])
    {
        $fields = array_merge($fields, $this->data);

        if (TEST) {
            echo 'Ключ шаблона письма — <tt>' . $this->fMailTpl . '</tt>';
            echo 'Шаблон письма — <tt>' . $this->data[$this->fMailTpl] . '</tt>';
        }

        $mailBody = $this->modx->parseChunk($this->data[$this->fMailTpl], $fields, '[+', '+]');

        $this->addAddresses();
        $this->mail->From = $this->emailFrom;
        $this->mail->Subject = $this->getMailSubject();
        $this->mail->IsHTML(true);
        $this->mail->Body = $mailBody;
        $this->mail->AltBody = $this->modx->stripTags($mailBody);

        if (TEST) {
            echo '<div style="border:1px solid;padding:1rem;background:#eee;">';
            echo 'EMAIL' . '<br>';
            echo 'Subject: ' . $this->mail->Subject . '<br>';
            echo 'To: ' . join(', ', $this->emailsTo) . '<br>';
            echo 'From: ' . $this->emailFrom . '<br>';
            echo 'Body: ' . '<br>';
            pre(htmlspecialchars($mailBody));
            echo '</div>';
        }

        return $this->mail->send();
    }
}
