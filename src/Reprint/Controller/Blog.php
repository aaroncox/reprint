<?php

namespace Reprint\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Blog
{
  public function postAction(Application $app, $category, $author, $permlink)
  {
    return $app['twig']->render('post.html.twig', array(
      'username' => $author,
      'post' => $app['steemd']->getPost($author, $permlink)
    ));
  }
  public function listAction(Application $app, $category = null, Request $request)
  {
    $page = $request->query->get('page', 1);
    $perPage = 5;
    $query = array(
      'accounts' => $app['blog']['filters']['accounts'],
      'tags' => [$category]
    );
    $response = $app['steemd']->getContent($query, $perPage, $page);
    return $app['twig']->render('list.html.twig', array(
      'page' => $response['page'],
      'pages' => $response['pages'],
      'total' => $response['total'],
      'perPage' => $response['perPage'],
      'posts' => $response['content'],
      'recent' => array_slice($response['results'], 0, 5),
      'category' => $category,
      'categories' => $response['categories'],
    ));
  }
}
