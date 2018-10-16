<?php

// Composerでインストールしたライブラリを一括読み込み
require_once __DIR__ . '/vendor/autoload.php';

// アクセストークンを使いCurlHTTPClientをインスタンス化
$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));

// CurlHTTPClientとシークレットを使いLINEBotをインスタンス化
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);

// LINE Message APIがリクエストに付与した署名を取得
$signature = $_SERVER['HTTP_' . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];

//  署名が正当かチェック。正当であればリクエストをパースし配列へ
try{
  $events = $bot->parseEventRequest(file_get_contents('php://input'), $signature);
} catch(\LINE\LINEBot\Exception\InvalidSignatureException $e) {
  error_log('parseEventRequest failed. InvalidSignatureException => '.var_export($e, true));

} catch(\LINE\LINEBot\Exception\UnknownEventTypeException $e) {
  error_log('parseEventRequest failed. UnknownEventTypeException => '.var_export($e, true));

} catch(\LINE\LINEBot\Exception\UnknownMessageTypeException $e) {
  error_log('parseEventRequest failed. UnknownMessageTypeException => '.var_export($e, true));

} catch(\LINE\LINEBot\Exception\InvalidEventRequestException $e) {
  error_log('parseEventRequest failed. InvalidEventRequestException => '.var_export($e, true));
}


// 配列に格納された各イベントをループで処理
foreach ($events as $event){
  // MessageEventクラスのインスタンでなければ処理をスキップ
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent)) {
    error_log('Non message event has come');
    continue;
  }
  // TextMessageクラスのインスタンスでなければ処理をスキップ
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage)) {
    error_log('Non text message has come');
    continue;
  }

}

// テキストを返信。引数はLINEBot、返信先、テキスト
function replyTextMessage($bot, $replyToken, $text){
  //返信を行いレスポンスを取得
  //TextMessageBuilderの引数はテキスト
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text));

  //レスポンスが異常な場合
  if(!$response->isSucceeded()){
    //エラー内容を出力
    error_log('Failed! '. $response->getHTTPStatus . ' ' .
                              $response->getRawBody());
  }
}

// 画像を返信。引数はLINEBot、返信先、画像URL、サムネイルURL
function replyImageMessage($bot, $replyToken, $originalImageUrl,$previewImageUrl){
  // ImageMessageBuilderの引数は画像URL、サムネイルURL
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\
                        MessageBuilder\ImageMessageBuilder(
                          $originalImageUrl, $previewImageUrl));
  if (!$response->isSucceeded()){
    error_log( 'Failed!'. $response->getHTTPSatus . ' ' .
                              $response->getRawBody());
  }
}

// 位置情報を返信。引数はLINEBot、返信先、タイトル、住所、
// 緯度、経度
function replyLocationMessage($bot, $replyToken, $title, $address,$lat,
                              $lon) {
  //LocationMessageBuilderの引数はダイアログのタイトル、住所、緯度、経度
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\
                              MessageBuilder\LocationMessageBuilder(
                              $title, $address, $lat, $lon));
  if (!$response->isSucceeded()) {
    error_log('Failed!'. $response->getHTTPStatus . ' ' .
                              $response->getRawBody());
  }
}

// スタンプを返信。引数はLINEBot、返信先、
// スタンプのパッケージID、スタンプID
function replyStickerMessage($bot, $replyToken, $packageId,
                              $stickerId) {
  // StickerMessageBuilderの引数はスタンプのパッケージID、スタンプID
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\
                              MessageBuilder\StickerMessageBuilder(
                              $packageId, $stickerId));
  if (!$response->isSucceeded()){
    error_log('Failed!'. $response->getHTTPStatus .' ' .
                              $response->getRawBody());
  }
}


// 動画を返信。引数はLINEBot、返信先、動画URL、サムネイルURL
function replyVideoMessage($bot, $replyToken, $originalContentUrl,
                              $previewImageUrl) {
  // VideoMessageBuilderの引数は動画URL、サムネイルURL
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\
                              MessageBuilder\VideoMessageBuilder(
                              $originalContentUrl, $previewImageUrl));
  if (!$response->isSucceeded()){
    error_log('Failed!'. $response->getHTTPStatus .' ' .
                              $response->getRawBody());
  }
}

// オーディオファイルを返信。引数はLINEBot、返信先、
// ファイルのURL、ファイルの再生時間
function replyAudioMessage($bot, $replyToken, $originalContentUrl,
                              $audioLength){
  // AudioMessageBuilderの引数はファイルのURL、ファイルの再生時間
  $response = $bot->replyMessage($replyToken, new \LINE\LINEBot\
                              MessageBuilder\AudioMessageBuilder(
                              $originalContentUrl, $audioLength));
  if (!$response->isSucceeded()){
    error_log('Failed!'. $response->getHTTPStatus .' ' .
                              $response->getRawBody());
  }
}

// 複数のメッセージをまとめて返信。引数はLINEBot、
// 返信先、メッセージ、（可変長引数）
function replyMultiMessage($bot, $replyToken, ...$msgs) {
  // MultiMessageBuilderをインスタンス化
  $builder = new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder();
  // ビルダーにメッセージを全て追記
  foreach($msgs as $value) {
    $builder->add($value);
  }
  $response = $bot->replyMessage($replyToken, $builder);
  if (!$response->isSucceeded()){
    error_log('Failed!'. $response->getHTTPStatus .' ' .
                              $response->getRawBody());
  }
}

// Buttonsテンプレートを返信。引数はLINEBot、返信先、代替テキスト、
// 画像URL、タイトル、本文、アクション（可変長引数）
function replyBottonsTemplate($bot, $replyToken, $alternativeText,
                              $imageUrl, $title, $text, ...$actions){
  // アクションを格納する配列
  $actionArray = array();
  // アクションをすべて追加
  foreach($actions as $value) {
    array_Push($actionArray, $value);
  }
  // TemplateMessageBuilderの引数は代替テキスト、BottonTemplateBuilder
  $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
    $alternativeText,
    // ButtonTemplateBuilderの引数はタイトル、本文、
    // 画像URL、アクションの配列
    new \LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder(
                              $title, $text, $imageUrl, $actionArray)
  );
  $response = $bot->replyMessage($replyToken, $builder);
  if (!$response->isSucceeded()){
    error_log('Failed!'. $response->getHTTPStatus .' ' .
                            $response->getRawBody());
  }
}

// Confirmテンプレートを返信。引数はLINEBot、返信先、代替テキスト、
// 本文、アクション（可変長引数）
function replyConfirmTemplate($bot, $replyToken, $alternativeText, $text,
                              ...$actions){
  $actionArray = array();
  foreach($actions as $value) {
    array_Push($actionArray, $value);
  }
  $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
    $alternativeText,
    // Confirmテンプレートの引数はテキスト、アクションの配列
    new \LINE\LINEBot\MessageBuilder\TemplateBuilder\
                              ConfirmTemplateBuilder($text, $actionArray)
  );
  $response = $bot->replyMessage($replyToken, $builder);
  if (!$response->isSucceeded()){
    error_log('Failed!'. $response->getHTTPStatus .' ' .
                            $response->getRawBody());
  }
}

// Carouselテンプレートを返信。引数はLINEBot、返信先、代替テキスト、
// ダイアログの配列
function replyCarouselTemplate($bot, $replyToken, $alternativeText,
                              $columnArray) {
  $builder = new \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder(
    $alternativeText,
    // Carouselテンプレートの引数はダイアログの配列
    new \LINE\LINEBot\MessageBuilder\TemplateBuilder\
                              CarouselTemplateBuilder($columnArray)
  );
  $response = $bot->replyMessage($replyToken, $builder);
  if (!$response->isSucceeded()){
    error_log('Failed!'. $response->getHTTPStatus .' ' .
                            $response->getRawBody());
  }
}

foreach ($events as $event) {
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent)) {
      error_log('Non message event has come');
      continue;
  }
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage)) {
    error_log('Non message event has come');
    continue;
  }


  // オウム返し
  $parameters = $event->getText();
  $bot->replyText($event->getReplyToken(), $parameters);

  //　ユーザーIDを取得
  $userId = $event->getUserId();

  //parametesを分解
  $parameter = explode("、",$parameters);

  $location = $parameter[0];
  $space = $parameter[1];
  $before_slary = $parameter[2];
  $before_bonus = $parameter[3];
  $houserent = $parameter[4];
  $ages = $parameter[5];

  if($location == '東京都'){
  $housebenefit = 2590;
  }elseif($location == '神奈川県'){
  $housebenefit = 2070;
  }elseif($location == '千葉県'){
  $housebenefit = 1700;
  }elseif($location == '埼玉県'){
  $housebenefit = 1750;
  }elseif($location == '茨城県'){
  $housebenefit = 1270;
  }elseif($location == '群馬県'){
  $housebenefit = 1170;
  }elseif($location == '栃木県'){
  $housebenefit = 1310;
  }else{
    $exception = "申し訳ございません。シミュレーション対象外の地域です。";
    $response = $bot->pushMessage($userId, new \LINE\LINEBot\MessageBuilder\
                                      TextMessageBuilder($exception));

      if(!$response->isSucceeded()){
      error_log('Failed! '. $response->getHTTPStatus . ' ' .
                                    $response->getRawBody());
      }
  exit;
  }

  if($before_slary >= 58000 && $before_slary < 63000){
  $health_insurance_expense_nomal = 2871;
  $health_insurance_expense_kaigo = 3326;
  $pension_premiums = 8052;
  }elseif($before_slary >= 63000 && $before_slary < 73000){
  $health_insurance_expense_nomal = 3366;
  $health_insurance_expense_kaigo = 3899;
  $pension_premiums = 8052;
  }else{
    $health_insurance_expense_nomal = 15000;
    $health_insurance_expense_kaigo = 18000;
    $pension_premiums = 20000;
  }



  $calculation[] = $housebenefit; // [0] 住宅利益
  $calculation[] = $space * $housebenefit; // [1] 現物支給額
  $calculation[] = $before_slary; // [2] 月額給与
  $calculation[] = $before_bonus; // [3] 賞与
  $calculation[] = $calculation[2] * 12 + $calculation[3]; // [4] スマートサラリー導入前の年収
  $calculation[] = $ages; // [5] 年齢
  if($ages < 40){
    $calculation[] = $health_insurance_expense_nomal; // [6] 40歳未満　健康保険料
  }else{
    $calculation[] = $health_insurance_expense_kaigo; // [6] 40歳以上　健康保険料（介護保険料加算）
  }

  $calculation[] = $pension_premiums; // [7] 厚生年金保険料


  $payment_reduce = $houserent * 0.8;
  $after_salary = $before_salary - $payment_reduce;

  $calculation[] = $payment_reduce; // [8] 家賃×0.8
  $calculation[] = $after_salary; // [9] スマートサラリー導入後の給与

  $message1 = "勤務地：$parameter[0]\n\n住宅利益：$calculation[0]円/1畳\n広さ：$parameter[1]畳\n\n現物支給額換算：$calculation[1]円";

  $message2 = "スマートサラリー導入前\n給与：$calculation[2]円\n賞与：$calculation[3]円\n年収：$calculation[4]円\n\n年齢：$calculation[5]歳\n健康保険料：$calculation[6]円\n厚生年金保険料：$calculation[7]円";

  $message3 = "スマートサラリー導入後\n\n家賃の8割（= $calculation[8]円）を差し引き\n導入後の給与：$calculation[9]円";

  // メッセージ1をユーザーID宛にプッシュ
  $response = $bot->pushMessage($userId, new \LINE\LINEBot\MessageBuilder\
                                    TextMessageBuilder($message1));

    if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' ' .
                                  $response->getRawBody());
    }

  // メッセージ2をユーザーID宛にプッシュ
  $response = $bot->pushMessage($userId, new \LINE\LINEBot\MessageBuilder\
                                    TextMessageBuilder($message2));

    if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' ' .
                                  $response->getRawBody());
    }

  // メッセージ3をユーザーID宛にプッシュ
  $response = $bot->pushMessage($userId, new \LINE\LINEBot\MessageBuilder\
                                    TextMessageBuilder($message3));

    if(!$response->isSucceeded()){
    error_log('Failed! '. $response->getHTTPStatus . ' ' .
                                  $response->getRawBody());
    }


}


?>
