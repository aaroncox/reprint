<?php

namespace Reprint\Steem;

use Greymass\SteemPHP\RPC;

class Client
{

  protected $config;
  protected $client;

  public function __construct($config, $filters)
  {
    $this->config = array_merge($config, $filters);
    $this->client = new RPC($this->getConfig('host'));
  }

  public function getConfig($key) {
    if(isset($this->config[$key])) {
      return $this->config[$key];
    }
    return null;
  }

  public function getContent($query = array(), $limit = 5, $skip = 0) {
    // Load default parameters if the query is empty
    if(empty($query)) {
      $query = array(
        'accounts' => $this->getConfig('accounts'),
        'tags' => $this->getConfig('tags'),
        'title' => $this->getConfig('title'),
      );
    }
    $content = array();
    foreach($query['accounts'] as $name => $data) {
      if(in_array('post', $data)) {
        $content = array_merge($content, $this->getPosts($name, $query, $limit, $skip));
      }
      // if(in_array('reblog', $data)) {
      //   $content = array_merge($content, $this->getReblogs($name, $tags));
      // }
    }
    // Sort the posts chronologically
    $content = $this->sortContent($content);
    // Slice to get our desired amount
    $content = array_slice($content, 0, $limit);
    return $content;
  }

  public function getContentByTag($tag, $limit = 100, $skip = 0) {
    $accounts = $this->getConfig('accounts');
    $query = array('tags' => array($tag));
    $content = array();
    foreach($accounts as $name => $data) {
      if(in_array('post', $data)) {
        $content = array_merge($content, $this->getPosts($name, $query, $limit, $skip));
      }
      // if(in_array('reblog', $data)) {
      //   $content = array_merge($content, $this->getReblogs($name, $tags));
      // }
      // if(in_array('vote', $data)) {

      // }
    }
    // Sort the posts chronologically
    $content = $this->sortContent($content);
    // Slice to get our desired amount
    $content = array_slice($content, 0, $limit);
    return $content;
  }

  public function sortContent($posts) {
    // Sort the posts by the new timestamp
    uasort($posts, function($a, $b) {
      if ($a->ts == $b->ts) {
        return 0;
      }
      return ($a->ts < $b->ts) ? 1 : -1;
    });
    return $posts;
  }

  public function getPost($author, $permlink)
  {
    return $this->client->get_content($author, $permlink);
  }

  public function getPosts($account, $query = array(), $limit = 5, $skip = 0)
  {
    $posts = $this->getPostsFromAccount($account, $query);
    // Sort the posts chronologically
    $posts = $this->sortContent($posts);
    // Slice to get our desired amount
    $posts = array_slice($posts, $skip, $limit);
    // Return our posts
    return $posts;
  }

  public function getPostsFromAccount($account, $query)
  {
    $response = $this->client->get_posts($account);
    $return = [];
    foreach($response as $data) {
      // Does this match our tag query?
      $valid = true;
      if(isset($query['tags']) && !empty($query['tags']) && $data->json_metadata && count(array_intersect(array_map('strtolower', $query['tags']), array_map('strtolower', $data->json_metadata['tags']))) == 0) {
        $valid = false;
      }
      // Does this match our title query?
      if(isset($query['title']) && strpos($data->title, $query['title']) === false) {
        $valid = false;
      }
      if($valid) {
        $return[] = $data;
      }
    }
    return $return;
  }

  public function getReblogs($account, $tags)
  {
    // Temporary solution using steemdb until we get proper APIs
    $url = sprintf('https://steemdb.com/api/account/%s/contentreblog', $account);
    $json = json_decode(file_get_contents($url), true);
    $response = array();
    foreach($json as $reblog) {
      // Add the content to our response
      $response[] = $content = new Comment($reblog['content'][0]);
    }
    return $response;
  }

}
