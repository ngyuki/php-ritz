# 虎の威を借りるオレオレフレームワーク

PSR-7(HTTP Message Interface) とか PHR-15(HTTP Middlewares) とか。

## 主要なフレームワークで使われているリクエスト/レスポンスクラス

- zend-expressive
    - zend-diactoros
        - https://github.com/zendframework/zend-expressive/blob/master/src/Application.php#L503-L514
- symfony-httpfoundation
    - 独自（非 PSR-7）
        - https://github.com/symfony/http-foundation/blob/master/Request.php
        - https://github.com/symfony/http-foundation/blob/master/Response.php
    - PSR-7 のブリッジはある
        - https://github.com/symfony/psr-http-message-bridge
        - 相互変換できるだけっぽい
        - 変換する先は zend-diactoros
- relayphp
    - PSR-7 にしか依存しない
        - https://github.com/relayphp/Relay.Relay/blob/1.x/src/Runner.php
    - zend-diactoros が基本っぽい
        - https://github.com/relayphp/Relay.Relay/blob/1.x/tests/RunnerTest.php
- stackphp
    - symfony-httpkernel 経由で symfony-httpfoundation
        - https://github.com/stackphp/run/blob/master/src/Stack/run.php
- slim
    - 独自に PSR-7 を実装
        - https://github.com/slimphp/Slim/blob/3.x/Slim/Http/Request.php
        - https://github.com/slimphp/Slim/blob/3.x/Slim/Http/Response.php
- laravel
    - symfony/http-foundation
        - https://github.com/laravel/framework/blob/5.4/src/Illuminate/Http/Request.php
        - https://github.com/laravel/framework/blob/5.4/src/Illuminate/Http/Response.php
- cakephp
    - 独自に PSR-7 を実装
        - https://github.com/cakephp/cakephp/blob/master/src/Http/ServerRequest.php
        - https://github.com/cakephp/cakephp/blob/master/src/Http/Response.php
    - ただし zend-diactoros の MessageTrait とかは使っている
- aura
    - 独自（非 PSR-7）
        - https://github.com/auraphp/Aura.Http/blob/1.x/src/Aura/Http/Message/Request.php
        - https://github.com/auraphp/Aura.Http/blob/1.x/src/Aura/Http/Message/Response.php
- bear.sunday
    - 独自っぽい
        - https://github.com/bearsunday/BEAR.Resource/blob/1.x/src/Request.php
        - https://github.com/bearsunday/BEAR.Sunday/blob/1.1.0/src/Provide/Transfer/HttpResponder.php
        - https://github.com/bearsunday/BEAR.Resource/blob/1.x/src/ResourceObject.php
    - そもそもリクエスト/レスポンスの考え方からして独特

## Action の戻り値

アクションの戻り値を Response にする案が考えられるが、それをしようとするとアクションの中でテンプレートのレンダリングが必要になるため、コントローラーが Renderer に依存してしまう。

アクションはほとんどのケースで決まった名前のテンプレートに変数をアサインして表示するだけなので、アサインする変数の連想配列を返すだけで十分。

ただ、下記のようなケースはありえる

- テンプレートを通常とは異なるものを指定したい
- レスポンスヘッダを弄りたい
- ビューを表示せずに直接レスポンスを返したい

なので ZF2 のように ViewModel を返すことにして、アクションの呼び出し元で ViewModel を元にレンダリングを行う。
いちいち ViewModel を作るのもめんどいので配列を返せば自動で ViewModel が作られるようにする。

ViewModel もまた Response を実装することで、レンダリングはミドルウェアで実装する。

Response が ViewModel であればレンダリングした結果を withBody する。Response が ViewModel で無ければ何もしない（アクションからレスポンスを直接返したいとき）。テンプレート名も ViewModel に含んでいて、もし未指定ならデフォルトのテンプレート名が使用される（コントローラー名＋アクション名）。

## Application/Controller の継承

Application/Controller はなるべく継承しない。継承前提だとコンストラクタインジェクションで DI しにくい。

継承元の抽象クラスでコンストラクタを実装しないという案は考えられるけど、それならトレイトで十分。

継承で実現していた TemplateMethod パターンによるフックポイントをどう代替するかが課題だが、下記の２つを設ける。

- アプリケーションでパイプラインを自由に構築できる
    - プラグイン/アクションヘルパ/継承元コントローラーの Pre/PostDispatch に相当
- コントローラーがミドルウェアを実装しているならディスパッチ前に呼び出す
    - コントローラーごとの Pre/PostDispatch に相当

アプリケーションクラスでパイプラインを作る必要があるのがちょっと手間かもしれない。

## Forward の実装

- Action で ForwardException とかを発破する
- RouteMiddleware の直上の Middleware でキャッチする
- Request の URI を書き換えてパイプラインを再実行する

とかでできるかと思ったけど `$delegate` はパイプラインのキューを実行毎に unshift していく実装なので、
２回実行することができない。

なので、例外発生時に ErrorController に forward したりができない。

Forward を使用しない前提で考える。

## エラーのハンドリング

エラーのハンドリングに ZF1 みたいに ErrorController を使うと forward が必要になって面倒。

そもそもエラー時にやることなんてせいぜいエラー画面表示するだけ。ZE の skeleton だとテンプレートを表示するだけの実装になっていたりするし。

今、アクションは ViewModel をレスポンスとして返して、ミドルウェアでレンダリングしているので、レンダリングの下のミドルウェアで try/catch して ViewModel でエラーのテンプレートを指定して返す。

この方法の問題はレンダリングの段階で発生したエラーはハンドリング出来ないこと。ただ、これはアプリケーションでパイプラインの作り方でどうとでもなる。エラーハンドラのミドルウェアをレンダリングの上にして、エラーハンドラでパイプラインを作って実行すれば良い。

## コントローラーのロードをどこでやるか

Route と Dispatch の間のミドルウェアで、コントローラーのインスタンスを instanceof とかしたい。
というのも、その位置のミドルウェアを ZF1 の Pre/PostDispatch のように使いたいため。

ので、Route の段階でコントローラーをインスタンス化する必要がある。

Route -> Load -> Dispatch のような３段のミドルウェアにしても良いかもしれないけど、うーん。

## ルーティングで設定するコントローラー名

`HogeController::class` のようにクラス名を直接指定するようにしたい。
ただ、コントローラーのクラス名からテンプレートを導出する方法が課題。

例えば `App\HogeController` なら `app/hoge` にするとか？ ZF2 はこの形式。
PSR-4 により 名前空間の App はディレクトリに現れないにも関わらず、
テンプレートでは app ディレクトリが現れるのが微妙？
トップに `app` `error` `layout` ディレクトリが来るなら自然な気もする？
（今までは `_layout` みたいに普通のテンプレートのディレクトリと区別してたし）

ZE はコントローラーでテンプレート名を明示するのでそういう問題はない。

コントローラー名からテンプレートディレクトリの解決にも PSR-4 みたいな方法を使う？

```php
return [
    'app.view.autoload' => [
        'App\\Controller\\' => __DIR__ . '/resource/view/',
    ],
];
```

## デフォルトのテンプレート名は誰が設定する？

いま Route ミドルウェアでデフォルトのテンプレート名を設定しているけどこれは妥当？

パイプラインを Route -> Render -> Dispatch の並びにして、
Route 結果から Render でデフォルトのテンプレート名を使うほうが自然？

その場合、いま RouteResult はコントローラーのインスタンスとメソッド名しか持っていないので、
そこからテンプレート名を導出すれば良いか？

あるいは RouteResult で $template みたいなプロパティを増やす？
Route ミドルウェアの役割を次の通りに考えればそんなに不自然でも無い。

「ディスパッチするコントローラー＆メソッドとレンダリングするテンプレートをリクエストから導出する」

ただデフォルトテンプレート名の解決はともかく、
ViewModel に反映するのは DispatchMiddleware でやったほうが
コントローラーのユニットテストはやりやすい。
（getTemplate が null を返す状況を考えなくていいから）

## セッション

- zend-session はユニットテストのためのスタブ的なのが標準で用意されていない？
    - `runInSeparateProcess` でどうにかすることも出来なくはないが・・
    - 直接セッションに依存しないよにすればいいのだけどうーん
- symfony はセッションが http-foundation に含むのが微妙
- laravel のセッションは依存でかすぎ
- aura/session もユニットテストのためのスタブみたいなのなさ気な気がする
    - むしろ無いのが普通？

## PSR のリクエストオブジェクトがショボい件

仕方がないことだけど PSR-7 の ServerRequestInterface をそのまま使うと、
Symfony とかと比べてショボさ感じる。
リクエストメソッドのチェックとかクエリパラメータの取得とか。

```php
if (strtoupper($request->getMethod()) === 'POST') {
    $val = $request->getParsedBody()['val'] ?? null;
}
```

こんな感じに書きたい。

```php
if ($request->isMethod('POST'))) {
    $val = $request->getPost('val');
}
```

アクションの引数にはリクエストのアトリビュートを元に DI されるので、
ディスパッチャーの前段のミドルウェアでラップしたオブジェクトを入れる？
ただし Controller で MiddlewareInterface を実装している場合、
前段のミドルウェアとアクションの間で呼ばれるので、そこでリクエストを with すると、
アクションに渡されるリクエストとラップしたリクエストの中身が異なってしまう。
あんまり無いことだとは思うけど。

アクションの Invoker を継承してアトリビュートを追加する？

```php
public function invoke(ServerRequestInterface $request, DelegateInterface $delegate, $instance, $method)
{
    $request = $request->withAttribute(UsefulRequest::class, new UsefulRequest($request));
    return parent::invoke($request, $delegate, $instance, $method);
}
```

あるいは、Controller の process と、アクションの間に挟まるミドルウェアを作れるようにする？
いや寧ろ Controller の process を実行するミドルウェアを別に設ける？

## ルーティング

ルーティングが書きにくい気がする。。。
無理にインデントを揃えようとするから？

```php
return [
    'app.routes' => value(function(RouteCollector $r) {
        $r->get('/', [HomeController::class, 'index']);
        $r->get('/view', [HomeController::class, 'view']);
        $r->get('/response', [HomeController::class, 'response']);
        $r->get('/raise', [HomeController::class, 'raise']);
        $r->get('/login', [LoginController::class, 'index']);
        $r->post('/login', [LoginController::class, 'login']);
        $r->get('/logout', [LoginController::class, 'logout']);
        $r->addGroup('/user', function (RouteCollector $r) {
            $r->get('/{name}', [HomeController::class, 'user']);
        });
    }),
];
```

配列でコントローラーとメソッドを分けているから角括弧で見にくくなっているのだろうか。
RouteCollector をラップして次のようにしてみる？

```php
return [
    'app.routes' => value(function(RouteCollector $r) {
        $r->get('/', HomeController::class, 'index');
        $r->get('/view', HomeController::class, 'view');
        $r->get('/response', HomeController::class, 'response');
        $r->get('/raise', HomeController::class, 'raise');
        $r->get('/login', LoginController::class, 'index');
        $r->post('/login', LoginController::class, 'login');
        $r->get('/logout', LoginController::class, 'logout');
        $r->addGroup('/user', function (RouteCollector $r) {
            $r->get('/{name}', HomeController::class, 'user');
        });
    }),
];
```

普通に文字列結合でもいい気がする。

```php
return function(RouteCollector $r) {
    $r->get('/', HomeController::class . '@index');
    $r->get('/view', HomeController::class . '@view');
    $r->get('/response', HomeController::class . '@response');
    $r->get('/raise', HomeController::class . '@raise');
    $r->get('/login', LoginController::class . '@index');
    $r->post('/login', LoginController::class . '@login');
    $r->get('/logout', LoginController::class . '@logout');
    $r->addGroup('/user', function (RouteCollector $r) {
        $r->get('/{name}', HomeController::class . '@user');
    });
});
```

メソッドチェイン？

```php
return function(RouteCollector $r) {
    $r->get('/')->controller(HomeController::class)->method('index');
    $r->get('/view')->controller(HomeController::class)->method('view');
    $r->get('/response')->controller(HomeController::class)->method('response');
    $r->get('/raise')->controller(HomeController::class)->method('raise');
    $r->get('/login')->controller(LoginController::class)->method('index');
    $r->post('/login')->controller(LoginController::class)->method('login');
    $r->get('/logout')->controller(LoginController::class)->method('logout');
    $r->addGroup('/user', function (RouteCollector $r) {
        $r->get('/{name}')->controller(HomeController::class)->method('@user');
    });
});
```

こんな風にグループ化できると良いかも？

```php
return function(RouteCollector $r) {
    $r->controller(UserController::class)->group(function (RouteCollector $r) {
            $r->prefix('/user')->group(function (RouteCollector $r) {
                $r->get('', 'index');
                $r->get('/new', 'create');
                $r->post('/new', 'create');
            });
            $r->prefix('/user/{id}')->group(function (RouteCollector $r) {
                $r->get('', 'show');
                $r->get('/edit', 'edit');
                $r->post('/edit', 'edit');
                $r->get('/delete', 'delete');
                $r->post('/delete', 'delete');
            });
        })
    ;
});
```

## コンフィグファイルのキャッシュ

glob は多分遅いのでファイル名をキャッシュするといいと思うんだけど、
キャッシュするかの設定をコンフィグファイルに書くので、鶏卵になってしまう。

例えば Bootstrap.php は固定で特定のファイルを読んで、そこにキャッシュの設定がある前提にする？

```php
$config = require __DIR__ . '/../bootstrap/app.php';
return (new Configure())
    ->useCache($config['app.config.cache_dir'] ?? null)
    ->init(function(){
        $env = getenv(APP_ENV);
        return array_merge(
            glob(__DIR__ . '/../bootstrap/*.php'),
            glob(__DIR__ . '/../config/default.php'),
            glob(__DIR__ . '/../config/local.php')
        );
    })
;
```

`app.php` が２回読まれるけど、別に良い？
次のように最初に読むファイルも Configure に読ませて重複排除する？

```php
return (new Configure())
    ->load(__DIR__ . '/../bootstrap/app.php')
    ->useCache(function ($config){
        return $config['app.config.cache_dir'] ?? null;
    })
    ->load(function(){
        $env = getenv(APP_ENV);
        return array_merge(
            glob(__DIR__ . '/../bootstrap/*.php'),
            glob(__DIR__ . '/../config/default.php'),
            glob(__DIR__ . "/../config/$env.php"),
            glob(__DIR__ . '/../config/local.php')
        );
    })
    ->get()
;
```

## デバッグフラグ

デバッグフラグはいろいろな場所で見たいことがあると思うけど、
いちいち DI 定義するのはめんどくさすぎる・・・

- DebugMiddleware がリクエストのアトリビュートに入れる
    - Controller 以外で使うときに面倒くさい
- Debug クラスを作ってオートワイヤリングで DI する
    - それだけのために Debug クラスなんてのを作るのは過剰な気がする
- Config みたいなフレームワークの基本的なコンフィグを持つクラスを作る

## Factory と Interface

フレームワークを構成するクラスは Factory と Interface も用意しておくことで、PHP-DI 以外の DI コンテナでも使いやすくする？

全部に Factory を作るのは過剰な気もするので、ContainerFactory を PHP-DI の記法ではなくクロージャーを用いたファクトリを使うことでコピペで別のコンテナのファクトリを作りやすくする。

ミドルウェアについてはインタフェースにする意味があんまりない。MiddlewareInterface なので。インタフェースを作ったとしても DI のエントリの ID にするぐらいしか使い道は無い。

## Server もコンテナに入れる？

```php
$server = new Server();
$server->run($container->get(Application::class), $container->get('debug'));
```

みたいなコードがちょっと微妙な感じする。

```php
/* app.php */
return [
    'debug' => true,
    'app.class' => Application::class,
];

/* index.php */
$container->get(Server::class)->run();

/* ContainerFactory.php */
return [
    Server::class => function ($container) {
        return new Server(
            $container->get($container->get(Application::class)),
            $container->get('debug')
        );
    },
];
```

`'app.class'` は無くてもいいかな？

```php
/* index.php */
$container->get(Server::class)->run(Application::class);
```

## Whoops とインテグレート

例外の表示に Whoops を使いたい。
ただ、フレームワークのコアとして有効なのは過剰なようにも思う。

A. フレームワークで例外をハンドリングしないというオプションを設けて、
Whoops を例外ハンドラとして登録する。

もしくは、

B. アプリケーションのミドルウェアとして Whoops を登録する。

という案が考えられる。

エラー画面のテストもできるようにすることを考えると、
テスト時は Whoops は無効にしたい。

A. ならテスト時は「例外をハンドリングしない」を無効にする
B. ならテスト時はそのミドルウェアを登録しない

HttpException をどうするかも問題。
こいつも Whoops で表示する？
ルートパラメータが違ったり権限とかの問題で Whoops が出るのは微妙？
それはそれで良いような気もする。
