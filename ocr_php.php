<?php
/**
 * 実行方法
 * php ocr.php -i 画像ファイル -k 'L,T,W,H' -r 'L,T,W,H'
 *
 * オプション
 * -i
 * 画像ファイル
 *
 * -k
 * 画像にフィルターをかける。
 * 引数L,T,W,Hは0-1の値をいれ、フィルターの範囲を
 * 設定する
 *
 * -r
 * フィルター内の文字を消去
 * 引数L,T,W,Hは0-1の値をいれ、フィルターの範囲を
 * 設定する
 *
 * [$apiKey description]
 * @var string
 *
 */

//各種オプションの設定
$opt = getopt('hi:k:r:help::');//-h -help -i −ｋ −ｒオプション定義
$dec = 0;

$options = [
    "keep" => ["left" => NULL, "top" => NULL, "width" => NULL, "height" => NULL],
    "reject" => ["left" => NULL, "top" => NULL, "width" => NULL, "height" => NULL]
];

//ヘルプ
$helpopts = "\nUsage: php [ProgramName].php [options]\n\nexample:\nphp [ProgramName].php [-i ImageFile] [-k 'Left,Top,Width,Height'] [-r 'Left,Top,Width,Height']\nphp [ProgramName].php [-k 'Left,Top,Width,Height'] [-i ImageFile] [-r 'Left,Top,Width,Height']\n
options:\n-i  ImageFile :画像ファイル\n-k 'Left,Top,Width,Height' :画像のフィルター範囲を設定 'Left,Top,Width,Height'フィルターの基準となる座標を0-1の値で指定し位置を決める\n-r 'Left,Top,Width,Height' :画像のフィルター範囲を設定しフィルター内の文字を破棄 'Left,Top,Width,Height'フィルターの基準となる座標を0-1の値で指定し位置を決める\n";

//-h -help オプションがあれば
if (isset($opt["h"]) || isset($opt["help"])) {
    echo $helpopts;
    exit(0);
}

// −i オプションがあれば
if (isset($opt["i"])) {
    //画像かどうか
    if (preg_match('/gif$|png$|jpg$|jpeg$/i', $opt["i"])) {
        $imageNm = $opt["i"];
    } else {
        echo "拡張子 .gif , .png , .jpg , .jpeg で指定してください\n";
        exit(1);
    }

    // −ｋオプションがあれば
    if (isset($opt["k"])) {
        $k = explode(',', $opt["k"]); //文字列を','で区切る

        //0−1の英数字以外入力するとエラー
        foreach ($k as $key => $value) {
            if (preg_match('/^[a-zA-Z2-9]+$/', $value)) {
                echo $helpopts;
                exit(1);
            }
        }

        foreach ($k as $key => &$value) {
            $value = floatval($value); //文字列をfloat型に変換
        }
        unset($value);//最後の要素への参照を解除

        if (isset($k[0])){
            $options["keep"]["left"] = $k[0];
        } elseif ($options["keep"]["left"] == NULL) {
            echo "'Left','Top','Width','Height'の値が指定されていません。\n";
            exit(1);
        }
        if (isset($k[1])) {
            $options["keep"]["top"] = $k[1];
        } elseif ($options["keep"]["top"] == NULL) {
            echo "Top,Width,Heightの値が指定されていません。\n";
            exit(1);
        }
        if (isset($k[2])) {
            $options["keep"]["width"] = $k[2];
        } elseif ($options["keep"]["width"] == NULL) {
            echo "Width,Heightの値が指定されていません。\n";
            exit(1);
        }
        if (isset($k[3])) {
            $options["keep"]["height"] = $k[3];
        } elseif ($options["keep"]["height"] == NULL) {
            echo "Heightの値が指定されていません。\n";
            exit(1);
        }

        // −rオプションがあれば
        if (isset($opt["r"])) {
            $r = explode(',', $opt["r"]);//文字列を','で区切る

            foreach ($r as $key => $value) {
                //0-1の英数字以外はじく
                if (preg_match('/^[a-zA-Z2-9]+$/', $value)) {
                    echo "$helpopts";
                    exit(1);
                }
            }
            foreach ($r as $key => &$value) {
                $value = floatval($value); //文字列をfloat型に変換
            }

            unset($value);//最後の要素への参照を解除

            if (isset($r[0])) {
                $options["reject"]["left"] = $r[0];
            }elseif ($options["reject"]["left"] == NULL) {
                echo "'Left','Top','Width','Height'の値が指定されていません。\n";
                exit(1);
            }
            if (isset($r[1])) {
                $options["reject"]["top"] = $r[1];
            }elseif ($options["reject"]["top"] == NULL) {
                echo "'Top','Width','Height'の値が指定されていません。\n";
                exit(1);
            }
            if (isset($r[2])) {
                $options["reject"]["width"] = $r[2];
            }elseif ($options["reject"]["width"] == NULL) {
                echo "'Width','Height'の値が指定されていません。\n";
                exit(1);
            }
            if (isset($r[3])) {
                $options["reject"]["height"] = $r[3];
            }elseif ($options["reject"]["height"] == NULL) {
                echo "'Height'の値が指定されていません。\n";
                exit(1);
            }
        }
    }elseif (!isset($opt["k"]) && isset($opt["i"])){
        $imageNm = $opt["i"];
        $dec = 1;
    }
  // オプションがない場合
} elseif (isset($argv[1])) { 
    if (!(preg_match('/gif$|png$|jpg$|jpeg$/i', $argv[1]))) {
        echo "画像ファイルを指定してください\nまたは拡張子が間違っています\n拡張子 .gif , .png , .jpg , .jpeg で指定してください\n";
        exit(1);
    } else {
        $imageNm = $argv[1];
        $dec = 1;
    }
}
$apiKey = "APIアクセスキー"; //アクセスキー

// リクエスト用json作成
$json = json_encode(array(
    "requests" => array(
        array(
            "image" => array(
                "content" => base64_encode(file_get_contents($imageNm)),
            ),
            "features" => array(
                array(
                    "type" => "TEXT_DETECTION",
                    "maxResults" => 10,
                ),
            ),
        ),
    ),
));

    $curl = curl_init(); //cURLセッションの初期化
    curl_setopt($curl, CURLOPT_URL, "https://vision.googleapis.com/v1/images:annotate?key=" . $apiKey); // Google Cloud Vision APIのURLを設定
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // curl_execの結果を文字列で取得
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // サーバ証明書の検証を行わない
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST"); // POSTでリクエストする
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json")); // 送信するHTTPヘッダーの設定
    curl_setopt($curl, CURLOPT_TIMEOUT, 15); // タイムアウト時間の設定（秒）
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json); // 送信するjsonデータを設定

    // curl実行
    $res = curl_exec($curl);
    $data = json_decode($res, true);
    curl_close($curl);


    if ($dec == 1) {
        print $data["responses"][0]["fullTextAnnotation"]["text"];
        exit(0);
    }

// 字幕フィルター
    $width = $data["responses"][0]["fullTextAnnotation"]["pages"][0]["width"];
    $height = $data["responses"][0]["fullTextAnnotation"]["pages"][0]["height"];
    echo "画像サイズ：w=$width ,h=$height \n";

    //Keepの設定
    $L = $options["keep"]["left"];  //"keep" -L オプション
    $T = $options["keep"]["top"];  //"keep" -T オプション
    $W = $options["keep"]["width"];  //"keep" -W オプション
    $H = $options["keep"]["height"];  //"keep" -H オプション
    //フィルターの座標設定
    $kpLeft = $width * $L;
    $kpTop = $height * $T;

    //LとW,TとHの和が１を超えるとき
    $exceed = $L + $W + $T + $H;
    if($exceed <= 2) {
        $kpWidth = $width * ($L + $W);  //width幅を決める
        $kpHeight = $height * ($T + $H);  //heightの高さを決める
        echo "L=$L , T=$T , W=$W , H=$H\n";
    }else{//LとW,TとHの和が１を超えるとき
        echo "画像範囲を超えています。\nLとWの和、TとHの和を1以下にしてください\n";
        echo "例: -k '0,1,1,0' -r '0.2,0.6,0.8,0.4'\n";
        exit(1);
    }

    //Rejectの設定
    $L = $options["reject"]["left"];  //"reject" -L オプション
    $T = $options["reject"]["top"];  //"reject" -T オプション
    $W = $options["reject"]["width"];  //"reject" -W オプション
    $H = $options["reject"]["height"];  //"reject" -H オプション
    //フィルターの座標設定
    $reLeft = $width * $L;
    $reTop = $height * $T;

    //LとW,TとHの和が１を超えるとき
    $exceed = $L + $W + $T + $H;
    if($exceed <= 2) {
        $reWidth = $width * ($L + $W); //width幅を決める
        $reHeight = $height * ($T + $H);//heightの高さを決める
    } else{
        echo "画像範囲を超えています。\nLとWの和、TとHの和を1以下にしてください\n";
        echo "例: -k '0,1,1,0' -r '0.2,0.6,0.8,0.4'\n";
        exit(1);
    }
    echo "L=$L , T=$T , W=$W , H=$H\n";

    echo "kpLEFT=$kpLeft , kpTOP=$kpTop , kpRIGHT=$kpWidth , kpBOTTOM=$kpHeight\n";
    echo "reLEFT=$reLeft , reTOP=$reTop , reWidth=$reWidth , reHeight=$reHeight\n\n";
    $blocks = sizeof($data["responses"][0]["fullTextAnnotation"]["pages"][0]["blocks"]);//blocksの要素数を取得
    // blocksの要素の数だけループ
    for ($i = 0; $blocks - 1 >= $i; $i++) {
        $words = sizeof($data["responses"][0]["fullTextAnnotation"]["pages"][0]["blocks"][$i]["paragraphs"][0]["words"]);//wordsの要素数を取得
        // wordsの要素の数だけループ
        for ($j = 0; $words - 1 >= $j; $j++) {
            $symbols = sizeof($data["responses"][0]["fullTextAnnotation"]["pages"][0]["blocks"][$i]["paragraphs"][0]["words"][$j]["symbols"]);//symbolsの要素数を取得
            // symbolsの要素の数だけループ
            for ($k = 0; $symbols - 1 >= $k; $k++) {

                //文字のｘ、ｙ座標を取得する
                //文字の配列[0]番目のｘ座標を取得
                $X = $data["responses"][0]["fullTextAnnotation"]["pages"][0]["blocks"][$i]["paragraphs"][0]["words"][$j]["symbols"][$k]["boundingBox"]["vertices"][0]["x"];
                //文字の配列[0]番目のｙ座標を取得
                $Y = $data["responses"][0]["fullTextAnnotation"]["pages"][0]["blocks"][$i]["paragraphs"][0]["words"][$j]["symbols"][$k]["boundingBox"]["vertices"][0]["y"];
                //文字の配列[2]番目のｘ座標を取得
                $addX = $data["responses"][0]["fullTextAnnotation"]["pages"][0]["blocks"][$i]["paragraphs"][0]["words"][$j]["symbols"][$k]["boundingBox"]["vertices"][2]["x"];
                //文字の配列[2]番目のｙ座標を取得
                $addY = $data["responses"][0]["fullTextAnnotation"]["pages"][0]["blocks"][$i]["paragraphs"][0]["words"][$j]["symbols"][$k]["boundingBox"]["vertices"][2]["y"];
                // 文字の取得
                $ocrtext = $data["responses"][0]["fullTextAnnotation"]["pages"][0]["blocks"][$i]["paragraphs"][0]["words"][$j]["symbols"][$k]["text"];

                //$deb = array($ocrtext => $data["responses"][0]["fullTextAnnotation"]["pages"][0]["blocks"][$i]["paragraphs"][0]["words"][$j]["symbols"][$k]["boundingBox"]["vertices"]);//文字と座標の紐付け（デバック用)

                //選択フィルター処理
                //選択フィルター外の文字を消去
                if ($kpLeft < $X && $X < $kpWidth && $kpTop < $Y && $Y < $kpHeight && $kpLeft < $addX && $addX < $kpWidth && $kpTop < $addY && $addY < $kpHeight) {
                    //オプション -r があれば
                    if (isset($opt["r"])) {
                        if (!($reLeft < $X && $addX < $reWidth && $reTop < $Y && $addY < $reHeight)) {
                            print_r($ocrtext);
                        }
                    } else {
                        print_r($ocrtext);
                    }
                }
            }
        }
    }
    echo "\n";