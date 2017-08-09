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

## サンプルアプリ

次のコマンドでサンプルアプリが実行できます。

```sh
conposer install
php -S 0.0.0.0:8888
open http://localhost:8888/
```

### ディレクトリ構造

サンプルアプリは次のディレクトリ構造になっています。

- app/
    - オートロードされる PHP クラスを格納するディレクトリ
- bootstrap/
    - DI コンテナの初期化のためのコードを格納するディレクトリ
    - 環境依存しない初期化の設定を格納します
- cache/
    - キャッシュファイルを書き込むディレクトリ
- config/
    - DI コンテナの初期化のためのコードを格納
    - 環境依存のアプリの設定ファイルを格納します
- public/
    - サーバのドキュメントルートに設定するディレクトリ
- resource/
    - ビューのテンプレートファイルなどを格納するディレクトリ
- tests/App/
    - アプリケーションのテストコード

### ネームスペース構造

サンプルアプリのネームスペースは次の構造になっています。

- Ritz\App\Bootstrap
    - アプリケーションクラスやコンテナファクトリなどのアプリの初期化で使用されるクラス
- Ritz\App\Component
    - Controller や Service で使用されるその他のコンポーネントクラスが格納されます
- Ritz\App\Controller
    - アプリのコントローラークラスが格納されます
- Ritz\App\Middleware
    - アプリで独自に使用する PSR-15 Middleware が格納されます
- Ritz\App\Service
    - アプリのユースケースを実装するサービスクラスを格納します
```

### 処理の流れ

サンプルアプリは次の流れで処理されます。

- `ContainerFactory` がコンテナを作成する
    - `Configure` クラスが `bootstrap/` と `config/` から DI コンテナの初期化のコードを読む
    - `config/` からは環境変数 `APP_ENV` に基づいたファイルも読まれる
    - ルート定義も `bootstrap/` に含まれている
- コンテナから `Application` クラスのオブジェクトを取り出す
- `Application` オブジェクトを `Server::run` クラスに渡してアプリケーションを実行する
- `Application` が PSR-15 Middleware として実行される
    - 実行時にパイプラインを構築する
- パイプラインが順番に実行される

サンプルアプリの幾つかのコードには詳細なコメントが記述されているため、アプリケーション構成の参考にしてください。

## Application クラス

`Application` クラスは PSR-15 の `MiddlewareInterface` を実装し、`process` メソッドでパイプラインを初期化して実行する必要があります。

下記の 3 つのミドルウェアはフレームワークが提供する基本的なミドルウェアで、基本的には必須です（もちろん独自のミドルウェアに差し替えることも出来ます）。

- `RenderMiddleware`
- `RouteMiddleware`
- `DispatchMiddleware`

必要に応じて、アプリケーション独自のミドルウェアをパイプラインの任意の場所に差し込むことができます。例えば、次のようなアプリケーション独自パイプラインが考えられます。

- 例外発生時に特定のテンプレート選択する
- CSRF トークンのチェックを行う
- 認証のチェックとリダイレクトを行なう
- アクションの前後でリクエスト・レスポンスオブジェクトを加工する

## ルート定義

ルート定義は下記のようになります。ルーターの実装には `FastRoute` を使用しているため、具体的なルート定義の方法は `FastRoute` のドキュメントを参照してください。

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

ルートのハンドラ（`get` メソッドの第２引数）にはコントローラー名とアクション名からなる配列を指定します。コントローラー名は DI コンテナからコントローラーインスタンスを取り出す際のエントリ名です（典型的にはコントローラーのクラス名）。アクション名はコントローラーのメソッド名です。

ルートのハンドラに文字列のインデックスが含まれる場合、リクエストの属性にそのままセットされます。

```php
// routes.php
return [
    'app.routes' => value(function(RouteCollector $r) {
        $r->get('/', [HomeController::class, 'indexAction', 'attr' => 'val']);
    }),
];

// HomeController.php
class HomeController
{
    public function attrAction(ServerRequestInterface $request)
    {
        $attr = $request->getAttribute('attr'); // val
    }
}
```

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
return (new ViewModel())->withTemplate('./edit');
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

`ViewModel` でテンプレート名が指定されていない場合、`RenderMiddleware` のコンストラクタに渡された `TemplateResolver` がコントローラーのクラス名とアクションメソッド名を元にテンプレート名が導出されます。

`TemplateResolver` はコンストラクタで、コントローラーの名前空間とテンプレートのプレフィックスのマッピングの配列を受け取ります。例えば、次のように `TemplateResolver` のインスタンスを設定します。

```php
return [
    'app.view.autoload' => [
        'Ritz\\App\\Controller\\' => 'App/',
    ],
    TemplateResolver::class => function (ContainerInterface $container) {
        return new TemplateResolver($container->get('app.view.autoload'));
    },
];
```

`app.view.autoload` は Composer の PSR-4 のオートローダーの設定に似ています。上の設定では `Ritz\\App\\Controller\\` という名前空間のプレフィックスは `App/` というテンプレート名に置換されます。

さらに、クラス名とメソッド名に次の加工が施されて、テンプレート名が導出されます。

- コントローラーのクラス名のサフィックスが `Controller` なら除去
- アクションメソッドのサフィックスが `Action` なら除去
- 名前空間区切りはディレクトリ区切りに置き換わる
- コントローラー名とアクションメソッド名はディレクトリ区切りで結合される

例えば `Ritz\App\Controller\HomeController::indexAction` というクラス名・メソッド名であれば、テンプレートは `App/Home/index` となります。
