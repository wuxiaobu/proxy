<?php 

namespace Wuxiaobu\Proxy;

use App\Models\ProxyPool;
use GuzzleHttp\Clien;
use \QL\QueryList;
use Log;

Class Spider
{ 
  static private $instance;
  private $driver;
  static public $currentCount;
  private $crawler;
  private $connectTimeout;
  private $timeout;

  public $inputEncoding;
  public $outputEncoding;
  public $sleep;

  private function __construct(){
    $this->crawler = QueryList::getInstance();
    $this->connectTimeout = 5; 
    $this->timeout = 5; 
  }

  static public function getInstance(){
    if (!self::$instance instanceof self) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function setDriver($driver){
    $driver = ucfirst($driver);
    $this->driver = $driver;
    $className = "Wuxiaobu\\Proxy\\Drivers\\" . $this->driver;
    if (class_exists($className)) {
      $this->driver = new $className($this);
    } else {
      throw new \Exception('驱动未找到:', $className);
    }
  }


  public function getDriver(){
    return $this->driver;
  }

  public function handle(){
    $this->driver->handle();
  }

  protected function queryListProcess($urls, $selector, $func){
    foreach ($urls as $url) {
        //代理池上限判断
      if (!$this->checkLimit()) {
        break;
      }
      try {
        $host = parse_url($url, PHP_URL_HOST);
        $options = [
            'headers' => [
              'Referer' => "http://$host/",
              'User-Agent' => $this->getUserAgent(),
              'Accept' => "text/html,application/xhtml+xml,application/xml;",
              'Upgrade-Insecure-Requests' => "1",
              'Host' => $host,
              'DNT' => "1",
            ],
            'connect_timeout' => $this->connectTimeout,
            'timeout' => $this->timeout
        ];
        //抓取网页内容
        $content = $this->crawler->get($url, [], $options);
        //编码设置
        if ($this->inputEncoding && $this->outputEncoding) {
          $content->encoding($this->outputEncoding, $this->inputEncoding);
        }
        //获取数据列表
        $table = $content->find($selector);
        //遍历数据列
        $table->map(function ($tr) use ($func) {
            //获取IP、端口、透明度、协议
            list($ip, $port, $anonymity, $protocol) = call_user_func_array($func, [$tr]);
            //代理入库
            $this->addProxy($ip, $port, $anonymity, $protocol);
        });
      } catch (\Exception $e) {
        Log::error("代理爬取失败[url:{$url}]：" . $e->getMessage());
      }
      if ($this->sleep) {
        sleep($this->sleep);
      }
    }
  }

  protected function addProxy($ip, $port, $anonymity, $protocol){
    $proxy = ProxyPool::where('id', $ip)
        ->where('port', $port)
        ->where('protocol', $protocol)
        ->first();   

    if (!$proxy && $this->checkData($ip, $port, $anonymity, $protocol) && $this->checkLimit()) {
      $proxy = new ProxyPool();
      $proxy->ip = $ip;
      $proxy->port = $port;
      $proxy->anonymity = $anonymity;
      $proxy->protocol = $protocol;
      $proxy->save();
      static::$currentCount++;
      Log::info("代理入库：$proxy");
    }
  }

  private function checkData($ip, $port, $anonymity, $protocol){
    if ($ip && $port && $anonymity && $protocol && filter_var($ip, FILTER_VALIDATE_IP)) {
      return true;
    }
    return false;
  }

  private function checkLimit(){
    if (!static::$currentCount) {
      static::$currentCount = ProxyPool::where('quality', ProxyPool::QUALITY_COMMON)->count();
    }
    if (static::$currentCount > config('proxy.limit_count')) {
      Log::info('代理池IP数量达到上限');
      return false;
    }

    return true;
  }

  private function getUserAgent(){
    $userAgents = [
      'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_8; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50',
      'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-us) AppleWebKit/534.50 (KHTML, like Gecko) Version/5.1 Safari/534.50',
      'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0',
      'Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1',
      'Opera/9.80 (Macintosh; Intel Mac OS X 10.6.8; U; en) Presto/2.8.131 Version/11.11',
      'Opera/9.80 (Windows NT 6.1; U; en) Presto/2.8.131 Version/11.11',
      ' Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_0) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11',
      'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Maxthon 2.0)',
      'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; TencentTraveler 4.0)',
      'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; 360SE)',
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36',
    ];
    $userAgentsCount = count($userAgents);

    return $userAgents[rand(0, $userAgentsCount - 1)];
  }

}