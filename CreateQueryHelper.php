<?php
/**
 * Created by PhpStorm.
 * User: harry
 * Date: 14-9-13
 * Time: 下午7:22
 */

namespace harryzheng0907\rest;

use Yii;
use yii\helpers\ArrayHelper;


class CreateQueryHelper {
    public static $exclude_field = ['fields', 'expand', 'sort', 'page', 'per-page','expand-fields','r','PHPSESSID'];
    public static  function createQuery($modelClass, $ignore=[]){
        $model = $modelClass::find();
        $wheres = ['and'];
        $filter_fields = self::getQueryParams($ignore);
        $condition_transform_functions = self::conditionTransformFunctions();
        foreach($filter_fields as $key => $value){
            if($value == '' || in_array($key,self::$exclude_field))
                continue;
            $field_key = $key;
            if(!strpos($key,'.')){
                $field_key =  $modelClass::tableName().'.'.$key ;
            }else{
                $relation_model = substr($field_key,0,strrpos($key,'.'));
                $model->joinWith($relation_model);
                if(strpos($relation_model,'.')){
                    $temp = substr($field_key,strrpos($field_key,'.'));
                    $field_key = substr($relation_model,strrpos($relation_model,'.')+1).$temp;
                }
            }

            $type = 'EQUAL';
            if(preg_match("/^[A-Z]+_/",$value, $matches) && array_key_exists(trim($matches[0],'_'),$condition_transform_functions)){
                $type = trim($matches[0],'_');
                $value = str_replace($matches[0],'',$value);
            }

            $wheres = ArrayHelper::merge($wheres, [$condition_transform_functions[$type]($field_key,$value)]);
        }
        if(count($wheres) > 1)
            $model->andWhere($wheres);


        return $model;
    }

    public static function addOrderSort($sort, $table, &$query)
    {
        if($sort == '')
            $order = $table.".id DESC";
        else {
            $sorts = explode(',', $sort);
            foreach ($sorts as $sort) {
                if(!strpos($sort,'.')) {
                        preg_match('/\w+\s+(DESC|ASC)/', $sort, $sort_field);
                        $type = !empty($sort_field) ? trim($sort_field[1]) : 'DESC';
                        $field = !empty($sort_field) ? trim( substr($sort, 0, -strlen($type)) ) : trim($sort);
                        $order[$table.'.'.$field] = $type == 'DESC'?SORT_DESC:SORT_ASC;
                } else {
                        $sort_table = trim( substr($sort,0,strrpos($sort,'.')) );
                        preg_match('/\w+\.\w+\s+(DESC|ASC)/', $sort, $sort_field);
                        $type = trim($sort_field[1]);
                        $field = trim( substr(substr($sort, strrpos($sort,'.')+1), 0, -strlen($type) ) );
                        $order[trim($sort_table).'.'.$field] =  $type == 'DESC'?SORT_DESC:SORT_ASC;;
                        $query->select[] = explode(' ',$sort_field[0])[0];
                        $query->joinWith($sort_table);
                }
            }
            $query->select[] = $table.".*";
        }
        $query->orderBy($order);
    }

    private static function getQueryParams($ignore)
    {
        $pairs = explode("&", urldecode(Yii::$app->getRequest()->queryString));
        $vars = [];
        foreach ($pairs as $pair) {
            if($pair == '')continue;
            $nv = explode("=", $pair);
            if(count($nv) != 2) continue;
            $name = urldecode($nv[0]);
            $value = urldecode($nv[1]);
            if (!in_array($name, $ignore)) {
                $vars[$name] = $value;
            }

        }
        return $vars;
    }

    private static function splitParam($param)
    {
        $keys = explode(".",$param);
        $condition = '';
        $i = 1;
        foreach($keys as $key) {
            $condition .= '"'.$key.'"';
            if($i < count($keys)) {
                $condition .= '.';
            }
            $i ++;
        }
		return $condition;
    }

    public static function conditionTransformFunctions(){
         return [
             'EQUAL' => function($field,$value){
                    return [$field=>$value];
                },
             'NOTEQUAL' => function($field,$value){
                     return ['NOT',[$field=>$value]];
                 },
             'NULL' => function($field,$value){
                     return [$field=>null];
                 },
             'LIKE' => function($field,$value){
                     return ['LIKE',$field,$value];
                 },
             'LLIKE' => function($field,$value){
                     return ['LIKE',$field,'%'.$value,false];
                 },
             'RLIKE' => function($field,$value){
                     return ['LIKE',$field,$value.'%',false];
                 },
             'IN' => function($field,$value){
                     return ['IN',$field,explode(',',$value)];
                 },
             'NOTIN' => function($field,$value){
                     return ['NOT IN',$field,explode(',',$value)];
                 },
             'MIN' => function($field,$value){
                     return ['>=',preg_replace("/_min$/",'',$field,1),$value];
                 },
             'MAX' => function($field,$value){
                     $time = DateTimeHelper::isNormalTime($value);
                     if(is_array($time)) {
                         $value = DateTimeHelper::getMaxNormalTime($time)['value'];
                         return ['<',preg_replace("/_max$/",'',$field,1),$value];
                     }
                     return ['<=',preg_replace("/_max$/",'',$field,1),$value];
                 },
             'RANGE' => function($field,$value){
                     // 判定是否是时间格式
                     $time = DateTimeHelper::isNormalTime($value);
                     if(is_array($time)) {
                         $maxTime = DateTimeHelper::getMaxNormalTime($time);
                         $value = DateTimeHelper::setNormalTime($time);
                         $maxValue = DateTimeHelper::setNormalTime($maxTime);
                         return ['and', "$field>='".date('Y-m-d H:i:s', strtotime($value))."' and $field<'".date('Y-m-d H:i:s', strtotime($maxValue))."'"];
                     }
                 }
        ];
    }
} 
