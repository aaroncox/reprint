<?php

namespace Reprint\Steem;

use Silex\Application as SilexApplication;

class Service
{
  public function __invoke(SilexApplication $app)
  {
    return new Client($app["steem"], $app["blog"]["filters"]);
  }
}
