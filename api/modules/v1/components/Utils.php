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

    static public function merge_associative_arrays(array $array, array $add_array)
    {
        if( !is_array($add_array) || !count($add_array) ) return $array;

        foreach ($add_array as $key => $item)
        {
            $array[$key] = $item;
        }

        return $array;
    }


}