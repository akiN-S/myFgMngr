<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;


class DeviceInfoController extends Controller
{
    //
    public function home(Request $request) {
        $devices = array();

        $fghost =  env('FG_HOST');
        $fglogin = env('FG_LOGIN');
        $fgpass = env('FG_PASS');
        $url = 'https://'.$fghost.'/logincheck';
        $data = array('username'=>$fglogin,'secretkey'=>$fgpass);
        $post_data = http_build_query($data);
        
        $curl_connection = curl_init($url);
        
        curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl_connection, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl_connection, CURLOPT_POST, TRUE);
        curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, TRUE); // returning result
        curl_setopt($curl_connection, CURLOPT_HEADER, TRUE);
        
        $response = curl_exec($curl_connection);
        
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
        

        // getting a list of DHCP leases
        $curl_connection = curl_init('https://'.$fghost.'/api/v2/monitor/system/dhcp/'); // URL to get a list of all DHCP leases, grouped by interface.
        curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl_connection, CURLOPT_SSL_VERIFYHOST, FALSE); 
        curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, TRUE); // returning result
        curl_setopt($curl_connection, CURLOPT_COOKIE, $matches[1][0]);
        $response = curl_exec($curl_connection);
        $dhcpLeases = (json_decode($response, true))["results"]; // parsing json and taking necessary info
        curl_close($curl_connection);


        // getting the DHCP configuration
        $curl_connection = curl_init('https://'.$fghost.'/api/v2/cmdb/system.dhcp/server'); // URL to get the DHCP configuration
        curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl_connection, CURLOPT_SSL_VERIFYHOST, FALSE); 
        curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, TRUE); // returning result
        curl_setopt($curl_connection, CURLOPT_COOKIE, $matches[1][0]);
        $response = curl_exec($curl_connection);
        $dhcpConifgAddrs = (json_decode($response, true))["results"][0]["reserved-address"]; // parsing json and taking necessary info
        curl_close($curl_connection);

        // making models array from the list of DHCP leases
        foreach($dhcpLeases as $dhcpLease){
            // putting info into model
            $device = new Device();
            $device->ip = $dhcpLease["ip"];
            $device->mac = $dhcpLease["mac"];
            $device->reserved = $dhcpLease["reserved"];
            $device->description = "none"; // setting "none" as temporary, will be set later if exsiting in DHCP config 
            $device->isLease = true;
            $device->isConfig = false; // setting false as temporary, will be cheanged later if exsiting in DHCP config 

            if(isset($dhcpLease["hostname"]))
                $device->hostname = $dhcpLease["hostname"];
            else
                $device->hostname = "none";
            
            // making models array
            $devices[] = $device;
        }


        // adding into models array from the DHCP configuration
        $devicesTmp = array();
        foreach($dhcpConifgAddrs as $dhcpConifgAddr){ // going through the DHCP configuration
            $flagRet = $this->setDescriptionInDevices($devices, $dhcpConifgAddr);  // setting Description if DHCP config device exists in the list of DHCP leases

            if($flagRet == false){ // if DHCP config device doesn't exist in the list of DHCP leases, adding a new model
                $device = new Device();
                $device->ip = $dhcpConifgAddr["ip"];
                $device->mac = $dhcpConifgAddr["mac"];
                $device->reserved = true;
                $device->description = $dhcpConifgAddr["description"];
                $device->hostname = "none";
                $device->isLease = false;
                $device->isConfig = true;
                $devicesTmp[] = $device;
            }
        }

        $devices = array_merge( $devices, $devicesTmp);
        // var_dump($devices); //debug

        return view('home', ['devices' => $devices]);
    }


    // setting Description if DHCP config device exists in the list of DHCP leases
    private function setDescriptionInDevices($devices, $targetDhcpConifgAddr){
        // going through $devices which has the list of DHCP leases
        foreach($devices as $device){
            if($device["mac"] == $targetDhcpConifgAddr["mac"]){ // if target DHCP config is fuond in the list of DHCP leases by MAC
                $device->description = $targetDhcpConifgAddr["description"]; // setting description
                $device->isConfig = true; // setting flang true
                return true; // returning true as set the description
            } 
        }
        return false; // returning false as not set the description, means not DHCP leased device
    }

}
