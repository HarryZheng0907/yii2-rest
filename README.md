概述
------------
这个扩展主要是用于增强Yii2 rest api中IndexAction的功能.

相对于官方的IndexAction，该扩展主要增加了以下几个功能

1.搜索功能
```
http://url/users?id=1&username=LIKE_dmi&created_at=MAX_1398153715&addresses.city=南京
```
2.显示多级子资源(官方只支持二级子资源（通过expand关键字指定），如users?expand=address）
```
http://url/users?expand=addresses,friends.addresses&expand-fields=addresses.phone,friends.addresses
```
3.允许通过子资源排序
```
http://url/users?sort=addresses.phone DESC
```
4.允许为子资源的表设置别名（防止两个不同的子资源取同一张表时的命名冲突）
```
use harryzheng0907\rest\ActiveRecord;

class User extends ActiveRecord
{
    public static function tableName()
    {
        return 'user';
    }
    
    public function getMainAddresses()
    {
        return $this->hasMany(Address::className(), ['user_id' => 'id'],'main_addresses');
    }
    public function getOtherAddresses()
    {
        return $this->hasMany(Address::className(), ['user_id' => 'id'],'other_addresses');
    }
}
```

安装
------------
通过 [composer](http://getcomposer.org/download/)安装

```
php composer.phar require harryzheng0907/yii2-rest
```
使用
------------

#### 使用概述
使用方式其实就是两点

1. 将IndexAction指向harryzheng0907\rest\IndexAction
2. 使用的AR Model要继承自harryzheng0907\rest\ActiveRecord

#### 详细步骤
1.新建一个全局父Controller,继承ActiveController，重新指定IndexAction
```
namespace app\controllers;

use yii\helpers\ArrayHelper;

class ActiveController extends \yii\rest\ActiveController {
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(),[
            'index' => [
                'class' => 'harryzheng0907\rest\IndexAction'
            ]
        ]);
    }
} 
```
2.新建特定资源的AR Model，继承harryzheng0907\rest\ActiveRecord，如user
```
namespace app\models;

use Yii;
use harryzheng0907\rest\ActiveRecord;

class User extends ActiveRecord
{
    public static function tableName()
    {
        return 'user';
    }
}
```
3.新建特定资源的controller，如user，继承第一步新建的全局controller
```
namespace app\controllers;

class UserController extends \app\controllers\ActiveController {
    public $modelClass = 'app\models\User';
} 
```
4.完成
