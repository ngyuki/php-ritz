<?php
namespace App\Controller;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use ngyuki\Ritz\Exception\HttpException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\TextResponse;
use ngyuki\Ritz\View\ViewModel;

class HomeController implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $response = $delegate->process($request);

        if ($response instanceof ViewModel) {
            $msg = $response->getVariables()['msg'] ?? null;
            $response = $response->withVariable(
                'msg', "$msg ... Controller で MiddlewareInterface を実装するとアクションの前段のミドルウェアとして実行される"
            );
        }

        return $response;
    }

    public function indexAction()
    {
        return [];
    }

    public function viewAction()
    {
        return (new ViewModel())->withTemplate('App/Home/view2')->withVariables([
            'msg' => "アクションから ViewModel 返すときにテンプレートで別の名前を指定する",
        ]);
    }

    public function userAction($name)
    {
        return [
            'name' => $name,
            'msg' => "ルートパラメータを使う例",
        ];
    }

    public function relativeAction()
    {
        return (new ViewModel())
            ->withRelative('relative-template')
            ->withVariable('msg', "テンプレート名を相対で指定する");
    }

    public function responseAction()
    {
        return new TextResponse("アクションから Response オブジェクトを直接返す");
    }

    public function forbiddenAction()
    {
        throw new HttpException(null, 403);
    }

    public function raiseAction()
    {
        throw new \RuntimeException("例外です");
    }
}
