<?php
namespace Ritz\Router;

interface RouterInterface
{
    /**
     * リクエストメソッドとURIからルーティング結果を得る
     *
     * @param string $httpMethod
     * @param string $uri
     *
     * @return RouteResult
     */
    public function route($httpMethod, $uri);
}
