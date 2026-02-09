<?php

namespace TCG\Voyager\FormFields;

use TCG\Voyager\FormFields\AbstractHandler;

class RepeaterHandler extends AbstractHandler
{
    protected $codename = 'repeater';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        return view('voyager::formfields.repeater',[
            'row' => $row,
            'options' => $options,
            'dataType' => $dataType,
            'dataTypeContent' => $dataTypeContent
        ]);
    }
}