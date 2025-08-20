<?php

namespace TCG\Voyager\FormFields;

use TCG\Voyager\FormFields\AbstractHandler;

class ValuesListHandler extends AbstractHandler
{
    protected $codename = 'values_list';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        return view('voyager::formfields.values_list', [
            'row'             => $row,
            'options'         => $options,
            'dataType'        => $dataType,
            'dataTypeContent' => $dataTypeContent,
        ]);
    }

}
