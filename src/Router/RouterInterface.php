<?php
namespace Ritz\Router;

interface RouterInterface
{
    /**
     * リクエストメソッドとURIからルーティング結果を得る
     *
     * 戻り値の配列は array($handler, $params) の形式
     * ルートが見つからなければ null を返す
     *
     * @param string $method
     * @param string $uri
     *
     * @return array|null
     */
    public function route($method, $uri);
}
