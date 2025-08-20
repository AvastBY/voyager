<?php

namespace TCG\Voyager\FormFields;

use TCG\Voyager\FormFields\AbstractHandler;

class AttributedGalleryHandler extends AbstractHandler
{
    protected $codename = 'attributed_gallery';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        return view('voyager::formfields.attributed_gallery',[
            'row' => $row,
            'options' => $options,
            'dataType' => $dataType,
            'dataTypeContent' => $dataTypeContent,
        ]);
    }
}
