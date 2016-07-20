<?php
namespace sky\yii\db;

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ActiveRecord extends \yii\db\ActiveRecord
{
    static $errors = [];
    
    const EVENT_ERROR = 'onError';
    
    public function addError($attribute, $error = '') {
        self::$errors[$this->className()][$attribute][] = $error;
        parent::addError($attribute, $error);
        $this->trigger(self::EVENT_ERROR);
    }
}
