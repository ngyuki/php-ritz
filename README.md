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
