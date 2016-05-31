<?php

namespace asakasinsky\cart\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "c_cart_order".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $device_id
 * @property string $name
 * @property string $phone
 * @property string $email
 * @property string $comment
 * @property string $guid
 * @property string $date
 * @property integer $status
 * @property integer $status_timestamp
 * @property integer $total
 * @property integer $qty
 * @property string $delivery
 * @property string $recipient_name
 * @property string $recipient_passport
 * @property string $recipient_address
 * @property string $order_file
 * @property string $payment_date
 * @property string $note
 * @property integer $ip
 * @property string $status_message
 * @property string $status_attachment
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $email_validation
 *
 * @property CartItem[] $cartItems
 */
class CartOrder extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cart_order}}';
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
            [['user_id', 'device_id', 'status', 'status_timestamp', 'total', 'qty', 'ip', 'created_at', 'updated_at', 'email_validation'], 'integer'],
            [['comment', 'note', 'status_message'], 'string'],
            [['date', 'payment_date'], 'safe'],
            [['name', 'phone', 'email', 'delivery', 'order_file', 'status_attachment'], 'string', 'max' => 255],
            [['guid'], 'string', 'max' => 36],
            [['recipient_name', 'recipient_passport', 'recipient_address'], 'string', 'max' => 512],
            [['device_id'], 'exist', 'skipOnError' => true, 'targetClass' => Device::className(), 'targetAttribute' => ['device_id' => 'id']],
//            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
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
            'device_id' => 'Device ID',
            'name' => 'Name',
            'phone' => 'Phone',
            'email' => 'Email',
            'comment' => 'Comment',
            'guid' => 'Guid',
            'date' => 'Date',
            'status' => 'Status',
            'status_timestamp' => 'Status Timestamp',
            'total' => 'Total',
            'qty' => 'Qty',
            'delivery' => 'Delivery',
            'recipient_name' => 'Recipient Name',
            'recipient_passport' => 'Recipient Passport',
            'recipient_address' => 'Recipient Address',
            'order_file' => 'Order File',
            'payment_date' => 'Payment Date',
            'note' => 'Note',
            'ip' => 'Ip',
            'status_message' => 'Status Message',
            'status_attachment' => 'Status Attachment',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'email_validation' => 'Email Validation',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCartItems()
    {
        return $this->hasMany(CartItem::className(), ['order_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDevice()
    {
        return $this->hasOne(Device::className(), ['id' => 'device_id']);
    }

//    /**
//     * @return \yii\db\ActiveQuery
//     */
//    public function getUser()
//    {
//        return $this->hasOne(User::className(), ['id' => 'user_id']);
//    }
}
