<?php

/**
 * This file is part of the Mongodm package.
 *
 * (c) Michael Gan <gc1108960@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @category Mongodm
 * @package  Mongodm
 * @author   Michael Gan <gc1108960@gmail.com>
 * @license  https://github.com/purekid/mongodm/blob/master/LICENSE.md MIT Licence
 * @link     https://github.com/purekid/mongodm
 */

namespace libs\mongodm;

/**
 * Mongodm - A PHP Mongodb ORM
 *
 * @category Mongodm
 * @package  Mongodm
 * @author   Michael Gan <gc1108960@gmail.com>
 * @license  https://github.com/purekid/mongodm/blob/master/LICENSE.md MIT Licence
 * @link     https://github.com/purekid/mongodm
 */
class Hydrator
{

    /**
     * Hydrate
     *
     * @param string $class   class
     * @param array  $results results
     * @param string $type    type
     * @param bool $exists
     *
     * @throws \Exception
     * @return Model|null
     */
    public static function hydrate($class, $results, $type = "collection" , $exists = false)
    {

        if (!class_exists($class)) {
            throw new \Exception("class {$class} not exists!");
        } elseif ($type == "collection") {
            $models = array();
            foreach ($results as $result) {
                $model = self::pack($class, $result , $exists);
                $models[] = $model;
            }

            return Collection::make($models);
        } else {
            $model = self::pack($class, $results , $exists);
            return $model;
        }

    }

    /**
     * Pack record to a Mongodm instance
     *
     * @param string $class  class
     * @param array  $result result
     * @param bool   $exists
     *
     * @static
     *
     * @return object type
     */
    protected static function pack($class, $result, $exists = false)
    {
        $model = new $class($result, true , $exists);
        return $model;
    }

}
