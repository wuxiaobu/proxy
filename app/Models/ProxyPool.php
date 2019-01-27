<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class ProxyPool extends Model
{ 
  const QUALITY_COMMON = 'common';//普通
  const QUALITY_STABLE = 'stable';//稳定
  const QUALITY_PREMIUM = 'premium';//优质

  const ANONYMITY_TRANSPARENT = 'transparent';//透明
  const ANONYMITY_DISTORTING = 'distorting';//混淆
  const ANONYMITY_ANONYMOUS = 'anonymous';//匿名
  const ANONYMITY_HIGH_ANONYMOUS = 'high_anonymous';//高匿名

  protected $table = 'proxy_pool';
}
