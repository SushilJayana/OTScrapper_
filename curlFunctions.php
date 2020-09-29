<?php

function curl($url, $type = "get", $fields = null)
{
    try {

        $header = getRandomHeaders();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . "/cacert.pem");
//        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_CERTINFO, 1);

        if ($type === "post") {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        }

        $response = curl_exec($ch);
        $err = curl_errno($ch);
        $err_message = curl_error($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        logInfo('Response :' . $responseCode . ' ,URL :'. $url. ',TYPE :'. $type);

        if ($err) logError('Error : curl, ErrCode: '. $err. ',Message : '. $err_message);

        return $response;

    } catch (\Exception $e) {
        logError('Exception : curl, ErrCode: '. $e->getCode(). ' ,Message : '. $e->getMessage());
        return null;
    }
}

function logError($error)
{
    $file = fopen(__DIR__ . "/logs/error.log", "a+");
    if (!$file) {
        echo('Error in opening new file');
        exit;
    }
    fwrite($file, date("Y-m-d H:i:s") . ' ERROR ' . $error . "\n");
    fclose($file);
}


function logInfo($info)
{
    $file = fopen(__DIR__ . "/logs/info.log", "a+");
    if (!$file) {
        echo('Error in opening new file');
        exit;
    }
    fwrite($file, date("Y-m-d H:i:s") . ' INFO ' . $info . "\n");
    fclose($file);
}


function getRandomHeaders()
{
    $arr_user_agent = array(
        'user-agent: Mozilla/5.0 (compatible; U; ABrowse 0.6; Syllable) AppleWebKit/420+ (KHTML, like Gecko)',
        'user-agent: Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; Acoo Browser 1.98.744; .NET CLR 3.5.30729)',
        'user-agent: Mozilla/4.0 (compatible; MSIE 7.0; America Online Browser 1.1; Windows NT 5.1; (R1 1.5); .NET CLR 2.0.50727; InfoPath.1)',
        'user-agent: AmigaVoyager/3.2 (AmigaOS/MC680x0)',
        'user-agent: Mozilla/5.0 (compatible; MSIE 9.0; AOL 9.7; AOLBuild 4343.19; Windows NT 6.1; WOW64; Trident/5.0; FunWebProducts)',
        'user-agent: Mozilla/5.0 (X11; U; UNICOS lcLinux; en-US) Gecko/20140730 (KHTML, like Gecko, Safari/419.3) Arora/0.8.0',
        'user-agent: Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; Avant Browser; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)',
        'user-agent: Mozilla/5.0 (Windows; U; WinNT; en; rv:1.0.2) Gecko/20030311 Beonex/0.8.2-stable',
        'user-agent: Mozilla/5.0 (X11; U; Linux i686; nl; rv:1.8.1b2) Gecko/20060821 BonEcho/2.0b2 (Debian-1.99+2.0b2+dfsg-1)',
        'user-agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Trident/4.0; .NET4.0C; .NET4.0E; .NET CLR 2.0.50727; .NET CLR 1.1.4322; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; Browzar)',
        'user-agent: Mozilla/5.0 (Macintosh; U; PPC Mac OS X Mach-O; XH; rv:8.578.498) fr, Gecko/20121021 Camino/8.723+ (Firefox compatible)',
        'user-agent: Mozilla/4.08 (Charon; Inferno)',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36',
        'user-agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/532.2 (KHTML, like Gecko) ChromePlus/4.0.222.3 Chrome/4.0.222.3 Safari/532.2',
        'user-agent: Mozilla/5.0 (Macintosh; U; PPC; en-US; mimic; rv:9.3.0) Gecko/20120117 Firefox/3.6.25 Classilla/CFM',
        'user-agent: Mozilla/5.0 (Windows NT 6.2) AppleWebKit/535.7 (KHTML, like Gecko) Comodo_Dragon/16.1.1.0 Chrome/16.0.912.63 Safari/535.7',
        'user-agent: Mozilla/5.0 (X11; Linux x86_64; rv:10.0.11) Gecko/20100101 conkeror/1.0pre (Debian-1.0~~pre+git120527-1)',
        'user-agent: Opera/9.80 (X11; Linux i686; Ubuntu/14.10) Presto/2.12.388 Version/12.16.2',
        'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A',
        'user-agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; x64; fr; rv:1.9.2.13) Gecko/20101203 Firebird/3.6.13',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.19582');

    $arr_referer = array(
        'Referer : https://www.bing.com/',
        'Referer : https://yandex.com/',
        'Referer : https://duckduckgo.com/',
        'Referer : https://swisscows.com/',
        'Referer : https://www.google.com.au/',
        'Referer : https://www.onesearch.com/'
    );


    $user_agent = array_rand($arr_user_agent, 1);
    $referer = array_rand($arr_referer, 1);


    return array(
        'accept-language: en-Us',
        $arr_user_agent[$user_agent],
        $arr_referer[$referer]
    );
}
