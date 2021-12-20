<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TelegramBot extends Controller
{
    public function setWebhook(Request $request) {
        $result = $this->sendTelegramData('setWebhook', [
            'query' => [ 'url' => $request->url . '/' . \Telegram::getAccessToken() ]
        ]);

        return redirect()->route('admin.setting.index')->with('status', $result);
    }
    public function getWebhookInfo(Request $request) {
        $result = $this->sendTelegramData('getWebhookInfo');

        return redirect()->route('admin.setting.index')->with('status', $result);
    }
    public function sendTelegramData() {
        $urls = array(
            'https://api.telegram.org/bot',
            '5050871888:AAFgx5j1E_frql2nR6cvQMEzyWyzvvv0WX0/',
            'sendMessage'
        );

        $array = array(
        	'chat_id'    => '',
        	'text' => 'Привет, Это Антиколлектор!'
        );

        $ch = curl_init(implode('',$urls));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $array);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $html = curl_exec($ch);
        curl_close($ch);

        dd($html);
    }
    public function getTest() {
        return Response::json([
            'status' => true,
        ]);
    }
}
