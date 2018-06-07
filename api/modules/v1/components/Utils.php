<?php
/**
 * Created by PhpStorm.
 * User: dpotekhin
 * Date: 07.06.2018
 * Time: 12:36
 */

namespace app\modules\v1\components;


class Utils
{

    static public function array_filter_key(array $array, $allowed)
    {
        return array_filter(
            $array,
            function ($key) use ($allowed) {
                return in_array($key, $allowed);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

}