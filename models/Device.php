<?php

namespace asakasinsky\cart\models;

use Yii;
use common\models\User;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

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
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property User $user
 */
class Device extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%device}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'created_at', 'updated_at'], 'integer'],
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
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCartOrders()
    {
        return $this->hasMany(CartOrder::className(), ['device_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
