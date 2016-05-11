<?php

namespace asakasinsky\cart;

use Yii;
use yii\base\Component;
use \yii\db\Query;
use \yii\db\Expression;
use asakasinsky\cart\models\CartItem;
use asakasinsky\cart\models\CartOrder;
use common\models\User;
use asakasinsky\cart\Device;

//use yii\twig\ViewRenderer;

//use yii\twig;

class Cart extends Component
{

    public $device = null;
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
        $this->device = new Device();
        $this->cartContents = $this->get();
    }

    private function _sortByOrder($a, $b)
    {
        return strnatcmp((int)$a['order'], (int)$b['order']);
    }

    private function _sortById($a, $b)
    {
        return strnatcmp($a['id'], $b['id']);
    }

    public function mail()
    {


    }

    public function sendMail($recepient, $replyTo, $subject, $htmlMessage, $listId = null)
    {
        /** @var \yii\swiftmailer\Mailer $mailer */
        $mailer = Yii::$app->mailer;

        $from = [
            Yii::$app->params['noReplyMail']['mail'] => Yii::$app->params['noReplyMail']['name']
        ];
        $returnPath = Yii::$app->params['commonMail']['mail'];

        $message = Yii::$app->mailer->compose()
            ->setFrom($from)
            ->setTo($recepient['email'])
            ->setSubject($subject)
            ->setHtmlBody($htmlMessage);
        $headers = $message->getSwiftMessage()->getHeaders();

        if (isset($recepient['guid'])) {
            $headers->addTextHeader('List-Unsubscribe', Yii::getAlias('@protocol') . '://' . Yii::getAlias('@domain') . '/mail/unsubscribe/' . $recepient['guid']);
        }
        if ($listId) {
            $headers->addTextHeader('List-id', 'order-' . $listId);
        }

        $headers->addTextHeader('Reply-To', $replyTo);
        $headers->addTextHeader('Return-Path', $returnPath);
        $message->send();
    }

    public function get($cartGuid = null)
    {
        $order = null;
        $cartItems = [];
        $resultSet = [];

        if (!$cartGuid) {
            $cartGuid = Yii::$app->session->get('cartGuid');
        }
        if ($cartGuid) {
            $order = CartOrder::find()
                ->where('guid = :guid ', ['guid' => $cartGuid])
                ->one();
            if (!$order) {
                Yii::$app->session->remove('cartGuid');
                return $this->cartContents;
            } else {
                $resultSet = (new Query())
                    ->select([
                        '{{%cart_item}}.`id` AS id',
                        '{{%cart_item}}.`product_id` AS productId',
                        '{{%cart_item}}.`product_name` AS productName',
                        '{{%cart_item}}.`product_image` AS productImage',
                        '{{%cart_item}}.`product_type` AS productType',
                        '{{%cart_item}}.`manufacture` AS manufacture',
                        '{{%cart_item}}.`manufacture_sku` AS manufactureSku',
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
            foreach ($resultSet as &$row) {
                $row['id'] = (int)$row['id'];
                $row['productId'] = (int)$row['productId'];
                $row['qty'] = (int)$row['qty'];
                $row['total'] = (int)$row['total'];
                $row['colorId'] = (int)$row['colorId'];
                $row['sizeId'] = (int)$row['sizeId'];
                $row['sizeCost'] = (int)$row['sizeCost'];
                $row['orderId'] = (int)$row['orderId'];
                $row['order'] = (int)$row['order'];
                $row['createdAt'] = (int)$row['createdAt'];
                $row['updatedAt'] = (int)$row['updatedAt'];
                $row['onlyAllSizes'] = (int)$row['onlyAllSizes'];

                $cartItems[$row['productId']]['id'] = $row['productId'];
                $cartItems[$row['productId']]['productId'] = $row['productId'];
                $cartItems[$row['productId']]['productImage'] = $row['productImage'];
                $cartItems[$row['productId']]['productName'] = $row['productName'];
                $cartItems[$row['productId']]['productUrl'] = $row['productUrl'];
                $cartItems[$row['productId']]['productType'] = $row['productType'];
                $cartItems[$row['productId']]['manufacture'] = $row['manufacture'];
                $cartItems[$row['productId']]['manufactureSku'] = $row['manufactureSku'];
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


            foreach ($cartItems as &$item) {
                if ((int)$item['onlyAllSizes'] === 1) {
                    $rows = array_filter($resultSet, function ($row) use ($item) {
                        return $row['productId'] === $item['productId'];
                    });
                    $rows = array_reverse(array_values($rows));
                    usort($rows, array($this->className(), '_sortByOrder'));

                    $byColors = [];
                    foreach ($rows as $_row) {
                        $byColors[$_row['colorId']][] = $_row;
                    }

                    foreach ($byColors as $color) {
                        $firstRow = reset($color);
                        $firstName = $firstRow['sizeName'];
                        $arrKeys = array_keys($color);
                        $lastName = $color[end(
                            $arrKeys
                        )]['sizeName'];
                        $firstRow['sizeName'] = $firstName . ' - ' . $lastName;
                        $prices = array_column($color, 'sizeCost');
                        $firstRow['sizeCost'] = array_sum($prices);
                        $firstRow['sizeId'] = 'all';
//                        $items[] = $firstRow;
                        $item['sizes'][$firstRow['id']] = $firstRow;
                    }
                    $item['sizes'] = array_reverse(array_values($item['sizes']));
                } else {
                    $rows = array_filter($resultSet, function ($row) use ($item) {
                        return $row['productId'] === $item['productId'];
                    });
                    $rows = array_reverse(array_values($rows));
                    usort($rows, array($this->className(), '_sortByOrder'));

                    $item['sizes'] = $rows;
                }

                $item['total'] = 0;
                $item['qty'] = 0;
                foreach ($item['sizes'] as &$size) {
                    $size['id'] = $size['productId'] . '_' . $size['colorId'] . '_' . $size['sizeId'];
                    $size['total'] = $size['qty'] * $size['sizeCost'];
                    $item['total'] = $item['total'] + $size['total'];
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
            $this->cartContents['cartTotal'] = ($order) ? $order->total : 0;
            $this->cartContents['qtyTotal'] = ($order) ? $order->qty : 0;
            $this->cartContents['items'] = $cartItems;
        }
        return $this->cartContents;
    }

    public function checkout($orderData = [])
    {
        $user = null;
        $userId = Yii::$app->session->get('userId');

        $user = User::find();
        if (!$userId) {
            $user = $user->where('email = :email', ['email' => $orderData['email']])
                ->one();
        } else {
            $user = $user->where('id = :id', ['id' => $userId])
                ->one();
        }
        if (!$user) {
            $user = new User();
            $user->email = $orderData['email'];
            $user->phone = $orderData['phone'];
            $user->name = $orderData['name'];
            $user->guid = Utils::createGuid();
            if (!$user->save()) {
                Yii::error(
                    $user->getErrors(),
                    __LINE__
                );
            } else {
                $userId = $user->id;
                Yii::$app->session->set('userId', $userId);
            }
        } else {
            $userId = $user->id;
            Yii::$app->session->set('userId', $userId);
        }
        $this->device->linkUser($userId);


        $staticFolder = Yii::getAlias('@staticFolder');
        $result = [
            'result' => false
        ];
        $cartGuid = Yii::$app->session->get('cartGuid');

        $order = CartOrder::find()
            ->where('guid = :guid ', ['guid' => $cartGuid])
            ->one();

        if ($order && $orderData) {
            $loader = new \Twig_Loader_Filesystem(Yii::getAlias('@common/mail'));
            $twig = new \Twig_Environment($loader, array(
                'cache' => Yii::getAlias('@runtime/Twig/cache'),
                'auto_reload' => TRUE
            ));
            $items = $this->get($order->guid);
            $imgPrefix = (Yii::getAlias('@imagePrefixOrder', false)) ? Yii::getAlias('@imagePrefixOrder', false) :  '200__';
            foreach ($items['items'] as &$item) {
                $item['productImage'] = str_replace('60__', $imgPrefix, $item['productImage']);
            }


            $relative_order_path = Yii::getAlias('@ordersFolder') . '/' . $cartGuid[0] . $cartGuid[1] . '/' . $cartGuid[2] . $cartGuid[3] . '/' . $cartGuid[4] . $cartGuid[5];
            $absolute_order_path = $staticFolder . '/' . $relative_order_path;

            $relative_order_file = $relative_order_path . '/' . $cartGuid . '.html';
            $absolute_order_file = $absolute_order_path . '/' . $cartGuid . '.html';


            if (!file_exists($absolute_order_path)) {
                mkdir($absolute_order_path, 0755, true);
            }


            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }

            $order->status = 1;
            $order->phone = $orderData['phone'];
            $order->email = $orderData['email'];
            $order->name = $orderData['name'];
            $order->delivery = $orderData['delivery'];
            $order->recipient_name = $orderData['recipient_name'];
            $order->recipient_passport = $orderData['recipient_passport'];
            $order->recipient_address = $orderData['recipient_address'];
            $order->comment = $orderData['comment'];
            $order->date = date('Y-m-d H:i:s', time());
//            $order->date = new Expression('NOW()');
//            $order->ip = new Expression('INET_ATON(:ip)',['ip' => $ip]);
            $order->ip = ip2long($ip);
            $order->order_file = $relative_order_file;
            $order->user_id = $userId;

            if (!$order->save()) {
                Yii::error(
                    $order->getErrors(),
                    __LINE__
                );
                $result['result'] = 'error';
                return $result;
            }


            $htmlMessage = $twig->render(
                'order/confirm.twig',
                array(
                    'order' => $order,
                    'orderDate' => date('d-m-Y', strtotime($order->date)),
                    'siteName' => Yii::getAlias('@protocol') . '://' . Yii::getAlias('@domain'),
                    'user' => $user,
                    'items' => $items['items']
                )
            );
            $htmlMessageAdmin = $twig->render(
                'order/confirmAdmin.twig',
                array(
                    'order' => $order,
                    'orderDate' => date('d-m-Y', strtotime($order->date)),
                    'siteName' => Yii::getAlias('@protocol') . '://' . Yii::getAlias('@domain'),
                    'user' => $user,
                    'items' => $items['items']
                )
            );

            $content = $htmlMessage;
            $fp = fopen($absolute_order_file, 'wb');
            fwrite($fp, $content);
            fclose($fp);

            $this->sendMail([
                'email' => $order->email,
                'guid' => $user->guid,
                'name' => $user->name
            ],
                Yii::$app->params['replyToMail']['mail'],
                'Ваш заказ принят', $htmlMessage, $order->guid
            );

            $this->sendMail([
                'email' => Yii::$app->params['replyToMail']['mail'],
                'guid' => null,
                'name' => null
            ],
                $order->email,
                'Поступил заказ', $htmlMessageAdmin
            );

            // Заказ оформлен, очищаем сохранённую GUID заказа
            Yii::$app->session->remove('cartGuid');
            $result['email'] = $orderData['email'];
            $result['result'] = 'ok';
            $result['orderLink'] = '/' . $relative_order_file;
        }
        return $result;
    }

    public function remove($items = [])
    {
        $result = false;
        $cartGuid = Yii::$app->session->get('cartGuid');
        $order = CartOrder::find()
            ->where('guid = :guid ', ['guid' => $cartGuid])
            ->one();
        if ($order && $items) {
            foreach ($items as $item) {
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

    public function put($items = [])
    {
        $order = null;
        $cartGuid = Yii::$app->session->get('cartGuid');

        if ($cartGuid) {
            $order = CartOrder::find()
                ->where('guid = :guid ', ['guid' => $cartGuid])
                ->one();
        }
        if (!$order || !$cartGuid) {
            $cartGuid = Utils::createGuid();
            Yii::$app->session->set('cartGuid', $cartGuid);
            $order = new CartOrder();
            $ts = time();
            $order->created_at = $ts;
            $order->updated_at = $ts;
            $order->guid = $cartGuid;
            $order->device_id = $this->device->id;
            if (!$order->save()) {
                Yii::error(
                    $order->getErrors(),
                    __LINE__
                );
            }
        }
        $rows = [];

        foreach ($items as $item) {
            if (!isset($item['rowId'])) {
                $rowId = Utils::createGuid($cartGuid);
                $item['rowId'] = $rowId;
                $item['orderId'] = $order->id;
                $this->insertRow($item);
                $rows[] = [
                    'rowId' => $rowId,
                    'productId' => $item['productId'],
                    'id' => $item['id']
                ];
            } else {
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
        $orderId = (int)$orderId;
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

    protected function insertRow($itemData)
    {
        $idParts = explode('_', $itemData['id']);
        $productId = (int)$idParts[0];
        $colorId = (int)$idParts[1];
        $sizeId = $idParts[2];

        $resultSet = (new Query())
            ->select([
                '{{%product}}.`only_all_sizes`          AS `only_all_sizes`',
                '{{%product}}.`type`                 AS `product_type`',
                '{{%product}}.`manufacture`                 AS `manufacture`',
                '{{%product}}.`manufacture_sku`                 AS `manufacture_sku`',
                '{{%product_size}}.`id`                 AS `size_id`',
                '{{%product_size}}.`price`              AS `size_cost`',
                '{{%product_size}}.`qty`                AS `size_qty`',
                'CONCAT("' . Yii::$app->homeUrl . '/catalog/", {{%product}}.slug, ".html") AS url',
                '{{%product_type_size}}.`name`          AS `size_name`',
                '{{%product_type_size}}.`id`            AS `size_order`',
            ])
            ->from('{{%product}}')
            ->leftJoin('{{%product_size}}', '{{%product_size}}.product_id = {{%product}}.id')
            ->leftJoin('{{%product_type_size}}', '{{%product_type_size}}.id = {{%product_size}}.type_size_id')
            ->where('{{%product_size}}.product_id = :product_id', ['product_id' => $productId]);

        if ($colorId) {
            $resultSet = $resultSet->andWhere('{{%product_size}}.color_id = :colorId', ['colorId' => $colorId]);
        }

        if ($sizeId !== 'all') {
            $sizeId = (int)$sizeId;
            $resultSet = $resultSet
                ->andWhere('{{%product_size}}.id = :sizeId', ['sizeId' => $sizeId]);
        }
        $resultSet = $resultSet
            ->andWhere('{{%product_size}}.price != 0')
            ->all();
        foreach ($resultSet as $row) {
            $itemRow = new CartItem();
            $itemRow->product_id = (int)$itemData['productId'];
            $itemRow->product_name = $itemData['productName'];
            $itemRow->product_image = $itemData['productImage'];
            $itemRow->manufacture = $row['manufacture'];
            $itemRow->manufacture_sku = $row['manufacture_sku'];
            $itemRow->product_url = $row['url'];
            $itemRow->product_type = $row['product_type'];
            $itemRow->qty = (int)$itemData['qty'];
            $itemRow->total = (int)$itemData['qty'] * (int)$row['size_cost'];

            $itemRow->color_id = $colorId;
            $itemRow->color_image = $itemData['colorImage'];
            $itemRow->color_name = '';

            $itemRow->size_id = $row['size_id'];
            $itemRow->size_name = $row['size_name'];
            $itemRow->size_cost = (int)$row['size_cost'];

            $itemRow->only_all_sizes = (int)$row['only_all_sizes'];
            $itemRow->row_id = $itemData['rowId'];
            $itemRow->order_id = $itemData['orderId'];

            if (!$itemRow->save()) {
                Yii::error(
                    $itemRow->getErrors(),
                    __LINE__
                );
            }
        }

    }

    protected function changeQtyRow($item)
    {
        $qty = (int)$item['qty'];
        $rows = CartItem::find()
            ->where('row_id = :row_id ', ['row_id' => $item['rowId']])
            ->andWhere('order_id = :order_id ', ['order_id' => $item['orderId']])
            ->all();
        foreach ($rows as $row) {
            $row->qty = $qty;
            $row->total = (int)$row['qty'] * (int)$row['size_cost'];
            if (!$row->save()) {
                Yii::error(
                    $row->getErrors(),
                    __LINE__
                );
            }
        }
    }
}
