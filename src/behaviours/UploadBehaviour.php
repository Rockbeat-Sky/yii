<?php

namespace sky\yii\behaviours;

use Yii;
use yii\db\BaseActiveRecord;
use yii\behaviors\AttributeBehavior;
use yii\web\UploadedFile;


class UploadBehavior extends AttributeBehavior
{

    public $attribute = 'file';
    public $idAttribute = 'id';
    public $path = 'file/{id}';
    public $forceReplaceOldFile = true;
    public $value;

    public function init()
    {
        parent::init();

        if (empty($this->attributes)) {
            $this->attributes = [
                BaseActiveRecord::EVENT_BEFORE_INSERT => $this->attribute,
                BaseActiveRecord::EVENT_BEFORE_UPDATE => $this->attribute,
            ];
        }
    }

    protected function getValue($event)
    {
        $owner = $this->owner;
        
        $file = $owner->{$this->attribute};
        
        if (is_string($file)) {
            return $file;
        }
        
        if ($file instanceof UploadedFile) {
            $path = $this->path;
            
            $path = str_replace("{id}", $this->owner->{$this->idAttribute}, $this->path);

            $filePath = $path . time() . '.' . $fileAttr->getExtension();

            if (!$this->foreceReplaceOldFile && file_exists($filePath)) {
                return null;
            }
            $file->saveAs($filePath);
            return $filePath;
        } 
        
        return null;
    }
}
