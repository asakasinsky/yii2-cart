<?php

namespace asakasinsky\cart;

use Yii;
use yii\base\Component;
use asakasinsky\cart\models\CartItem;
use asakasinsky\cart\models\CartOrder;

class Cart extends Component
{

    public $itemsTableName = '{{%cart_items}}';
    public $ordersTableName = '{{%cart_orders}}';
    public $items = [];
    protected $cartContents = [
        'cartTotal' => 0,
        'qtyTotal' => 0
    ];

    public function __construct()
    {
        $guid = Yii::$app->session->get('cartGuid');
        if ($guid)
        {
            $this->cartContents = $this->getCartFromDb($guid);
        }
    }

    public function getCartFromDb($guid)
    {
        $result = [
            'cartTotal' => 0,
            'qtyTotal' => 0
        ];

        return $result;
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

    public function put ($items)
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
        }
        if (! $order || ! $cartGuid)
        {
            $cartGuid = $this->createGuid();
            Yii::$app->session->set('cartGuid', $cartGuid);
            $order = new CartOrder();
            $order->guid = $cartGuid;
            $order->save();
        }


        $items = [
            0 => [
                'colorId' => 470,
                'colorImage' => "http://tokana.ru/uploads/28/a8/e6/28a8e64d69fc78383dd5a0ea04008757.jpg",
                'id' => "278_470_all",
                'productId' => 278,
                'productImage' => "/uploads/9a/2a/5c/60__9a2a5c13fef6664e3ad22c8759a65214.jpg",
                'productName' => "Розовая пантера",
                'qty' => 4,
                'sizeCost' => 2376,
                'sizeId' => "all",
                'sizeName' => "46-56",
                'total' => 9504,
            ]
        ];

        foreach($items as $item)
        {
            if (! isset($item['row_id']))
            {
                var_dump($item);
            }
        }

    }
}
