<?php

namespace asakasinsky\cart;

use Yii;


class Utils
{
    /**
     * Create GUID function
     * http://en.wikipedia.org/wiki/Globally_unique_identifier
     *
     * @param  string $namespace  for more enthropy
     * @return string $guid       00000000-0000-0000-0000-000000000000
     */
    public static function createGuid ($namespace='')
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

    public function one_pixel_gif($namespace='')
    {
        header('Content-Type: image/gif');
        echo hex2bin('47494638396101000100900000ff000000000021f90405100000002c00000000010001000002020401003b');
    }

    public function one_pixel_png($namespace='')
    {
        header('Content-Type: image/png');
        echo hex2bin('89504e470d0a1a0a0000000d494844520000000100000001010300000025db56ca00000003504c5445000000a77a3dda0000000174524e530040e6d8660000000a4944415408d76360000000020001e221bc330000000049454e44ae426082');
    }

    public function is_guid($guid='')
    {
        $result = FALSE;
        $r = '/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/';
        if (preg_match($r, $guid))
        {
            $result = TRUE;
        }
        return $result;
    }

}
