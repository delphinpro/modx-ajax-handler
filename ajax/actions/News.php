<?php
/**
 * ModX Ajax action example
 *
 * @author      delphinpro <delphinpro@gmail.com>
 * @copyright   copyright © 2018 delphinpro
 * @license     licensed under the MIT license
 */


/**
 * Class NewsAjaxAction
 */
class NewsAjaxAction extends AjaxAction
{

    public function exec()
    {
        $start = $this->getIntParam('start', 1, 1) - 1;
        $chunk = $this->getIntParam('chunk', 6, 1);
        $sectionId = $this->getIntParam('sectionId', 0, 0);

        if (!$sectionId) {
            throw new Exception('Неверный идентификатор контейнера — ' . $sectionId);
        }

        $res = $this->modx->runSnippet('DocLister', [
            'parents'      => $sectionId,
            'api'          => 1,
            'JSONformat'   => 'new',
            'depth'        => 1,
            'selectFields' => 'c.id,c.pagetitle,c.pub_date',
            'tvList'       => 'newsCover',
            'offset'       => $start,
            'display'      => $chunk,

            'sortType' => 'other',
            'orderBy'  => 'menuindex ASC',
        ]);
        $res = json_decode($res, true);

        $months = ['','января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
            'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];

        $items = [];
        foreach ($res['rows'] as $row) {
            $day = date("j", $row['pub_date']);
            $month = $months[date("n", $row['pub_date'])];
            $year = date("Y", $row['pub_date']);

            $items[] = [
                'id'         => (int)$row['id'],
                'title'      => $row['e_title'],
                'date'       => date('Y-m-d', $row['pub_date']),
                'dateFormat' => $day . ' ' . $month . ' ' . $year,
                'cover'      => $row['tv_newsCover'] ? $this->modx->runSnippet('phpthumb', [
                    'input'   => $row['tv_newsCover'],
                    'options' => 'w_270,h_200,zc_C,bg_EEEEEE',
                ]) : null,
                'url'        => str_replace('ajax/', '', $row['url']),
            ];
        }

        AjaxResponse::getInstance()->send([
            'items' => $items,
            'total' => $res['total'],
            'rows'  => $res['rows'],
        ]);
    }
}
