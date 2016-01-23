<?php

namespace asakasinsky\cart\models;

use Yii;
use common\models\User;


/**
 * This is the model class for table "c_device".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $guid
 * @property string $device
 * @property string $model
 * @property string $brand
 * @property string $os_name
 * @property string $os_version
 * @property string $client_type
 * @property string $client_name
 * @property string $client_version
 * @property string $pointing_device
 *
 * @property User $user
 */
class Device extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'c_device';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [['guid'], 'string', 'max' => 36],
            [['device', 'brand', 'os_name', 'client_name'], 'string', 'max' => 30],
            [['model'], 'string', 'max' => 128],
            [['os_version', 'client_version'], 'string', 'max' => 20],
            [['client_type'], 'string', 'max' => 12],
            [['pointing_device'], 'string', 'max' => 45],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'guid' => 'Guid',
            'device' => 'Device',
            'model' => 'Model',
            'brand' => 'Brand',
            'os_name' => 'Os Name',
            'os_version' => 'Os Version',
            'client_type' => 'Client Type',
            'client_name' => 'Client Name',
            'client_version' => 'Client Version',
            'pointing_device' => 'Pointing Device',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
