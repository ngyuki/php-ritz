# PSR-7 と PSR-15 を用いた軽量フレームワーク

[PSR-7(HTTP Message Interface)][PSR-7] や [PSR-15(HTTP Middlewares)][PSR-15] を用いた軽量で薄いオレオレフレームワークです。

[PSR-7]: http://www.php-fig.org/psr/psr-7/
[PSR-15]: https://github.com/php-fig/fig-standards/tree/master/proposed/http-middleware

以下のようなライブラリを利用して構築しています。

- [zendframework/zend-diactoros](https://github.com/zendframework/zend-diactoros)
    - PSR-7 の実装
- [zendframework/zend-stratigility](https://github.com/zendframework/zend-stratigility)
    - PSR-15 の実装
- [nikic/fast-route](https://github.com/nikic/FastRoute)
    - ルーター
- [php-di/php-di](https://github.com/PHP-DI/PHP-DI)
    - DI コンテナ

## 基本的なコンセプト

このフレームワークでは、いわゆるコントローラークラスで継承を使用していません。アプリケーションで実装するコントローラーの継承元となる `AbstractController` のようなクラスは存在しません。

コントローラーのインスタンスは DI コンテナで管理されているため、コントローラーが必要とするオブジェクトはすべて DI でインジェクションします。

また、コントローラーの前後で共通のフックポイントが必要な場合は（`AbstractController` クラスの `preDispatch` や `postDispatch` で実現していたもの）、PSR-15 ミドルウェアをディスパッチの前段に置くことで実現できます。

一般的なフレームワークのコントローラーは多くのオブジェクトに依存している重量級であるのに対して、このフレームワークのコントローラーは超軽量です。そのため、コントローラーのユニットテストも容易に可能です。

## index.php

典型的な index.php は下記のような形です。

```php
<?php
namespace App;

use Ritz\Bootstrap\Configure;
use Ritz\Bootstrap\ContainerFactory;
use Ritz\Bootstrap\Server;

require __DIR__  . '/../vendor/autoload.php';

// 環境変数
$env = getenv('APP_ENV');

// アプリケーションのコンフィグファイルのリスト
$files = array_merge(
    glob(__DIR__ . '/../boot/*.php'),
    glob(__DIR__ . "/../config/$env.php"),
    glob(__DIR__ . '/../config/local.php')
);

// コンフィグファイルの読み込み
$config = (new Configure())->init($files);

// コンフィグを元にコンテナを作成
$container = (new ContainerFactory())->create($config);

// コンテナからアプリケーションオブジェクトとデバッグフラグを取り出してサーバを実行する
$server = new Server()
$server->run($container->get(Application::class), $container->get('debug'));
```

example ではこれらの処理を Bootstrap クラスにまとめているため index.php は下記だけしかありません。

```php
require __DIR__  . '/../vendor/autoload.php';
App\Bootstrap::main();
```

## コンフィグファイル

コンフィグファイルでは DI の定義を行います。

例えば下記のようになります。

**app.php**

```php
return [
    'app.cache_dir' => __DIR__ . '/../cache/',
    'app.view.directory' => dirname(__DIR__) . '/resource/view/',
    'app.view.suffix' => '.phtml',
];
```

**routes.php**

```php
return [
    'app.routes' => DI\value(function(RouteCollector $r) {
        $r->get('/', [HomeController::class, 'index']);
    });
];
```

**service.php**

```php
return [
    HelloService::class => DI\object(HelloService::class)),
];
```

複数のファイルに分割して記述されていますが、これらのファイルの内容は `Configure` クラスによってマージされます。

標準のコンテナのファクトリである `ContainerFactory` は PHP-DI のコンテナを作成するため、コンフィグファイルは PHP-DI の記述に則る必要があります。詳細は PHP-DI のドキュメントを参照してください。

## デフォルトのコンテナファクトリとコンフィグのエントリ

`ContainerFactory` ではいくつかのオブジェクトがデフォルトで定義されています。これらのオブジェクトは下記に示す幾つかのエントリに依存しているため、アプリケーションのコンフィグでこれらを定義する必要があります。

```php
return [ 
    // キャッシュディレクトリ
    // オプショナルで未指定や null ならキャッシュは使用されない
    'app.cache_dir' => __DIR__ . '/../cache/',

    // テンプレートファイルのディレクトリ
    'app.view.directory' => dirname(__DIR__) . '/resource/view/',

    // テンプレートファイルの拡張子
    'app.view.suffix' => '.phtml',

    // ルート定義
    'app.routes' => value(function(RouteCollector $r) {
        $r->get('/', [HomeController::class, 'index']);
    });
];
```

## Application クラス

`Application` クラスは PSR-15 の `MiddlewareInterface` を実装する必要があります。

典型的な `Application` クラスの実装は下記のようになります。

```php
class Application implements MiddlewareInterface
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $pipeline = new MiddlewarePipe();
        $pipeline->pipe($this->container->get(RenderMiddleware::class));
        $pipeline->pipe($this->container->get(RouteMiddleware::class));
        $pipeline->pipe($this->container->get(DispatchMiddleware::class));
        return $pipeline->process($request, $delegate);
    }
}
```

この例では３つのミドルウェアでパイプラインを作成しています。基本的にこの３つのミドルウェアは Web アプリとして振る舞うために必須です。必要に応じでアプリケーション独自のミドルウェアを追加します。

例えば、下記のようにルーティングとディスパッチの間にミドルウェアを挿し込むことができます。

```php
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $pipeline = new MiddlewarePipe();
        $pipeline->pipe($this->container->get(RenderMiddleware::class));
        $pipeline->pipe($this->container->get(RouteMiddleware::class));

        $pipeline->pipe(function (ServerRequestInterface $request, DelegateInterface $delegate) {
            // Pre dispatch
            $response = $delegate->process($request);
            // Post dispatch
            return $response;
        });

        $pipeline->pipe($this->container->get(DispatchMiddleware::class));
        return $pipeline->process($request, $delegate);
    }
```

ミドルウェアは後段ミドルウェアの前と後の両方を処理することができます。この例ではいわゆる PreDispatch と PostDispatch の両方を１つのミドルウェアで処理しています。

ミドルウェアのユースケースには下記のようなものが考えられます。

- 例外発生時に特定のテンプレート選択する
- CSRF トークンのチェックを行う
- 認証のチェックとリダイレクトを行なう
- アクションの前後でリクエスト・レスポンスオブジェクトを加工する

## ルート定義

ルート定義は下記のようになります。

```php
use function DI\value;
use FastRoute\RouteCollector;
use App\Controller\HomeController;
use App\Controller\UserController;

return [
    'app.routes' => value(function(RouteCollector $r) {
        $r->get('/', [HomeController::class, 'indexAction']);
        $r->get('/user/{id}', [UserController::class, 'showAction']);
    }),
];
```

ルーターの実装には `FastRoute` を使用しているため、具体的なルート定義の方法は `FastRoute` のドキュメントを参照してください。

ルートのハンドラ（`get` メソッドの第２引数）にはコントローラー名とアクション名からなる配列を指定します。コントローラー名は DI コンテナからコントローラーインスタンスを取り出す際のエントリ名です（典型的にはコントローラーのクラス名）。アクション名はコントローラーのメソッド名です。

この例では、`GET /` というルートで DI コンテナの `HomeController::class` エントリからコントローラーのインスタンスが取り出されて `indexAction` メソッドが実行されます。

## アクションメソッドの引数

コントローラーのアクションメソッドでは、引数の名前やタイプヒントに基いて下記から自動的に値やオブジェクトが注入されます。

- DI コンテナ
- リクエストのアトリビュート
- PSR-15 の `process` メソッド引数（`ServerRequestInterface` と `DelegateInterface`）

```php
public function showAction(
    // DI コンテナから取得
    UserRepository $userRepository,
    // リクエストオブジェクト
    ServerRequestInterface $request,
    // リクエストのアトリビュートに設定されたルートパラメータ
    $id
) {
    return ['user' => $userRepository->get($id)];
}
```

リクエストのアトリビュートには、いわゆるルートパラメータ、即ち、ルート定義の `/user/{id}` のようなプレースホルダのパラメータを含みます。↑の例では、URL が `/user/123` であれば、ルートパラメータを元に `$id` には 123 が設定されます。

他のミドルウェアでリクエストのアトリビュートへ任意の値が追加されていれば、それらもメソッドの引数に注入できます。

```php
// ミドルウェアでリクエストに属性を追加
public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    $requset = $requset->withAttribute('foo', 987);
    $requset = $requset->withAttribute(Bar::class, new Bar());
    return $delegate->process($request);
}

// アクションメソッドに注入される
public function showAction($foo, Bar $bar)) {
    // ...
}
```

## アクションメソッドの戻り値

コントローラーのアクションメソッドの戻り値で `ViewModel` を返すと `RendererMiddleware` にとってテンプレートのレンダリングが行われ、その結果がレスポンスボディに反映されます。

```php
// ViewModel オブジェクトを返す
return new ViewModel();

// テンプレートにアサインする変数を指定する
return new ViewModel(['val' => 123]);
```

戻り値が配列の場合は自動的にその配列がアサインされた `ViewModel` に変換されます

```php
// 配列は自動的に ViewModel オブジェクトに変換される
return ['val' => 123];
```

レンダリングされるテンプレート名は、コントローラーのクラス名とアクションメソッド名から自動的に導出されます。例えば `App\Controller\HomeController::indexAction` -> `App/Home/index` のようにテンプレート名が導出されます。導出の規則は後述します。

`ViewModel` でテンプレート名を指定すれば別のテンプレートを使うことができます。

```php
return (new ViewModel())->withTemplate('App/Home/index');
```

自動的に導出されたテンプレート名に対して相対でテンプレートを指定することができます。例えば自動的に導出されたテンプレート名が `App/Home/index` の場合、下記のコードではテンプレート名が `App/Home/edit` となります。

```php
return (new ViewModel())->withRelative('edit');
```

`ViewModel` は PSR-15 の `ResponseInterface` を実装しています。ステータスコードやレスポンスヘッダを加工したいときは `ResponseInterface` のメソッドが使用できます。

```php
// 200 以外のステータスコードとカスタムヘッダを指定する
return (new ViewModel())->withStatus(400)->withHeader('X-Custom', 'xxx');
```

アクションメソッドが `ViewModel` ではない `ResponseInterface` を実装したオブジェクトを返すと、そのレスポンスオブジェクトがそのままブラウザに応答されます。

```php
// リダイレクト
return new RedirectResponse('/');
```

アクションメソッドが文字列を返すと自動的に `TextResponse` に変換されます。

```php
// 文字列は TextResponse に変換される
return "hello";

// 上と等価
return new TextResponse("hello");
```

## テンプレート名の自動的な解決

`ViewModel` でテンプレート名が指定されていない場合、デフォルトではコントローラーのクラス名とアクションメソッド名を元にしたテンプレート名が使用されます。

- コントローラーの名前空間から `Controller` というセグメントを除去
- コントローラーのクラス名のサフィックスが `Controller` なら除去
- アクションメソッドのサフィックスが `Action` なら除去
- 名前空間区切りはディレクトリ区切りに置き換わる
- コントローラー名とアクションメソッド名はディレクトリ区切りで結合される

例えば `App\Controller\HomeController::indexAction` というクラス名・メソッド名であれば、テンプレートは `App/Home/index` となります。

## コントローラーでミドルウェアを実装 → 廃止

コントローラーで PSR-15 の `MiddlewareInterface` を実装すると、アクションの前段のミドルウェアとしてコントローラー自身が組み込まれます。これはコントローラーごとの Pre/PostDispatch として使用できます。

```php
class HomeController implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        // ... Pre Dispatch ...

        $response = $delegate->process($request);

        // ... Post Dispatch ...

        return $response;
    }
}
```

**※廃止**

もしそういうことがやりたければそういう Middleware を作ると良いです。

```php
public function process(ServerRequestInterface $request, DelegateInterface $delegate)
        $instance = RouteResult::from($request)->getInstance();
        if ($instance instanceof MiddlewareInterface) {
            $pipeline = new MiddlewarePipe();
            $pipeline->pipe($instance);
            return $pipeline->process($request, $delegate);
        }
        return $delegate->process($request);
    }
}
```
