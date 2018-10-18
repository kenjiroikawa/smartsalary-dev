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
  }elseif( substr_count($parameters, '、') > 7){
    $error = "9項目以上が入力されています。\n案内に沿って、8項目を入力してください。";
    $bot->replyText($event->getReplyToken(), $error);
    exit;
  }elseif(!preg_match("/[^一-龠]/u",$location) == 0 ){
    $error = "都道府県の入力に誤りがあります。勤務先の都道府県を漢字で入力してください。\n\n（例）東京都";
    $bot->replyText($event->getReplyToken(), $error);
    exit;
  }elseif(!preg_match("/^[0-9]+$/",$space) == 1 ){
    $error = "住宅広さの入力に誤りがあります。お住まいの住宅の広さ（畳）を半角数値で入力してください。\n\n（例）2LDK リビング10畳、洋室6畳、和室6畳の場合\n→「22」と入力してください。";
    $bot->replyText($event->getReplyToken(), $error);
    exit;
  }else{

  }

  // 基本情報をベースに各種控除を算出
  $basic_deduction = 330000;                                   // 基礎控除（住民税）

  if($partner == 'あり'){                                      //配偶者控除（住民税）
    $partner_deduction = 330000;
  }else{
    $partner_deduction = 0;
  }

  $dependant_deduction = $dependants * 330000;                 //扶養控除（住民税）


  // 事前計算
  $before_yearly_income = $before_slary * 12 + $before_bonus;  // 導入前：年収
  $payment_reduce = $houserent * 0.8;                          // 導入後：会社支払家賃
  $rest_payment = $houserent * 0.2;                            // 導入後：本人支払家賃
  $after_salary = $parameter[2] - $payment_reduce;             // 導入後：給与
  $after_yearly_income = $after_salary * 12 + $before_bonus;   // 導入後：年収


// 都道府県による住宅利益の分類 開始---------------------------------------
  if($location == '東京都' or $location == '東京'){
  $housebenefit = 2590;
}elseif($location == '神奈川県' or $location == '神奈川'){
  $housebenefit = 2070;
}elseif($location == '千葉県' or $location == '千葉'){
  $housebenefit = 1700;
}elseif($location == '埼玉県' or $location == '埼玉'){
  $housebenefit = 1750;
}elseif($location == '茨城県' or $location == '茨城'){
  $housebenefit = 1270;
}elseif($location == '群馬県' or $location == '群馬'){
  $housebenefit = 1170;
}elseif($location == '栃木県' or $location == '栃木'){
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
    $before_social_insurance = [2871,3326,8052];
  }elseif($before_slary >= 63000 && $before_slary < 73000){
    $before_social_insurance = [3366,3899,8052];
  }elseif($before_slary >= 73000 && $before_slary < 83000){
    $before_social_insurance = [3861,4473,8052];
  }elseif($before_slary >= 83000 && $before_slary < 93000){
    $before_social_insurance = [4356,5046,8052];
  }elseif($before_slary >= 93000 && $before_slary < 101000){
    $before_social_insurance = [4851,5620,8967];
  }elseif($before_slary >= 101000 && $before_slary < 107000){
    $before_social_insurance = [5148,5964,9516];
  }elseif($before_slary >= 107000 && $before_slary < 114000){
    $before_social_insurance = [5445,6308,10065];
  }elseif($before_slary >= 114000 && $before_slary < 122000){
    $before_social_insurance = [5841,6767,10797];
  }elseif($before_slary >= 122000 && $before_slary < 130000){
    $before_social_insurance = [6237,7226,11529];
  }elseif($before_slary >= 130000 && $before_slary < 138000){
    $before_social_insurance = [6633,7684,12261];
  }elseif($before_slary >= 138000 && $before_slary < 146000){
    $before_social_insurance = [7029,8143,12993];
  }elseif($before_slary >= 146000 && $before_slary < 155000){
    $before_social_insurance = [7425,8602,13725];
  }elseif($before_slary >= 155000 && $before_slary < 165000){
    $before_social_insurance = [7920,9176,14640];
  }elseif($before_slary >= 165000 && $before_slary < 175000){
    $before_social_insurance = [8415,9749,15555];
  }elseif($before_slary >= 175000 && $before_slary < 185000){
    $before_social_insurance = [8910,10323,16470];
  }elseif($before_slary >= 185000 && $before_slary < 195000){
    $before_social_insurance = [9405,10896,17385];
  }elseif($before_slary >= 195000 && $before_slary < 210000){
    $before_social_insurance = [9900,11470,18300];
  }elseif($before_slary >= 210000 && $before_slary < 230000){
    $before_social_insurance = [10890,12617,20130];
  }elseif($before_slary >= 230000 && $before_slary < 250000){
    $before_social_insurance = [11880,13764,21960];
  }elseif($before_slary >= 250000 && $before_slary < 270000){
    $before_social_insurance = [12870,14911,23790];
  }elseif($before_slary >= 270000 && $before_slary < 290000){
    $before_social_insurance = [13860,16058,25620];
  }elseif($before_slary >= 290000 && $before_slary < 310000){
    $before_social_insurance = [14850,17205,27450];
  }elseif($before_slary >= 310000 && $before_slary < 330000){
    $before_social_insurance = [15840,18352,29280];
  }elseif($before_slary >= 330000 && $before_slary < 350000){
    $before_social_insurance = [16830,19499,31110];
  }elseif($before_slary >= 350000 && $before_slary < 370000){
    $before_social_insurance = [17820,20646,32940];
  }elseif($before_slary >= 370000 && $before_slary < 395000){
    $before_social_insurance = [18810,21793,34770];
  }elseif($before_slary >= 395000 && $before_slary < 425000){
    $before_social_insurance = [20295,23513,37515];
  }elseif($before_slary >= 425000 && $before_slary < 455000){
    $before_social_insurance = [21780,25324,40260];
  }elseif($before_slary >= 455000 && $before_slary < 485000){
    $before_social_insurance = [23265,26954,43005];
  }elseif($before_slary >= 485000 && $before_slary < 515000){
    $before_social_insurance = [24750,28675,45750];
  }elseif($before_slary >= 515000 && $before_slary < 545000){
    $before_social_insurance = [26235,30395,48495];
  }elseif($before_slary >= 545000 && $before_slary < 575000){
    $before_social_insurance = [27720,32116,51240];
  }elseif($before_slary >= 575000 && $before_slary < 605000){
    $before_social_insurance = [29205,33836,53985];
  }elseif($before_slary >= 605000 && $before_slary < 635000){
    $before_social_insurance = [30690,35557,56730];
  }elseif($before_slary >= 635000 && $before_slary < 665000){
    $before_social_insurance = [32175,37277,56730];
  }elseif($before_slary >= 665000 && $before_slary < 695000){
    $before_social_insurance = [33660,38998,56730];
  }elseif($before_slary >= 695000 && $before_slary < 730000){
    $before_social_insurance = [35145,40718,56730];
  }elseif($before_slary >= 730000 && $before_slary < 770000){
    $before_social_insurance = [37125,43012,56730];
  }elseif($before_slary >= 770000 && $before_slary < 810000){
    $before_social_insurance = [39105,45306,56730];
  }elseif($before_slary >= 810000 && $before_slary < 855000){
    $before_social_insurance = [41085,47600,56730];
  }elseif($before_slary >= 855000 && $before_slary < 905000){
    $before_social_insurance = [43560,50468,56730];
  }elseif($before_slary >= 905000 && $before_slary < 955000){
    $before_social_insurance = [46035,53335,56730];
  }elseif($before_slary >= 955000 && $before_slary < 1005000){
    $before_social_insurance = [48510,56203,56730];
  }elseif($before_slary >= 1005000 && $before_slary < 1055000){
    $before_social_insurance = [50985,59070,56730];
  }elseif($before_slary >= 1055000 && $before_slary < 1115000){
    $before_social_insurance = [53955,62511,56730];
  }elseif($before_slary >= 1115000 && $before_slary < 1175000){
    $before_social_insurance = [56925,65952,56730];
  }elseif($before_slary >= 1115000 && $before_slary < 1235000){
    $before_social_insurance = [59895,69393,56730];
  }elseif($before_slary >= 1235000 && $before_slary < 1295000){
    $before_social_insurance = [62865,72834,56730];
  }elseif($before_slary >= 1295000 && $before_slary < 1355000){
    $before_social_insurance = [65835,76275,56730];
  }else{
    $before_social_insurance = [68805,79716,56730];
  }
// 導入前：給与に応じた社会保険料の計算 終了-------------------------------


// 導入前：健康保険料の計算　※40歳未満/以上 介護保険加算の有無で場合分け
  if($ages < 40){
    $before_health_insurance_expense = $before_social_insurance[0]; // 導入前：40歳未満　健康保険料
  }else{
    $before_health_insurance_expense = $before_social_insurance[1]; // 導入前：40歳以上　健康保険料（介護保険料加算）
  }

// 導入前：厚生年金保険料の計算
  $before_pension_premiums = $before_social_insurance[2];

// 導入前：年間の社会保険料の合計
  $before_social_insurance_total = ($before_health_insurance_expense + $before_pension_premiums) * 12;

// 導入前：社保控除後の金額
  $before_pretax_salary = $before_slary - $before_health_insurance_expense - $before_pension_premiums;

// 導入前：社保控除後の金額に応じた源泉徴収額の計算 開始----------------------
  if($before_pretax_salary >= 0 && $before_pretax_salary < 88000){
    $before_dependant = [0,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 88000 && $before_pretax_salary < 89000){
    $before_dependant = [130,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 89000 && $before_pretax_salary < 90000){
    $before_dependant = [180,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 90000 && $before_pretax_salary < 91000){
    $before_dependant = [230,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 91000 && $before_pretax_salary < 92000){
    $before_dependant = [290,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 92000 && $before_pretax_salary < 93000){
    $before_dependant = [340,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 93000 && $before_pretax_salary < 94000){
    $before_dependant = [390,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 94000 && $before_pretax_salary < 95000){
    $before_dependant = [440,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 95000 && $before_pretax_salary < 96000){
    $before_dependant = [490,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 96000 && $before_pretax_salary < 97000){
    $before_dependant = [540,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 97000 && $before_pretax_salary < 98000){
    $before_dependant = [590,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 98000 && $before_pretax_salary < 99000){
    $before_dependant = [640,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 99000 && $before_pretax_salary < 101000){
    $before_dependant = [720,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 101000 && $before_pretax_salary < 103000){
    $before_dependant = [830,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 103000 && $before_pretax_salary < 105000){
    $before_dependant = [930,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 105000 && $before_pretax_salary < 107000){
    $before_dependant = [1030,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 107000 && $before_pretax_salary < 109000){
    $before_dependant = [1130,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 109000 && $before_pretax_salary < 111000){
    $before_dependant = [1240,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 111000 && $before_pretax_salary < 113000){
    $before_dependant = [1340,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 113000 && $before_pretax_salary < 115000){
    $before_dependant = [1440,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 115000 && $before_pretax_salary < 117000){
    $before_dependant = [1540,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 117000 && $before_pretax_salary < 119000){
    $before_dependant = [1640,0,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 119000 && $before_pretax_salary < 121000){
    $before_dependant = [1750,120,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 121000 && $before_pretax_salary < 123000){
    $before_dependant = [1850,220,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 123000 && $before_pretax_salary < 125000){
    $before_dependant = [1950,330,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 125000 && $before_pretax_salary < 127000){
    $before_dependant = [2050,430,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 127000 && $before_pretax_salary < 129000){
    $before_dependant = [2150,530,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 129000 && $before_pretax_salary < 131000){
    $before_dependant = [2260,630,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 131000 && $before_pretax_salary < 133000){
    $before_dependant = [2360,740,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 133000 && $before_pretax_salary < 135000){
    $before_dependant = [2460,840,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 135000 && $before_pretax_salary < 137000){
    $before_dependant = [2550,930,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 137000 && $before_pretax_salary < 139000){
    $before_dependant = [2610,990,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 139000 && $before_pretax_salary < 141000){
    $before_dependant = [2680,1050,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 141000 && $before_pretax_salary < 143000){
    $before_dependant = [2740,1110,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 143000 && $before_pretax_salary < 145000){
    $before_dependant = [2800,1170,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 145000 && $before_pretax_salary < 147000){
    $before_dependant = [2860,1240,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 147000 && $before_pretax_salary < 149000){
    $before_dependant = [2920,1300,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 149000 && $before_pretax_salary < 151000){
    $before_dependant = [2980,1360,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 151000 && $before_pretax_salary < 153000){
    $before_dependant = [3050,1430,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 153000 && $before_pretax_salary < 155000){
    $before_dependant = [3120,1500,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 155000 && $before_pretax_salary < 157000){
    $before_dependant = [3200,1570,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 157000 && $before_pretax_salary < 159000){
    $before_dependant = [3270,1640,0,0,0,0,0,0];
  }elseif($before_pretax_salary >= 159000 && $before_pretax_salary < 161000){
    $before_dependant = [3340,1720,100,0,0,0,0,0];
  }elseif($before_pretax_salary >= 161000 && $before_pretax_salary < 163000){
    $before_dependant = [3410,1790,170,0,0,0,0,0];
  }elseif($before_pretax_salary >= 163000 && $before_pretax_salary < 165000){
    $before_dependant = [3480,1860,250,0,0,0,0,0];
  }elseif($before_pretax_salary >= 165000 && $before_pretax_salary < 167000){
    $before_dependant = [3550,1930,320,0,0,0,0,0];
  }elseif($before_pretax_salary >= 167000 && $before_pretax_salary < 500000){ //途中休憩：ここから再開
    $before_dependant = [6550,5930,5320,5000,4500,4000,3500,3000];
  }elseif($before_pretax_salary >= 500000 && $before_pretax_salary < 1000000){
    $before_dependant = [16550,15930,15320,15000,14500,14000,13500,13000];
  }else{
    $before_dependant = [26550,25930,25320,25000,24500,24000,23500,23000];
/*  }elseif($before_pretax_salary >=  && $before_pretax_salary < ){
    $before_dependant = [,,,,,,,];
  }elseif($before_pretax_salary >=  && $before_pretax_salary < ){
    $before_dependant = [,,,,,,,];*/
  }
//導入前：社保控除後の金額に応じた源泉徴収額の計算 終了----------------------


//導入前：扶養家族に応じた源泉徴収額の計算 開始----------------------
if($dependants == 0 ){
  $before_income_tax =  $before_dependant[0];
}elseif($dependants == 1 ){
  $before_income_tax =  $before_dependant[1];
}elseif($dependants == 2 ){
  $before_income_tax =  $before_dependant[2];
}elseif($dependants == 3 ){
  $before_income_tax =  $before_dependant[3];
}elseif($dependants == 4 ){
  $before_income_tax =  $before_dependant[4];
}elseif($dependants == 5 ){
  $before_income_tax =  $before_dependant[5];
}elseif($dependants == 6 ){
  $before_income_tax =  $before_dependant[6];
}else{
  $before_income_tax =  $before_dependant[7];
}
//導入前：扶養家族に応じた源泉徴収額の計算 終了----------------------


// 導入前：住民税の計算 開始-----------------------------------------

  // 給与所得控除の計算
  if($before_salary < 1625000){
    $before_salary_deduction = 650000;
  }elseif($before_salary >= 1625000 && $before_salary <= 1800000){
    $before_salary_deduction = $before_salary * 0.4;
  }elseif($before_salary > 1800000 && $before_salary <= 3600000){
    $before_salary_deduction = $before_salary * 0.3 + 180000;
  }elseif($before_salary > 3600000 && $before_salary <= 6600000){
    $before_salary_deduction = $before_salary * 0.2 + 540000;
  }elseif($before_salary > 6600000 && $before_salary <= 10000000){
    $before_salary_deduction = $before_salary * 0.1 + 1200000;
  }elseif($before_salary > 1625000 && $before_salary <= 1800000){
    $before_salary_deduction = $before_salary * 0.4;
  }else{
    $before_salary_deduction = 2200000;
  }


  //所得控除
  $before_income_deduction = $basic_deduction + $partner_deduction + $dependant_deduction + $before_social_insurance_total;

  // 住民税計算用の課税対象金額の計算
  $before_inhabitant_tax_yearly = $before_yearly_income - $before_salary_deduction - $before_income_deduction ;

  // 月額住民税の計算
  $before_inhabitant_tax = floor($before_inhabitant_tax_yearly / 12);
// 導入前：住民税の計算 終了-----------------------------------------

// 導入前：社保、税金、家賃控除後の可処分所得の計算
$before_disposable_income = $before_salary - $before_health_insurance_expense - $before_pension_premiums - $before_income_tax - $before_inhabitant_tax - $houserent;


// 導入後：給与に応じた社会保険料の計算 開始-------------------------------
  if($after_salary_in_kind >= 58000 && $after_salary_in_kind < 63000){
    $after_social_insurance = [2871,3326,8052];
  }elseif($after_salary_in_kind >= 63000 && $after_salary_in_kind < 73000){
    $after_social_insurance = [3366,3899,8052];
  }elseif($after_salary_in_kind >= 73000 && $after_salary_in_kind < 83000){
    $after_social_insurance = [3861,4473,8052];
  }elseif($after_salary_in_kind >= 83000 && $after_salary_in_kind < 93000){
    $after_social_insurance = [4356,5046,8052];
  }elseif($after_salary_in_kind >= 93000 && $after_salary_in_kind < 101000){
    $after_social_insurance = [4851,5620,8967];
  }elseif($after_salary_in_kind >= 101000 && $after_salary_in_kind < 107000){
    $after_social_insurance = [5148,5964,9516];
  }elseif($after_salary_in_kind >= 107000 && $after_salary_in_kind < 114000){
    $after_social_insurance = [5445,6308,10065];
  }elseif($after_salary_in_kind >= 114000 && $after_salary_in_kind < 122000){
    $after_social_insurance = [5841,6767,10797];
  }elseif($after_salary_in_kind >= 122000 && $after_salary_in_kind < 130000){
    $after_social_insurance = [6237,7226,11529];
  }elseif($after_salary_in_kind >= 130000 && $after_salary_in_kind < 138000){
    $after_social_insurance = [6633,7684,12261];
  }elseif($after_salary_in_kind >= 138000 && $after_salary_in_kind < 146000){
    $after_social_insurance = [7029,8143,12993];
  }elseif($after_salary_in_kind >= 146000 && $after_salary_in_kind < 155000){
    $after_social_insurance = [7425,8602,13725];
  }elseif($after_salary_in_kind >= 155000 && $after_salary_in_kind < 165000){
    $after_social_insurance = [7920,9176,14640];
  }elseif($after_salary_in_kind >= 165000 && $after_salary_in_kind < 175000){
    $after_social_insurance = [8415,9749,15555];
  }elseif($after_salary_in_kind >= 175000 && $after_salary_in_kind < 185000){
    $after_social_insurance = [8910,10323,16470];
  }elseif($after_salary_in_kind >= 185000 && $after_salary_in_kind < 195000){
    $after_social_insurance = [9405,10896,17385];
  }elseif($after_salary_in_kind >= 195000 && $after_salary_in_kind < 210000){
    $after_social_insurance = [9900,11470,18300];
  }elseif($after_salary_in_kind >= 210000 && $after_salary_in_kind < 230000){
    $after_social_insurance = [10890,12617,20130];
  }elseif($after_salary_in_kind >= 230000 && $after_salary_in_kind < 250000){
    $after_social_insurance = [11880,13764,21960];
  }elseif($after_salary_in_kind >= 250000 && $after_salary_in_kind < 270000){
    $after_social_insurance = [12870,14911,23790];
  }elseif($after_salary_in_kind >= 270000 && $after_salary_in_kind < 290000){
    $after_social_insurance = [13860,16058,25620];
  }elseif($after_salary_in_kind >= 290000 && $after_salary_in_kind < 310000){
    $after_social_insurance = [14850,17205,27450];
  }elseif($after_salary_in_kind >= 310000 && $after_salary_in_kind < 330000){
    $after_social_insurance = [15840,18352,29280];
  }elseif($after_salary_in_kind >= 330000 && $after_salary_in_kind < 350000){
    $after_social_insurance = [16830,19499,31110];
  }elseif($after_salary_in_kind >= 350000 && $after_salary_in_kind < 370000){
    $after_social_insurance = [17820,20646,32940];
  }elseif($after_salary_in_kind >= 370000 && $after_salary_in_kind < 395000){
    $after_social_insurance = [18810,21793,34770];
  }elseif($after_salary_in_kind >= 395000 && $after_salary_in_kind < 425000){
    $after_social_insurance = [20295,23513,37515];
  }elseif($after_salary_in_kind >= 425000 && $after_salary_in_kind < 455000){
    $after_social_insurance = [21780,25324,40260];
  }elseif($after_salary_in_kind >= 455000 && $after_salary_in_kind < 485000){
    $after_social_insurance = [23265,26954,43005];
  }elseif($after_salary_in_kind >= 485000 && $after_salary_in_kind < 515000){
    $after_social_insurance = [24750,28675,45750];
  }elseif($after_salary_in_kind >= 515000 && $after_salary_in_kind < 545000){
    $after_social_insurance = [26235,30395,48495];
  }elseif($after_salary_in_kind >= 545000 && $after_salary_in_kind < 575000){
    $after_social_insurance = [27720,32116,51240];
  }elseif($after_salary_in_kind >= 575000 && $after_salary_in_kind < 605000){
    $after_social_insurance = [29205,33836,53985];
  }elseif($after_salary_in_kind >= 605000 && $after_salary_in_kind < 635000){
    $after_social_insurance = [30690,35557,56730];
  }elseif($after_salary_in_kind >= 635000 && $after_salary_in_kind < 665000){
    $after_social_insurance = [32175,37277,56730];
  }elseif($after_salary_in_kind >= 665000 && $after_salary_in_kind < 695000){
    $after_social_insurance = [33660,38998,56730];
  }elseif($after_salary_in_kind >= 695000 && $after_salary_in_kind < 730000){
    $after_social_insurance = [35145,40718,56730];
  }elseif($after_salary_in_kind >= 730000 && $after_salary_in_kind < 770000){
    $after_social_insurance = [37125,43012,56730];
  }elseif($after_salary_in_kind >= 770000 && $after_salary_in_kind < 810000){
    $after_social_insurance = [39105,45306,56730];
  }elseif($after_salary_in_kind >= 810000 && $after_salary_in_kind < 855000){
    $after_social_insurance = [41085,47600,56730];
  }elseif($after_salary_in_kind >= 855000 && $after_salary_in_kind < 905000){
    $after_social_insurance = [43560,50468,56730];
  }elseif($after_salary_in_kind >= 905000 && $after_salary_in_kind < 955000){
    $after_social_insurance = [46035,53335,56730];
  }elseif($after_salary_in_kind >= 955000 && $after_salary_in_kind < 1005000){
    $after_social_insurance = [48510,56203,56730];
  }elseif($after_salary_in_kind >= 1005000 && $after_salary_in_kind < 1055000){
    $after_social_insurance = [50985,59070,56730];
  }elseif($after_salary_in_kind >= 1055000 && $after_salary_in_kind < 1115000){
    $after_social_insurance = [53955,62511,56730];
  }elseif($after_salary_in_kind >= 1115000 && $after_salary_in_kind < 1175000){
    $after_social_insurance = [56925,65952,56730];
  }elseif($after_salary_in_kind >= 1115000 && $after_salary_in_kind < 1235000){
    $after_social_insurance = [59895,69393,56730];
  }elseif($after_salary_in_kind >= 1235000 && $after_salary_in_kind < 1295000){
    $after_social_insurance = [62865,72834,56730];
  }elseif($after_salary_in_kind >= 1295000 && $after_salary_in_kind < 1355000){
    $after_social_insurance = [65835,76275,56730];
  }else{
    $after_social_insurance = [68805,79716,56730];
  }
// 導入前：給与に応じた社会保険料の計算 終了-------------------------------


// 導入後：健康保険料の計算　※40歳未満/以上 介護保険加算の有無で場合分け
  if($ages < 40){
    $after_health_insurance_expense = $after_social_insurance[0]; // 導入前：40歳未満　健康保険料
  }else{
    $after_health_insurance_expense = $after_social_insurance[1]; // 導入前：40歳以上　健康保険料（介護保険料加算）
  }

// 導入後：厚生年金保険料の計算
  $after_pension_premiums = $after_social_insurance[2];

// 導入後：年間の社会保険料の合計
  $after_social_insurance_total = ($after_health_insurance_expense + $after_pension_premiums) * 12;

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

  $calculation[] = $before_salary_deduction;                // [26] 給与所得控除
  $calculation[] = $before_income_deduction;                // [27] 所得控除


  $message1 = "シミュレーション結果\n\n基本情報\n年齢：$calculation[0]歳\n配偶者：$calculation[1]\n扶養家族：$calculation[2]人\n\n家賃：$calculation[3]円\n勤務地の都道府県：$calculation[4]\n都道府県毎の住宅利益：$calculation[5]円/1畳\n広さ：$calculation[6]畳\n現物支給額換算：$calculation[7]円";

  $message2 = "導入前\n月額給与：$calculation[8]円\n年間賞与：$calculation[9]円\n年収：$calculation[10]円\n
健康保険料：$calculation[11]円\n厚生年金保険料：$calculation[12]円\n所得税：$calculation[13]円\n住民税：$calculation[14]円\n社保、税金、家賃控除後の可処分所得：$calculation[15]円";

  $message3 = "導入後\n\n会社負担家賃（家賃×0.8）：$calculation[16]円\n本人負担家賃（家賃×0.2）：$calculation[17]円\n月額給与：$calculation[18]円\n年間賞与：$calculation[9]円\n年収：$calculation[19] 円\n健康保険料：$calculation[20]円\n厚生年金保険料：$calculation[21]円\n所得税：$calculation[22]円\n住民税：$calculation[23]円\n社保、税金、家賃控除後の可処分所得：$calculation[24]円\n\nスマートサラリー導入効果：$calculation[25]円\n";

  $message4 = " 給与所得控除：$calculation[26]円\n所得控除：$calculation[27]円";
  // メッセージをユーザーに返信
$bot->replyText($event->getReplyToken(), $message1, $message2, $message3, $message4);

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
