<?php
namespace Ritz\App\Component;

class IdentityStab implements IdentityInterface
{
    private $session;

    public function __construct()
    {
        $this->session = new \ArrayObject();
    }

    public function is()
    {
        return $this->session->offsetExists(static::class);
    }

    /**
     * @param string|null $name
     * @return array
     */
    public function get($name = null)
    {
        if ($name === null) {
            return $this->session->offsetGet(static::class);
        } else {
            return $this->session->offsetGet(static::class)[$name];
        }
    }

    public function set(array $values)
    {
        $this->session->offsetSet(static::class, $values);
    }

    public function clear()
    {
        $this->session = new \ArrayObject();
    }
}
