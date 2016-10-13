<?php

namespace Reprint\Steem;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application as SilexApplication;

class Provider implements ServiceProviderInterface
{

  protected $host;

  public function boot(SilexApplication $app)
  {

  }

  public function register(Container $app)
  {
    $app['steemd'] = new Service();
  }

}
