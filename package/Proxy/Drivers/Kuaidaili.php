<?php

namespace Wuxiaobu\Proxy\Drivers;

use App\Models\ProxyPool;
use Wuxiaobu\Proxy\Spider;

class Kuaidaili extends Spider
{
    public function handle()
    {
        $this->sleep = rand(3, 5);
        $urls = [
            "http://www.kuaidaili.com/free/inha/",
            "http://www.kuaidaili.com/free/inha/2/",
            "http://www.kuaidaili.com/free/inha/3/",
            "http://www.kuaidaili.com/free/inha/4/",
            "http://www.kuaidaili.com/free/inha/5/",
            "http://www.kuaidaili.com/free/intr/",
            "http://www.kuaidaili.com/free/intr/2/",
            "http://www.kuaidaili.com/free/intr/3/",
            "http://www.kuaidaili.com/free/intr/4/",
            "http://www.kuaidaili.com/free/intr/5/",
        ];
        $this->queryListProcess($urls, "#list table tbody tr", function ($tr) {
            $ip = $tr->find('td:eq(0)')->text();
            $port = $tr->find('td:eq(1)')->text();
            $temp = $tr->find('td:eq(2)')->text();
            if (strpos($temp, '高匿') !== false) {
                $anonymity = ProxyPool::ANONYMITY_HIGH_ANONYMOUS;
            } elseif (strpos($temp, '透明') !== false) {
                $anonymity = ProxyPool::ANONYMITY_TRANSPARENT;
            } else {
                $anonymity = ProxyPool::ANONYMITY_ANONYMOUS;
            }
            $protocol = strtolower($tr->find('td:eq(3)')->text());
            return [$ip, $port, $anonymity, $protocol];
        });
    }
}