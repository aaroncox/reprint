<?php

namespace Reprint\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Account
{
  public function viewAction(Application $app, $account_name)
  {
    return $app['twig']->render('account.html.twig', array(
      'account' => $app['steemd']->getAccount($account_name)
    ));
  }
}
