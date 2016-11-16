<?php

namespace Reprint;

use Doctrine\DBAL\Schema\Table;
use MJanssen\Provider\RoutingServiceProvider;
use Silex\Application as SilexApplication;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\HttpCacheServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\WebProfilerServiceProvider;
use Symfony\Bundle\WebProfilerBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Translation\Loader\YamlFileLoader as TranslationYamlFileLoader;
use Symfony\Component\Yaml\Yaml;

class Application extends SilexApplication
{

  public function __construct()
  {
    $this->rootDir = APPLICATION_PATH . "/../";
    parent::__construct();
    // Setup the Reprint application
    $this->setConfiguration();
    $this->registerProviders();
    $this->registerRoutes();
    // Enable extras if the environment is set to 'development'
    if('development' == APPLICATION_ENV) {
      $this->debug = true;
      $this->register(new MonologServiceProvider(), array(
          'monolog.logfile' => $this->rootDir.'var/logs/silex_dev.log',
      ));
      $this->register(new WebProfilerServiceProvider(), array(
          'profiler.cache_dir' => $this->rootDir.'var/cache/profiler',
      ));
    }
  }

  private function registerProviders()
  {
    $this->register(new ServiceControllerServiceProvider());
    $this->register(new AssetServiceProvider());
    $this->register(new TwigServiceProvider());
    $this->register(new HttpFragmentServiceProvider());
    $this->register(new HttpCacheServiceProvider(), array(
       'http_cache.cache_dir' => $this['var_dir'].'/cache/',
       'http_cache.esi'       => null,
    ));
    $this->register(new TranslationServiceProvider());
    $this['translator'] = $this->extend('translator', function ($translator, $app) {
        $loader     = new TranslationYamlFileLoader();
        $translator->addResource('yaml', $app->rootDir.'/resources/translations/fr.yml', 'fr');
        return $translator;
    });
    $this->register(new Steem\Provider(), [
      'config' => $this['steem']
    ]);
    $this->registerTwig();
  }

  private function registerRoutes() {
    // Load our routes configuration and load all routes
    if($config = Yaml::parse(file_get_contents($this->rootDir . 'resources/config/routes.yaml'))) {
      foreach($config as $property => $value) {
        $this[$property] = $value;
      }
      $this->register(new RoutingServiceProvider);
    }
  }

  private function registerTwig()
  {
    $this->register(new TwigServiceProvider(), array(
      'twig.options' => array(
        // Disable cache if in development
        'cache' => (APPLICATION_ENV == 'development') ? false : $this['var_dir'].'/cache/twig',
        'strict_variables' => true,
      ),
      'twig.path' => array($this->rootDir.'public/themes/'.$this['blog']['theme']),
    ));
    $this['twig'] = $this->extend('twig', function ($twig, $app) {
      $twig->addGlobal('steem', $app['steem']);
      $twig->addGlobal('blog', $app['blog']);
      $twig->addGlobal('display', new Helper\Display($app['blog']));
      return $twig;
    });
  }

  private function setConfiguration() {
    // Establish Default configuration that is overwritten by yaml
    $this['blog'] = array(
      'title' => 'Reprint.io',
      'theme' => 'foundation6-default',
    );
    $this['locale'] = 'en';
    $this['steem'] = array(
      'host' => 'https://node.steem.ws',
    );
    $this['var_dir'] = $this->rootDir.'var';
    // Check for the existence of our configuration
    if($config = Yaml::parse(file_get_contents($this->rootDir . 'resources/config/config.yaml'))) {
      // Set all of the available properties
      foreach($config as $property => $value) {
        $this[$property] = $value;
      }
    }
  }

}