<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PushAlert extends Model
{
    

    public function send_notification ($tokens, $title, $body, $type, $data = []  )
    {

        $url = 'https://fcm.googleapis.com/fcm/send';
        $fields = array(
            'registration_ids' =>$tokens,
            'notification' => [
                "title" => $title, 
                "body" => $body,
                "type" => $type,
            ],
        );
        if(count($data) > 0 ){
            $fields['data'] =  $data;
        }
        $headers = array(
            'Authorization:key= AAAAoGYIwQs:APA91bH4SIsPdzlcADnP5u0T4tbwl4JJCylkHtOb9bve_NJtJ-gQShDVG-jK6netrXUUcBICh2qSPTAMuHrR45My1d4B6_M3r9dpbCQ7SqXNcJiI2Cajis1gcwjswktm901Nn2TjEuAa',
            'Content-Type:application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($fields));

        $result = curl_exec($ch);
        if($result === false)
            die('cUrl faild: '.curl_error($ch));
        curl_close($ch);

        return $result;    
    }
}
