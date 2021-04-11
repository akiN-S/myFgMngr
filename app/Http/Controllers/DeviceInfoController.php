<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeviceInfo;


class DeviceInfoController extends Controller
{
    //
    public function home(Request $request) {
        $devicesInfo;

        $fghost =  env('FG_HOST');
        $fglogin = env('FG_LOGIN');
        $fgpass = env('FG_PASS');
        $url = 'https://'.$fghost.'/logincheck';
        $data = array('username'=>$fglogin,'secretkey'=>$fgpass);
        $post_data = http_build_query($data);
        
        $curl_connection = curl_init($url);
        
        curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl_connection, CURLOPT_SSL_VERIFYHOST, FALSE); //　　〃
        curl_setopt($curl_connection, CURLOPT_POST, TRUE);
        curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_connection, CURLOPT_HEADER, TRUE);
        
        $response = curl_exec($curl_connection);
        
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
        
        $curl_connection = curl_init('https://'.$fghost.'/api/v2/cmdb/firewall/vip/');
        curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl_connection, CURLOPT_SSL_VERIFYHOST, FALSE); //　　〃
        curl_setopt($curl_connection, CURLOPT_COOKIE, $matches[1][0]);
        $response = curl_exec($curl_connection);

        // echo ($response);
        
        curl_close($curl_connection);

        return view('home', ['devicesInfo' => $response]);
    }

}
