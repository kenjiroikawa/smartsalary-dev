<?PHP

// Composerでインストールしたライブラリを一括読み込み
require_once __DIR__ . '/vendor/autoload.php';

// アクセストークンを使いCurlHTTPClientをインスタンス化
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));

// CurlHTTPClientとシークレットを使いLINEBotをインスタンス化
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv(
                                'CHANNEL_SECRET')]);


// あなたのユーザーIDを入力してください。
$userId = 'U1fe57aa194beef5c5f3c916ce6839d55'；
$message = 'Hello Push API';

// メッセージをユーザーID宛にプッシュ
$response = $bot->pushMessage($userId, new \LINE\LINEBot\MessageBuilder\
                              TextMessageBuilder($message));
if(!$response->isSucceeded()){
  error_log('Failed! '. $response->getHTTPStatus . ' ' .
                            $response->getRawBody());
}

?>
