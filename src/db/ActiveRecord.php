<?php
namespace sky\yii\db;

use Yii;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ActiveRecord extends \yii\db\ActiveRecord
{
    static $errors = [];
    
    const EVENT_ADD_ERROR = 'onAddError';
    const EVENT_ERROR = 'onError';
    
    public function init()
    {
        foreach ($this->event() as $name => $event) {
            foreach ($event as $handler) {
                $this->on($name, $handler[0], $handler[1]);
            }
        }
    }
    
    public function event()
    {
        return [
            self::EVENT_BEFORE_INSERT => [
                [[$this, 'onSetValue'], ['attributes' => ['created_at', 'updated_at'], 'value' => time()]],
                [[$this, 'onSetValue'], ['attributes' => ['created_by', 'updated_by'], 'value' => Yii::$app->user->id]],
            ],
            self::EVENT_BEFORE_UPDATE => [
                [[$this, 'onSetValue'], ['attributes' => ['updated_at'], 'value' => time()]],
                [[$this, 'onSetValue'], ['attributes' => ['updated_by'], 'value' => Yii::$app->user->id]],
            ],
        ];
    }
    
    public function addError($attribute, $error = '') {
        if (!self::isError()) {
            $this->trigger(self::EVENT_ERROR);
        }
        self::$errors[$this->className()][$attribute][] = $error;
        parent::addError($attribute, $error);
        $this->trigger(self::EVENT_ADD_ERROR);
    }
    
    public static function isError()
    {
        return self::$errors ? true : false;
    }
    
    public function onSetValue($event)
    {
        if (is_array($event->data['attributes'])) {
            foreach ($event->data['attributes'] as $attribute) {
                if ($this->hasAttribute($attribute) || $this->hasProperty($attribute)) {
                    $this->{$attribute} = $event->data['value'];
                }
            }
        }
    }
}
