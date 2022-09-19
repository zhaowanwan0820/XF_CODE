<?php
namespace NCFGroup\Common\Extensions\Base;

class ProtoBufferBase implements \JsonSerializable
{
    public function softClone($object)
    {
        foreach ($object as $key => $value) {
            if(property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function toArray()
    {
        $array = $this->getObjectVars($this);
        array_walk_recursive($array, function(&$property, $key){
            if(is_object($property)
               && method_exists($property, 'toArray')){
                $property = $property->toArray();
            }
        });
        return $array;
    }

    public function __set($key, $val)
    {
        $method = 'set'.ucfirst($key);
        if(method_exists($this,  $method)) {
            $param = new \ReflectionParameter(array($this, $method), 0);
            if($param->getClass()) {
                $class = $param->getClass()->getName();
                $obj = new $class();
                $obj->softClone($val);
                $val = $obj;
            }
            $this->{$method}($val);
            return $this;
        }
        $this->$key = $val;
    }

    public function __get($key)
    {
       $method = 'get'.ucfirst($key);
       if(method_exists($this,  $method)) {
           return $this->{$method}();
       }

       if(property_exists($this, $key)) {
           return $this->{$key};
       }
       return null;
    }

    public function getObjectVars($obj)
    {
        $ref = new \ReflectionObject($obj);
        $pros = $ref->getProperties();
        $result = array();
        foreach ($pros as $pro) {
            $pro->setAccessible(true);
            $result[$pro->getName()] = $pro->getValue($obj);
        }
        return $result;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
