<?php
/**
 * Created by PhpStorm.
 * User: harry
 * Date: 15-3-20
 * Time: 下午3:38
 */

namespace harryzheng0907\rest;

use yii;
use harryzheng0907\rest\CreateQueryHelper;
use yii\data\ActiveDataProvider;

class IndexAction extends \yii\rest\IndexAction{
    protected function prepareDataProvider()
    {
        $modelClass = $this->modelClass;
        $sort = yii::$app->request->get('sort','');
        $query = CreateQueryHelper::createQuery($this->modelClass);
        CreateQueryHelper::addOrderSort($sort, $modelClass::tableName(), $query);
        return new ActiveDataProvider([
            'query' => $query->distinct(),
            'pagination' => isset($_GET['page'])?[]:false
        ]);
    }
} 