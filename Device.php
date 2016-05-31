<?php

namespace asakasinsky\cart;

use Yii;
use \yii\base\Component;
use \yii\web\Cookie;
use asakasinsky\cart\models\Device as DeviceModel;
use DeviceDetector\DeviceDetector;

class Device extends Component
{

    public $tag = null;
    public $id = null;

    function __construct()
    {
        $this->tag = $this->getTag();
        if (! $this->tag) {
            $this->registerDevice();
        }
        $this->id = $this->getId();
        parent::__construct();
    }

    public function getTag()
    {
        $cookies = Yii::$app->request->cookies;
        $cookieTag = ($cookies->has('GUID')) ?
            $cookies->getValue('GUID') :
            null;
//        $eTag = isset($_SERVER['HTTP_IF_NONE_MATCH']) ?
//            $_SERVER['HTTP_IF_NONE_MATCH'] :
//            null;
        $eTag = null;
        $tag = $cookieTag ?:
            ($eTag ?:
                null
            );
        return $tag;
    }

    public function getId()
    {
        $id = Yii::$app->session->get('deviceId');
        if (! $id) {
            $device = DeviceModel::find()
                ->where('guid = :guid ', ['guid' => $this->tag])
                ->one();
            if ($device) {
                $id = $device->id;
                Yii::$app->session->set('deviceId', $id);
            }
        }
        return $id;
    }

    protected function createTag()
    {
        $guid = Utils::createGuid();
        return $guid;
    }

    protected function setTag($guid)
    {
        $cookies = Yii::$app->response->cookies;
        $cookies->add(new Cookie([
            'name' => 'GUID',
            'value' => $guid,
            'expire' => time() + 60 * 60 * 24 * 365,
            'domain' => '.'. Yii::getAlias('@domain'),
            'httpOnly' => true
        ]));
    }

    public function linkUser($userId=null)
    {
        $deviceId = Yii::$app->session->get('deviceId');
        $deviceQuery = DeviceModel::find();

        if ($deviceId) {
            $deviceQuery = $deviceQuery->where('id = :id ', ['id' => $deviceId]);
        } else {
            $deviceQuery = $deviceQuery->where('guid = :guid ', ['guid' => $this->tag]);
        }
        $device = $deviceQuery->one();
        if (! $device) {
            $this->registerDevice($userId);

        } else {
            $device->user_id = $userId;

            if (! $device->save())
            {
                Yii::error(
                    $device->getErrors(),
                    __LINE__
                );
                return $device->getErrors();
            }
        }
        return true;
    }

    public function registerDevice($userId=null)
    {
        $guid = $this->createTag();
        $userAgent = Yii::$app->request->userAgent;
        $dd = new DeviceDetector($userAgent);
        $dd->parse();

        $os = $dd->getOs();
        $client = $dd->getClient();

        $device = new DeviceModel();
        $device->guid = $guid;
        $device->device = $dd->getDeviceName();
        $device->model = $dd->getModel();
        $device->brand = $dd->getBrandName();
        $device->os_name = $os['name'];
        $device->os_version = $os['version'];
        $device->client_type = $client['type'];
        $device->client_name = $client['name'];
        $device->client_version = $client['version'];
        $device->user_id = $userId;
        Yii::$app->session->set('deviceId', $device->id);
        if (! $device->save())
        {
            Yii::error(
                [
                    'info' => 'Проблема создания устройства в БД',
                    'message' => $device->getErrors()
                ],
                __LINE__
            );
            return $device->getErrors();
        }

        $this->setTag($guid);
        $this->id = $device->id;
        $this->tag = $guid;
        return $device;
    }
}
