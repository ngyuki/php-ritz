<?php
namespace App\Component;

interface IdentityInterface
{
    /**
     * @return bool
     */
    public function is();

    /**
     * @param string|null $name
     * @return mixed|array
     */
    public function get($name = null);

    /**
     * @param array $values
     */
    public function set(array $values);

    public function clear();
}
