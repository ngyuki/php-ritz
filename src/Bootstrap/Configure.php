<?php
namespace Ritz\Bootstrap;

class Configure
{
    /**
     * @param array $bootFiles
     * @return array
     */
    public function init(array $bootFiles)
    {
        $config = [];

        foreach ($bootFiles as $fn) {
            /** @noinspection PhpIncludeInspection */
            $config = $this->merge($config, (array)(require $fn));
        }

        return $config;
    }

    protected function merge(array $a, array $b)
    {
        foreach ($b as $k => $v) {
            if (is_int($k)) {
                $a[] = $v;
            } elseif (isset($a[$k]) && is_array($a[$k]) && is_array($v)) {
                $a[$k] = $this->merge($a[$k], $v);
            } else {
                $a[$k] = $v;
            }
        }
        return $a;
    }
}
