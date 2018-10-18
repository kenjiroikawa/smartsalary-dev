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


  $parameters = $event->getText();         // 入力情報を受取
  $parameter = explode("、",$parameters);  // 入力情報を分割

  // 基本情報：各項目をそれぞれの変数に代入
  $ages = $parameter[0];            // 年齢
  $partner = $parameter[1];         // 配偶者の有無
  $dependants = $parameter[2];      // 扶養家族の数
  $location = $parameter[3];        // 勤務地の都道府県
  $space = $parameter[4];           // お住まいの居住空間の広さ
  $houserent = $parameter[5];       // お住まいの家賃
  $before_salary = $parameter[6];   // 現在の月額給与
  $before_bonus = $parameter[7];    // 現在の年間賞与


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
  $before_yearly_income = $before_salary * 12 + $before_bonus; // 導入前：年収
  $payment_reduce = $houserent * 0.8;                          // 導入後：会社支払家賃
  $rest_payment = $houserent * 0.2;                            // 導入後：本人支払家賃
  $after_salary = $before_salary - $payment_reduce;            // 導入後：給与
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
  if($before_salary >= 58000 && $before_salary < 63000){
    $before_social_insurance = [2871,3326,8052];
  }elseif($before_salary >= 63000 && $before_salary < 73000){
    $before_social_insurance = [3366,3899,8052];
  }elseif($before_salary >= 73000 && $before_salary < 83000){
    $before_social_insurance = [3861,4473,8052];
  }elseif($before_salary >= 83000 && $before_salary < 93000){
    $before_social_insurance = [4356,5046,8052];
  }elseif($before_salary >= 93000 && $before_salary < 101000){
    $before_social_insurance = [4851,5620,8967];
  }elseif($before_salary >= 101000 && $before_salary < 107000){
    $before_social_insurance = [5148,5964,9516];
  }elseif($before_salary >= 107000 && $before_salary < 114000){
    $before_social_insurance = [5445,6308,10065];
  }elseif($before_salary >= 114000 && $before_salary < 122000){
    $before_social_insurance = [5841,6767,10797];
  }elseif($before_salary >= 122000 && $before_salary < 130000){
    $before_social_insurance = [6237,7226,11529];
  }elseif($before_salary >= 130000 && $before_salary < 138000){
    $before_social_insurance = [6633,7684,12261];
  }elseif($before_salary >= 138000 && $before_salary < 146000){
    $before_social_insurance = [7029,8143,12993];
  }elseif($before_salary >= 146000 && $before_salary < 155000){
    $before_social_insurance = [7425,8602,13725];
  }elseif($before_salary >= 155000 && $before_salary < 165000){
    $before_social_insurance = [7920,9176,14640];
  }elseif($before_salary >= 165000 && $before_salary < 175000){
    $before_social_insurance = [8415,9749,15555];
  }elseif($before_salary >= 175000 && $before_salary < 185000){
    $before_social_insurance = [8910,10323,16470];
  }elseif($before_salary >= 185000 && $before_salary < 195000){
    $before_social_insurance = [9405,10896,17385];
  }elseif($before_salary >= 195000 && $before_salary < 210000){
    $before_social_insurance = [9900,11470,18300];
  }elseif($before_salary >= 210000 && $before_salary < 230000){
    $before_social_insurance = [10890,12617,20130];
  }elseif($before_salary >= 230000 && $before_salary < 250000){
    $before_social_insurance = [11880,13764,21960];
  }elseif($before_salary >= 250000 && $before_salary < 270000){
    $before_social_insurance = [12870,14911,23790];
  }elseif($before_salary >= 270000 && $before_salary < 290000){
    $before_social_insurance = [13860,16058,25620];
  }elseif($before_salary >= 290000 && $before_salary < 310000){
    $before_social_insurance = [14850,17205,27450];
  }elseif($before_salary >= 310000 && $before_salary < 330000){
    $before_social_insurance = [15840,18352,29280];
  }elseif($before_salary >= 330000 && $before_salary < 350000){
    $before_social_insurance = [16830,19499,31110];
  }elseif($before_salary >= 350000 && $before_salary < 370000){
    $before_social_insurance = [17820,20646,32940];
  }elseif($before_salary >= 370000 && $before_salary < 395000){
    $before_social_insurance = [18810,21793,34770];
  }elseif($before_salary >= 395000 && $before_salary < 425000){
    $before_social_insurance = [20295,23513,37515];
  }elseif($before_salary >= 425000 && $before_salary < 455000){
    $before_social_insurance = [21780,25324,40260];
  }elseif($before_salary >= 455000 && $before_salary < 485000){
    $before_social_insurance = [23265,26954,43005];
  }elseif($before_salary >= 485000 && $before_salary < 515000){
    $before_social_insurance = [24750,28675,45750];
  }elseif($before_salary >= 515000 && $before_salary < 545000){
    $before_social_insurance = [26235,30395,48495];
  }elseif($before_salary >= 545000 && $before_salary < 575000){
    $before_social_insurance = [27720,32116,51240];
  }elseif($before_salary >= 575000 && $before_salary < 605000){
    $before_social_insurance = [29205,33836,53985];
  }elseif($before_salary >= 605000 && $before_salary < 635000){
    $before_social_insurance = [30690,35557,56730];
  }elseif($before_salary >= 635000 && $before_salary < 665000){
    $before_social_insurance = [32175,37277,56730];
  }elseif($before_salary >= 665000 && $before_salary < 695000){
    $before_social_insurance = [33660,38998,56730];
  }elseif($before_salary >= 695000 && $before_salary < 730000){
    $before_social_insurance = [35145,40718,56730];
  }elseif($before_salary >= 730000 && $before_salary < 770000){
    $before_social_insurance = [37125,43012,56730];
  }elseif($before_salary >= 770000 && $before_salary < 810000){
    $before_social_insurance = [39105,45306,56730];
  }elseif($before_salary >= 810000 && $before_salary < 855000){
    $before_social_insurance = [41085,47600,56730];
  }elseif($before_salary >= 855000 && $before_salary < 905000){
    $before_social_insurance = [43560,50468,56730];
  }elseif($before_salary >= 905000 && $before_salary < 955000){
    $before_social_insurance = [46035,53335,56730];
  }elseif($before_salary >= 955000 && $before_salary < 1005000){
    $before_social_insurance = [48510,56203,56730];
  }elseif($before_salary >= 1005000 && $before_salary < 1055000){
    $before_social_insurance = [50985,59070,56730];
  }elseif($before_salary >= 1055000 && $before_salary < 1115000){
    $before_social_insurance = [53955,62511,56730];
  }elseif($before_salary >= 1115000 && $before_salary < 1175000){
    $before_social_insurance = [56925,65952,56730];
  }elseif($before_salary >= 1115000 && $before_salary < 1235000){
    $before_social_insurance = [59895,69393,56730];
  }elseif($before_salary >= 1235000 && $before_salary < 1295000){
    $before_social_insurance = [62865,72834,56730];
  }elseif($before_salary >= 1295000 && $before_salary < 1355000){
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
  $before_pretax_salary = $before_salary - $before_health_insurance_expense - $before_pension_premiums;

// 導入前：社保控除後の金額に応じた源泉徴収額の計算 開始----------------------
if($before_pretax_salary >= 0 && $before_pretax_salary < 88000){$before_dependant = [0,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 88000 && $before_pretax_salary < 89000){$before_dependant = [130,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 89000 && $before_pretax_salary < 90000){$before_dependant = [180,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 90000 && $before_pretax_salary < 91000){$before_dependant = [230,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 91000 && $before_pretax_salary < 92000){$before_dependant = [290,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 92000 && $before_pretax_salary < 93000){$before_dependant = [340,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 93000 && $before_pretax_salary < 94000){$before_dependant = [390,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 94000 && $before_pretax_salary < 95000){$before_dependant = [440,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 95000 && $before_pretax_salary < 96000){$before_dependant = [490,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 96000 && $before_pretax_salary < 97000){$before_dependant = [540,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 97000 && $before_pretax_salary < 98000){$before_dependant = [590,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 98000 && $before_pretax_salary < 99000){$before_dependant = [640,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 99000 && $before_pretax_salary < 101000){$before_dependant = [720,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 101000 && $before_pretax_salary < 103000){$before_dependant = [830,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 103000 && $before_pretax_salary < 105000){$before_dependant = [930,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 105000 && $before_pretax_salary < 107000){$before_dependant = [1030,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 107000 && $before_pretax_salary < 109000){$before_dependant = [1130,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 109000 && $before_pretax_salary < 111000){$before_dependant = [1240,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 111000 && $before_pretax_salary < 113000){$before_dependant = [1340,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 113000 && $before_pretax_salary < 115000){$before_dependant = [1440,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 115000 && $before_pretax_salary < 117000){$before_dependant = [1540,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 117000 && $before_pretax_salary < 119000){$before_dependant = [1640,0,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 119000 && $before_pretax_salary < 121000){$before_dependant = [1750,120,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 121000 && $before_pretax_salary < 123000){$before_dependant = [1850,220,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 123000 && $before_pretax_salary < 125000){$before_dependant = [1950,330,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 125000 && $before_pretax_salary < 127000){$before_dependant = [2050,430,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 127000 && $before_pretax_salary < 129000){$before_dependant = [2150,530,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 129000 && $before_pretax_salary < 131000){$before_dependant = [2260,630,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 131000 && $before_pretax_salary < 133000){$before_dependant = [2360,740,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 133000 && $before_pretax_salary < 135000){$before_dependant = [2460,840,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 135000 && $before_pretax_salary < 137000){$before_dependant = [2550,930,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 137000 && $before_pretax_salary < 139000){$before_dependant = [2610,990,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 139000 && $before_pretax_salary < 141000){$before_dependant = [2680,1050,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 141000 && $before_pretax_salary < 143000){$before_dependant = [2740,1110,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 143000 && $before_pretax_salary < 145000){$before_dependant = [2800,1170,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 145000 && $before_pretax_salary < 147000){$before_dependant = [2860,1240,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 147000 && $before_pretax_salary < 149000){$before_dependant = [2920,1300,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 149000 && $before_pretax_salary < 151000){$before_dependant = [2980,1360,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 151000 && $before_pretax_salary < 153000){$before_dependant = [3050,1430,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 153000 && $before_pretax_salary < 155000){$before_dependant = [3120,1500,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 155000 && $before_pretax_salary < 157000){$before_dependant = [3200,1570,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 157000 && $before_pretax_salary < 159000){$before_dependant = [3270,1640,0,0,0,0,0,0];
}elseif($before_pretax_salary >= 159000 && $before_pretax_salary < 161000){$before_dependant = [3340,1720,100,0,0,0,0,0];
}elseif($before_pretax_salary >= 161000 && $before_pretax_salary < 163000){$before_dependant = [3410,1790,170,0,0,0,0,0];
}elseif($before_pretax_salary >= 163000 && $before_pretax_salary < 165000){$before_dependant = [3480,1860,250,0,0,0,0,0];
}elseif($before_pretax_salary >= 165000 && $before_pretax_salary < 167000){$before_dependant = [3550,1930,320,0,0,0,0,0];
}elseif($before_pretax_salary >= 167000 && $before_pretax_salary < 169000){$before_dependant = [3620,2000,390,0,0,0,0,0];
}elseif($before_pretax_salary >= 169000 && $before_pretax_salary < 171000){$before_dependant = [3700,2070,460,0,0,0,0,0];
}elseif($before_pretax_salary >= 171000 && $before_pretax_salary < 173000){$before_dependant = [3770,2140,530,0,0,0,0,0];
}elseif($before_pretax_salary >= 173000 && $before_pretax_salary < 175000){$before_dependant = [3840,2220,600,0,0,0,0,0];
}elseif($before_pretax_salary >= 175000 && $before_pretax_salary < 177000){$before_dependant = [3910,2290,670,0,0,0,0,0];
}elseif($before_pretax_salary >= 177000 && $before_pretax_salary < 179000){$before_dependant = [3980,2360,750,0,0,0,0,0];
}elseif($before_pretax_salary >= 179000 && $before_pretax_salary < 181000){$before_dependant = [4050,2430,820,0,0,0,0,0];
}elseif($before_pretax_salary >= 181000 && $before_pretax_salary < 183000){$before_dependant = [4120,2500,890,0,0,0,0,0];
}elseif($before_pretax_salary >= 183000 && $before_pretax_salary < 185000){$before_dependant = [4200,2570,960,0,0,0,0,0];
}elseif($before_pretax_salary >= 185000 && $before_pretax_salary < 187000){$before_dependant = [4270,2640,1030,0,0,0,0,0];
}elseif($before_pretax_salary >= 187000 && $before_pretax_salary < 189000){$before_dependant = [4340,2720,1100,0,0,0,0,0];
}elseif($before_pretax_salary >= 189000 && $before_pretax_salary < 191000){$before_dependant = [4410,2790,1170,0,0,0,0,0];
}elseif($before_pretax_salary >= 191000 && $before_pretax_salary < 193000){$before_dependant = [4480,2860,1250,0,0,0,0,0];
}elseif($before_pretax_salary >= 193000 && $before_pretax_salary < 195000){$before_dependant = [4550,2930,1320,0,0,0,0,0];
}elseif($before_pretax_salary >= 195000 && $before_pretax_salary < 197000){$before_dependant = [4630,3000,1390,0,0,0,0,0];
}elseif($before_pretax_salary >= 197000 && $before_pretax_salary < 199000){$before_dependant = [4700,3070,1460,0,0,0,0,0];
}elseif($before_pretax_salary >= 199000 && $before_pretax_salary < 201000){$before_dependant = [4770,3140,1530,0,0,0,0,0];
}elseif($before_pretax_salary >= 201000 && $before_pretax_salary < 203000){$before_dependant = [4840,3220,1600,0,0,0,0,0];
}elseif($before_pretax_salary >= 203000 && $before_pretax_salary < 205000){$before_dependant = [4910,3290,1670,0,0,0,0,0];
}elseif($before_pretax_salary >= 205000 && $before_pretax_salary < 207000){$before_dependant = [4980,3360,1750,130,0,0,0,0];
}elseif($before_pretax_salary >= 207000 && $before_pretax_salary < 209000){$before_dependant = [5050,3430,1820,200,0,0,0,0];
}elseif($before_pretax_salary >= 209000 && $before_pretax_salary < 211000){$before_dependant = [5130,3500,1890,280,0,0,0,0];
}elseif($before_pretax_salary >= 211000 && $before_pretax_salary < 213000){$before_dependant = [5200,3570,1960,350,0,0,0,0];
}elseif($before_pretax_salary >= 213000 && $before_pretax_salary < 215000){$before_dependant = [5270,3640,2030,420,0,0,0,0];
}elseif($before_pretax_salary >= 215000 && $before_pretax_salary < 217000){$before_dependant = [5340,3720,2100,490,0,0,0,0];
}elseif($before_pretax_salary >= 217000 && $before_pretax_salary < 219000){$before_dependant = [5410,3790,2170,560,0,0,0,0];
}elseif($before_pretax_salary >= 219000 && $before_pretax_salary < 221000){$before_dependant = [5480,3860,2250,630,0,0,0,0];
}elseif($before_pretax_salary >= 221000 && $before_pretax_salary < 224000){$before_dependant = [5560,3950,2340,710,0,0,0,0];
}elseif($before_pretax_salary >= 224000 && $before_pretax_salary < 227000){$before_dependant = [5680,4060,2440,830,0,0,0,0];
}elseif($before_pretax_salary >= 227000 && $before_pretax_salary < 230000){$before_dependant = [5780,4170,2550,930,0,0,0,0];
}elseif($before_pretax_salary >= 230000 && $before_pretax_salary < 233000){$before_dependant = [5890,4280,2650,1040,0,0,0,0];
}elseif($before_pretax_salary >= 233000 && $before_pretax_salary < 236000){$before_dependant = [5990,4380,2770,1140,0,0,0,0];
}elseif($before_pretax_salary >= 236000 && $before_pretax_salary < 239000){$before_dependant = [6110,4490,2870,1260,0,0,0,0];
}elseif($before_pretax_salary >= 239000 && $before_pretax_salary < 242000){$before_dependant = [6210,4590,2980,1360,0,0,0,0];
}elseif($before_pretax_salary >= 242000 && $before_pretax_salary < 245000){$before_dependant = [6320,4710,3080,1470,0,0,0,0];
}elseif($before_pretax_salary >= 245000 && $before_pretax_salary < 248000){$before_dependant = [6420,4810,3200,1570,0,0,0,0];
}elseif($before_pretax_salary >= 248000 && $before_pretax_salary < 251000){$before_dependant = [6530,4920,3300,1680,0,0,0,0];
}elseif($before_pretax_salary >= 251000 && $before_pretax_salary < 254000){$before_dependant = [6640,5020,3410,1790,170,0,0,0];
}elseif($before_pretax_salary >= 254000 && $before_pretax_salary < 257000){$before_dependant = [6750,5140,3510,1900,290,0,0,0];
}elseif($before_pretax_salary >= 257000 && $before_pretax_salary < 260000){$before_dependant = [6850,5240,3620,2000,390,0,0,0];
}elseif($before_pretax_salary >= 260000 && $before_pretax_salary < 263000){$before_dependant = [6960,5350,3730,2110,500,0,0,0];
}elseif($before_pretax_salary >= 263000 && $before_pretax_salary < 266000){$before_dependant = [7070,5450,3840,2220,600,0,0,0];
}elseif($before_pretax_salary >= 266000 && $before_pretax_salary < 269000){$before_dependant = [7180,5560,3940,2330,710,0,0,0];
}elseif($before_pretax_salary >= 269000 && $before_pretax_salary < 272000){$before_dependant = [7280,5670,4050,2430,820,0,0,0];
}elseif($before_pretax_salary >= 272000 && $before_pretax_salary < 275000){$before_dependant = [7390,5780,4160,2540,930,0,0,0];
}elseif($before_pretax_salary >= 275000 && $before_pretax_salary < 278000){$before_dependant = [7490,5880,4270,2640,1030,0,0,0];
}elseif($before_pretax_salary >= 278000 && $before_pretax_salary < 281000){$before_dependant = [7610,5990,4370,2760,1140,0,0,0];
}elseif($before_pretax_salary >= 281000 && $before_pretax_salary < 284000){$before_dependant = [7710,6100,4480,2860,1250,0,0,0];
}elseif($before_pretax_salary >= 284000 && $before_pretax_salary < 287000){$before_dependant = [7820,6210,4580,2970,1360,0,0,0];
}elseif($before_pretax_salary >= 287000 && $before_pretax_salary < 290000){$before_dependant = [7920,6310,4700,3070,1460,0,0,0];
}elseif($before_pretax_salary >= 290000 && $before_pretax_salary < 293000){$before_dependant = [8040,6420,4800,3190,1570,0,0,0];
}elseif($before_pretax_salary >= 293000 && $before_pretax_salary < 296000){$before_dependant = [8140,6520,4910,3290,1670,0,0,0];
}elseif($before_pretax_salary >= 296000 && $before_pretax_salary < 299000){$before_dependant = [8250,6640,5010,3400,1790,160,0,0];
}elseif($before_pretax_salary >= 299000 && $before_pretax_salary < 302000){$before_dependant = [8420,6740,5130,3510,1890,280,0,0];
}elseif($before_pretax_salary >= 302000 && $before_pretax_salary < 305000){$before_dependant = [8670,6860,5250,3630,2010,400,0,0];
}elseif($before_pretax_salary >= 305000 && $before_pretax_salary < 308000){$before_dependant = [8910,6980,5370,3760,2130,520,0,0];
}elseif($before_pretax_salary >= 308000 && $before_pretax_salary < 311000){$before_dependant = [9160,7110,5490,3880,2260,640,0,0];
}elseif($before_pretax_salary >= 311000 && $before_pretax_salary < 314000){$before_dependant = [9400,7230,5620,4000,2380,770,0,0];
}elseif($before_pretax_salary >= 314000 && $before_pretax_salary < 317000){$before_dependant = [9650,7350,5740,4120,2500,890,0,0];
}elseif($before_pretax_salary >= 317000 && $before_pretax_salary < 320000){$before_dependant = [9890,7470,5860,4250,2620,1010,0,0];
}elseif($before_pretax_salary >= 320000 && $before_pretax_salary < 323000){$before_dependant = [10140,7600,5980,4370,2750,1130,0,0];
}elseif($before_pretax_salary >= 323000 && $before_pretax_salary < 326000){$before_dependant = [10380,7720,6110,4490,2870,1260,0,0];
}elseif($before_pretax_salary >= 326000 && $before_pretax_salary < 329000){$before_dependant = [10630,7840,6230,4610,2990,1380,0,0];
}elseif($before_pretax_salary >= 329000 && $before_pretax_salary < 332000){$before_dependant = [10870,7960,6350,4740,3110,1500,0,0];
}elseif($before_pretax_salary >= 332000 && $before_pretax_salary < 335000){$before_dependant = [11120,8090,6470,4860,3240,1620,0,0];
}elseif($before_pretax_salary >= 335000 && $before_pretax_salary < 338000){$before_dependant = [11360,8210,6600,4980,3360,1750,130,0];
}elseif($before_pretax_salary >= 338000 && $before_pretax_salary < 341000){$before_dependant = [11610,8370,6720,5110,3480,1870,260,0];
}elseif($before_pretax_salary >= 341000 && $before_pretax_salary < 344000){$before_dependant = [11850,8620,6840,5230,3600,1990,380,0];
}elseif($before_pretax_salary >= 344000 && $before_pretax_salary < 347000){$before_dependant = [12100,8860,6960,5350,3730,2110,500,0];
}elseif($before_pretax_salary >= 347000 && $before_pretax_salary < 350000){$before_dependant = [12340,9110,7090,5470,3850,2240,620,0];
}elseif($before_pretax_salary >= 350000 && $before_pretax_salary < 353000){$before_dependant = [12590,9350,7210,5600,3970,2360,750,0];
}elseif($before_pretax_salary >= 353000 && $before_pretax_salary < 356000){$before_dependant = [12830,9600,7330,5720,4090,2480,870,0];
}elseif($before_pretax_salary >= 356000 && $before_pretax_salary < 359000){$before_dependant = [13080,9840,7450,5840,4220,2600,990,0];
}elseif($before_pretax_salary >= 359000 && $before_pretax_salary < 362000){$before_dependant = [13320,10090,7580,5960,4340,2730,1110,0];
}elseif($before_pretax_salary >= 362000 && $before_pretax_salary < 365000){$before_dependant = [13570,10330,7700,6090,4460,2850,1240,0];
}elseif($before_pretax_salary >= 365000 && $before_pretax_salary < 368000){$before_dependant = [13810,10580,7820,6210,4580,2970,1360,0];
}elseif($before_pretax_salary >= 368000 && $before_pretax_salary < 371000){$before_dependant = [14060,10820,7940,6330,4710,3090,1480,0];
}elseif($before_pretax_salary >= 371000 && $before_pretax_salary < 374000){$before_dependant = [14300,11070,8070,6450,4830,3220,1600,0];
}elseif($before_pretax_salary >= 374000 && $before_pretax_salary < 377000){$before_dependant = [14550,11310,8190,6580,4950,3340,1730,100];
}elseif($before_pretax_salary >= 377000 && $before_pretax_salary < 380000){$before_dependant = [14790,11560,8320,6700,5070,3460,1850,220];
}elseif($before_pretax_salary >= 380000 && $before_pretax_salary < 383000){$before_dependant = [15040,11800,8570,6820,5200,3580,1970,350];
}elseif($before_pretax_salary >= 383000 && $before_pretax_salary < 386000){$before_dependant = [15280,12050,8810,6940,5320,3710,2090,470];
}elseif($before_pretax_salary >= 386000 && $before_pretax_salary < 389000){$before_dependant = [15530,12290,9060,7070,5440,3830,2220,590];
}elseif($before_pretax_salary >= 389000 && $before_pretax_salary < 392000){$before_dependant = [15770,12540,9300,7190,5560,3950,2340,710];
}elseif($before_pretax_salary >= 392000 && $before_pretax_salary < 395000){$before_dependant = [16020,12780,9550,7310,5690,4070,2460,840];
}elseif($before_pretax_salary >= 395000 && $before_pretax_salary < 398000){$before_dependant = [16260,13030,9790,7430,5810,4200,2580,960];
}elseif($before_pretax_salary >= 398000 && $before_pretax_salary < 401000){$before_dependant = [16510,13270,10040,7560,5930,4320,2710,1080];
}elseif($before_pretax_salary >= 401000 && $before_pretax_salary < 404000){$before_dependant = [16750,13520,10280,7680,6050,4440,2830,1200];
}elseif($before_pretax_salary >= 404000 && $before_pretax_salary < 407000){$before_dependant = [17000,13760,10530,7800,6180,4560,2950,1330];
}elseif($before_pretax_salary >= 407000 && $before_pretax_salary < 410000){$before_dependant = [17240,14010,10770,7920,6300,4690,3070,1450];
}elseif($before_pretax_salary >= 410000 && $before_pretax_salary < 413000){$before_dependant = [17490,14250,11020,8050,6420,4810,3200,1570];
}elseif($before_pretax_salary >= 413000 && $before_pretax_salary < 416000){$before_dependant = [17730,14500,11260,8170,6540,4930,3320,1690];
}elseif($before_pretax_salary >= 416000 && $before_pretax_salary < 419000){$before_dependant = [17980,14740,11510,8290,6670,5050,3440,1820];
}elseif($before_pretax_salary >= 419000 && $before_pretax_salary < 422000){$before_dependant = [18220,14990,11750,8530,6790,5180,3560,1940];
}elseif($before_pretax_salary >= 422000 && $before_pretax_salary < 425000){$before_dependant = [18470,15230,12000,8770,6910,5300,3690,2060];
}elseif($before_pretax_salary >= 425000 && $before_pretax_salary < 428000){$before_dependant = [18710,15480,12240,9020,7030,5420,3810,2180];
}elseif($before_pretax_salary >= 428000 && $before_pretax_salary < 431000){$before_dependant = [18960,15720,12490,9260,7160,5540,3930,2310];
}elseif($before_pretax_salary >= 431000 && $before_pretax_salary < 434000){$before_dependant = [19210,15970,12730,9510,7280,5670,4050,2430];
}elseif($before_pretax_salary >= 434000 && $before_pretax_salary < 437000){$before_dependant = [19450,16210,12980,9750,7400,5790,4180,2550];
}elseif($before_pretax_salary >= 437000 && $before_pretax_salary < 440000){$before_dependant = [19700,16460,13220,10000,7520,5910,4300,2680];
}elseif($before_pretax_salary >= 440000 && $before_pretax_salary < 443000){$before_dependant = [20090,16700,13470,10240,7650,6030,4420,2800];
}elseif($before_pretax_salary >= 443000 && $before_pretax_salary < 446000){$before_dependant = [20580,16950,13710,10490,7770,6160,4540,2920];
}elseif($before_pretax_salary >= 446000 && $before_pretax_salary < 449000){$before_dependant = [21070,17190,13960,10730,7890,6280,4670,3040];
}elseif($before_pretax_salary >= 449000 && $before_pretax_salary < 452000){$before_dependant = [21560,17440,14200,10980,8010,6400,4790,3170];
}elseif($before_pretax_salary >= 452000 && $before_pretax_salary < 455000){$before_dependant = [22050,17680,14450,11220,8140,6520,4910,3290];
}elseif($before_pretax_salary >= 455000 && $before_pretax_salary < 458000){$before_dependant = [22540,17930,14690,11470,8260,6650,5030,3410];
}elseif($before_pretax_salary >= 458000 && $before_pretax_salary < 461000){$before_dependant = [23030,18170,14940,11710,8470,6770,5160,3530];
}elseif($before_pretax_salary >= 461000 && $before_pretax_salary < 464000){$before_dependant = [23520,18420,15180,11960,8720,6890,5280,3660];
}elseif($before_pretax_salary >= 464000 && $before_pretax_salary < 467000){$before_dependant = [24010,18660,15430,12200,8960,7010,5400,3780];
}elseif($before_pretax_salary >= 467000 && $before_pretax_salary < 470000){$before_dependant = [24500,18910,15670,12450,9210,7140,5520,3900];
}elseif($before_pretax_salary >= 470000 && $before_pretax_salary < 473000){$before_dependant = [24990,19150,15920,12690,9450,7260,5650,4020];
}elseif($before_pretax_salary >= 473000 && $before_pretax_salary < 476000){$before_dependant = [25480,19400,16160,12940,9700,7380,5770,4150];
}elseif($before_pretax_salary >= 476000 && $before_pretax_salary < 479000){$before_dependant = [25970,19640,16410,13180,9940,7500,5890,4270];
}elseif($before_pretax_salary >= 479000 && $before_pretax_salary < 482000){$before_dependant = [26460,20000,16650,13430,10190,7630,6010,4390];
}elseif($before_pretax_salary >= 482000 && $before_pretax_salary < 485000){$before_dependant = [26950,20490,16900,13670,10430,7750,6140,4510];
}elseif($before_pretax_salary >= 485000 && $before_pretax_salary < 488000){$before_dependant = [27440,20980,17140,13920,10680,7870,6260,4640];
}elseif($before_pretax_salary >= 488000 && $before_pretax_salary < 491000){$before_dependant = [27930,21470,17390,14160,10920,7990,6380,4760];
}elseif($before_pretax_salary >= 491000 && $before_pretax_salary < 494000){$before_dependant = [28420,21960,17630,14410,11170,8120,6500,4880];
}elseif($before_pretax_salary >= 494000 && $before_pretax_salary < 497000){$before_dependant = [28910,22450,17880,14650,11410,8240,6630,5000];
}elseif($before_pretax_salary >= 497000 && $before_pretax_salary < 500000){$before_dependant = [29400,22940,18120,14900,11660,8420,6750,5130];
}elseif($before_pretax_salary >= 500000 && $before_pretax_salary < 503000){$before_dependant = [29890,23430,18370,15140,11900,8670,6870,5250];
}elseif($before_pretax_salary >= 503000 && $before_pretax_salary < 506000){$before_dependant = [30380,23920,18610,15390,12150,8910,6990,5370];
}elseif($before_pretax_salary >= 506000 && $before_pretax_salary < 509000){$before_dependant = [30880,24410,18860,15630,12390,9160,7120,5490];
}elseif($before_pretax_salary >= 509000 && $before_pretax_salary < 512000){$before_dependant = [31370,24900,19100,15880,12640,9400,7240,5620];
}elseif($before_pretax_salary >= 512000 && $before_pretax_salary < 515000){$before_dependant = [31860,25390,19350,16120,12890,9650,7360,5740];
}elseif($before_pretax_salary >= 515000 && $before_pretax_salary < 518000){$before_dependant = [32350,25880,19590,16370,13130,9890,7480,5860];
}elseif($before_pretax_salary >= 518000 && $before_pretax_salary < 521000){$before_dependant = [32840,26370,19900,16610,13380,10140,7610,5980];
}elseif($before_pretax_salary >= 521000 && $before_pretax_salary < 524000){$before_dependant = [33330,26860,20390,16860,13620,10380,7730,6110];
}elseif($before_pretax_salary >= 524000 && $before_pretax_salary < 527000){$before_dependant = [33820,27350,20880,17100,13870,10630,7850,6230];
}elseif($before_pretax_salary >= 527000 && $before_pretax_salary < 530000){$before_dependant = [34310,27840,21370,17350,14110,10870,7970,6350];
}elseif($before_pretax_salary >= 530000 && $before_pretax_salary < 533000){$before_dependant = [34800,28330,21860,17590,14360,11120,8100,6470];
}elseif($before_pretax_salary >= 533000 && $before_pretax_salary < 536000){$before_dependant = [35290,28820,22350,17840,14600,11360,8220,6600];
}elseif($before_pretax_salary >= 536000 && $before_pretax_salary < 539000){$before_dependant = [35780,29310,22840,18080,14850,11610,8380,6720];
}elseif($before_pretax_salary >= 539000 && $before_pretax_salary < 542000){$before_dependant = [36270,29800,23330,18330,15090,11850,8630,6840];
}elseif($before_pretax_salary >= 542000 && $before_pretax_salary < 545000){$before_dependant = [36760,30290,23820,18570,15340,12100,8870,6960];
}elseif($before_pretax_salary >= 545000 && $before_pretax_salary < 548000){$before_dependant = [37250,30780,24310,18820,15580,12340,9120,7090];
}elseif($before_pretax_salary >= 548000 && $before_pretax_salary < 551000){$before_dependant = [37740,31270,24800,19060,15830,12590,9360,7210];
}elseif($before_pretax_salary >= 551000 && $before_pretax_salary < 554000){$before_dependant = [38280,31810,25340,19330,16100,12860,9630,7350];
}elseif($before_pretax_salary >= 554000 && $before_pretax_salary < 557000){$before_dependant = [38830,32370,25890,19600,16380,13140,9900,7480];
}elseif($before_pretax_salary >= 557000 && $before_pretax_salary < 560000){$before_dependant = [39380,32920,26440,19980,16650,13420,10180,7630];
}elseif($before_pretax_salary >= 560000 && $before_pretax_salary < 563000){$before_dependant = [39930,33470,27000,20530,16930,13690,10460,7760];
}elseif($before_pretax_salary >= 563000 && $before_pretax_salary < 566000){$before_dependant = [40480,34020,27550,21080,17200,13970,10730,7900];
}elseif($before_pretax_salary >= 566000 && $before_pretax_salary < 569000){$before_dependant = [41030,34570,28100,21630,17480,14240,11010,8040];
}elseif($before_pretax_salary >= 569000 && $before_pretax_salary < 572000){$before_dependant = [41590,35120,28650,22190,17760,14520,11280,8180];
}elseif($before_pretax_salary >= 572000 && $before_pretax_salary < 575000){$before_dependant = [42140,35670,29200,22740,18030,14790,11560,8330];
}elseif($before_pretax_salary >= 575000 && $before_pretax_salary < 578000){$before_dependant = [42690,36230,29750,23290,18310,15070,11830,8610];
}elseif($before_pretax_salary >= 578000 && $before_pretax_salary < 581000){$before_dependant = [43240,36780,30300,23840,18580,15350,12110,8880];
}elseif($before_pretax_salary >= 581000 && $before_pretax_salary < 584000){$before_dependant = [43790,37330,30850,24390,18860,15620,12380,9160];
}elseif($before_pretax_salary >= 584000 && $before_pretax_salary < 587000){$before_dependant = [44340,37880,31410,24940,19130,15900,12660,9430];
}elseif($before_pretax_salary >= 587000 && $before_pretax_salary < 590000){$before_dependant = [44890,38430,31960,25490,19410,16170,12940,9710];
}elseif($before_pretax_salary >= 590000 && $before_pretax_salary < 593000){$before_dependant = [45440,38980,32510,26050,19680,16450,13210,9990];
}elseif($before_pretax_salary >= 593000 && $before_pretax_salary < 596000){$before_dependant = [46000,39530,33060,26600,20130,16720,13490,10260];
}elseif($before_pretax_salary >= 596000 && $before_pretax_salary < 599000){$before_dependant = [46550,40080,33610,27150,20690,17000,13760,10540];
}elseif($before_pretax_salary >= 599000 && $before_pretax_salary < 602000){$before_dependant = [47100,40640,34160,27700,21240,17280,14040,10810];
}elseif($before_pretax_salary >= 602000 && $before_pretax_salary < 605000){$before_dependant = [47650,41190,34710,28250,21790,17550,14310,11090];
}elseif($before_pretax_salary >= 605000 && $before_pretax_salary < 608000){$before_dependant = [48200,41740,35270,28800,22340,17830,14590,11360];
}elseif($before_pretax_salary >= 608000 && $before_pretax_salary < 611000){$before_dependant = [48750,42290,35820,29350,22890,18100,14870,11640];
}elseif($before_pretax_salary >= 611000 && $before_pretax_salary < 614000){$before_dependant = [49300,42840,36370,29910,23440,18380,15140,11920];
}elseif($before_pretax_salary >= 614000 && $before_pretax_salary < 617000){$before_dependant = [49860,43390,36920,30460,23990,18650,15420,12190];
}elseif($before_pretax_salary >= 617000 && $before_pretax_salary < 620000){$before_dependant = [50410,43940,37470,31010,24540,18930,15690,12470];
}elseif($before_pretax_salary >= 620000 && $before_pretax_salary < 623000){$before_dependant = [50960,44500,38020,31560,25100,19210,15970,12740];
}elseif($before_pretax_salary >= 623000 && $before_pretax_salary < 626000){$before_dependant = [51510,45050,38570,32110,25650,19480,16240,13020];
}elseif($before_pretax_salary >= 626000 && $before_pretax_salary < 629000){$before_dependant = [52060,45600,39120,32660,26200,19760,16520,13290];
}elseif($before_pretax_salary >= 629000 && $before_pretax_salary < 632000){$before_dependant = [52610,46150,39680,33210,26750,20280,16800,13570];
}elseif($before_pretax_salary >= 632000 && $before_pretax_salary < 635000){$before_dependant = [53160,46700,40230,33760,27300,20830,17070,13840];
}elseif($before_pretax_salary >= 635000 && $before_pretax_salary < 638000){$before_dependant = [53710,47250,40780,34320,27850,21380,17350,14120];
}elseif($before_pretax_salary >= 638000 && $before_pretax_salary < 641000){$before_dependant = [54270,47800,41330,34870,28400,21930,17620,14400];
}elseif($before_pretax_salary >= 641000 && $before_pretax_salary < 644000){$before_dependant = [54820,48350,41880,35420,28960,22480,17900,14670];
}elseif($before_pretax_salary >= 644000 && $before_pretax_salary < 647000){$before_dependant = [55370,48910,42430,35970,29510,23030,18170,14950];
}elseif($before_pretax_salary >= 647000 && $before_pretax_salary < 650000){$before_dependant = [55920,49460,42980,36520,30060,23590,18450,15220];
}elseif($before_pretax_salary >= 650000 && $before_pretax_salary < 653000){$before_dependant = [56470,50010,43540,37070,30610,24140,18730,15500];
}elseif($before_pretax_salary >= 653000 && $before_pretax_salary < 656000){$before_dependant = [57020,50560,44090,37620,31160,24690,19000,15770];
}elseif($before_pretax_salary >= 656000 && $before_pretax_salary < 659000){$before_dependant = [57570,51110,44640,38180,31710,25240,19280,16050];
}elseif($before_pretax_salary >= 659000 && $before_pretax_salary < 662000){$before_dependant = [58130,51660,45190,38730,32260,25790,19550,16330];
}elseif($before_pretax_salary >= 662000 && $before_pretax_salary < 665000){$before_dependant = [58680,52210,45740,39280,32810,26340,19880,16600];
}elseif($before_pretax_salary >= 665000 && $before_pretax_salary < 668000){$before_dependant = [59230,52770,46290,39830,33370,26890,20430,16880];
}elseif($before_pretax_salary >= 668000 && $before_pretax_salary < 671000){$before_dependant = [59780,53320,46840,40380,33920,27440,20980,17150];
}elseif($before_pretax_salary >= 671000 && $before_pretax_salary < 674000){$before_dependant = [60330,53870,47390,40930,34470,28000,21530,17430];
}elseif($before_pretax_salary >= 674000 && $before_pretax_salary < 677000){$before_dependant = [60880,54420,47950,41480,35020,28550,22080,17700];
}elseif($before_pretax_salary >= 677000 && $before_pretax_salary < 680000){$before_dependant = [61430,54970,48500,42030,35570,29100,22640,17980];
}elseif($before_pretax_salary >= 680000 && $before_pretax_salary < 683000){$before_dependant = [61980,55520,49050,42590,36120,29650,23190,18260];
}elseif($before_pretax_salary >= 683000 && $before_pretax_salary < 686000){$before_dependant = [62540,56070,49600,43140,36670,30200,23740,18530];
}elseif($before_pretax_salary >= 686000 && $before_pretax_salary < 689000){$before_dependant = [63090,56620,50150,43690,37230,30750,24290,18810];
}elseif($before_pretax_salary >= 689000 && $before_pretax_salary < 692000){$before_dependant = [63640,57180,50700,44240,37780,31300,24840,19080];
}elseif($before_pretax_salary >= 692000 && $before_pretax_salary < 695000){$before_dependant = [64190,57730,51250,44790,38330,31860,25390,19360];
}elseif($before_pretax_salary >= 695000 && $before_pretax_salary < 698000){$before_dependant = [64740,58280,51810,45340,38880,32410,25940,19630];
}elseif($before_pretax_salary >= 698000 && $before_pretax_salary < 701000){$before_dependant = [65290,58830,52360,45890,39430,32960,26490,20030];
}elseif($before_pretax_salary >= 701000 && $before_pretax_salary < 704000){$before_dependant = [65840,59380,52910,46450,39980,33510,27050,20580];
}elseif($before_pretax_salary >= 704000 && $before_pretax_salary < 707000){$before_dependant = [66400,59930,53460,47000,40530,34060,27600,21130];
}elseif($before_pretax_salary >= 707000 && $before_pretax_salary < 710000){$before_dependant = [66950,60480,54010,47550,41090,34610,28150,21690];
}elseif($before_pretax_salary >= 710000 && $before_pretax_salary < 713000){$before_dependant = [67500,61040,54560,48100,41640,35160,28700,22240];
}elseif($before_pretax_salary >= 713000 && $before_pretax_salary < 716000){$before_dependant = [68050,61590,55110,48650,42190,35710,29250,22790];
}elseif($before_pretax_salary >= 716000 && $before_pretax_salary < 719000){$before_dependant = [68600,62140,55660,49200,42740,36270,29800,23340];
}elseif($before_pretax_salary >= 719000 && $before_pretax_salary < 722000){$before_dependant = [69150,62690,56220,49750,43290,36820,30350,23890];
}elseif($before_pretax_salary >= 722000 && $before_pretax_salary < 725000){$before_dependant = [69700,63240,56770,50300,43840,37370,30910,24440];
}elseif($before_pretax_salary >= 725000 && $before_pretax_salary < 728000){$before_dependant = [70260,63790,57320,50860,44390,37920,31460,24990];
}elseif($before_pretax_salary >= 728000 && $before_pretax_salary < 731000){$before_dependant = [70810,64340,57870,51410,44940,38470,32010,25550];
}elseif($before_pretax_salary >= 731000 && $before_pretax_salary < 734000){$before_dependant = [71360,64890,58420,51960,45500,39020,32560,26100];
}elseif($before_pretax_salary >= 734000 && $before_pretax_salary < 737000){$before_dependant = [71910,65450,58970,52510,46050,39570,33110,26650];
}elseif($before_pretax_salary >= 737000 && $before_pretax_salary < 740000){$before_dependant = [72460,66000,59520,53060,46600,40130,33660,27200];
}elseif($before_pretax_salary >= 740000 && $before_pretax_salary < 743000){$before_dependant = [73010,66550,60080,53610,47150,40680,34210,27750];
}elseif($before_pretax_salary >= 743000 && $before_pretax_salary < 746000){$before_dependant = [73560,67100,60630,54160,47700,41230,34770,28300];
}elseif($before_pretax_salary >= 746000 && $before_pretax_salary < 749000){$before_dependant = [74110,67650,61180,54720,48250,41780,35320,28850];
}elseif($before_pretax_salary >= 749000 && $before_pretax_salary < 752000){$before_dependant = [74670,68200,61730,55270,48800,42330,35870,29400];
}elseif($before_pretax_salary >= 752000 && $before_pretax_salary < 755000){$before_dependant = [75220,68750,62280,55820,49360,42880,36420,29960];
}elseif($before_pretax_salary >= 755000 && $before_pretax_salary < 758000){$before_dependant = [75770,69310,62830,56370,49910,43430,36970,30510];
}elseif($before_pretax_salary >= 758000 && $before_pretax_salary < 761000){$before_dependant = [76320,69860,63380,56920,50460,43980,37520,31060];
}elseif($before_pretax_salary >= 761000 && $before_pretax_salary < 764000){$before_dependant = [76870,70410,63940,57470,51010,44540,38070,31610];
}elseif($before_pretax_salary >= 764000 && $before_pretax_salary < 767000){$before_dependant = [77420,70960,64490,58020,51560,45090,38620,32160];
}elseif($before_pretax_salary >= 767000 && $before_pretax_salary < 770000){$before_dependant = [77970,71510,65040,58570,52110,45640,39180,32710];
}elseif($before_pretax_salary >= 770000 && $before_pretax_salary < 773000){$before_dependant = [78530,72060,65590,59130,52660,46190,39730,33260];
}elseif($before_pretax_salary >= 773000 && $before_pretax_salary < 776000){$before_dependant = [79080,72610,66140,59680,53210,46740,40280,33820];
}elseif($before_pretax_salary >= 776000 && $before_pretax_salary < 779000){$before_dependant = [79630,73160,66690,60230,53770,47290,40830,34370];
}elseif($before_pretax_salary >= 779000 && $before_pretax_salary < 782000){$before_dependant = [80180,73720,67240,60780,54320,47840,41380,34920];
}elseif($before_pretax_salary >= 782000 && $before_pretax_salary < 785000){$before_dependant = [80730,74270,67790,61330,54870,48400,41930,35470];
}elseif($before_pretax_salary >= 785000 && $before_pretax_salary < 788000){$before_dependant = [81280,74820,68350,61880,55420,48950,42480,36020];
}elseif($before_pretax_salary >= 788000 && $before_pretax_salary < 791000){$before_dependant = [81830,75370,68900,62430,55970,49500,43040,36570];
}elseif($before_pretax_salary >= 791000 && $before_pretax_salary < 794000){$before_dependant = [82460,75920,69450,62990,56520,50050,43590,37120];
}elseif($before_pretax_salary >= 794000 && $before_pretax_salary < 797000){$before_dependant = [83100,76470,70000,63540,57070,50600,44140,37670];
}elseif($before_pretax_salary >= 797000 && $before_pretax_salary < 800000){$before_dependant = [83730,77020,70550,64090,57630,51150,44690,38230];
}elseif($before_pretax_salary >= 800000 && $before_pretax_salary < 803000){$before_dependant = [84370,77580,71100,64640,58180,51700,45240,38780];
}elseif($before_pretax_salary >= 803000 && $before_pretax_salary < 806000){$before_dependant = [85000,78130,71650,65190,58730,52250,45790,39330];
}elseif($before_pretax_salary >= 806000 && $before_pretax_salary < 809000){$before_dependant = [85630,78680,72210,65740,59280,52810,46340,39880];
}elseif($before_pretax_salary >= 809000 && $before_pretax_salary < 812000){$before_dependant = [86260,79230,72760,66290,59830,53360,46890,40430];
}elseif($before_pretax_salary >= 812000 && $before_pretax_salary < 815000){$before_dependant = [86900,79780,73310,66840,60380,53910,47450,40980];
}elseif($before_pretax_salary >= 815000 && $before_pretax_salary < 818000){$before_dependant = [87530,80330,73860,67400,60930,54460,48000,41530];
}elseif($before_pretax_salary >= 818000 && $before_pretax_salary < 821000){$before_dependant = [88160,80880,74410,67950,61480,55010,48550,42090];
}elseif($before_pretax_salary >= 821000 && $before_pretax_salary < 824000){$before_dependant = [88800,81430,74960,68500,62040,55560,49100,42640];
}elseif($before_pretax_salary >= 824000 && $before_pretax_salary < 827000){$before_dependant = [89440,82000,75510,69050,62590,56110,49650,43190];
}elseif($before_pretax_salary >= 827000 && $before_pretax_salary < 830000){$before_dependant = [90070,82630,76060,69600,63140,56670,50200,43740];
}elseif($before_pretax_salary >= 830000 && $before_pretax_salary < 833000){$before_dependant = [90710,83260,76620,70150,63690,57220,50750,44290];
}elseif($before_pretax_salary >= 833000 && $before_pretax_salary < 836000){$before_dependant = [91360,83930,77200,70720,64260,57800,51330,44860];
}elseif($before_pretax_salary >= 836000 && $before_pretax_salary < 839000){$before_dependant = [92060,84630,77810,71340,64870,58410,51940,45480];
}elseif($before_pretax_salary >= 839000 && $before_pretax_salary < 842000){$before_dependant = [92770,85340,78420,71950,65490,59020,52550,46090];
}elseif($before_pretax_salary >= 842000 && $before_pretax_salary < 845000){$before_dependant = [93470,86040,79040,72560,66100,59640,53160,46700];
}elseif($before_pretax_salary >= 845000 && $before_pretax_salary < 848000){$before_dependant = [94180,86740,79650,73180,66710,60250,53780,47310];
}elseif($before_pretax_salary >= 848000 && $before_pretax_salary < 851000){$before_dependant = [94880,87450,80260,73790,67320,60860,54390,47930];
}elseif($before_pretax_salary >= 851000 && $before_pretax_salary < 854000){$before_dependant = [95590,88150,80870,74400,67940,61470,55000,48540];
}elseif($before_pretax_salary >= 854000 && $before_pretax_salary < 857000){$before_dependant = [96290,88860,81490,75010,68550,62090,55610,49150];
}elseif($before_pretax_salary >= 857000 && $before_pretax_salary < 860000){$before_dependant = [97000,89560,82130,75630,69160,62700,56230,49760];
}elseif($before_pretax_salary == 860000){$before_dependant = [97350,89920,82480,75930,69470,63010,56530,50070];
}elseif($before_pretax_salary > 860000 && $before_pretax_salary < 970000){
$delta_hightax = floor(($before_pretax_salary - 860000) * 0.23483);
$before_dependant = [97350+$delta_hightax,89920+$delta_hightax,82480+$delta_hightax,75930+$delta_hightax,69470+$delta_hightax,63010+$delta_hightax,56530+$delta_hightax,50070+$delta_hightax];
}elseif($before_pretax_salary == 970000){$before_dependant = [123190,115760,108320,101770,95310,88850,82370,75910];
}elseif($before_pretax_salary > 970000 && $before_pretax_salary < 1720000){
$delta_hightax = floor(($before_pretax_salary - 970000) * 0.33693);
$before_dependant = [123190+$delta_hightax,115760+$delta_hightax,108320+$delta_hightax,101770+$delta_hightax,95310+$delta_hightax,88850+$delta_hightax,82370+$delta_hightax,75910+$delta_hightax];
}elseif($before_pretax_salary == 1720000){$before_dependant = [375890,368460,361020,354470,348010,341550,335070,328610];
}elseif($before_pretax_salary > 1720000 && $before_pretax_salary < 3550000){
$delta_hightax = floor(($before_pretax_salary - 1720000) * 0.4084);
$before_dependant = [123190+$delta_hightax,115760+$delta_hightax,108320+$delta_hightax,101770+$delta_hightax,95310+$delta_hightax,88850+$delta_hightax,82370+$delta_hightax,75910+$delta_hightax];
}elseif($before_pretax_salary == 3550000){$before_dependant = [1123270,1115840,1108400,1101850,1095390,1088930,1082450,1075990];
}elseif($before_pretax_salary > 3550000){
$delta_hightax = floor(($before_pretax_salary - 3550000) * 0.45945);
$before_dependant = [1123270+$delta_hightax,1115840+$delta_hightax,1108400+$delta_hightax,1101850+$delta_hightax,1095390+$delta_hightax,1088930+$delta_hightax,1082450+$delta_hightax,1075990+$delta_hightax];
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
  if($before_yearly_income < 1625000){
    $before_salary_deduction = 650000;
  }elseif($before_yearly_income >= 1625000 && $before_yearly_income <= 1800000){
    $before_salary_deduction = $before_yearly_income * 0.4;
  }elseif($before_yearly_income > 1800000 && $before_yearly_income <= 3600000){
    $before_salary_deduction = $before_yearly_income * 0.3 + 180000;
  }elseif($before_yearly_income > 3600000 && $before_yearly_income <= 6600000){
    $before_salary_deduction = $before_yearly_income * 0.2 + 540000;
  }elseif($before_yearly_income > 6600000 && $before_yearly_income <= 10000000){
    $before_salary_deduction = $before_yearly_income * 0.1 + 1200000;
  }elseif($before_yearly_income > 1625000 && $before_yearly_income <= 1800000){
    $before_salary_deduction = $before_yearly_income * 0.4;
  }else{
    $before_salary_deduction = 2200000;
  }



  //所得控除
  $before_income_deduction = $basic_deduction + $partner_deduction + $dependant_deduction + $before_social_insurance_total;

  // 住民税計算用の課税対象金額の計算
  $before_inhabitant_tax_yearly = $before_yearly_income - $before_salary_deduction - $before_income_deduction ;

  // 月額住民税の計算
  $before_inhabitant_tax = floor($before_inhabitant_tax_yearly / 120);

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
if($after_pretax_salary >= 0 && $after_pretax_salary < 88000){$after_dependant = [0,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 88000 && $after_pretax_salary < 89000){$after_dependant = [130,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 89000 && $after_pretax_salary < 90000){$after_dependant = [180,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 90000 && $after_pretax_salary < 91000){$after_dependant = [230,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 91000 && $after_pretax_salary < 92000){$after_dependant = [290,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 92000 && $after_pretax_salary < 93000){$after_dependant = [340,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 93000 && $after_pretax_salary < 94000){$after_dependant = [390,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 94000 && $after_pretax_salary < 95000){$after_dependant = [440,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 95000 && $after_pretax_salary < 96000){$after_dependant = [490,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 96000 && $after_pretax_salary < 97000){$after_dependant = [540,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 97000 && $after_pretax_salary < 98000){$after_dependant = [590,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 98000 && $after_pretax_salary < 99000){$after_dependant = [640,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 99000 && $after_pretax_salary < 101000){$after_dependant = [720,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 101000 && $after_pretax_salary < 103000){$after_dependant = [830,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 103000 && $after_pretax_salary < 105000){$after_dependant = [930,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 105000 && $after_pretax_salary < 107000){$after_dependant = [1030,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 107000 && $after_pretax_salary < 109000){$after_dependant = [1130,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 109000 && $after_pretax_salary < 111000){$after_dependant = [1240,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 111000 && $after_pretax_salary < 113000){$after_dependant = [1340,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 113000 && $after_pretax_salary < 115000){$after_dependant = [1440,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 115000 && $after_pretax_salary < 117000){$after_dependant = [1540,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 117000 && $after_pretax_salary < 119000){$after_dependant = [1640,0,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 119000 && $after_pretax_salary < 121000){$after_dependant = [1750,120,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 121000 && $after_pretax_salary < 123000){$after_dependant = [1850,220,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 123000 && $after_pretax_salary < 125000){$after_dependant = [1950,330,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 125000 && $after_pretax_salary < 127000){$after_dependant = [2050,430,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 127000 && $after_pretax_salary < 129000){$after_dependant = [2150,530,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 129000 && $after_pretax_salary < 131000){$after_dependant = [2260,630,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 131000 && $after_pretax_salary < 133000){$after_dependant = [2360,740,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 133000 && $after_pretax_salary < 135000){$after_dependant = [2460,840,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 135000 && $after_pretax_salary < 137000){$after_dependant = [2550,930,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 137000 && $after_pretax_salary < 139000){$after_dependant = [2610,990,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 139000 && $after_pretax_salary < 141000){$after_dependant = [2680,1050,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 141000 && $after_pretax_salary < 143000){$after_dependant = [2740,1110,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 143000 && $after_pretax_salary < 145000){$after_dependant = [2800,1170,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 145000 && $after_pretax_salary < 147000){$after_dependant = [2860,1240,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 147000 && $after_pretax_salary < 149000){$after_dependant = [2920,1300,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 149000 && $after_pretax_salary < 151000){$after_dependant = [2980,1360,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 151000 && $after_pretax_salary < 153000){$after_dependant = [3050,1430,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 153000 && $after_pretax_salary < 155000){$after_dependant = [3120,1500,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 155000 && $after_pretax_salary < 157000){$after_dependant = [3200,1570,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 157000 && $after_pretax_salary < 159000){$after_dependant = [3270,1640,0,0,0,0,0,0];
}elseif($after_pretax_salary >= 159000 && $after_pretax_salary < 161000){$after_dependant = [3340,1720,100,0,0,0,0,0];
}elseif($after_pretax_salary >= 161000 && $after_pretax_salary < 163000){$after_dependant = [3410,1790,170,0,0,0,0,0];
}elseif($after_pretax_salary >= 163000 && $after_pretax_salary < 165000){$after_dependant = [3480,1860,250,0,0,0,0,0];
}elseif($after_pretax_salary >= 165000 && $after_pretax_salary < 167000){$after_dependant = [3550,1930,320,0,0,0,0,0];
}elseif($after_pretax_salary >= 167000 && $after_pretax_salary < 169000){$after_dependant = [3620,2000,390,0,0,0,0,0];
}elseif($after_pretax_salary >= 169000 && $after_pretax_salary < 171000){$after_dependant = [3700,2070,460,0,0,0,0,0];
}elseif($after_pretax_salary >= 171000 && $after_pretax_salary < 173000){$after_dependant = [3770,2140,530,0,0,0,0,0];
}elseif($after_pretax_salary >= 173000 && $after_pretax_salary < 175000){$after_dependant = [3840,2220,600,0,0,0,0,0];
}elseif($after_pretax_salary >= 175000 && $after_pretax_salary < 177000){$after_dependant = [3910,2290,670,0,0,0,0,0];
}elseif($after_pretax_salary >= 177000 && $after_pretax_salary < 179000){$after_dependant = [3980,2360,750,0,0,0,0,0];
}elseif($after_pretax_salary >= 179000 && $after_pretax_salary < 181000){$after_dependant = [4050,2430,820,0,0,0,0,0];
}elseif($after_pretax_salary >= 181000 && $after_pretax_salary < 183000){$after_dependant = [4120,2500,890,0,0,0,0,0];
}elseif($after_pretax_salary >= 183000 && $after_pretax_salary < 185000){$after_dependant = [4200,2570,960,0,0,0,0,0];
}elseif($after_pretax_salary >= 185000 && $after_pretax_salary < 187000){$after_dependant = [4270,2640,1030,0,0,0,0,0];
}elseif($after_pretax_salary >= 187000 && $after_pretax_salary < 189000){$after_dependant = [4340,2720,1100,0,0,0,0,0];
}elseif($after_pretax_salary >= 189000 && $after_pretax_salary < 191000){$after_dependant = [4410,2790,1170,0,0,0,0,0];
}elseif($after_pretax_salary >= 191000 && $after_pretax_salary < 193000){$after_dependant = [4480,2860,1250,0,0,0,0,0];
}elseif($after_pretax_salary >= 193000 && $after_pretax_salary < 195000){$after_dependant = [4550,2930,1320,0,0,0,0,0];
}elseif($after_pretax_salary >= 195000 && $after_pretax_salary < 197000){$after_dependant = [4630,3000,1390,0,0,0,0,0];
}elseif($after_pretax_salary >= 197000 && $after_pretax_salary < 199000){$after_dependant = [4700,3070,1460,0,0,0,0,0];
}elseif($after_pretax_salary >= 199000 && $after_pretax_salary < 201000){$after_dependant = [4770,3140,1530,0,0,0,0,0];
}elseif($after_pretax_salary >= 201000 && $after_pretax_salary < 203000){$after_dependant = [4840,3220,1600,0,0,0,0,0];
}elseif($after_pretax_salary >= 203000 && $after_pretax_salary < 205000){$after_dependant = [4910,3290,1670,0,0,0,0,0];
}elseif($after_pretax_salary >= 205000 && $after_pretax_salary < 207000){$after_dependant = [4980,3360,1750,130,0,0,0,0];
}elseif($after_pretax_salary >= 207000 && $after_pretax_salary < 209000){$after_dependant = [5050,3430,1820,200,0,0,0,0];
}elseif($after_pretax_salary >= 209000 && $after_pretax_salary < 211000){$after_dependant = [5130,3500,1890,280,0,0,0,0];
}elseif($after_pretax_salary >= 211000 && $after_pretax_salary < 213000){$after_dependant = [5200,3570,1960,350,0,0,0,0];
}elseif($after_pretax_salary >= 213000 && $after_pretax_salary < 215000){$after_dependant = [5270,3640,2030,420,0,0,0,0];
}elseif($after_pretax_salary >= 215000 && $after_pretax_salary < 217000){$after_dependant = [5340,3720,2100,490,0,0,0,0];
}elseif($after_pretax_salary >= 217000 && $after_pretax_salary < 219000){$after_dependant = [5410,3790,2170,560,0,0,0,0];
}elseif($after_pretax_salary >= 219000 && $after_pretax_salary < 221000){$after_dependant = [5480,3860,2250,630,0,0,0,0];
}elseif($after_pretax_salary >= 221000 && $after_pretax_salary < 224000){$after_dependant = [5560,3950,2340,710,0,0,0,0];
}elseif($after_pretax_salary >= 224000 && $after_pretax_salary < 227000){$after_dependant = [5680,4060,2440,830,0,0,0,0];
}elseif($after_pretax_salary >= 227000 && $after_pretax_salary < 230000){$after_dependant = [5780,4170,2550,930,0,0,0,0];
}elseif($after_pretax_salary >= 230000 && $after_pretax_salary < 233000){$after_dependant = [5890,4280,2650,1040,0,0,0,0];
}elseif($after_pretax_salary >= 233000 && $after_pretax_salary < 236000){$after_dependant = [5990,4380,2770,1140,0,0,0,0];
}elseif($after_pretax_salary >= 236000 && $after_pretax_salary < 239000){$after_dependant = [6110,4490,2870,1260,0,0,0,0];
}elseif($after_pretax_salary >= 239000 && $after_pretax_salary < 242000){$after_dependant = [6210,4590,2980,1360,0,0,0,0];
}elseif($after_pretax_salary >= 242000 && $after_pretax_salary < 245000){$after_dependant = [6320,4710,3080,1470,0,0,0,0];
}elseif($after_pretax_salary >= 245000 && $after_pretax_salary < 248000){$after_dependant = [6420,4810,3200,1570,0,0,0,0];
}elseif($after_pretax_salary >= 248000 && $after_pretax_salary < 251000){$after_dependant = [6530,4920,3300,1680,0,0,0,0];
}elseif($after_pretax_salary >= 251000 && $after_pretax_salary < 254000){$after_dependant = [6640,5020,3410,1790,170,0,0,0];
}elseif($after_pretax_salary >= 254000 && $after_pretax_salary < 257000){$after_dependant = [6750,5140,3510,1900,290,0,0,0];
}elseif($after_pretax_salary >= 257000 && $after_pretax_salary < 260000){$after_dependant = [6850,5240,3620,2000,390,0,0,0];
}elseif($after_pretax_salary >= 260000 && $after_pretax_salary < 263000){$after_dependant = [6960,5350,3730,2110,500,0,0,0];
}elseif($after_pretax_salary >= 263000 && $after_pretax_salary < 266000){$after_dependant = [7070,5450,3840,2220,600,0,0,0];
}elseif($after_pretax_salary >= 266000 && $after_pretax_salary < 269000){$after_dependant = [7180,5560,3940,2330,710,0,0,0];
}elseif($after_pretax_salary >= 269000 && $after_pretax_salary < 272000){$after_dependant = [7280,5670,4050,2430,820,0,0,0];
}elseif($after_pretax_salary >= 272000 && $after_pretax_salary < 275000){$after_dependant = [7390,5780,4160,2540,930,0,0,0];
}elseif($after_pretax_salary >= 275000 && $after_pretax_salary < 278000){$after_dependant = [7490,5880,4270,2640,1030,0,0,0];
}elseif($after_pretax_salary >= 278000 && $after_pretax_salary < 281000){$after_dependant = [7610,5990,4370,2760,1140,0,0,0];
}elseif($after_pretax_salary >= 281000 && $after_pretax_salary < 284000){$after_dependant = [7710,6100,4480,2860,1250,0,0,0];
}elseif($after_pretax_salary >= 284000 && $after_pretax_salary < 287000){$after_dependant = [7820,6210,4580,2970,1360,0,0,0];
}elseif($after_pretax_salary >= 287000 && $after_pretax_salary < 290000){$after_dependant = [7920,6310,4700,3070,1460,0,0,0];
}elseif($after_pretax_salary >= 290000 && $after_pretax_salary < 293000){$after_dependant = [8040,6420,4800,3190,1570,0,0,0];
}elseif($after_pretax_salary >= 293000 && $after_pretax_salary < 296000){$after_dependant = [8140,6520,4910,3290,1670,0,0,0];
}elseif($after_pretax_salary >= 296000 && $after_pretax_salary < 299000){$after_dependant = [8250,6640,5010,3400,1790,160,0,0];
}elseif($after_pretax_salary >= 299000 && $after_pretax_salary < 302000){$after_dependant = [8420,6740,5130,3510,1890,280,0,0];
}elseif($after_pretax_salary >= 302000 && $after_pretax_salary < 305000){$after_dependant = [8670,6860,5250,3630,2010,400,0,0];
}elseif($after_pretax_salary >= 305000 && $after_pretax_salary < 308000){$after_dependant = [8910,6980,5370,3760,2130,520,0,0];
}elseif($after_pretax_salary >= 308000 && $after_pretax_salary < 311000){$after_dependant = [9160,7110,5490,3880,2260,640,0,0];
}elseif($after_pretax_salary >= 311000 && $after_pretax_salary < 314000){$after_dependant = [9400,7230,5620,4000,2380,770,0,0];
}elseif($after_pretax_salary >= 314000 && $after_pretax_salary < 317000){$after_dependant = [9650,7350,5740,4120,2500,890,0,0];
}elseif($after_pretax_salary >= 317000 && $after_pretax_salary < 320000){$after_dependant = [9890,7470,5860,4250,2620,1010,0,0];
}elseif($after_pretax_salary >= 320000 && $after_pretax_salary < 323000){$after_dependant = [10140,7600,5980,4370,2750,1130,0,0];
}elseif($after_pretax_salary >= 323000 && $after_pretax_salary < 326000){$after_dependant = [10380,7720,6110,4490,2870,1260,0,0];
}elseif($after_pretax_salary >= 326000 && $after_pretax_salary < 329000){$after_dependant = [10630,7840,6230,4610,2990,1380,0,0];
}elseif($after_pretax_salary >= 329000 && $after_pretax_salary < 332000){$after_dependant = [10870,7960,6350,4740,3110,1500,0,0];
}elseif($after_pretax_salary >= 332000 && $after_pretax_salary < 335000){$after_dependant = [11120,8090,6470,4860,3240,1620,0,0];
}elseif($after_pretax_salary >= 335000 && $after_pretax_salary < 338000){$after_dependant = [11360,8210,6600,4980,3360,1750,130,0];
}elseif($after_pretax_salary >= 338000 && $after_pretax_salary < 341000){$after_dependant = [11610,8370,6720,5110,3480,1870,260,0];
}elseif($after_pretax_salary >= 341000 && $after_pretax_salary < 344000){$after_dependant = [11850,8620,6840,5230,3600,1990,380,0];
}elseif($after_pretax_salary >= 344000 && $after_pretax_salary < 347000){$after_dependant = [12100,8860,6960,5350,3730,2110,500,0];
}elseif($after_pretax_salary >= 347000 && $after_pretax_salary < 350000){$after_dependant = [12340,9110,7090,5470,3850,2240,620,0];
}elseif($after_pretax_salary >= 350000 && $after_pretax_salary < 353000){$after_dependant = [12590,9350,7210,5600,3970,2360,750,0];
}elseif($after_pretax_salary >= 353000 && $after_pretax_salary < 356000){$after_dependant = [12830,9600,7330,5720,4090,2480,870,0];
}elseif($after_pretax_salary >= 356000 && $after_pretax_salary < 359000){$after_dependant = [13080,9840,7450,5840,4220,2600,990,0];
}elseif($after_pretax_salary >= 359000 && $after_pretax_salary < 362000){$after_dependant = [13320,10090,7580,5960,4340,2730,1110,0];
}elseif($after_pretax_salary >= 362000 && $after_pretax_salary < 365000){$after_dependant = [13570,10330,7700,6090,4460,2850,1240,0];
}elseif($after_pretax_salary >= 365000 && $after_pretax_salary < 368000){$after_dependant = [13810,10580,7820,6210,4580,2970,1360,0];
}elseif($after_pretax_salary >= 368000 && $after_pretax_salary < 371000){$after_dependant = [14060,10820,7940,6330,4710,3090,1480,0];
}elseif($after_pretax_salary >= 371000 && $after_pretax_salary < 374000){$after_dependant = [14300,11070,8070,6450,4830,3220,1600,0];
}elseif($after_pretax_salary >= 374000 && $after_pretax_salary < 377000){$after_dependant = [14550,11310,8190,6580,4950,3340,1730,100];
}elseif($after_pretax_salary >= 377000 && $after_pretax_salary < 380000){$after_dependant = [14790,11560,8320,6700,5070,3460,1850,220];
}elseif($after_pretax_salary >= 380000 && $after_pretax_salary < 383000){$after_dependant = [15040,11800,8570,6820,5200,3580,1970,350];
}elseif($after_pretax_salary >= 383000 && $after_pretax_salary < 386000){$after_dependant = [15280,12050,8810,6940,5320,3710,2090,470];
}elseif($after_pretax_salary >= 386000 && $after_pretax_salary < 389000){$after_dependant = [15530,12290,9060,7070,5440,3830,2220,590];
}elseif($after_pretax_salary >= 389000 && $after_pretax_salary < 392000){$after_dependant = [15770,12540,9300,7190,5560,3950,2340,710];
}elseif($after_pretax_salary >= 392000 && $after_pretax_salary < 395000){$after_dependant = [16020,12780,9550,7310,5690,4070,2460,840];
}elseif($after_pretax_salary >= 395000 && $after_pretax_salary < 398000){$after_dependant = [16260,13030,9790,7430,5810,4200,2580,960];
}elseif($after_pretax_salary >= 398000 && $after_pretax_salary < 401000){$after_dependant = [16510,13270,10040,7560,5930,4320,2710,1080];
}elseif($after_pretax_salary >= 401000 && $after_pretax_salary < 404000){$after_dependant = [16750,13520,10280,7680,6050,4440,2830,1200];
}elseif($after_pretax_salary >= 404000 && $after_pretax_salary < 407000){$after_dependant = [17000,13760,10530,7800,6180,4560,2950,1330];
}elseif($after_pretax_salary >= 407000 && $after_pretax_salary < 410000){$after_dependant = [17240,14010,10770,7920,6300,4690,3070,1450];
}elseif($after_pretax_salary >= 410000 && $after_pretax_salary < 413000){$after_dependant = [17490,14250,11020,8050,6420,4810,3200,1570];
}elseif($after_pretax_salary >= 413000 && $after_pretax_salary < 416000){$after_dependant = [17730,14500,11260,8170,6540,4930,3320,1690];
}elseif($after_pretax_salary >= 416000 && $after_pretax_salary < 419000){$after_dependant = [17980,14740,11510,8290,6670,5050,3440,1820];
}elseif($after_pretax_salary >= 419000 && $after_pretax_salary < 422000){$after_dependant = [18220,14990,11750,8530,6790,5180,3560,1940];
}elseif($after_pretax_salary >= 422000 && $after_pretax_salary < 425000){$after_dependant = [18470,15230,12000,8770,6910,5300,3690,2060];
}elseif($after_pretax_salary >= 425000 && $after_pretax_salary < 428000){$after_dependant = [18710,15480,12240,9020,7030,5420,3810,2180];
}elseif($after_pretax_salary >= 428000 && $after_pretax_salary < 431000){$after_dependant = [18960,15720,12490,9260,7160,5540,3930,2310];
}elseif($after_pretax_salary >= 431000 && $after_pretax_salary < 434000){$after_dependant = [19210,15970,12730,9510,7280,5670,4050,2430];
}elseif($after_pretax_salary >= 434000 && $after_pretax_salary < 437000){$after_dependant = [19450,16210,12980,9750,7400,5790,4180,2550];
}elseif($after_pretax_salary >= 437000 && $after_pretax_salary < 440000){$after_dependant = [19700,16460,13220,10000,7520,5910,4300,2680];
}elseif($after_pretax_salary >= 440000 && $after_pretax_salary < 443000){$after_dependant = [20090,16700,13470,10240,7650,6030,4420,2800];
}elseif($after_pretax_salary >= 443000 && $after_pretax_salary < 446000){$after_dependant = [20580,16950,13710,10490,7770,6160,4540,2920];
}elseif($after_pretax_salary >= 446000 && $after_pretax_salary < 449000){$after_dependant = [21070,17190,13960,10730,7890,6280,4670,3040];
}elseif($after_pretax_salary >= 449000 && $after_pretax_salary < 452000){$after_dependant = [21560,17440,14200,10980,8010,6400,4790,3170];
}elseif($after_pretax_salary >= 452000 && $after_pretax_salary < 455000){$after_dependant = [22050,17680,14450,11220,8140,6520,4910,3290];
}elseif($after_pretax_salary >= 455000 && $after_pretax_salary < 458000){$after_dependant = [22540,17930,14690,11470,8260,6650,5030,3410];
}elseif($after_pretax_salary >= 458000 && $after_pretax_salary < 461000){$after_dependant = [23030,18170,14940,11710,8470,6770,5160,3530];
}elseif($after_pretax_salary >= 461000 && $after_pretax_salary < 464000){$after_dependant = [23520,18420,15180,11960,8720,6890,5280,3660];
}elseif($after_pretax_salary >= 464000 && $after_pretax_salary < 467000){$after_dependant = [24010,18660,15430,12200,8960,7010,5400,3780];
}elseif($after_pretax_salary >= 467000 && $after_pretax_salary < 470000){$after_dependant = [24500,18910,15670,12450,9210,7140,5520,3900];
}elseif($after_pretax_salary >= 470000 && $after_pretax_salary < 473000){$after_dependant = [24990,19150,15920,12690,9450,7260,5650,4020];
}elseif($after_pretax_salary >= 473000 && $after_pretax_salary < 476000){$after_dependant = [25480,19400,16160,12940,9700,7380,5770,4150];
}elseif($after_pretax_salary >= 476000 && $after_pretax_salary < 479000){$after_dependant = [25970,19640,16410,13180,9940,7500,5890,4270];
}elseif($after_pretax_salary >= 479000 && $after_pretax_salary < 482000){$after_dependant = [26460,20000,16650,13430,10190,7630,6010,4390];
}elseif($after_pretax_salary >= 482000 && $after_pretax_salary < 485000){$after_dependant = [26950,20490,16900,13670,10430,7750,6140,4510];
}elseif($after_pretax_salary >= 485000 && $after_pretax_salary < 488000){$after_dependant = [27440,20980,17140,13920,10680,7870,6260,4640];
}elseif($after_pretax_salary >= 488000 && $after_pretax_salary < 491000){$after_dependant = [27930,21470,17390,14160,10920,7990,6380,4760];
}elseif($after_pretax_salary >= 491000 && $after_pretax_salary < 494000){$after_dependant = [28420,21960,17630,14410,11170,8120,6500,4880];
}elseif($after_pretax_salary >= 494000 && $after_pretax_salary < 497000){$after_dependant = [28910,22450,17880,14650,11410,8240,6630,5000];
}elseif($after_pretax_salary >= 497000 && $after_pretax_salary < 500000){$after_dependant = [29400,22940,18120,14900,11660,8420,6750,5130];
}elseif($after_pretax_salary >= 500000 && $after_pretax_salary < 503000){$after_dependant = [29890,23430,18370,15140,11900,8670,6870,5250];
}elseif($after_pretax_salary >= 503000 && $after_pretax_salary < 506000){$after_dependant = [30380,23920,18610,15390,12150,8910,6990,5370];
}elseif($after_pretax_salary >= 506000 && $after_pretax_salary < 509000){$after_dependant = [30880,24410,18860,15630,12390,9160,7120,5490];
}elseif($after_pretax_salary >= 509000 && $after_pretax_salary < 512000){$after_dependant = [31370,24900,19100,15880,12640,9400,7240,5620];
}elseif($after_pretax_salary >= 512000 && $after_pretax_salary < 515000){$after_dependant = [31860,25390,19350,16120,12890,9650,7360,5740];
}elseif($after_pretax_salary >= 515000 && $after_pretax_salary < 518000){$after_dependant = [32350,25880,19590,16370,13130,9890,7480,5860];
}elseif($after_pretax_salary >= 518000 && $after_pretax_salary < 521000){$after_dependant = [32840,26370,19900,16610,13380,10140,7610,5980];
}elseif($after_pretax_salary >= 521000 && $after_pretax_salary < 524000){$after_dependant = [33330,26860,20390,16860,13620,10380,7730,6110];
}elseif($after_pretax_salary >= 524000 && $after_pretax_salary < 527000){$after_dependant = [33820,27350,20880,17100,13870,10630,7850,6230];
}elseif($after_pretax_salary >= 527000 && $after_pretax_salary < 530000){$after_dependant = [34310,27840,21370,17350,14110,10870,7970,6350];
}elseif($after_pretax_salary >= 530000 && $after_pretax_salary < 533000){$after_dependant = [34800,28330,21860,17590,14360,11120,8100,6470];
}elseif($after_pretax_salary >= 533000 && $after_pretax_salary < 536000){$after_dependant = [35290,28820,22350,17840,14600,11360,8220,6600];
}elseif($after_pretax_salary >= 536000 && $after_pretax_salary < 539000){$after_dependant = [35780,29310,22840,18080,14850,11610,8380,6720];
}elseif($after_pretax_salary >= 539000 && $after_pretax_salary < 542000){$after_dependant = [36270,29800,23330,18330,15090,11850,8630,6840];
}elseif($after_pretax_salary >= 542000 && $after_pretax_salary < 545000){$after_dependant = [36760,30290,23820,18570,15340,12100,8870,6960];
}elseif($after_pretax_salary >= 545000 && $after_pretax_salary < 548000){$after_dependant = [37250,30780,24310,18820,15580,12340,9120,7090];
}elseif($after_pretax_salary >= 548000 && $after_pretax_salary < 551000){$after_dependant = [37740,31270,24800,19060,15830,12590,9360,7210];
}elseif($after_pretax_salary >= 551000 && $after_pretax_salary < 554000){$after_dependant = [38280,31810,25340,19330,16100,12860,9630,7350];
}elseif($after_pretax_salary >= 554000 && $after_pretax_salary < 557000){$after_dependant = [38830,32370,25890,19600,16380,13140,9900,7480];
}elseif($after_pretax_salary >= 557000 && $after_pretax_salary < 560000){$after_dependant = [39380,32920,26440,19980,16650,13420,10180,7630];
}elseif($after_pretax_salary >= 560000 && $after_pretax_salary < 563000){$after_dependant = [39930,33470,27000,20530,16930,13690,10460,7760];
}elseif($after_pretax_salary >= 563000 && $after_pretax_salary < 566000){$after_dependant = [40480,34020,27550,21080,17200,13970,10730,7900];
}elseif($after_pretax_salary >= 566000 && $after_pretax_salary < 569000){$after_dependant = [41030,34570,28100,21630,17480,14240,11010,8040];
}elseif($after_pretax_salary >= 569000 && $after_pretax_salary < 572000){$after_dependant = [41590,35120,28650,22190,17760,14520,11280,8180];
}elseif($after_pretax_salary >= 572000 && $after_pretax_salary < 575000){$after_dependant = [42140,35670,29200,22740,18030,14790,11560,8330];
}elseif($after_pretax_salary >= 575000 && $after_pretax_salary < 578000){$after_dependant = [42690,36230,29750,23290,18310,15070,11830,8610];
}elseif($after_pretax_salary >= 578000 && $after_pretax_salary < 581000){$after_dependant = [43240,36780,30300,23840,18580,15350,12110,8880];
}elseif($after_pretax_salary >= 581000 && $after_pretax_salary < 584000){$after_dependant = [43790,37330,30850,24390,18860,15620,12380,9160];
}elseif($after_pretax_salary >= 584000 && $after_pretax_salary < 587000){$after_dependant = [44340,37880,31410,24940,19130,15900,12660,9430];
}elseif($after_pretax_salary >= 587000 && $after_pretax_salary < 590000){$after_dependant = [44890,38430,31960,25490,19410,16170,12940,9710];
}elseif($after_pretax_salary >= 590000 && $after_pretax_salary < 593000){$after_dependant = [45440,38980,32510,26050,19680,16450,13210,9990];
}elseif($after_pretax_salary >= 593000 && $after_pretax_salary < 596000){$after_dependant = [46000,39530,33060,26600,20130,16720,13490,10260];
}elseif($after_pretax_salary >= 596000 && $after_pretax_salary < 599000){$after_dependant = [46550,40080,33610,27150,20690,17000,13760,10540];
}elseif($after_pretax_salary >= 599000 && $after_pretax_salary < 602000){$after_dependant = [47100,40640,34160,27700,21240,17280,14040,10810];
}elseif($after_pretax_salary >= 602000 && $after_pretax_salary < 605000){$after_dependant = [47650,41190,34710,28250,21790,17550,14310,11090];
}elseif($after_pretax_salary >= 605000 && $after_pretax_salary < 608000){$after_dependant = [48200,41740,35270,28800,22340,17830,14590,11360];
}elseif($after_pretax_salary >= 608000 && $after_pretax_salary < 611000){$after_dependant = [48750,42290,35820,29350,22890,18100,14870,11640];
}elseif($after_pretax_salary >= 611000 && $after_pretax_salary < 614000){$after_dependant = [49300,42840,36370,29910,23440,18380,15140,11920];
}elseif($after_pretax_salary >= 614000 && $after_pretax_salary < 617000){$after_dependant = [49860,43390,36920,30460,23990,18650,15420,12190];
}elseif($after_pretax_salary >= 617000 && $after_pretax_salary < 620000){$after_dependant = [50410,43940,37470,31010,24540,18930,15690,12470];
}elseif($after_pretax_salary >= 620000 && $after_pretax_salary < 623000){$after_dependant = [50960,44500,38020,31560,25100,19210,15970,12740];
}elseif($after_pretax_salary >= 623000 && $after_pretax_salary < 626000){$after_dependant = [51510,45050,38570,32110,25650,19480,16240,13020];
}elseif($after_pretax_salary >= 626000 && $after_pretax_salary < 629000){$after_dependant = [52060,45600,39120,32660,26200,19760,16520,13290];
}elseif($after_pretax_salary >= 629000 && $after_pretax_salary < 632000){$after_dependant = [52610,46150,39680,33210,26750,20280,16800,13570];
}elseif($after_pretax_salary >= 632000 && $after_pretax_salary < 635000){$after_dependant = [53160,46700,40230,33760,27300,20830,17070,13840];
}elseif($after_pretax_salary >= 635000 && $after_pretax_salary < 638000){$after_dependant = [53710,47250,40780,34320,27850,21380,17350,14120];
}elseif($after_pretax_salary >= 638000 && $after_pretax_salary < 641000){$after_dependant = [54270,47800,41330,34870,28400,21930,17620,14400];
}elseif($after_pretax_salary >= 641000 && $after_pretax_salary < 644000){$after_dependant = [54820,48350,41880,35420,28960,22480,17900,14670];
}elseif($after_pretax_salary >= 644000 && $after_pretax_salary < 647000){$after_dependant = [55370,48910,42430,35970,29510,23030,18170,14950];
}elseif($after_pretax_salary >= 647000 && $after_pretax_salary < 650000){$after_dependant = [55920,49460,42980,36520,30060,23590,18450,15220];
}elseif($after_pretax_salary >= 650000 && $after_pretax_salary < 653000){$after_dependant = [56470,50010,43540,37070,30610,24140,18730,15500];
}elseif($after_pretax_salary >= 653000 && $after_pretax_salary < 656000){$after_dependant = [57020,50560,44090,37620,31160,24690,19000,15770];
}elseif($after_pretax_salary >= 656000 && $after_pretax_salary < 659000){$after_dependant = [57570,51110,44640,38180,31710,25240,19280,16050];
}elseif($after_pretax_salary >= 659000 && $after_pretax_salary < 662000){$after_dependant = [58130,51660,45190,38730,32260,25790,19550,16330];
}elseif($after_pretax_salary >= 662000 && $after_pretax_salary < 665000){$after_dependant = [58680,52210,45740,39280,32810,26340,19880,16600];
}elseif($after_pretax_salary >= 665000 && $after_pretax_salary < 668000){$after_dependant = [59230,52770,46290,39830,33370,26890,20430,16880];
}elseif($after_pretax_salary >= 668000 && $after_pretax_salary < 671000){$after_dependant = [59780,53320,46840,40380,33920,27440,20980,17150];
}elseif($after_pretax_salary >= 671000 && $after_pretax_salary < 674000){$after_dependant = [60330,53870,47390,40930,34470,28000,21530,17430];
}elseif($after_pretax_salary >= 674000 && $after_pretax_salary < 677000){$after_dependant = [60880,54420,47950,41480,35020,28550,22080,17700];
}elseif($after_pretax_salary >= 677000 && $after_pretax_salary < 680000){$after_dependant = [61430,54970,48500,42030,35570,29100,22640,17980];
}elseif($after_pretax_salary >= 680000 && $after_pretax_salary < 683000){$after_dependant = [61980,55520,49050,42590,36120,29650,23190,18260];
}elseif($after_pretax_salary >= 683000 && $after_pretax_salary < 686000){$after_dependant = [62540,56070,49600,43140,36670,30200,23740,18530];
}elseif($after_pretax_salary >= 686000 && $after_pretax_salary < 689000){$after_dependant = [63090,56620,50150,43690,37230,30750,24290,18810];
}elseif($after_pretax_salary >= 689000 && $after_pretax_salary < 692000){$after_dependant = [63640,57180,50700,44240,37780,31300,24840,19080];
}elseif($after_pretax_salary >= 692000 && $after_pretax_salary < 695000){$after_dependant = [64190,57730,51250,44790,38330,31860,25390,19360];
}elseif($after_pretax_salary >= 695000 && $after_pretax_salary < 698000){$after_dependant = [64740,58280,51810,45340,38880,32410,25940,19630];
}elseif($after_pretax_salary >= 698000 && $after_pretax_salary < 701000){$after_dependant = [65290,58830,52360,45890,39430,32960,26490,20030];
}elseif($after_pretax_salary >= 701000 && $after_pretax_salary < 704000){$after_dependant = [65840,59380,52910,46450,39980,33510,27050,20580];
}elseif($after_pretax_salary >= 704000 && $after_pretax_salary < 707000){$after_dependant = [66400,59930,53460,47000,40530,34060,27600,21130];
}elseif($after_pretax_salary >= 707000 && $after_pretax_salary < 710000){$after_dependant = [66950,60480,54010,47550,41090,34610,28150,21690];
}elseif($after_pretax_salary >= 710000 && $after_pretax_salary < 713000){$after_dependant = [67500,61040,54560,48100,41640,35160,28700,22240];
}elseif($after_pretax_salary >= 713000 && $after_pretax_salary < 716000){$after_dependant = [68050,61590,55110,48650,42190,35710,29250,22790];
}elseif($after_pretax_salary >= 716000 && $after_pretax_salary < 719000){$after_dependant = [68600,62140,55660,49200,42740,36270,29800,23340];
}elseif($after_pretax_salary >= 719000 && $after_pretax_salary < 722000){$after_dependant = [69150,62690,56220,49750,43290,36820,30350,23890];
}elseif($after_pretax_salary >= 722000 && $after_pretax_salary < 725000){$after_dependant = [69700,63240,56770,50300,43840,37370,30910,24440];
}elseif($after_pretax_salary >= 725000 && $after_pretax_salary < 728000){$after_dependant = [70260,63790,57320,50860,44390,37920,31460,24990];
}elseif($after_pretax_salary >= 728000 && $after_pretax_salary < 731000){$after_dependant = [70810,64340,57870,51410,44940,38470,32010,25550];
}elseif($after_pretax_salary >= 731000 && $after_pretax_salary < 734000){$after_dependant = [71360,64890,58420,51960,45500,39020,32560,26100];
}elseif($after_pretax_salary >= 734000 && $after_pretax_salary < 737000){$after_dependant = [71910,65450,58970,52510,46050,39570,33110,26650];
}elseif($after_pretax_salary >= 737000 && $after_pretax_salary < 740000){$after_dependant = [72460,66000,59520,53060,46600,40130,33660,27200];
}elseif($after_pretax_salary >= 740000 && $after_pretax_salary < 743000){$after_dependant = [73010,66550,60080,53610,47150,40680,34210,27750];
}elseif($after_pretax_salary >= 743000 && $after_pretax_salary < 746000){$after_dependant = [73560,67100,60630,54160,47700,41230,34770,28300];
}elseif($after_pretax_salary >= 746000 && $after_pretax_salary < 749000){$after_dependant = [74110,67650,61180,54720,48250,41780,35320,28850];
}elseif($after_pretax_salary >= 749000 && $after_pretax_salary < 752000){$after_dependant = [74670,68200,61730,55270,48800,42330,35870,29400];
}elseif($after_pretax_salary >= 752000 && $after_pretax_salary < 755000){$after_dependant = [75220,68750,62280,55820,49360,42880,36420,29960];
}elseif($after_pretax_salary >= 755000 && $after_pretax_salary < 758000){$after_dependant = [75770,69310,62830,56370,49910,43430,36970,30510];
}elseif($after_pretax_salary >= 758000 && $after_pretax_salary < 761000){$after_dependant = [76320,69860,63380,56920,50460,43980,37520,31060];
}elseif($after_pretax_salary >= 761000 && $after_pretax_salary < 764000){$after_dependant = [76870,70410,63940,57470,51010,44540,38070,31610];
}elseif($after_pretax_salary >= 764000 && $after_pretax_salary < 767000){$after_dependant = [77420,70960,64490,58020,51560,45090,38620,32160];
}elseif($after_pretax_salary >= 767000 && $after_pretax_salary < 770000){$after_dependant = [77970,71510,65040,58570,52110,45640,39180,32710];
}elseif($after_pretax_salary >= 770000 && $after_pretax_salary < 773000){$after_dependant = [78530,72060,65590,59130,52660,46190,39730,33260];
}elseif($after_pretax_salary >= 773000 && $after_pretax_salary < 776000){$after_dependant = [79080,72610,66140,59680,53210,46740,40280,33820];
}elseif($after_pretax_salary >= 776000 && $after_pretax_salary < 779000){$after_dependant = [79630,73160,66690,60230,53770,47290,40830,34370];
}elseif($after_pretax_salary >= 779000 && $after_pretax_salary < 782000){$after_dependant = [80180,73720,67240,60780,54320,47840,41380,34920];
}elseif($after_pretax_salary >= 782000 && $after_pretax_salary < 785000){$after_dependant = [80730,74270,67790,61330,54870,48400,41930,35470];
}elseif($after_pretax_salary >= 785000 && $after_pretax_salary < 788000){$after_dependant = [81280,74820,68350,61880,55420,48950,42480,36020];
}elseif($after_pretax_salary >= 788000 && $after_pretax_salary < 791000){$after_dependant = [81830,75370,68900,62430,55970,49500,43040,36570];
}elseif($after_pretax_salary >= 791000 && $after_pretax_salary < 794000){$after_dependant = [82460,75920,69450,62990,56520,50050,43590,37120];
}elseif($after_pretax_salary >= 794000 && $after_pretax_salary < 797000){$after_dependant = [83100,76470,70000,63540,57070,50600,44140,37670];
}elseif($after_pretax_salary >= 797000 && $after_pretax_salary < 800000){$after_dependant = [83730,77020,70550,64090,57630,51150,44690,38230];
}elseif($after_pretax_salary >= 800000 && $after_pretax_salary < 803000){$after_dependant = [84370,77580,71100,64640,58180,51700,45240,38780];
}elseif($after_pretax_salary >= 803000 && $after_pretax_salary < 806000){$after_dependant = [85000,78130,71650,65190,58730,52250,45790,39330];
}elseif($after_pretax_salary >= 806000 && $after_pretax_salary < 809000){$after_dependant = [85630,78680,72210,65740,59280,52810,46340,39880];
}elseif($after_pretax_salary >= 809000 && $after_pretax_salary < 812000){$after_dependant = [86260,79230,72760,66290,59830,53360,46890,40430];
}elseif($after_pretax_salary >= 812000 && $after_pretax_salary < 815000){$after_dependant = [86900,79780,73310,66840,60380,53910,47450,40980];
}elseif($after_pretax_salary >= 815000 && $after_pretax_salary < 818000){$after_dependant = [87530,80330,73860,67400,60930,54460,48000,41530];
}elseif($after_pretax_salary >= 818000 && $after_pretax_salary < 821000){$after_dependant = [88160,80880,74410,67950,61480,55010,48550,42090];
}elseif($after_pretax_salary >= 821000 && $after_pretax_salary < 824000){$after_dependant = [88800,81430,74960,68500,62040,55560,49100,42640];
}elseif($after_pretax_salary >= 824000 && $after_pretax_salary < 827000){$after_dependant = [89440,82000,75510,69050,62590,56110,49650,43190];
}elseif($after_pretax_salary >= 827000 && $after_pretax_salary < 830000){$after_dependant = [90070,82630,76060,69600,63140,56670,50200,43740];
}elseif($after_pretax_salary >= 830000 && $after_pretax_salary < 833000){$after_dependant = [90710,83260,76620,70150,63690,57220,50750,44290];
}elseif($after_pretax_salary >= 833000 && $after_pretax_salary < 836000){$after_dependant = [91360,83930,77200,70720,64260,57800,51330,44860];
}elseif($after_pretax_salary >= 836000 && $after_pretax_salary < 839000){$after_dependant = [92060,84630,77810,71340,64870,58410,51940,45480];
}elseif($after_pretax_salary >= 839000 && $after_pretax_salary < 842000){$after_dependant = [92770,85340,78420,71950,65490,59020,52550,46090];
}elseif($after_pretax_salary >= 842000 && $after_pretax_salary < 845000){$after_dependant = [93470,86040,79040,72560,66100,59640,53160,46700];
}elseif($after_pretax_salary >= 845000 && $after_pretax_salary < 848000){$after_dependant = [94180,86740,79650,73180,66710,60250,53780,47310];
}elseif($after_pretax_salary >= 848000 && $after_pretax_salary < 851000){$after_dependant = [94880,87450,80260,73790,67320,60860,54390,47930];
}elseif($after_pretax_salary >= 851000 && $after_pretax_salary < 854000){$after_dependant = [95590,88150,80870,74400,67940,61470,55000,48540];
}elseif($after_pretax_salary >= 854000 && $after_pretax_salary < 857000){$after_dependant = [96290,88860,81490,75010,68550,62090,55610,49150];
}elseif($after_pretax_salary >= 857000 && $after_pretax_salary < 860000){$after_dependant = [97000,89560,82130,75630,69160,62700,56230,49760];
}elseif($after_pretax_salary == 860000){$after_dependant = [97350,89920,82480,75930,69470,63010,56530,50070];
}elseif($after_pretax_salary > 860000 && $after_pretax_salary < 970000){
$delta_hightax = floor(($after_pretax_salary - 860000) * 0.23483);
$after_dependant = [97350+$delta_hightax,89920+$delta_hightax,82480+$delta_hightax,75930+$delta_hightax,69470+$delta_hightax,63010+$delta_hightax,56530+$delta_hightax,50070+$delta_hightax];
}elseif($after_pretax_salary == 970000){$after_dependant = [123190,115760,108320,101770,95310,88850,82370,75910];
}elseif($after_pretax_salary > 970000 && $after_pretax_salary < 1720000){
$delta_hightax = floor(($after_pretax_salary - 970000) * 0.33693);
$after_dependant = [123190+$delta_hightax,115760+$delta_hightax,108320+$delta_hightax,101770+$delta_hightax,95310+$delta_hightax,88850+$delta_hightax,82370+$delta_hightax,75910+$delta_hightax];
}elseif($after_pretax_salary == 1720000){$after_dependant = [375890,368460,361020,354470,348010,341550,335070,328610];
}elseif($after_pretax_salary > 1720000 && $after_pretax_salary < 3550000){
$delta_hightax = floor(($after_pretax_salary - 1720000) * 0.4084);
$after_dependant = [123190+$delta_hightax,115760+$delta_hightax,108320+$delta_hightax,101770+$delta_hightax,95310+$delta_hightax,88850+$delta_hightax,82370+$delta_hightax,75910+$delta_hightax];
}elseif($after_pretax_salary == 3550000){$after_dependant = [1123270,1115840,1108400,1101850,1095390,1088930,1082450,1075990];
}elseif($after_pretax_salary > 3550000){
$delta_hightax = floor(($after_pretax_salary - 3550000) * 0.45945);
$after_dependant = [1123270+$delta_hightax,1115840+$delta_hightax,1108400+$delta_hightax,1101850+$delta_hightax,1095390+$delta_hightax,1088930+$delta_hightax,1082450+$delta_hightax,1075990+$delta_hightax];
}

// 導入後：社保控除後の金額に応じた源泉徴収額の計算 終了----------------------



// 導入後：扶養家族に応じた源泉徴収額の計算 開始----------------------
if($dependants == 0 ){
  $after_income_tax =  $after_dependant[0];
}elseif($dependants == 1 ){
  $after_income_tax =  $after_dependant[1];
}elseif($dependants == 2 ){
  $after_income_tax =  $after_dependant[2];
}elseif($dependants == 3 ){
  $after_income_tax =  $after_dependant[3];
}elseif($dependants == 4 ){
  $after_income_tax =  $after_dependant[4];
}elseif($dependants == 5 ){
  $after_income_tax =  $after_dependant[5];
}elseif($dependants == 6 ){
  $after_income_tax =  $after_dependant[6];
}else{
  $after_income_tax =  $after_dependant[7];
}
// 導入後：扶養家族に応じた源泉徴収額の計算 終了----------------------


// 導入後：住民税の計算 開始-----------------------------------------
  // 給与所得控除の計算
  if($after_yearly_income < 1625000){
    $after_salary_deduction = 650000;
  }elseif($after_yearly_income >= 1625000 && $after_yearly_income <= 1800000){
    $after_salary_deduction = $after_yearly_income * 0.4;
  }elseif($after_yearly_income > 1800000 && $after_yearly_income <= 3600000){
    $after_salary_deduction = $after_yearly_income * 0.3 + 180000;
  }elseif($after_yearly_income > 3600000 && $after_yearly_income <= 6600000){
    $after_salary_deduction = $after_yearly_income * 0.2 + 540000;
  }elseif($after_yearly_income > 6600000 && $after_yearly_income <= 10000000){
    $after_salary_deduction = $after_yearly_income * 0.1 + 1200000;
  }elseif($after_yearly_income > 1625000 && $after_yearly_income <= 1800000){
    $after_salary_deduction = $after_yearly_income * 0.4;
  }else{
    $after_salary_deduction = 2200000;
  }

  //所得控除
  $after_income_deduction = $basic_deduction + $partner_deduction + $dependant_deduction + $after_social_insurance_total;

  // 住民税計算用の課税対象金額の計算
  $after_inhabitant_tax_yearly = $after_yearly_income - $after_salary_deduction - $after_income_deduction ;

  // 月額住民税の計算
  $after_inhabitant_tax = floor($after_inhabitant_tax_yearly / 120);

// 導入後：住民税の計算 終了-----------------------------------------

// 導入後：社保、税金、家賃控除後の可処分所得の計算
  $after_disposable_income = $after_salary - $after_health_insurance_expense - $after_pension_premiums - $after_income_tax - $after_inhabitant_tax - $rest_payment;

// 導入前後の比較
  // 所得税比較
  $delta_income_tax = $before_income_tax - $after_income_tax;

  // 社会保険料比較
  $delta_social_insurance = floor(($before_social_insurance_total - $after_social_insurance_total) / 12);

  // 住民税比較
  $delta_inhabitant_tax = $before_inhabitant_tax - $after_inhabitant_tax;

  // 導入前、導入後の可処分所得の増加分の計算
  $effect = $after_disposable_income - $before_disposable_income;
  $effect_recalculation = $delta_income_tax + $delta_social_insurance + $delta_inhabitant_tax;

// LINE上に表示する数値を配列に代入
  // 基本情報、事前計算関連
  $calculation[] = $ages;                                   // [0] 年齢
  $calculation[] = $partner;                                // [1] 配偶者
  $calculation[] = $dependants;                             // [2] 扶養家族
  $calculation[] = $location;                               // [3] 勤務地の都道府県
  $calculation[] = $houserent;                              // [4] 家賃
  $calculation[] = $space;                                  // [5] 広さ
  $calculation[] = $housebenefit;                           // [6] 都道府県毎の住宅利益
  $calculation[] = $in_kind_as_house;                       // [7] 現物支給額

  $calculation[] = $before_salary;                          // [8] 導入前：月額給与
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
  $calculation[] = $before_inhabitant_tax_yearly / 12;      // [28] 住民税年額
  $calculation[] = $before_inhabitant_tax;                  // [29] 住民税月額

  $calculation[] = $delta_income_tax;                       // [30]所得税差分
  $calculation[] = $delta_social_insurance;                 // [31]社会保険料差分
  $calculation[] = $delta_inhabitant_tax;                   // [32]住民税差分
  $calculation[] = $effect_recalculation = $delta_income_tax + $delta_social_insurance + $delta_inhabitant_tax; // [33]可処分所得増加分の検算

// ユーザーにシミュレーション結果等を返信
  $message0 = "【シミュレーション結果】\n\nスマートサラリーを導入すると最大で毎月$calculation[25]円多く手元に残るようになります。\n\n内訳\n・1ヶ月後 所得税分Start\n→　$calculation[30]円 UP!\n・4ヶ月後 社会保険分Start\n→　$calculation[31]円 UP!\n・翌年度以降 住民税分Start\n→最大　$calculation[32]円 UP!\n\n※1:住民税分は導入時期によって変動します。\n※2:簡易シミュレーションのため、実際の数値とは多少の誤差が発生します。";

  $message1 = "【基本情報】\n\n年齢：$calculation[0]歳\n配偶者：$calculation[1]\n扶養家族：$calculation[2]人\n勤務地の都道府県：$calculation[3]\n\n家賃：$calculation[4]円\n自宅の居住空間の広さ：$calculation[5]畳\n$calculation[4]の住宅利益：1畳あたり$calculation[6]円\n現物支給額換算：$calculation[7]円";

  $message2 = "【スマートサラリー導入前】\n\n月額給与：$calculation[8]円\n年間賞与：$calculation[9]円\n年収：$calculation[10]円\n\n健康保険料：$calculation[11]円\n厚生年金保険料：$calculation[12]円\n所得税：$calculation[13]円\n住民税：$calculation[14]円\n社保、税金、家賃控除後の可処分所得：$calculation[15]円";

  $message3 = "【スマートサラリー導入後】\n\n会社負担家賃（家賃×0.8）：$calculation[16]円\n本人負担家賃（家賃×0.2）：$calculation[17]円\n\n月額給与：$calculation[18]円\n年間賞与：$calculation[9]円\n年収：$calculation[19] 円\n\n健康保険料：$calculation[20]円\n厚生年金保険料：$calculation[21]円\n所得税：$calculation[22]円\n住民税：$calculation[23]円\n社保、税金、家賃控除後の可処分所得：$calculation[24]円\n\nスマートサラリー導入効果：$calculation[25]円\n";

  $message4 = "【開発確認用パラメータ】\n\n年収：$calculation[10]円\n給与所得控除：$calculation[26]円\n所得控除：$calculation[27]円\n住民税年額：$calculation[28]円\n住民税月額：$calculation[29]円\n\n所得税差分：$calculation[30]円\n社会保険料差分：$calculation[31]円\n住民税差分：$calculation[32]円\n可処分所得増加分の検算：$calculation[33]円";

  // メッセージをユーザーに返信
$bot->replyText($event->getReplyToken(), $message0, $message1, $message2, $message3, $message4);

}

?>
