# 虎の威を借りるオレオレフレームワーク

PSR-7(HTTP Message Interface) とか PHR-15(HTTP Middlewares) とか。

## アクションの戻り値は ViewModel にしてコントローラーをレンダラに依存させない

アクションの戻り値を Response にする場合、アクションの中でテンプレートのレンダリングが必要で、コントローラーがレンダラに依存してしまう。

アクションはほとんどのケースで、コントローラー名とアクション名から導出されるテンプレートに変数をアサインして表示するだけなので、アサインする変数の連想配列を返すだけで十分。

ただ、下記のようなケースはありえる

- テンプレートを通常とは異なるものを指定したい
- レスポンスヘッダを弄りたい
- ビューを表示せずに直接レスポンスを返したい

なので ZF2 のように ViewModel を返すようにして、アクションの呼び出し元で ViewModel を元にレンダリングを行う。

さらに、ViewModel もまた Response を実装することで、レンダリングはそれ用のミドルウェアで実装する。

- アクションが が Body が空の ViewModel を返す
- 上位のミドルウェアでレスポンスが ViewModel ならレンダリングした結果を withBody する

## Application/Controller は継承を前提にしない

Application/Controller はなるべく継承しない。継承前提だとコンストラクタインジェクションで DI しにくい。

継承元の抽象クラスでコンストラクタを実装しないという案は考えられるけど、それならトレイトで十分。

継承で実現していた TemplateMethod パターンによるフックポイントをどう代替するかが課題だが、ミドルウェアのパイプラインを代替として使用する。

## Forward は実装しない

PSR-15 と、いわゆる Forward は相性が悪い。次のようにしてできると思ったけど・・・

- Action で ForwardException とかを発破する
- RouteMiddleware の直上の Middleware でキャッチする
- Request の URI を書き換えてパイプラインを再実行する

`$delegate` はパイプラインを `SplQueue` で持っていて、実行毎に `unshif`t していく実装なので、同じ `$delegate` を 2 回実行できない。

## エラーのハンドリングはミドルウェアでやる

エラーのハンドリングに ZF1 みたいに ErrorController を使うと forward が必要になる。

そもそもエラー時にやることなんてせいぜいエラー画面表示するだけ。ZE の skeleton だとテンプレートを表示するだけの実装になっていたりするし。

今、アクションは ViewModel をレスポンスとして返して、レンダリング用のミドルウェアでレンダリングしているので、レンダリングの下のミドルウェアで try/catch して ViewModel でエラーのテンプレートを指定して返す。

この方法の問題はレンダリングの段階で発生したエラーはハンドリング出来ないこと。ただ、これはアプリケーションでパイプラインの作り方でどうとでもなる。エラーハンドラのミドルウェアをレンダリングの上にして、エラーハンドラでパイプラインを作って実行すれば良い。

## コントローラーのロードはルーターでやる

Route と Dispatch の間のミドルウェアで、コントローラーのインスタンスを instanceof とかしたい。
というのも、その位置のミドルウェアを ZF1 の Pre/PostDispatch のように使いたいため。

ので、Route の段階でコントローラーをインスタンス化する。

## Middleware の並び ... Route -> Render -> Dispatch

Render -> Route -> Dispatch の並びだと、Route で発生したエラー（404 とか）をエラーとして表示しやすいので、テンプレート名の自動解決は Route でやって VireModel に設定して Render に渡すようにしようと思った。

けど、Route は 404 でも次のパイプラインに進むので、Route では基本的にバグ以外で例外は発生しない。
なので、Route -> Render -> Dispatch の順番にして、ルート結果を元に Render でテンプレート名を自動解決する。

## セッションはフレームワークに含めない

好きなものをアプリで使えば良い。

- zend-session はユニットテストのためのスタブ的なのが標準で用意されていない
    - `runInSeparateProcess` でどうにかすることも出来なくはないが・・
    - セッションに直接依存しないようにすれば良い
- symfony はセッションが http-foundation に含まれていて微妙
- laravel のセッションは依存でかすぎ
- aura/session もユニットテストのためのスタブみたいなものはなさそう
    - むしろ無いのが普通？

## PSR のリクエストオブジェクトがショボい件

PSR-7 の ServerRequestInterface をそのまま使うと、Symfony とかと比べてショボさ感じる。
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

アクションの前段のミドルウェアでリクエストのアトリビュートにラップしたリクエストを入れれば、アクションでそれを使うことができる。

```php
public function process(ServerRequestInterface $request, DelegateInterface $delegate)
{
    $request = $request->withAttribute(UsefulRequest::class, new UsefulRequest($request));
    return $delegate->process($request);
}
```

あるいは ActionInvoker を継承してアトリビュートを追加しても良い。

```php
public function invoke(ServerRequestInterface $request, DelegateInterface $delegate, $instance, $method)
{
    $request = $request->withAttribute(UsefulRequest::class, new UsefulRequest($request));
    return parent::invoke($request, $delegate, $instance, $method);
}
```

## ルーティングが書きにくい？

ルーティングが書きにくい気がする。。。無理にインデントを揃えようとするから？

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

そういうのは独自の RouteCollector を実装すれば良いわけなので、フレームワークとしては現状のままで良い。

↓みたいなメソッドシグネチャを持つ RouterInterface を設けるのでも良い？

```php
/**
 * @param string $method
 * @param string $uri
 * @return array [string $controller, string $action, array $params]
 */

public function route($method, $uri);
```

## コンフィグファイルのキャッシュをどうする？

glob は多分遅いのでファイル名をキャッシュするといいと思うんだけど、
キャッシュの設定をコンフィグファイルに書くので、鶏卵になってしまう。

プロダクションでしか必要ないものだし、あらかじめ一覧を `config/config-files.php` とかにビルドするような仕組みでも設ける？ のも過剰な気がするし、なくていいか。

## デバッグフラグをどうやってインジェクションする？

デバッグフラグはいろいろな場所で見たいことがあると思うけど、
いちいち DI 定義するのはめんどくさすぎる・・・

- DebugMiddleware がリクエストのアトリビュートに入れる
    - Controller 以外で使うときに面倒くさい
- Debug クラスを作ってオートワイヤリングで DI する
    - それだけのために Debug クラスなんてのを作るのは過剰な気がする
- Config みたいなフレームワークの基本的なコンフィグを持つクラスを作る
- そもそもデバッグフラグみたいなので動作を変えるべきではない
    - オブジェクトの属性とかで振る舞いを制御すべき

## TemplateResolver で PSR-4 みたいにテンプレート名を解決するの必要？

マルチモジュールのようにするのであれば別だけど、大抵の場合は下記の規則で十分。

- コントローラーの名前空間の `Controller` というセグメントを除去
- コントローラーのクラス名のサフィックスが `Controller` なら除去
- アクションメソッドのサフィックスが `Action` なら除去
- 名前空間区切りはディレクトリ区切りに置換
- コントローラー名とアクションメソッド名をディレクトリ区切りで結合

e.g.) `App\Controller\HomeController::indexAction` -> `App\Home\index`

もともとサンプルアプリで名前空間を `Ritz\App` にしたときにテンプレートディレクトリの階層が深くなるのが嫌だっただけ。なので、これ以外の規則が必要になったときに拡張するとかで十分だと思う。
