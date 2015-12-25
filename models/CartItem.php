<?php

namespace  asakasinsky\cart\models;

use Yii;

/**
 * This is the model class for table "{{%cart_item}}".
 *
 * @property integer $id
 * @property integer $product_id
 * @property string $product_name
 * @property string $product_image
 * @property string $product_type
 * @property string $product_url
 * @property integer $qty
 * @property integer $total
 * @property integer $color_id
 * @property string $color_image
 * @property string $color_name
 * @property integer $size_id
 * @property string $size_name
 * @property integer $size_cost
 * @property integer $only_all_sizes
 * @property string $row_id
 * @property integer $order_id
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property CartOrder $order
 */
class CartItem extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cart_item}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['product_id', 'color_id', 'size_id', 'order_id', 'created_at', 'updated_at'], 'required'],
            [['product_id', 'qty', 'total', 'color_id', 'size_id', 'size_cost', 'only_all_sizes', 'order_id', 'created_at', 'updated_at'], 'integer'],
            [['product_name', 'product_image', 'product_type', 'color_image', 'color_name', 'size_name'], 'string', 'max' => 128],
            [['product_url'], 'string', 'max' => 1024],
            [['row_id'], 'string', 'max' => 36],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => CartOrder::className(), 'targetAttribute' => ['order_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Product ID',
            'product_name' => 'Product Name',
            'product_image' => 'Product Image',
            'product_type' => 'Product Type',
            'product_url' => 'Product Url',
            'qty' => 'Qty',
            'total' => 'Total',
            'color_id' => 'Color ID',
            'color_image' => 'Color Image',
            'color_name' => 'Color Name',
            'size_id' => 'Size ID',
            'size_name' => 'Size Name',
            'size_cost' => 'Size Cost',
            'only_all_sizes' => 'Only All Sizes',
            'row_id' => 'Row ID',
            'order_id' => 'Order ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(CartOrder::className(), ['id' => 'order_id']);
    }
}
