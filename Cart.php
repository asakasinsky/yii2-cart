<?php

namespace asakasinsky\cart;
use yii\base\Component;
// use yii\helpers\VarDumper;

class Cart extends Component
{

    public $itemsTableName = '{{%cart_items}}';
    public $ordersTableName = '{{%cart_orders}}';
    public $items = [];
    protected $cartContents = [];
}
