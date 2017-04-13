<?php
namespace App\Controller;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\TextResponse;
use ngyuki\Ritz\View\ViewModel;
use App\Component\IdentityInterface;
use App\Component\Session;
use App\Service\HelloService;

class HomeController implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $response = $delegate->process($request);

        if ($response instanceof ViewModel) {
            $response = $response->withVariable(
                'process', "Controller で MiddlewareInterface を実装するとアクションの前段のミドルウェアとして実行される"
            );
        }

        return $response;
    }

    public function indexAction(IdentityInterface $identity, Session $session, HelloService $hello)
    {
        $val = $session['val'] = $session['val'] + 1;

        return [
            'hello' => $hello->say($identity->get('username')),
            'val' => $val,
            'msg' => "アクションから連想配列を返すと自動で ViewModel となってレンダリングされる",
        ];
    }

    public function viewAction()
    {
        return (new ViewModel())->withTemplate('App/Home/alt-view')->withVariables([
            'val' => __METHOD__,
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

    public function relativeTemplateAction()
    {
        return (new ViewModel())
            ->withRelative('relative')
            ->withVariable('msg', "テンプレート名を相対で指定する");
    }

    public function responseAction()
    {
        return new TextResponse("アクションから Response オブジェクトを直接返すこともできる");
    }

    public function raiseAction()
    {
        throw new \RuntimeException("例外です");
    }
}
