<?php

namespace TCG\Voyager\Models;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Events\SettingUpdated;

class Setting extends Model
{
    protected $table = 'settings';

    protected $guarded = [];

    public $timestamps = false;

    protected $dispatchesEvents = [
        'updating' => SettingUpdated::class,
    ];
    
    public function getInputNameAttribute(){
    	$key = $this->key;
    	if($this->multilingual) $key .= '.'.$this->locale;
		return $key;
	}
}
