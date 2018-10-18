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


  // 入力情報を受取
  $parameters = $event->getText();

  //　ユーザーIDを取得
  $userId = $event->getUserId();

  //parametersを分解
  $parameter = explode("、",$parameters);

  // 基本情報：各parametersをそれぞれの変数に代入
  $location = $parameter[0];
  $space = $parameter[1];
  $before_slary = $parameter[2];
  $before_bonus = $parameter[3];
  $houserent = $parameter[4];
  $ages = $parameter[5];
  $partner = $parameter[6];
  $dependants = $parameter[7];


  //入力のバリデーション

  if( substr_count($parameters, '、') < 7){
    $error = "入力項目が不足しています。\n案内に沿って、8項目を入力してください。";
    $bot->replyText($event->getReplyToken(), $error);
    exit;
/*  }elsif( substr_count($parameters, '、') > 7){
    $error = "入力に誤りがあります。\n案内に沿って、8項目を入力してください。";
    $response = $bot->pushMessage($userId, new \LINE\LINEBot\MessageBuilder\
                                      TextMessageBuilder($error));

      if(!$response->isSucceeded()){
      error_log('Failed! '. $response->getHTTPStatus . ' ' .
                                    $response->getRawBody());
      }
    exit;
  }elsif(preg_match("/[^一-龠]/u",$location){
    $error = "入力に誤りがあります。\n";
    $response = $bot->pushMessage($userId, new \LINE\LINEBot\MessageBuilder\
                                      TextMessageBuilder($error));

      if(!$response->isSucceeded()){
      error_log('Failed! '. $response->getHTTPStatus . ' ' .
                                    $response->getRawBody());
        }
    exit;
  }elsif(preg_match("/^[0-9]+$/", $space)){
    $error = "広さの入力に誤りがあります。半角数値のみで入力してください。\n";
    $response = $bot->pushMessage($userId, new \LINE\LINEBot\MessageBuilder\
                                      TextMessageBuilder($error));

      if(!$response->isSucceeded()){
      error_log('Failed! '. $response->getHTTPStatus . ' ' .
                                    $response->getRawBody());
        }
    exit;*/
  }else{
  
  }

  // 事前計算
  $before_yearly_income = $before_slary * 12 + $before_bonus;  // 導入前：年収
  $payment_reduce = $houserent * 0.8;                          // 導入後：会社支払家賃
  $rest_payment = $houserent * 0.2;                            // 導入後：本人支払家賃
  $after_salary = $parameter[2] - $payment_reduce;             // 導入後：給与
  $after_yearly_income = $after_salary * 12 + $before_bonus;    // 導入後：年収



// 都道府県による住宅利益の分類 開始---------------------------------------
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
    $exception = "申し訳ございません。シミュレーション対象外の地域です。\n対象の地域は、東京都・神奈川県・埼玉県・千葉県・茨城県・群馬県・栃木県となります。";
    $bot->replyText($event->getReplyToken(), $exception);
  exit;
  }
// 都道府県による住宅利益の分類 終了---------------------------------------

// 導入後、社会保険料の計算対象に加算される現物支給額の計算
  $in_kind_as_house = $space * $housebenefit;

// 導入後の給与に現物給与支給額を加算
  $after_salary_in_kind = $after_salary + $in_kind_as_house;

// 導入前：給与に応じた社会保険料の計算 開始-------------------------------
  if($before_slary >= 58000 && $before_slary < 63000){
    $before_health_insurance_expense_nomal = 2871;
    $before_health_insurance_expense_kaigo = 3326;
                  $before_pension_premiums = 8052;
  }elseif($before_slary >= 63000 && $before_slary < 73000){
    $before_health_insurance_expense_nomal = 3366;
    $before_health_insurance_expense_kaigo = 3899;
                  $before_pension_premiums = 8052;
  }elseif($before_slary >= 73000 && $before_slary < 83000){
    $before_health_insurance_expense_nomal = 3861;
    $before_health_insurance_expense_kaigo = 4473;
                  $before_pension_premiums = 8052;
  }elseif($before_slary >= 83000 && $before_slary < 93000){
    $before_health_insurance_expense_nomal = 4356;
    $before_health_insurance_expense_kaigo = 5046;
                  $before_pension_premiums = 8052;
  }elseif($before_slary >= 93000 && $before_slary < 101000){
    $before_health_insurance_expense_nomal = 4851;
    $before_health_insurance_expense_kaigo = 5620;
                  $before_pension_premiums = 8967;
  }elseif($before_slary >= 101000 && $before_slary < 107000){
    $before_health_insurance_expense_nomal = 5148;
    $before_health_insurance_expense_kaigo = 5964;
                  $before_pension_premiums = 9516;
  }elseif($before_slary >= 107000 && $before_slary < 114000){
    $before_health_insurance_expense_nomal = 5445;
    $before_health_insurance_expense_kaigo = 6308;
                  $before_pension_premiums = 10065;
  }elseif($before_slary >= 114000 && $before_slary < 122000){
    $before_health_insurance_expense_nomal = 5841;
    $before_health_insurance_expense_kaigo = 6767;
                  $before_pension_premiums = 10797;
  }elseif($before_slary >= 122000 && $before_slary < 130000){
    $before_health_insurance_expense_nomal = 6237;
    $before_health_insurance_expense_kaigo = 7226;
                  $before_pension_premiums = 11529;
  }elseif($before_slary >= 130000 && $before_slary < 138000){
    $before_health_insurance_expense_nomal = 6633;
    $before_health_insurance_expense_kaigo = 7684;
                  $before_pension_premiums = 12261;
  }elseif($before_slary >= 138000 && $before_slary < 146000){
    $before_health_insurance_expense_nomal = 7029;
    $before_health_insurance_expense_kaigo = 8143;
                  $before_pension_premiums = 12993;
  }elseif($before_slary >= 146000 && $before_slary < 155000){
    $before_health_insurance_expense_nomal = 7425;
    $before_health_insurance_expense_kaigo = 8602;
                  $before_pension_premiums = 13725;
  }elseif($before_slary >= 155000 && $before_slary < 165000){
    $before_health_insurance_expense_nomal = 7920;
    $before_health_insurance_expense_kaigo = 9176;
                  $before_pension_premiums = 14640;
  }elseif($before_slary >= 165000 && $before_slary < 175000){
    $before_health_insurance_expense_nomal = 8415;
    $before_health_insurance_expense_kaigo = 9749;
                  $before_pension_premiums = 15555;
  }elseif($before_slary >= 175000 && $before_slary < 185000){
    $before_health_insurance_expense_nomal = 8910;
    $before_health_insurance_expense_kaigo = 10323;
                  $before_pension_premiums = 16470;
  }elseif($before_slary >= 185000 && $before_slary < 195000){
    $before_health_insurance_expense_nomal = 9405;
    $before_health_insurance_expense_kaigo = 10896;
                  $before_pension_premiums = 17385;
  }elseif($before_slary >= 195000 && $before_slary < 210000){
    $before_health_insurance_expense_nomal = 9900;
    $before_health_insurance_expense_kaigo = 11470;
                  $before_pension_premiums = 18300;
  }elseif($before_slary >= 210000 && $before_slary < 230000){
    $before_health_insurance_expense_nomal = 10890;
    $before_health_insurance_expense_kaigo = 12617;
                  $before_pension_premiums = 20130;
  }elseif($before_slary >= 230000 && $before_slary < 250000){
    $before_health_insurance_expense_nomal = 11880;
    $before_health_insurance_expense_kaigo = 13764;
                  $before_pension_premiums = 21960;
  }elseif($before_slary >= 250000 && $before_slary < 270000){
    $before_health_insurance_expense_nomal = 12870;
    $before_health_insurance_expense_kaigo = 14911;
                  $before_pension_premiums = 23790;
  }elseif($before_slary >= 270000 && $before_slary < 290000){
    $before_health_insurance_expense_nomal = 13860;
    $before_health_insurance_expense_kaigo = 16058;
                  $before_pension_premiums = 25620;
  }elseif($before_slary >= 290000 && $before_slary < 310000){
    $before_health_insurance_expense_nomal = 14850;
    $before_health_insurance_expense_kaigo = 17205;
                  $before_pension_premiums = 27450;
  }elseif($before_slary >= 310000 && $before_slary < 330000){
    $before_health_insurance_expense_nomal = 15840;
    $before_health_insurance_expense_kaigo = 18352;
                  $before_pension_premiums = 29280;
  }elseif($before_slary >= 330000 && $before_slary < 350000){
    $before_health_insurance_expense_nomal = 16830;
    $before_health_insurance_expense_kaigo = 19499;
                  $before_pension_premiums = 31110;
  }elseif($before_slary >= 350000 && $before_slary < 370000){
    $before_health_insurance_expense_nomal = 17820;
    $before_health_insurance_expense_kaigo = 20646;
                  $before_pension_premiums = 32940;
  }elseif($before_slary >= 370000 && $before_slary < 395000){
    $before_health_insurance_expense_nomal = 18810;
    $before_health_insurance_expense_kaigo = 21793;
                  $before_pension_premiums = 34770;
  }elseif($before_slary >= 395000 && $before_slary < 425000){
    $before_health_insurance_expense_nomal = 20295;
    $before_health_insurance_expense_kaigo = 23513;
                  $before_pension_premiums = 37515;
  }elseif($before_slary >= 425000 && $before_slary < 455000){
    $before_health_insurance_expense_nomal = 21780;
    $before_health_insurance_expense_kaigo = 25234;
                  $before_pension_premiums = 40260;
  }elseif($before_slary >= 455000 && $before_slary < 485000){
    $before_health_insurance_expense_nomal = 23265;
    $before_health_insurance_expense_kaigo = 26954;
                  $before_pension_premiums = 43005;
  }elseif($before_slary >= 485000 && $before_slary < 515000){
    $before_health_insurance_expense_nomal = 24750;
    $before_health_insurance_expense_kaigo = 28675;
                  $before_pension_premiums = 45750;
  }elseif($before_slary >= 515000 && $before_slary < 545000){
    $before_health_insurance_expense_nomal = 26235;
    $before_health_insurance_expense_kaigo = 30395;
                  $before_pension_premiums = 48495;
  }elseif($before_slary >= 545000 && $before_slary < 575000){
    $before_health_insurance_expense_nomal = 27720;
    $before_health_insurance_expense_kaigo = 32116;
                  $before_pension_premiums = 51240;
  }elseif($before_slary >= 575000 && $before_slary < 605000){
    $before_health_insurance_expense_nomal = 29205;
    $before_health_insurance_expense_kaigo = 33836;
                  $before_pension_premiums = 53985;
  }elseif($before_slary >= 605000 && $before_slary < 635000){
    $before_health_insurance_expense_nomal = 30690;
    $before_health_insurance_expense_kaigo = 35557;
                  $before_pension_premiums = 56730;
  }elseif($before_slary >= 635000 && $before_slary < 665000){
    $before_health_insurance_expense_nomal = 32175;
    $before_health_insurance_expense_kaigo = 37277;
                  $before_pension_premiums = 56730;
  }elseif($before_slary >= 665000 && $before_slary < 695000){
    $before_health_insurance_expense_nomal = 33660;
    $before_health_insurance_expense_kaigo = 38998;
                  $before_pension_premiums = 56730;
  }elseif($before_slary >= 695000 && $before_slary < 730000){
    $before_health_insurance_expense_nomal = 35145;
    $before_health_insurance_expense_kaigo = 40718;
                  $before_pension_premiums = 56730;
  }elseif($before_slary >= 730000 && $before_slary < 770000){
    $before_health_insurance_expense_nomal = 37125;
    $before_health_insurance_expense_kaigo = 43012;
                  $before_pension_premiums = 56730;
  }elseif($before_slary >= 770000 && $before_slary < 810000){
    $before_health_insurance_expense_nomal = 39105;
    $before_health_insurance_expense_kaigo = 45306;
                  $before_pension_premiums = 56730;
  }elseif($before_slary >= 810000 && $before_slary < 855000){
    $before_health_insurance_expense_nomal = 41085;
    $before_health_insurance_expense_kaigo = 47600;
                  $before_pension_premiums = 56730;
  }elseif($before_slary >= 855000 && $before_slary < 905000){
    $before_health_insurance_expense_nomal = 43560;
    $before_health_insurance_expense_kaigo = 50468;
                  $before_pension_premiums = 56730;
  }elseif($before_slary >= 905000 && $before_slary < 955000){
    $before_health_insurance_expense_nomal = 46035;
    $before_health_insurance_expense_kaigo = 53335;
                  $before_pension_premiums = 56730;
  }elseif($before_slary >= 955000 && $before_slary < 1005000){
    $before_health_insurance_expense_nomal = 48510;
    $before_health_insurance_expense_kaigo = 56203;
                  $before_pension_premiums = 56730;
  }elseif($before_slary >= 1005000 && $before_slary < 1055000){
    $before_health_insurance_expense_nomal = 50985;
    $before_health_insurance_expense_kaigo = 59070;
                  $before_pension_premiums = 56730;
  }elseif($before_slary >= 1055000 && $before_slary < 1115000){
    $before_health_insurance_expense_nomal = 53955;
    $before_health_insurance_expense_kaigo = 62511;
                  $before_pension_premiums = 56730;
  }elseif($before_slary >= 1115000 && $before_slary < 1175000){
    $before_health_insurance_expense_nomal = 56925;
    $before_health_insurance_expense_kaigo = 65952;
                  $before_pension_premiums = 56730;
  }elseif($before_slary >= 1115000 && $before_slary < 1235000){
    $before_health_insurance_expense_nomal = 59895;
    $before_health_insurance_expense_kaigo = 69393;
                  $before_pension_premiums = 56730;
  }elseif($before_slary >= 1235000 && $before_slary < 1295000){
    $before_health_insurance_expense_nomal = 62865;
    $before_health_insurance_expense_kaigo = 72834;
                  $before_pension_premiums = 56730;
  }elseif($before_slary >= 1295000 && $before_slary < 1355000){
    $before_health_insurance_expense_nomal = 65835;
    $before_health_insurance_expense_kaigo = 76275;
                  $before_pension_premiums = 56730;
  }else{
    $before_health_insurance_expense_nomal = 68805;
    $before_health_insurance_expense_kaigo = 79716;
                  $before_pension_premiums = 56730;
  }
// 導入前：給与に応じた社会保険料の計算 終了-------------------------------


// 導入前：40歳未満/以上 介護保険加算の仕分け
  if($ages < 40){
    $before_health_insurance_expense = $before_health_insurance_expense_nomal; // 導入前：40歳未満　健康保険料
  }else{
    $before_health_insurance_expense = $before_health_insurance_expense_kaigo; // 導入前：40歳以上　健康保険料（介護保険料加算）
  }

// 導入前：社保控除後の金額
  $before_pretax_salary = $before_slary - $before_health_insurance_expense - $before_pension_premiums;

// 導入前：社保控除後の金額に応じた源泉徴収額の計算 開始----------------------
  if($before_pretax_salary >= 58000 && $before_pretax_salary < 400000){
    $before_dependant0 = 5000;
    $before_dependant1 = 4000;
    $before_dependant2 = 3000;
    $before_dependant3 = 2000;
    $before_dependant4 = 1000;
    $before_dependant5 = 500;
    $before_dependant6 = 300;
    $before_dependant7 = 100;
  }elseif($before_pretax_salary >= 400000 && $before_pretax_salary < 800000){
    $before_dependant0 = 25000;
    $before_dependant1 = 24000;
    $before_dependant2 = 23000;
    $before_dependant3 = 22000;
    $before_dependant4 = 21000;
    $before_dependant5 = 20000;
    $before_dependant6 = 19000;
    $before_dependant7 = 18000;
  }else{
    $before_dependant0 = 45000;
    $before_dependant1 = 44000;
    $before_dependant2 = 43000;
    $before_dependant3 = 42000;
    $before_dependant4 = 41000;
    $before_dependant5 = 40000;
    $before_dependant6 = 39000;
    $before_dependant7 = 38000;
  }
//導入前：社保控除後の金額に応じた源泉徴収額の計算 終了----------------------


//導入前：扶養家族に応じた源泉徴収額の計算 開始----------------------
if($dependants == 0 ){
  $before_income_tax =  $before_dependant0;
}elseif($dependants == 1 ){
  $before_income_tax =  $before_dependant1;
}elseif($dependants == 2 ){
  $before_income_tax =  $before_dependant2;
}elseif($dependants == 3 ){
  $before_income_tax =  $before_dependant3;
}elseif($dependants == 4 ){
  $before_income_tax =  $before_dependant4;
}elseif($dependants == 5 ){
  $before_income_tax =  $before_dependant5;
}elseif($dependants == 6 ){
  $before_income_tax =  $before_dependant6;
}else{
  $before_income_tax =  $before_dependant7;
}
//導入前：扶養家族に応じた源泉徴収額の計算 終了----------------------


// 導入前：住民税の計算 開始-----------------------------------------
  $before_inhabitant_tax = 10000;
// 導入前：住民税の計算 終了-----------------------------------------

// 導入前：社保、税金、家賃控除後の可処分所得の計算
$before_disposable_income = $parameter[2] - $before_health_insurance_expense - $before_pension_premiums - $before_income_tax - $before_inhabitant_tax - $parameter[4];


// 導入後：健康保険料の計算 開始-----------------------------------------
    if($after_salary_in_kind >= 58000 && $after_salary_in_kind < 63000){
      $after_health_insurance_expense_nomal = 2871;
      $after_health_insurance_expense_kaigo = 3326;
                    $after_pension_premiums = 8052;
    }elseif($after_salary_in_kind >= 63000 && $after_salary_in_kind < 73000){
      $after_health_insurance_expense_nomal = 3366;
      $after_health_insurance_expense_kaigo = 3899;
                    $after_pension_premiums = 8052;
    }elseif($after_salary_in_kind >= 73000 && $after_salary_in_kind < 83000){
      $after_health_insurance_expense_nomal = 3861;
      $after_health_insurance_expense_kaigo = 4473;
                    $after_pension_premiums = 8052;
    }elseif($after_salary_in_kind >= 83000 && $after_salary_in_kind < 93000){
      $after_health_insurance_expense_nomal = 4356;
      $after_health_insurance_expense_kaigo = 5046;
                    $after_pension_premiums = 8052;
    }elseif($after_salary_in_kind >= 93000 && $after_salary_in_kind < 101000){
      $after_health_insurance_expense_nomal = 4851;
      $after_health_insurance_expense_kaigo = 5620;
                    $after_pension_premiums = 8967;
    }elseif($after_salary_in_kind >= 101000 && $after_salary_in_kind < 107000){
      $after_health_insurance_expense_nomal = 5148;
      $after_health_insurance_expense_kaigo = 5964;
                    $after_pension_premiums = 9516;
    }elseif($after_salary_in_kind >= 107000 && $after_salary_in_kind < 114000){
      $after_health_insurance_expense_nomal = 5445;
      $after_health_insurance_expense_kaigo = 6308;
                    $after_pension_premiums = 10065;
    }elseif($after_salary_in_kind >= 114000 && $after_salary_in_kind < 122000){
      $after_health_insurance_expense_nomal = 5841;
      $after_health_insurance_expense_kaigo = 6767;
                    $after_pension_premiums = 10797;
    }elseif($after_salary_in_kind >= 122000 && $after_salary_in_kind < 130000){
      $after_health_insurance_expense_nomal = 6237;
      $after_health_insurance_expense_kaigo = 7226;
                    $after_pension_premiums = 11529;
    }elseif($after_salary_in_kind >= 130000 && $after_salary_in_kind < 138000){
      $after_health_insurance_expense_nomal = 6633;
      $after_health_insurance_expense_kaigo = 7684;
                    $after_pension_premiums = 12261;
    }elseif($after_salary_in_kind >= 138000 && $after_salary_in_kind < 146000){
      $after_health_insurance_expense_nomal = 7029;
      $after_health_insurance_expense_kaigo = 8143;
                    $after_pension_premiums = 12993;
    }elseif($after_salary_in_kind >= 146000 && $after_salary_in_kind < 155000){
      $after_health_insurance_expense_nomal = 7425;
      $after_health_insurance_expense_kaigo = 8602;
                    $after_pension_premiums = 13725;
    }elseif($after_salary_in_kind >= 155000 && $after_salary_in_kind < 165000){
      $after_health_insurance_expense_nomal = 7920;
      $after_health_insurance_expense_kaigo = 9176;
                    $after_pension_premiums = 14640;
    }elseif($after_salary_in_kind >= 165000 && $after_salary_in_kind < 175000){
      $after_health_insurance_expense_nomal = 8415;
      $after_health_insurance_expense_kaigo = 9749;
                    $after_pension_premiums = 15555;
    }elseif($after_salary_in_kind >= 175000 && $after_salary_in_kind < 185000){
      $after_health_insurance_expense_nomal = 8910;
      $after_health_insurance_expense_kaigo = 10323;
                    $after_pension_premiums = 16470;
    }elseif($after_salary_in_kind >= 185000 && $after_salary_in_kind < 195000){
      $after_health_insurance_expense_nomal = 9405;
      $after_health_insurance_expense_kaigo = 10896;
                    $after_pension_premiums = 17385;
    }elseif($after_salary_in_kind >= 195000 && $after_salary_in_kind < 210000){
      $after_health_insurance_expense_nomal = 9900;
      $after_health_insurance_expense_kaigo = 11470;
                    $after_pension_premiums = 18300;
    }elseif($after_salary_in_kind >= 210000 && $after_salary_in_kind < 230000){
      $after_health_insurance_expense_nomal = 10890;
      $after_health_insurance_expense_kaigo = 12617;
                    $after_pension_premiums = 20130;
    }elseif($after_salary_in_kind >= 230000 && $after_salary_in_kind < 250000){
      $after_health_insurance_expense_nomal = 11880;
      $after_health_insurance_expense_kaigo = 13764;
                    $after_pension_premiums = 21960;
    }elseif($after_salary_in_kind >= 250000 && $after_salary_in_kind < 270000){
      $after_health_insurance_expense_nomal = 12870;
      $after_health_insurance_expense_kaigo = 14911;
                    $after_pension_premiums = 23790;
    }elseif($after_salary_in_kind >= 270000 && $after_salary_in_kind < 290000){
      $after_health_insurance_expense_nomal = 13860;
      $after_health_insurance_expense_kaigo = 16058;
                    $after_pension_premiums = 25620;
    }elseif($after_salary_in_kind >= 290000 && $after_salary_in_kind < 310000){
      $after_health_insurance_expense_nomal = 14850;
      $after_health_insurance_expense_kaigo = 17205;
                    $after_pension_premiums = 27450;
    }elseif($after_salary_in_kind >= 310000 && $after_salary_in_kind < 330000){
      $after_health_insurance_expense_nomal = 15840;
      $after_health_insurance_expense_kaigo = 18352;
                    $after_pension_premiums = 29280;
    }elseif($after_salary_in_kind >= 330000 && $after_salary_in_kind < 350000){
      $after_health_insurance_expense_nomal = 16830;
      $after_health_insurance_expense_kaigo = 19499;
                    $after_pension_premiums = 31110;
    }elseif($after_salary_in_kind >= 350000 && $after_salary_in_kind < 370000){
      $after_health_insurance_expense_nomal = 17820;
      $after_health_insurance_expense_kaigo = 20646;
                    $after_pension_premiums = 32940;
    }elseif($after_salary_in_kind >= 370000 && $after_salary_in_kind < 395000){
      $after_health_insurance_expense_nomal = 18810;
      $after_health_insurance_expense_kaigo = 21793;
                    $after_pension_premiums = 34770;
    }elseif($after_salary_in_kind >= 395000 && $after_salary_in_kind < 425000){
      $after_health_insurance_expense_nomal = 20295;
      $after_health_insurance_expense_kaigo = 23513;
                    $after_pension_premiums = 37515;
    }elseif($after_salary_in_kind >= 425000 && $after_salary_in_kind < 455000){
      $after_health_insurance_expense_nomal = 21780;
      $after_health_insurance_expense_kaigo = 25234;
                    $after_pension_premiums = 40260;
    }elseif($after_salary_in_kind >= 455000 && $after_salary_in_kind < 485000){
      $after_health_insurance_expense_nomal = 23265;
      $after_health_insurance_expense_kaigo = 26954;
                    $after_pension_premiums = 43005;
    }elseif($after_salary_in_kind >= 485000 && $after_salary_in_kind < 515000){
      $after_health_insurance_expense_nomal = 24750;
      $after_health_insurance_expense_kaigo = 28675;
                    $after_pension_premiums = 45750;
    }elseif($after_salary_in_kind >= 515000 && $after_salary_in_kind < 545000){
      $after_health_insurance_expense_nomal = 26235;
      $after_health_insurance_expense_kaigo = 30395;
                    $after_pension_premiums = 48495;
    }elseif($after_salary_in_kind >= 545000 && $after_salary_in_kind < 575000){
      $after_health_insurance_expense_nomal = 27720;
      $after_health_insurance_expense_kaigo = 32116;
                    $after_pension_premiums = 51240;
    }elseif($after_salary_in_kind >= 575000 && $after_salary_in_kind < 605000){
      $after_health_insurance_expense_nomal = 29205;
      $after_health_insurance_expense_kaigo = 33836;
                    $after_pension_premiums = 53985;
    }elseif($after_salary_in_kind >= 605000 && $after_salary_in_kind < 635000){
      $after_health_insurance_expense_nomal = 30690;
      $after_health_insurance_expense_kaigo = 35557;
                    $after_pension_premiums = 56730;
    }elseif($after_salary_in_kind >= 635000 && $after_salary_in_kind < 665000){
      $after_health_insurance_expense_nomal = 32175;
      $after_health_insurance_expense_kaigo = 37277;
                    $after_pension_premiums = 56730;
    }elseif($after_salary_in_kind >= 665000 && $after_salary_in_kind < 695000){
      $after_health_insurance_expense_nomal = 33660;
      $after_health_insurance_expense_kaigo = 38998;
                    $after_pension_premiums = 56730;
    }elseif($after_salary_in_kind >= 695000 && $after_salary_in_kind < 730000){
      $after_health_insurance_expense_nomal = 35145;
      $after_health_insurance_expense_kaigo = 40718;
                    $after_pension_premiums = 56730;
    }elseif($after_salary_in_kind >= 730000 && $after_salary_in_kind < 770000){
      $after_health_insurance_expense_nomal = 37125;
      $after_health_insurance_expense_kaigo = 43012;
                    $after_pension_premiums = 56730;
    }elseif($after_salary_in_kind >= 770000 && $after_salary_in_kind < 810000){
      $after_health_insurance_expense_nomal = 39105;
      $after_health_insurance_expense_kaigo = 45306;
                    $after_pension_premiums = 56730;
    }elseif($after_salary_in_kind >= 810000 && $after_salary_in_kind < 855000){
      $after_health_insurance_expense_nomal = 41085;
      $after_health_insurance_expense_kaigo = 47600;
                    $after_pension_premiums = 56730;
    }elseif($after_salary_in_kind >= 855000 && $after_salary_in_kind < 905000){
      $after_health_insurance_expense_nomal = 43560;
      $after_health_insurance_expense_kaigo = 50468;
                    $after_pension_premiums = 56730;
    }elseif($after_salary_in_kind >= 905000 && $after_salary_in_kind < 955000){
      $after_health_insurance_expense_nomal = 46035;
      $after_health_insurance_expense_kaigo = 53335;
                    $after_pension_premiums = 56730;
    }elseif($after_salary_in_kind >= 955000 && $after_salary_in_kind < 1005000){
      $after_health_insurance_expense_nomal = 48510;
      $after_health_insurance_expense_kaigo = 56203;
                    $after_pension_premiums = 56730;
    }elseif($after_salary_in_kind >= 1005000 && $after_salary_in_kind < 1055000){
      $after_health_insurance_expense_nomal = 50985;
      $after_health_insurance_expense_kaigo = 59070;
                    $after_pension_premiums = 56730;
    }elseif($after_salary_in_kind >= 1055000 && $after_salary_in_kind < 1115000){
      $after_health_insurance_expense_nomal = 53955;
      $after_health_insurance_expense_kaigo = 62511;
                    $after_pension_premiums = 56730;
    }elseif($after_salary_in_kind >= 1115000 && $after_salary_in_kind < 1175000){
      $after_health_insurance_expense_nomal = 56925;
      $after_health_insurance_expense_kaigo = 65952;
                    $after_pension_premiums = 56730;
    }elseif($after_salary_in_kind >= 1115000 && $after_salary_in_kind < 1235000){
      $after_health_insurance_expense_nomal = 59895;
      $after_health_insurance_expense_kaigo = 69393;
                    $after_pension_premiums = 56730;
    }elseif($after_salary_in_kind >= 1235000 && $after_salary_in_kind < 1295000){
      $after_health_insurance_expense_nomal = 62865;
      $after_health_insurance_expense_kaigo = 72834;
                    $after_pension_premiums = 56730;
    }elseif($after_salary_in_kind >= 1295000 && $after_salary_in_kind < 1355000){
      $after_health_insurance_expense_nomal = 65835;
      $after_health_insurance_expense_kaigo = 76275;
                    $after_pension_premiums = 56730;
    }else{
      $after_health_insurance_expense_nomal = 68805;
      $after_health_insurance_expense_kaigo = 79716;
                    $after_pension_premiums = 56730;
    }
// 導入後：健康保険料の計算 終了-----------------------------------------

// 導入後：40歳未満/以上 介護保険加算の仕分け
  if($ages < 40){
    $after_health_insurance_expense = $after_health_insurance_expense_nomal; // 導入後：40歳未満 健康保険料
  }else{
    $after_health_insurance_expense = $after_health_insurance_expense_kaigo; // 導入後：40歳以上 健康保険料（介護保険料加算）
  }


// 導入後：社保控除後の金額
  $after_pretax_salary = $after_salary - $after_health_insurance_expense - $after_pension_premiums;

// 導入後：社保控除後の金額に応じた源泉徴収額の計算 開始----------------------
  if($after_pretax_salary >= 58000 && $after_pretax_salary < 400000){
    $after_dependant0 = 5000;
    $after_dependant1 = 4000;
    $after_dependant2 = 3000;
    $after_dependant3 = 2000;
    $after_dependant4 = 1000;
    $after_dependant5 = 500;
    $after_dependant6 = 300;
    $after_dependant7 = 100;

  }elseif($after_pretax_salary >= 400000 && $after_pretax_salary < 800000){
    $after_dependant0 = 25000;
    $after_dependant1 = 24000;
    $after_dependant2 = 23000;
    $after_dependant3 = 22000;
    $after_dependant4 = 21000;
    $after_dependant5 = 20000;
    $after_dependant6 = 19000;
    $after_dependant7 = 18000;
  }else{
    $after_dependant0 = 45000;
    $after_dependant1 = 44000;
    $after_dependant2 = 43000;
    $after_dependant3 = 42000;
    $after_dependant4 = 41000;
    $after_dependant5 = 40000;
    $after_dependant6 = 39000;
    $after_dependant7 = 38000;
  }
//導入後：社保控除後の金額に応じた源泉徴収額の計算 終了----------------------


//導入後：扶養家族に応じた源泉徴収額の計算 開始----------------------
if($dependants == 0 ){
  $after_income_tax =  $after_dependant0;
}elseif($dependants == 1 ){
  $after_income_tax =  $after_dependant1;
}elseif($dependants == 2 ){
  $after_income_tax =  $after_dependant2;
}elseif($dependants == 3 ){
  $after_income_tax =  $after_dependant3;
}elseif($dependants == 4 ){
  $after_income_tax =  $after_dependant4;
}elseif($dependants == 5 ){
  $after_income_tax =  $after_dependant5;
}elseif($dependants == 6 ){
  $after_income_tax =  $after_dependant6;
}else{
  $after_income_tax =  $after_dependant7;
}
//導入後：扶養家族に応じた源泉徴収額の計算 終了----------------------


// 導入後：住民税の計算 開始-----------------------------------------
  $after_inhabitant_tax = 8000;

// 導入後：社保、税金、家賃控除後の可処分所得の計算
  $after_disposable_income = $after_salary - $after_health_insurance_expense - $after_pension_premiums - $after_income_tax - $after_inhabitant_tax - $rest_payment;

// 導入前、導入後の可処分所得の増加分の計算
  $effect = $after_disposable_income - $before_disposable_income;


// LINE上に表示する数値を配列に代入
  // 基本情報、事前計算関連
  $calculation[] = $ages;                                   // [0] 年齢
  $calculation[] = $partner;                                // [1] 配偶者
  $calculation[] = $dependants;                             // [2] 扶養家族

  $calculation[] = $houserent;                              // [3] 家賃
  $calculation[] = $location;                               // [4] 勤務地の都道府県
  $calculation[] = $housebenefit;                           // [5] 都道府県毎の住宅利益
  $calculation[] = $space;                           // [6] 広さ
  $calculation[] = $in_kind_as_house;                       // [7] 現物支給額

  $calculation[] = $before_slary;                           // [8] 導入前：月額給与
  $calculation[] = $before_bonus;                           // [9] 導入前：年間賞与
  $calculation[] = $before_yearly_income;                   // [10] 導入前：年収
  $calculation[] = $before_health_insurance_expense;        // [11] 導入前：健康保険料
  $calculation[] = $before_pension_premiums;                // [12] 導入前：厚生年金保険料
  $calculation[] = $before_income_tax;                      // [13] 導入前：所得税
  $calculation[] = $before_inhabitant_tax;                  // [14] 導入前：住民税
  $calculation[] = $before_disposable_income;               // [15] 導入前：社保、税金、家賃控除後の可処分所得


  $calculation[] = $payment_reduce;                         // [16] 導入後：会社負担家賃（家賃×0.8）
  $calculation[] = $rest_payment;                           // [17] 導入後：本人負担家賃（家賃×0.2）
  $calculation[] = $after_salary;                           // [18] 導入後：月額給与
  $calculation[] = $after_yearly_income;                    // [19] 導入後：年収
  $calculation[] = $after_health_insurance_expense;         // [20] 導入後：健康保険料
  $calculation[] = $after_pension_premiums;                 // [21] 導入後：厚生年金保険料
  $calculation[] = $after_income_tax;                       // [22] 導入後：所得税
  $calculation[] = $after_inhabitant_tax;                   // [23] 導入後：住民税
  $calculation[] = $after_disposable_income;                // [24] 導入後：社保、税金、家賃控除後の可処分所得

  $calculation[] = $effect;                                 // [25] スマートサラリー導入効果



  $message1 = "シミュレーション結果\n\n基本情報\n年齢：$calculation[0]歳\n配偶者：$calculation[1]\n扶養家族：$calculation[2]人\n\n家賃：$calculation[3]円\n勤務地の都道府県：$calculation[4]円\n都道府県毎の住宅利益：$calculation[5]円/1畳\n広さ：$calculation[6]畳\n現物支給額換算：$calculation[7]円";

  $message2 = "導入前\n月額給与：$calculation[8]円\n年間賞与：$calculation[9]円\n年収：$calculation[10]円\n
健康保険料：$calculation[11]円\n厚生年金保険料：$calculation[12]円\n所得税：$calculation[13]円\n住民税：$calculation[14]円\n社保、税金、家賃控除後の可処分所得：$calculation[15]円";

  $message3 = "導入後\n\n会社負担家賃（家賃×0.8）：$calculation[16]円\n本人負担家賃（家賃×0.2）：$calculation[17]円\n月額給与：$calculation[18]円\n年間賞与：$calculation[9]円\n年収：$calculation[19] 円\n健康保険料：$calculation[20]円\n厚生年金保険料：$calculation[21]円\n所得税：$calculation[22]円\n住民税：$calculation[23]円\n社保、税金、家賃控除後の可処分所得：$calculation[24]円\n\nスマートサラリー導入効果：$calculation[25]円\n";

  // メッセージをユーザーに返信
$bot->replyText($event->getReplyToken(), $message1, $message2, $message3);

/*
  $response = $bot->replyMessage($event->getReplyToken(), new \LINE\LINEBot\MessageBuilder\
                                    TextMessageBuilder($message1));

  if (!$response->isSucceeded()){
    error_log('Failed!'. $response->getHTTPStatus .' ' .
                            $response->getRawBody());
  }
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
*/

}


?>
