<?php
error_reporting(0);
$list_proxy = array_filter(@explode("\n", str_replace(array("\r", " "), "", @file_get_contents(readline("[?] List Proxy       ")))));
$reff = readline("[?] Referral      ");
$list_query = array_filter(@explode("\n", str_replace(array("\r", " "), "", @file_get_contents(readline("[?] List Query       ")))));
echo "[*] Total Query : ".count($list_query)."\n";
Awal:
for ($i = 0; $i < count($list_query); $i++) {
    $c = $i + 1;
    echo "\n[$c]\n";
    $rand_proxy = array_rand($list_proxy, 1);
    $proxy = $list_proxy[$rand_proxy];
    echo "[*] Proxy : ".proxy($proxy)."\n";
    if(empty($reff)){
        $auth = get_auth($list_query[$i], $proxy);
    }
    else{
        $auth = get_auth($list_query[$i], $reff, $proxy);
    }
    echo "[*] Get Auth : ";
    if($auth){
        echo "success\n";
        $daily = check_daily($auth, $proxy);
        $start_farm = start_farm($auth, $proxy);
        echo "[*] Start Farm : $start_farm\n";
        Play:
        $play_game_count = get_info($auth, $proxy)['play_passes'];
        echo "[*] Play Game Count : $play_game_count\n";
        if($play_game_count > 0){
            echo "\n";
            for ($a = 0; $a < $play_game_count; $a++) {
                $c = $a + 1;
                echo "[-] Play Game $c\n";
                $play = play_game($auth, $proxy);
                if($play){
                    echo "\t[>] Game ID     : $play\n";
                    $time = claim_game($auth, $proxy)['need_time'] + 3;
                    echo "\t[>] Wait Time   : $time\n";
                    sleep($time);
                    echo "\t[>] Total Point : ".claim_game($auth, $proxy)['points']."\n";
                    sleep(5);
                }
                else{
                    goto Play;
                }
            }
        }
        $task = get_task($auth, $query, $proxy);
        echo "[*] Get Task : ";
        if($task){
            echo "success\n\n";
            for ($a = 0; $a < count($task); $a++) {
                $ex = explode("|", $task[$a]);
                echo "[-] ".$ex[1]."\n";
                echo "\t[>] Start Task : ".start_task($ex[0], $auth, $list_query[$i], $proxy)."\n";
                sleep(5);
            }
            sleep(35);
            for ($a = 0; $a < count($task); $a++) {
                $ex = explode("|", $task[$a]);
                echo "[-] ".$ex[1]."\n";
                echo "\t[>] Check Task : ".check_task($ex[0], $auth, $list_query[$i], $proxy)."\n";
                echo "\t[>] Claim Task : ".claim_task($ex[0], $auth, $proxy)."\n";
            }
        }
        else{
            echo "failed\n\n";
        }
        $claim_farm = claim_farm($auth, $proxy);
        echo "\n[*] Claim Farm : $claim_farm\n";
        $info = get_info($auth, $proxy);
        echo "[*] Balance : ".$info['available_balance']."\n";
    }
    else{
        echo "failed\n\n";
    }
}
echo "\n[*] All Done!, Waiting 180 min\n";
sleep(10800);
goto Awal;





function get_auth($query, $reff = false, $proxy = false){
    if($reff){
        $curl = curl("user/login", false, '{"init_data":"'.$query.'","invite_code":"'.$reff.'","from":"","is_bot":false}', $proxy)['data']['access_token'];
    }
    else{
        $curl = curl("user/login", false, '{"init_data":"'.$query.'","invite_code":"","from":"","is_bot":false}', $proxy)['data']['access_token'];
    }
    return $curl;
}

function check_daily($auth, $proxy = false){
    $curl = curl("daily/claim", $auth, '{"game_id":"fa873d13-d831-4d6f-8aee-9cff7a1d0db1"}', $proxy);
    return $curl;
}

function start_farm($auth, $proxy = false){
    $curl = curl("farm/start", $auth, '{"game_id":"53b22103-c7ff-413d-bc63-20f6fb806a07"}', $proxy)['data']['round_id'];
    return $curl;
}

function get_info($auth, $proxy = false){
    $curl = curl("user/balance", $auth, "{}", $proxy)['data'];
    return $curl;
}

function play_game($auth, $proxy = false){
    $curl = curl("game/play", $auth, '{"game_id":"59bcd12e-04e2-404c-a172-311a0084587d"}', $proxy)['data']['round_id'];
    return $curl;
}

function claim_game($auth, $proxy = false){
    $point = rand(200, 350);
    $curl = curl("game/claim", $auth, '{"game_id":"59bcd12e-04e2-404c-a172-311a0084587d","points":'.$point.'}', $proxy)['data'];
    return $curl;
}

function get_task($auth, $query, $proxy =false){
    $curl = curl("tasks/list", $auth, '{"language_code":"id","init_data":"'.$query.'"}', $proxy)['data'];
    $base = array_keys($curl);
    for ($i = 0; $i < count($base); $i++) {
        $get = $curl[$base[$i]];
        for ($j = 0; $j < count($get); $j++) {
            $list[] = $get[$j]['taskId']."|".$get[$j]['title'];
        }
    }
    return $list;
}

function start_task($id, $auth, $query, $proxy = false){
    $curl = curl("tasks/start", $auth, '{"task_id":'.$id.',"init_data":"'.$query.'"}', $proxy);
    if($curl['data']['status'] == 1){
        $final = "success";
    }
    elseif($curl['message']){
        $final = $curl['message'];
    }
    else{
        $final = "failed";
    }
    return $final;
}

function check_task($id, $auth, $query, $proxy = false){
    $curl = curl("tasks/check", $auth, '{"task_id":'.$id.',"init_data":"'.$query.'"}', $proxy);
    if($curl['data']['status'] == 1){
        $final = "waiting";
    }
    elseif($curl['data']['status'] == 2){
        $final = "ready to claim";
    }
    elseif($curl['message']){
        $final = $curl['message'];
    }
    else{
        $final = "failed";
    }
    return $final;
}

function claim_task($id, $auth, $proxy){
    $curl = curl("tasks/claim", $auth, '{"task_id":'.$id.'}', $proxy);
    if($curl['data']){
        $final = $curl['data'];
    }
    elseif($curl['message']){
        $final = $curl['message'];
    }
    else{
        $final = "failed";
    }
    return $final;
}

function claim_farm($auth, $proxy = false){
    $curl = curl("farm/claim", $auth, '{"game_id":"53b22103-c7ff-413d-bc63-20f6fb806a07"}', $proxy)['data']['points'];
    return $curl;  
}


function curl($path, $auth = false, $body = false, $proxy = false){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api-web.tomarket.ai/tomarket-game/v1/'.$path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if($body){
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    if($proxy){
        $ex = explode(":", $proxy);
        $proxyUrl = $ex[0].":".$ex[1];
        $proxyUser = $ex[2].":".$ex[3];
        curl_setopt($ch, CURLOPT_PROXY, $proxyUrl);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyUser);
    }
    $headers = array();
    $headers[] = 'Host: api-web.tomarket.ai';
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Accept: application/json, text/plain, */*';
    $headers[] = 'Accept-Language: id-ID,id;q=0.9';
    if($auth){
        $headers[] = 'Authorization: '.$auth;
    }
    $headers[] = 'Origin: https://mini-app.tomarket.ai';
    $headers[] = 'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 17_6_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148';
    $headers[] = 'Referer: https://mini-app.tomarket.ai/';
    if($body){
        $headers[] = 'Content-Length: '.strlen($body);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    $decode = json_decode($result, true);
    return $decode;
}

function proxy($proxy){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://ipecho.net/plain');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $ex = explode(":", $proxy);
    $proxyUrl = $ex[0].":".$ex[1];
    $proxyUser = $ex[2].":".$ex[3];
    curl_setopt($ch, CURLOPT_PROXY, $proxyUrl);
    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyUser);
    $headers = array();
    $headers[] = 'Host: ipecho.net';
    $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0';
    $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/png,image/svg+xml,*/*;q=0.8';
    $headers[] = 'Accept-Language: en-US,en;q=0.5';
    $headers[] = 'Connection: close';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    return $result;
}