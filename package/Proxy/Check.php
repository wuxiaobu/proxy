<?php

namespace Wuxiaobu\Proxy;

use GuzzleHttp\Client;
use Log;

class Check
{
  static private $instance;

  private function __construct(){

  }

  private function __clone(){

  }

  static public function getInstance(){
    if (!self::$instance instanceof self) {
        self::$instance = new self();
    }
    return self::$instance;
  }

  public function handle($proxy){
    return self::check($proxy);
  }

  public static function check($proxy){
    try {
      $client = new Client();
      $checkUrl = config('proxy.check_url');
      $checkKeyword = config('proxy.check_keyword');
      $beginSeconds = self::mSecondTime();
      $response = $client->request('GET', $checkUrl, [
        'proxy' => $proxy,
        'verify' => false,
        'connect_timeout' => config('proxy.connect_timeout'),
        'timeout' => config('proxy.timeout')
      ]);
      if (strpos($response->getBody()->getContents(), $checkKeyword) !== false) {
        $endSeconds = self::mSecondTime();
        $speed = intval($endSeconds - $beginSeconds);
        Log::info("代理检测成功[{$proxy}]：$speed ms[{$response->getStatusCode()}]");
        return $speed;
      } else {
        throw new \Exception('检测结果不包含关键字');
      }
    } catch (\Exception $exception) {
      Log::error("代理测试失败[{$proxy}]：" . $exception->getMessage());
      return false;
    }
  }

  public static function mSecondTime(){
    list($mSeconds, $seconds) = explode(' ', microtime());
    return (float)sprintf('%.0f', (floatval($mSeconds) + floatval($seconds)) * 1000);
  }

}