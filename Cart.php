<?php

namespace asakasinsky\cart;

use Yii;
use yii\base\Component;
use \yii\db\Query;
use asakasinsky\cart\models\CartItem;
use asakasinsky\cart\models\CartOrder;

class Cart extends Component
{

    public $itemsTableName = '{{%cart_items}}';
    public $ordersTableName = '{{%cart_orders}}';
    public $items = [];
    public $cartContents = [
        'cartTotal' => 0,
        'qtyTotal' => 0,
        'items' => [],
        'rows' => []
    ];

    public function __construct()
    {
        $this->cartContents = $this->get();
    }

    private function _sortByOrder($a, $b)
    {
        return strcmp((int)  $a['order'], (int)  $b['order']);
    }

    private function _sortById($a, $b)
    {
        return strcmp($a['id'], $b['id']);
    }


    /**
     * Create GUID function
     * http://en.wikipedia.org/wiki/Globally_unique_identifier
     *
     * @param  string $namespace  for more enthropy
     * @return string $guid       00000000-0000-0000-0000-000000000000
     */
    private function createGuid ($namespace='')
    {
        static $guid = '';
        $uid = uniqid($namespace, true);
        $allowed_keys = array(
            'REQUEST_TIME',
            'HTTP_USER_AGENT',
            'LOCAL_ADDR',
            'REMOTE_ADDR',
            'REMOTE_PORT'
        );
        $request_data = array_intersect_key(
            $_SERVER,
            array_fill_keys(
                $allowed_keys,
                null
            )
        );
        $data = implode('', $request_data);
        $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
        $guid = substr($hash,  0,  8).
            '-'.substr($hash,  8,  4).
            '-'.substr($hash, 12,  4).
            '-'.substr($hash, 16,  4).
            '-'.substr($hash, 20, 12);
        return $guid;
    }

    public function get()
    {
        $order = null;
        $cartItems = [];
        $resultSet = [];
        $cartGuid = Yii::$app->session->get('cartGuid');
        if ($cartGuid)
        {
            $order = CartOrder::find()
                ->where('guid = :guid ', ['guid' => $cartGuid])
                ->one();
            if ($order)
            {
//                $resultSet = CartItem::find()
//                    ->where('order_id = :order_id ', ['order_id' => $order->id])
////                    ->asArray()
//                    ->all();

                $resultSet = (new Query())
                    ->select([
                        '{{%cart_item}}.`id` AS id',
                        '{{%cart_item}}.`product_id` AS productId',
                        '{{%cart_item}}.`product_name` AS productName',
                        '{{%cart_item}}.`product_image` AS productImage',
                        '{{%cart_item}}.`product_type` AS productType',
                        '{{%cart_item}}.`product_url` AS productUrl',
                        '{{%cart_item}}.`qty` AS qty',
                        '{{%cart_item}}.`total` AS total',
                        '{{%cart_item}}.`color_id` AS colorId',
                        '{{%cart_item}}.`color_image` AS colorImage',
                        '{{%cart_item}}.`color_name` AS colorName',
                        '{{%cart_item}}.`size_id` AS sizeId',
                        '{{%cart_item}}.`size_name` AS sizeName',
                        '{{%cart_item}}.`size_cost` AS sizeCost',
                        '{{%cart_item}}.`only_all_sizes` AS onlyAllSizes',
                        '{{%cart_item}}.`row_id` AS rowId',
                        '{{%cart_item}}.`order_id` AS orderId',
                        '{{%cart_item}}.`created_at` AS createdAt',
                        '{{%cart_item}}.`updated_at` AS updatedAt',
                        '{{%product_type_size}}.`id` AS `order`'
                    ])
                    ->from('{{%cart_item}}')
                    ->leftJoin('{{%product_size}}', '{{%product_size}}.id = {{%cart_item}}.size_id')
                    ->leftJoin('{{%product_type_size}}', '{{%product_type_size}}.id = {{%product_size}}.type_size_id')
                    ->where('{{%cart_item}}.order_id = :order_id', ['order_id' => $order->id])
                    ->all();
            }
            foreach ($resultSet as &$row)
            {
                $row['id'] = (int) $row['id'];
                $row['productId'] = (int) $row['productId'];
                $row['qty'] = (int) $row['qty'];
                $row['total'] = (int) $row['total'];
                $row['colorId'] = (int) $row['colorId'];
                $row['sizeId'] = (int) $row['sizeId'];
                $row['sizeCost'] = (int) $row['sizeCost'];
                $row['orderId'] = (int) $row['orderId'];
                $row['order'] = (int) $row['order'];
                $row['createdAt'] = (int) $row['createdAt'];
                $row['updatedAt'] = (int) $row['updatedAt'];
                $row['onlyAllSizes'] = (int) $row['onlyAllSizes'];

                $cartItems[$row['productId']]['id'] = $row['productId'];
                $cartItems[$row['productId']]['productId'] = $row['productId'];
                $cartItems[$row['productId']]['productImage'] = $row['productImage'];
                $cartItems[$row['productId']]['productName'] = $row['productName'];
                $cartItems[$row['productId']]['colorId'] = ($row['colorId'] === 0) ? null : $row['colorId'];
                $cartItems[$row['productId']]['colorImage'] = $row['colorImage'];
                $cartItems[$row['productId']]['cost'] = $row['sizeCost'];
                $cartItems[$row['productId']]['qty'] = $row['qty'];
                $cartItems[$row['productId']]['sizeId'] = null;
                $cartItems[$row['productId']]['sizeName'] = '';
                $cartItems[$row['productId']]['onlyAllSizes'] = $row['onlyAllSizes'];
            }
            $cartItems = array_reverse(array_values($cartItems));
            $orderRows = [];


            foreach($cartItems as &$item)
            {
                if ((int) $item['onlyAllSizes'] === 1)
                {
                    $rows = array_filter($resultSet, function ($row) use($item) {
                        return $row['productId'] === $item['productId'];
                    });
                    $rows = array_reverse(array_values($rows));
                    usort($rows, array($this->className(), '_sortByOrder'));

                    $byColors = [];
                    foreach ($rows as $_row)
                    {
                        $byColors[$_row['colorId']][] = $_row;
                    }

                    foreach ($byColors as $color)
                    {
                        $firstRow = reset($color);
                        $firstName =$firstRow['sizeName'];
                        $arrKeys = array_keys($color);
                        $lastName = $color[end(
                            $arrKeys
                        )]['sizeName'];
                        $firstRow['sizeName'] = $firstName .' - '. $lastName;
                        $prices = array_column($color, 'sizeCost');
                        $firstRow['sizeCost'] = array_sum($prices);
                        $firstRow['sizeId'] = 'all';
//                        $items[] = $firstRow;
                        $item['sizes'][$firstRow['id']] = $firstRow;
                    }
                    $item['sizes'] = array_reverse(array_values($item['sizes']));
                }
                else
                {
                    $rows = array_filter($resultSet, function ($row) use($item) {
                        return $row['productId'] === $item['productId'];
                    });
                    $rows = array_reverse(array_values($rows));
                    usort($rows, array($this->className(), '_sortByOrder'));

                    $item['sizes'] = $rows;
                }

                $item['total'] = 0;
                $item['qty'] = 0;
                foreach ($item['sizes'] as &$size)
                {
                    $size['id'] = $size['productId'] .'_'. $size['colorId'] .'_'. $size['sizeId'];
                    $size['total'] = $size['qty'] * $size['sizeCost'];
                    $item['total'] = $item['total'] +  $size['total'];
                    $item['qty'] = $item['qty'] + $size['qty'];
                }
                $item['colorId'] = null;
                $item['colorImage'] = '';
                $item['cost'] = 0;
                $item['sizeId'] = null;
                $item['sizeName'] = '';
                $orderRows = array_merge($orderRows, $item['sizes']);
            }

            $this->cartContents['rows'] = $orderRows;
            $this->cartContents['cartTotal'] = $order->total;
            $this->cartContents['qtyTotal'] = $order->qty;
            $this->cartContents['items'] = $cartItems;
        }
        return $this->cartContents;
    }


    public function remove($items=[])
    {
        $result = false;
        $cartGuid = Yii::$app->session->get('cartGuid');
        $order = CartOrder::find()
            ->where('guid = :guid ', ['guid' => $cartGuid])
            ->one();
        if ($order && $items) {
            foreach($items as $item) {
                if (isset($item['rowId'])) {
                    $rowsCount = CartItem::deleteAll(
                        'row_id = :row_id AND order_id = :order_id',
                        [':row_id' => $item['rowId'], ':order_id' => $order['id']]
                    );
                }
            }
            $result = true;
            $this->updateOrderTotal($order['id']);
        }
        return [
            'result' => $result
        ];
    }

    public function put($items=[])
    {
        $order = null;

        $cartGuid = Yii::$app->session->get('cartGuid');
        $deviceId = Yii::$app->session->get('deviceId');

        if (! $deviceId)
        {
            $deviceId = $this->createGuid();
            Yii::$app->session->set('deviceId', $deviceId);
        }

        if ($cartGuid)
        {
            $order = CartOrder::find()
                ->where('guid = :guid ', ['guid' => $cartGuid])
                ->one();
            if (! $order)
            {
                $order = new CartOrder();
                $order->guid = $cartGuid;
                $order->device_id = null;
                if (! $order->save())
                {
                    Yii::error(
                        $order->getErrors(),
                        __LINE__
                    );
                }

            }
        }
        if (! $order || ! $cartGuid)
        {
            $cartGuid = $this->createGuid();
            Yii::$app->session->set('cartGuid', $cartGuid);
            $order = new CartOrder();
            $order->guid = $cartGuid;
            $order->device_id = null;
            if (! $order->save())
            {
                Yii::error(
                    $order->getErrors(),
                    __LINE__
                );
            }
        }
        $rows = [];

        foreach($items as $item)
        {
            if (! isset($item['rowId']))
            {
                $rowId =  $this->createGuid($cartGuid);
                $item['rowId'] = $rowId;
                $item['orderId'] = $order->id;
                $this->insertRow($item);
                $rows[] = [
                    'rowId' => $rowId,
                    'productId' => $item['productId'],
                    'id' => $item['id']
                ];
            }
            else
            {
                if ($item['qty'] <= 0) {
                    $rowsCount = CartItem::deleteAll(
                        'row_id = :row_id AND order_id = :order_id',
                        [':row_id' => $item['rowId'], ':order_id' => $order['id']]
                    );
                } else {
                    $item['orderId'] = $order->id;
                    $this->changeQtyRow($item);
                    $rows[] = [
                        'rowId' => $item['rowId'],
                        'productId' => $item['productId'],
                        'id' => $item['id']
                    ];
                }
            }
        }
        $this->updateOrderTotal($order->id);
        return [
            'rows' => $rows
        ];
    }

    protected function updateOrderTotal($orderId)
    {
        $orderId = (int) $orderId;
        $resultSet = (new Query())
            ->select([
                'SUM(total) AS total',
                'SUM(qty) AS qty',
            ])
            ->from('{{%cart_item}}')
            ->where('{{%cart_item}}.order_id = :order_id', ['order_id' => $orderId])
            ->one();
        $order = CartOrder::find()
            ->where('id = :order_id ', ['order_id' => $orderId])
            ->one();
        if ($order) {
            $order->total = $resultSet['total'];
            $order->qty = $resultSet['qty'];
            $order->save();
        }
    }

    protected function insertRow($rowData)
    {
        $idParts = explode('_', $rowData['id']);
        $productId = (int) $idParts[0];
        $colorId = (int) $idParts[1];
        $sizeId = $idParts[2];

        $resultSet = (new Query())
            ->select([
                '{{%product}}.`only_all_sizes`          AS `only_all_sizes`',
                '{{%product_size}}.`id`                 AS `size_id`',
                '{{%product_size}}.`price`              AS `size_cost`',
                '{{%product_size}}.`qty`                AS `size_qty`',
                'CONCAT("'. Yii::$app->homeUrl .'/catalog/", {{%product}}.slug, ".html") AS url',
                '{{%product_type_size}}.`name`          AS `size_name`',
                '{{%product_type_size}}.`id`            AS `size_order`',
            ])
            ->from('{{%product}}')
            ->leftJoin('{{%product_size}}', '{{%product_size}}.product_id = {{%product}}.id')
            ->leftJoin('{{%product_type_size}}', '{{%product_type_size}}.id = {{%product_size}}.type_size_id')

            ->where('{{%product_size}}.product_id = :product_id', ['product_id' => $productId]);

        if ($colorId)
        {
            $resultSet = $resultSet->andWhere('{{%product_size}}.color_id = :colorId', ['colorId' => $colorId]);
        }

        if ($sizeId !== 'all')
        {
            $sizeId = (int) $sizeId;
            $resultSet = $resultSet
                ->andWhere('{{%product_size}}.id = :sizeId', ['sizeId' => $sizeId]);
        }
        $resultSet = $resultSet
            ->andWhere('{{%product_size}}.price != 0')
            ->all();
        foreach($resultSet as $row)
        {
            $itemRow = new CartItem();
            $itemRow->product_id = (int) $rowData['productId'];
            $itemRow->product_name = $rowData['productName'];
            $itemRow->product_image = $rowData['productImage'];
            $itemRow->product_url = $row['url'];
            $itemRow->qty = (int) $rowData['qty'];
            $itemRow->total = (int) $rowData['qty'] * (int) $row['size_cost'];

            $itemRow->color_id = $colorId;
            $itemRow->color_image = $rowData['colorImage'];
            $itemRow->color_name = '';

            $itemRow->size_id = $row['size_id'];
            $itemRow->size_name = $row['size_name'];
            $itemRow->size_cost = (int) $row['size_cost'];

            $itemRow->only_all_sizes = (int) $row['only_all_sizes'];
            $itemRow->row_id = $rowData['rowId'];
            $itemRow->order_id = $rowData['orderId'];

            if (! $itemRow->save())
            {
                Yii::error(
                    $itemRow->getErrors(),
                    __LINE__
                );
            }
        }

    }

    protected function changeQtyRow($item)
    {
        $qty = (int) $item['qty'];
        $rows = CartItem::find()
            ->where('row_id = :row_id ', ['row_id' => $item['rowId']])
            ->andWhere('order_id = :order_id ', ['order_id' => $item['orderId']])
            ->all();
        foreach ($rows as $row)
        {
            $row->qty = $qty;
            $row->total = (int) $row['qty'] * (int) $row['size_cost'];
            if (! $row->save())
            {
                Yii::error(
                    $row->getErrors(),
                    __LINE__
                );
            }
        }
    }
}
