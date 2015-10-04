<?php
/**
 * Created by PhpStorm.
 * User: harry
 * Date: 15-3-20
 * Time: 下午3:50
 */

namespace harryzheng0907\rest;
use yii\helpers\ArrayHelper;
use yii;

class ActiveRecord  extends \yii\db\ActiveRecord{
    public $expand_param = 'expand';
    public $expand_fields_param = 'expand-fields';
    private $specify_extra_fields = [];

    public function extraFields()
    {
        $expand = explode(',', Yii::$app->request->get($this->expand_param,''));
        $expand_fields = explode(',', Yii::$app->request->get($this->expand_fields_param,''));
        $fields = [];
        //递归函数，循环设置specifyExtraFields
        $setExtraFields = function($expand_field,$obj) use(&$setExtraFields) {
            if(count($expand_field)<=0 || !$obj) return;
            $current_field = array_shift($expand_field);
            if($obj->getRelation($current_field, false) || array_key_exists($current_field,$obj->expandFields())){
                $obj->specify_extra_fields[$current_field] = function($obj,$field) {
                    return $obj->$field;
                };
                if(is_array($obj->$current_field)){
                    foreach($obj->$current_field as $item)
                        $setExtraFields($expand_field,$item);
                }else{
                    $setExtraFields($expand_field,$obj->$current_field);
                }
                return;
            }
            return ;
        };

        foreach($expand_fields as $key => $field)
            $expand_fields[$key] = explode('.',$field);

        foreach($expand as $field){
            $expand_fields[] = [$field];
        }

        foreach($expand_fields as $key => $field) {
            if($this->getRelation($field[0],false)|| array_key_exists($field[0],$this->expandFields())){
                $setExtraFields($field,$this);
                $fields[$field[0]] = function($obj,$field){
                    return $obj->$field;
                };
            }
        }
        return ArrayHelper::merge($fields,$this->expandFields());
    }

    public function expandFields(){
        return [];
    }

    public function fields()
    {
        $fields = parent::fields();
        return ArrayHelper::merge($fields,$this->specify_extra_fields);
    }

    public function hasOne($class, $link,$alias = null)
    {
        $query = parent::hasOne($class,$link);
        if($alias == null)
            $alias = lcfirst(str_replace('_','',$class::tableName()));
        return $query->from([$alias => $class::tableName()]);
    }

    public function hasMany($class, $link,$alias = null){
        $query = parent::hasMany($class,$link);
        if($alias == null)
            $alias = lcfirst(str_replace('_','',$class::tableName()));
        return $query->from([$alias => $class::tableName()]);
    }
} 