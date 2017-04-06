<?php
namespace ngyuki\Ritz\Bootstrap;

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
            $config = (array)(require $fn) + $config;
        }

        return $config;
    }
}
