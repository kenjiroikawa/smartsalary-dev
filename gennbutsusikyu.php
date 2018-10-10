<?PHP
// Composerでインストールしたライブラリを一括読み込み
require_once __DIR__ . '/vendor/autoload.php';

// 勤務地をオウム返し
$response = $bot->pushMessage($userId, new \LINE\LINEBot\MessageBuilder\
                                TextMessageBuilder($work_location));
if(!$response->isSucceeded()){
  error_log('Failed! '. $response->getHTTPStatus . ' ' .
                              $response->getRawBody());
  }


?>
