<?php

/**
 * Abstraction of a Twilio resource.
 *
 * @category Services
 * @package  Services_Twilio
 * @author   Neuman Vong <neuman@twilio.com>
 * @license  http://creativecommons.org/licenses/MIT/ MIT
 * @link     http://pear.php.net/package/Services_Twilio
 */
abstract class Services_Twilio_Resource
    implements Services_Twilio_DataProxy
{
    protected $name;
    protected $proxy;
    protected $subresources;

    public function __construct(Services_Twilio_DataProxy $proxy)
    {
        $this->subresources = array();
        $this->proxy = $proxy;
        $this->name = get_class($this);
        $this->init();
    }

    protected function init()
    {
        // Left empty for derived classes to implement
    }

    public function retrieveData($path, array $params = array())
    {
        return $this->proxy->retrieveData($path, $params);
    }

    public function deleteData($path, array $params = array())
    {
        return $this->proxy->deleteData($path, $params);
    }

    public function createData($path, array $params = array())
    {
        return $this->proxy->createData($path, $params);
    }

    public function getSubresources($name = null)
    {
        if (isset($name)) {
            return isset($this->subresources[$name])
                ? $this->subresources[$name]
                : null;
        }
        return $this->subresources;
    }

    public function addSubresource($name, Services_Twilio_Resource $res)
    {
        $this->subresources[$name] = $res;
    }

    protected function setupSubresources()
    {
        foreach (func_get_args() as $name) {
            $constantized = ucfirst(Services_Twilio_Resource::camelize($name));
            $type = "Services_Twilio_Rest_" . $constantized;
            $this->addSubresource($name, new $type($this));
        }
    }

    private static function the_callback($m) {

    }

    public static function decamelize($word)
    {
        return preg_replace_callback(
            '/(^|[a-z])([A-Z])/', function($m) {
              return strtolower(strlen($m[1]) ? $m[1] . '_' . $m[2] : $m[2]);
            }, $word
        );
    }

    public static function camelize($word)
    {
        return preg_replace_callback('/(^|_)([a-z])/', function($m) {
          return strtoupper($m[2]);
        }, $word);
    }
}

