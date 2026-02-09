<?php

namespace TCG\Voyager\FormFields;

use TCG\Voyager\FormFields\AbstractHandler;

class SvgHandler extends AbstractHandler
{
    protected $codename = 'svg';

    public function createContent($row, $dataType, $dataTypeContent, $options = [])
    {
        return view('voyager::formfields.svg',[
            'row' => $row,
            'options' => $options,
            'dataType' => $dataType,
            'dataTypeContent' => $dataTypeContent
        ]);
    }
}