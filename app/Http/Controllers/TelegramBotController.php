<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Requests;
// use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Api;
use Cookie;
use App\login;
use App\step2;
use App\step1;

class TelegramBotController extends Controller
{
    private $error = [ 'fio' => '', 'iin' => '', 'phoneNumber' => '', 'email' => '', 'password' => '' ];

    public function hook(Request $request) {
//         \Log::error($request->all());
        $this->getCommandsTelegramBot($request->all());
    }
    public function getCommandsTelegramBot($data) {
        $text = $data['message']['text'];
        $chat_id = $data['message']['chat']['id'];
        $dataLog = login::find($chat_id);
        $dataDB = step2::find($chat_id);
        if ($text === '/start') {
            if (!$dataLog) $this->createDatabaseTable($chat_id, 'login');
            else {
                $login = login::find($chat_id);
                $login->action = '';
                $login->iin = '';
                $login->password = '';
                $login->logged = '';
                $login->token = '';
                $login->save();
            }
            $this->sendTelegramMessage($chat_id, '1. Авторизация - /login
2. Регистрация - /register');
        }
        else if ($dataLog) {
            if ($text === '/login') {
                $dataLog->action = 'login';
                $dataLog->save();
                $this->sendTelegramMessage($chat_id, 'Введите ИИН');
            }
            else if ($text === '/register') {
                $dataLog->action = 'register';
                $dataLog->save();
                if (!$dataDB) $this->createDatabaseTable($chat_id, 'step2');
                else {
                    $step2 = step2::find($chat_id);
                    $step2->problem = '';
                    $step2->description_problem = '';
                    $step2->name_organization = '';
                    $step2->debt = '';
                    $step2->loan_data = '';
                    $step2->save();
                }
                $this->sendTelegramMessage($chat_id, 'Какая проблема?
1. Коллекторы
2. Кредитор
3. ЧСИ');
            }
            else if ($text === '/status') {
                if ($dataLog->logged === 'true') {
                    $getPush = json_decode(Http::post('https://crediter.kz/api/getPush', [
                            'token' => $dataLog->token,
                        ]),true);
                    if ($getPush['success']) {
                        \Log::error($getPush);
                        $statusOrder = '';
                        for ($i = 0; $i < count($getPush['data']); $i++) {
                            if (!$getPush['data'][$i]['status']) $statusOrder .= ($i.'. '.$getPush['data'][$i]['message']." - в совершенстве\n");
                            else $statusOrder .= ($i.'. '.$getPush['data'][$i]['message']." - в обработке\n");
                        }
                        $this->sendTelegramMessage($chat_id, $statusOrder);
                    }
                    else {
                        $this->sendTelegramMessage($chat_id, $getPush['message']);
                    }
                }
                else {
                    $this->sendTelegramMessage($chat_id, 'Неизвестный команда');
                }
            }
            else if ($dataLog->action === 'login') {
                $this->commandsLogin($data);
            }
            else if ($dataLog->action === 'register') {
                $this->commandsStep2($data);
            }
            else {
                $this->sendTelegramMessage($chat_id, 'Неизвестный команда');
            }
        }
        else {
            $this->createDatabaseTable($chat_id, 'login');
            $this->sendTelegramMessage($chat_id, 'Неизвестный команда');
        }
    }




    public function commandsLogin($data) {
        $text = $data['message']['text'];
        $chat_id = $data['message']['chat']['id'];
        $dataDB = login::find($chat_id);
        if ($dataDB->iin === '') {
            if ($text) {
                $dataDB->iin = $text;
                $dataDB->save();
                $this->sendTelegramMessage($chat_id, 'Введите пароль');
            }
            else {
                $this->sendTelegramMessage($chat_id, 'Вы ничего не ввели. Введите ИИН');
            }
        }
        else if ($dataDB->password === '') {
            if ($text) {
                $signIn = json_decode(Http::post('https://crediter.kz/api/signIn', [
                        'iin' => $dataDB->iin,
                        'password' => $text,
                    ]),true);
                if ($signIn['success']) {
                    $dataDB->token = $signIn['token'];
                    $dataDB->password = $text;
                    $dataDB->logged = 'true';
                    $dataDB->save();
                    $this->sendTelegramMessage($chat_id, '1. Авторизация - /login
2. Регистрация - /register
3.  - /status');
                }
                else {
                    $dataDB->iin = '';
                    $dataDB->password = '';
                    $this->sendTelegramMessage($chat_id, 'Неправильный ИИН или пароль. Введите ИИН заново');
                }
            }
            else {
                $this->sendTelegramMessage($chat_id, 'Вы ничего не ввели. Введите пароль');
            }
        }
    }






    public function commandsStep2($data) {
        $text = $data['message']['text'];
        $chat_id = $data['message']['chat']['id'];
        $dataDB = step2::find($chat_id);
        if ($dataDB->problem === '') {
            $problems = [ 'Коллекторы', 'Кредитор', 'ЧСИ' ];
            if (in_array($text, $problems)) {
                $dataDB->problem = $text;
                $dataDB->save();
                $this->sendTelegramMessage($chat_id,'Опишите проблему');
            }
            else {
                $this->sendTelegramMessage($chat_id, 'Какая проблема?
1. Коллекторы
2. Кредитор
3. ЧСИ');
            }
        }
        else if ($dataDB->description_problem === '') {
            $dataDB->description_problem = $text;
            $dataDB->save();
            $this->sendTelegramMessage($chat_id, 'Кому должен');
        }
        else if ($dataDB->name_organization === '') {
            $dataDB->name_organization = $text;
                $dataDB->save();
            $this->sendTelegramMessage($chat_id, 'Сколько должен(только цифр)');
        }
        else if ($dataDB->debt === '') {
            if ($this->checkNumber($text)) {
                $dataDB->debt = $text;
                $dataDB->save();
                $this->sendTelegramMessage($chat_id, 'Когда брал кредит(Пример: 10.10.2020)');
            }
            else {
                $this->sendTelegramMessage($chat_id, 'Сколько должен(только цифр)');
            }
        }
        else if ($dataDB->loan_data === '') {
            if ($this->checkDate($text)) {
                $dataDB->loan_data = $text;
                $dataDB->save();
                if (!step1::find($chat_id)) $this->createDatabaseTable($chat_id, 'step1');
                else {
                    $step1 = step1::find($chat_id);
                    $step1->fio = '';
                    $step1->iin = '';
                    $step1->phone_number = '';
                    $step1->email = '';
                    $step1->password = '';
                    $step1->confirmPhoneNumber = '';
                    $step1->save();
                }
                $this->sendTelegramMessage($chat_id, 'ФИО');
            }
            else {
                $this->sendTelegramMessage($chat_id, 'Когда брал кредит(Пример: 10.10.2020)');
            }
        }
        else {
            $this->commandsStep1($data);
        }
    }
    public function commandsStep1($data) {
        $text = $data['message']['text'];
        $chat_id = $data['message']['chat']['id'];

//         return json_decode(Http::post('https://crediter.kz/api/signIn', [
//                 'iin' => '4314123125123',
//                 'password' => 'Network Administrator',
//             ]),true);

        $dataDB = step1::find($chat_id);
        if ($dataDB->fio === '') {
            if ($this->validateFIO($text)) {
                $dataDB->fio = $text;
                $dataDB->save();
                $this->sendTelegramMessage($chat_id, 'Введите ИИН');
            }
            else if ($this->error['fio']) {
                $this->sendTelegramMessage($chat_id, $this->error['fio']);
            }
            else {
                $this->sendTelegramMessage($chat_id, 'Введите ФИО');
            }
        }
        else if ($dataDB->iin === '') {
            if ($this->validateIIN($text)) {
                $dataDB->iin = $text;
                $dataDB->save();
                $this->sendTelegramMessage($chat_id, 'Введите номер телефона');
            }
            else if ($this->error['iin']) {
                $this->sendTelegramMessage($chat_id, $this->error['iin']);
            }
            else {
                $this->sendTelegramMessage($chat_id, 'Введите ИИН');
            }
        }
        else if ($dataDB->phone_number === '') {
            if ($text = $this->validatePhoneNumber($text)) {
                $dataDB->phone_number = $text;
                $dataDB->save();
                $this->sendTelegramMessage($chat_id, 'Введите почту');
            }
            else if ($this->error['phoneNumber']) {
                $this->sendTelegramMessage($chat_id, $this->error['phoneNumber']);
            }
            else {
                $this->sendTelegramMessage($chat_id, 'Введите номер телефона');
            }
        }
        else if ($dataDB->email === '') {
            if ($this->validateEmail($text)) {
                $dataDB->email = $text;
                $dataDB->save();
                $this->sendTelegramMessage($chat_id, 'Введите пароль');
            }
            else if ($this->error['email']) {
                $this->sendTelegramMessage($chat_id, $this->error['email']);
            }
            else {
                $this->sendTelegramMessage($chat_id, 'Введите почту');
            }
        }
        else if ($dataDB->password === '') {
            if ($this->validatePassword($text)) {
                $dataDB->password = $text;
                $dataDB->save();
                $step1 = step1::find($chat_id);
                $signIn = json_decode(Http::post('https://crediter.kz/api/firstStep', [
                        'fio' => $step1->fio,
                        'iin' => $step1->iin,
                        'phone' => $step1->phone_number,
                        'email' => $step1->email,
                        'password' => $step1->password,
                    ]),true);
                if ($signIn['success']) {
                    $this->sendTelegramMessage($chat_id, 'Подтвердите телефон. Введите код');
                }
                else {
                    $this->sendTelegramMessage($chat_id, $signIn['message']);
                }
            }
            else if ($this->error['password']) {
                $this->sendTelegramMessage($chat_id, $this->error['password']);
            }
            else {
                $this->sendTelegramMessage($chat_id, 'Введите пароль');
            }
        }
        else {
            $this->commandsConfirm($data);
        }
    }
    public function commandsConfirm($data) {
        $text = $data['message']['text'];
        $chat_id = $data['message']['chat']['id'];
        $dataDB = step1::find($chat_id);
        if ($dataDB->confirmPhoneNumber === '') {
            $code = json_decode(Http::get('https://crediter.kz/api/checkCode', [
                    'fio' => $dataDB->fio,
                    'iin' => $dataDB->iin,
                    'phone' => $dataDB->phone_number,
                    'email' => $dataDB->email,
                    'password' => $dataDB->password,
                    'code' => $text,
                ]),true);
            \Log::error($code);
            if ($code['success']) {
                $dataDB->confirmPhoneNumber = 'true';
                $dataDB->save();
                $login = login::find($chat_id);
                $login->action = 'login';
                $login->iin = $dataDB->iin;
                $login->password = $dataDB->password;
                $login->logged = $dataDB->logged;
                $login->token = $code['token'];
                $step2 = step2::find($chat_id);
                $secondStep = json_decode(Http::post('https://crediter.kz/api/secondStep', [
                        'organization' => [[$step2->name_organization.'-'.$step2->debt.'-'.$step2->loan_data.'-'.$step2->problem.'-'.$step2->description_problem]],
                        'token' => $code['token'],
                    ]),true);
                if ($secondStep['success']) {
                    $this->sendTelegramMessage($chat_id, 'Подтвердите телефон. Введите код');
                }
                else {
                    $this->sendTelegramMessage($chat_id, $secondStep['message']);
                }
            }
            else {
                $this->sendTelegramMessage($chat_id, 'Код не соответствует. Введите заново');
            }
        }
        else {
            $this->sendTelegramMessage($chat_id, 'Data Yes');
        }
    }











    public function index(Request $request) {
//         $this->createDatabaseTable('1', 'step1');
//         $returned = ((json_decode(Http::post('https://crediter.kz/api/checkCode?phone=87077917011&code=1234'), true)));
//         return $returned;
//         $text = '077917011';
//         echo $text.'<br>';
//         if ($text = $this->validatePhoneNumber($text)) {
//             echo $text.'<br>';
//             echo 'Введите почту';
//         }
//         else if ($this->error['phoneNumber']) echo $this->error['phoneNumber'];
//         else echo 'Введите номер телефона';
    }














    public function copyTelegramMessage($chatId, $fromChatId, $messageId) {
        $urls = array(
            'https://api.telegram.org/bot',
            '5050871888:AAFgx5j1E_frql2nR6cvQMEzyWyzvvv0WX0/',
            'copyMessage'
        );

        $array = array(
          'chat_id' => $chatId,
          'from_chat_id' => $fromChatId,
          'message_id' => $messageId,
        );
        $this->sendTelegramMessageMessage($urls, $array);
    }
    public function sendTelegramMessageMessage($urls, $array) {
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
    public function sendTelegramMessage($chatId, $text) {
        $telegram = new Api('5050871888:AAFgx5j1E_frql2nR6cvQMEzyWyzvvv0WX0');

        $response = $telegram->sendMessage([
          'chat_id' => $chatId,
          'text' => $text,
        ]);

        $messageId = $response->getMessageId();
    }

//     public function getTest() {
//         return response()->json([
//             'name' => 'Abigail',
//             'state' => 'CA',
//         ], 200);
//     }

    /* VALIDATE STEP-2 */

    public function checkNumber($str) {
        $strNum = true;
        for ($i = 0; $i < strlen($str); $i++) {
            if (!($str[$i] >= '0' && $str[$i] <= '9' || $str[$i] === '-')) {
                $strNum = false;
            }
        }
        return $strNum;
    }
    public function checkDate($str) {
        if (!(strlen($str) === 10 && count(explode('.', $str)) === 3)) return false;
        $strSymbol = ['n','n','.','n','n','.','n','n','n','n'];
        for ($i = 0; $i < strlen($str); $i++) {
            if ($strSymbol[$i] === 'n') {
                if (!($str[$i] >= '0' && $str[$i] <= '9')) {
                    return false;
                }
            }
            else if (!($strSymbol[$i] === $str[$i])) return false;
        }
        return true;
    }

    /* VALIDATE STEP-1 */

    /* VALIDATE FIO */

    private function validateFIO($fio) {
        if (!$fio) return $this->checkError('fio', 'Вы ничего не ввели. Введите ФИО заново');
        else if (!preg_match('/[А-Яа-яЁё]/u', $fio)) return $this->checkError('fio', 'Только на кирилице. Введите ФИО заново');
        else if (!(strpos(trim($fio), ' ') > 0)) return $this->checkError('fio', 'Введите ФИО заново(Например: Абаев Абылай)');
        else return true;
    }

    /* VALIDATE IIN */

    private function validateIIN($iin) {
        if (!(strlen($iin) === 12 && ctype_digit(intval($iin)) && $this->isChecksumValid($iin))) {
            return $this->checkError('iin', 'Неправильный ИИН. Введите заново');
        }
        else return true;
    }
    private function isChecksumValid($value) {
        $weights = range(1, 11, 1);
        $weights2 = array_merge(range(3, 11, 1), [1, 2]);

        $checksum = $this->calc($value, $weights);
        if ($checksum == 10) {
            $checksum = $this->calc($value, $weights2);
        }

        return $checksum < 10 ? (int)substr($value, 11, 1) === $checksum : false;
    }
    private function calc($value, $weights) {
        $value = (string)$value;
        $convolution = 0;

        for ($i = 0; $i < 11; $i++) {
            $convolution += $value[$i] * $weights[$i];
        }

        return $convolution % 11;
    }

    /* VALIDATE PHONE NUMBER */

    private function validatePhoneNumber($phoneNumber) {
        if (!$phoneNumber) return $this->checkError('phoneNumber', 'Вы ничего не ввели. Введите номер телефона заново');
        else if (strlen($phoneNumber) === 11) {
            if ($phoneNumber[0] === '8') {
                if ($this->checkPhoneNumber(substr($phoneNumber, 1))) return ('7'.substr($phoneNumber, 1));
                else return false;
            }
            else if ($phoneNumber[0] === '7') {
                if ($this->checkPhoneNumber(substr($phoneNumber, 1))) return ('7'.substr($phoneNumber, 1));
                else return false;
            }
            else return $this->checkError('phoneNumber', 'Неправильный номер телефона. Введите заново');
        }
        else if (strlen($phoneNumber) === 12) {
            if ($phoneNumber[0] === '+' && $phoneNumber[1] === '7') {
                if ($this->checkPhoneNumber(substr($phoneNumber, 2))) return substr($phoneNumber, 1);
                else return false;
            }
            else return $this->checkError('phoneNumber', 'Неправильный номер телефона. Введите заново');
        }
        else if (strlen($phoneNumber) === 10) return $this->checkPhoneNumber($phoneNumber);
        else return $this->checkError('phoneNumber', 'Неправильный номер телефона. Введите заново');
    }
    private function checkPhoneNumber($phoneNumber) {
        if ($phoneNumber[0] === '7') return ('7'.$phoneNumber);
        else return $this->checkError('phoneNumber', 'Неправильный номер телефона. Введите заново');
    }

    /* VALIDATE EMAIL */

    private function validateEmail($email) {
        if (!$email) return $this->checkError('email', 'Вы ничего не ввели. Введите почту заново');
        $emailValid = array();
        for ($i = 0; $i < strlen($email); $i++) {
            $elm = $email[$i];
            if (strtoupper($elm) >= 'A' &&
                strtoupper($elm) <= 'Z' ||
                $elm >= '0' && $elm <= '9' ||
                $elm === '@' || $elm === '.' ||
                $elm === '-' || $elm === '_' ||
                $elm === '#' || $elm === '$' ||
                $elm === '%' || $elm === '\'' ||
                $elm === '&' || $elm === '*' ||
                $elm === '+' || $elm === '/' ||
                $elm === '^' || $elm === '=' ||
                $elm === '?' || $elm === '`' ||
                $elm === '{' || $elm === '}' ||
                $elm === '~' || $elm === '|'
            ) $emailValid[$i] = true;
            else $emailValid[$i] = false;
        }
        $emailVal = true;
        for($i = 0; $i < count($emailValid); $i++) {
            if(!$emailValid[$i]) {
                $emailVal = false;
                break;
            }
        }
        if (strpos($email, '@') > 0 &&
            strpos(implode('', array_reverse(str_split($email))), '.') > 0 &&
            strpos(implode('', array_reverse(str_split($email))), '@') > strpos(implode('', array_reverse(str_split($email))), '.')-1 &&
            $emailVal
        ) return true;
        else return $this->checkError('email', 'Вы неправильно ввели почту. Введите заново');
    }

    /* VALIDATE PASSWORD */

    private function validatePassword($password) {
        if (!(strlen($password) > 5)) return $this->checkError('password', 'Минимальная длина пароля должна быть не менее 6 символов. Введите пароль заново');
        else return true;
    }

    /* VALIDATE */

    private function checkError($name, $text) {
        $this->error[$name] = $text;
        return false;
    }







    /* DATABASE */

    private function createDatabaseTable($chat_id, $db_name) {
        $db_tables = [
            'login' => [
                'id' => $chat_id,
                'action' => '',
                'iin' => '',
                'password' => '',
                'token' => '',
                'logged' => '',
            ],
            'step1' => [
                'id' => $chat_id,
                'fio' => '',
                'iin' => '',
                'phone_number' => '',
                'email' => '',
                'password' => '',
                'confirmPhoneNumber' => '',
            ],
            'step2' => [
                'id' => $chat_id,
                'problem' => '',
                'description_problem' => '',
                'name_organization' => '',
                'debt' => '',
                'loan_data' => '',
            ],
        ];
        $db = '';
        if ($db_name === 'login') $db = new login($db_tables[$db_name]);
        else if ($db_name === 'step1') $db = new step1($db_tables[$db_name]);
        else if ($db_name === 'step2') $db = new step2($db_tables[$db_name]);
        $db->save();
    }

    /* DATABASE CHAGE */

//     private function changeDatabaseTable($chat_id, $db_name) {
//         $db = '';
//         if ($db_name === 'login') $db = new login($db_tables[$db_name]);
//         else if ($db_name === 'step1') $db = new step1($db_tables[$db_name]);
//         else if ($db_name === 'step2') $db = new step2($db_tables[$db_name]);
//         $db->save();
//     }
}
