<?php
namespace sky\yii\db;

use Yii;
use yii\helpers\Inflector;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ActiveRecord extends \yii\db\ActiveRecord
{
    static $errors = [];
    private static $_const;


    const EVENT_ADD_ERROR = 'onAddError';
    const EVENT_ERROR = 'onError';
    
    public function init()
    {
        foreach ($this->event() as $name => $event) {
            foreach ($event as $handler) {
                if (!isset($handler[1])) {
                    $handler[1] = null;
                }
                $this->on($name, $handler[0], $handler[1]);
            }
        }
    }
    
    /**
     * 
     * Example :
     * ```
     * return [
     *      self::EVENT_BEFORE_INSERT => [
     *          [[$this, 'onSetValue'], ['attributes' => ['created_at', 'updated_at'], 'value' => time()]],
     *          [[$this, 'onSetValue'], ['attributes' => ['created_by', 'updated_by'], 'value' => Yii::$app->user->id]],
     *      ],
     *      self::EVENT_BEFORE_UPDATE => [
     *          [[$this, 'onSetValue'], ['attributes' => ['updated_at'], 'value' => time()]],
     *      ],
     *  ];
     * 
     * @return array
     */
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
            self::EVENT_AFTER_VALIDATE => [
                [[$this, 'onTransaction']],
            ],
        ];
    }
    /**
     * get constants by name
     * 
     * @param string $name
     * @return array
     */
    public static function getConstants($name) 
    {
        if (isset(self::$_const[$name])) {
            return self::$_const[$name];
        }
        $self = new \ReflectionClass(new static());
        $contants = $self->getConstants();
        $prefix = strtoupper($name) . '_';
        $prefixLength = strlen($prefix);
        $prefixOffset = $prefixLength - 1;
        self::$_const[$name] = [];
        foreach ($contants as $key => $value) {
            if (substr($key, 0, $prefixLength) === $prefix) {
                self::$_const[$name][$value] = ucwords(strtolower(Inflector::humanize(substr($key, $prefixLength))));
            }
        }
        return self::$_const[$name];
    }
    
    public static function getConstant($name, $value)
    {
        if ($options = static::getConstants($name)) {
            if (isset($options[$value])) {
                return $options[$value];
            }
        }
        return false;
    }
    
    public function addError($attribute, $error = '') {
        if (!self::hasError()) {
            $this->trigger(self::EVENT_ERROR);
        }
        self::$errors[$this->className()][$attribute][] = $error;
        parent::addError($attribute, $error);
        $this->trigger(self::EVENT_ADD_ERROR);
        
        return $this;
    }
    
    /**
     * 
     * @return boolean
     */
    public static function hasError()
    {
        return self::$errors ? true : false;
    }
    
    protected function onSetValue($event)
    {
        if (is_array($event->data['attributes'])) {
            foreach ($event->data['attributes'] as $attribute) {
                if ($this->hasAttribute($attribute) || $this->hasProperty($attribute)) {
                    $this->{$attribute} = $event->data['value'];
                }
            }
        }
    }
    
    protected function onTransaction($event)
    {
        $transaction = $this->db->getTransaction();
        if ($transaction) {
            if ($transaction->isActive && self::hasError()) {
                throw new \yii\db\Exception(Yii::t('app', 'Fail Transaction Data'));
            }
        }
    }
}
