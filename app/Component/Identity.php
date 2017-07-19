<?php
namespace Ritz\App\Component;

class Identity implements IdentityInterface
{
    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
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
        $this->session->getManager()->regenerateId();
        $this->session->offsetSet(static::class, $values);
    }

    public function clear()
    {
        $this->session->getManager()->expireSessionCookie();
        $this->session->getManager()->destroy();
    }
}
