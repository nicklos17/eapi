<?php
use Phalcon\Config,
    Phalcon\Loader,
    Phalcon\DI\FactoryDefault,
    Phalcon\Mvc\Url as UrlProvider,
    Phalcon\Session\Adapter\Files,
    Phalcon\Dispatcher,
    Phalcon\Mvc\View,
    Phalcon\Mvc\Dispatcher as MvcDispatcher,
    Phalcon\Events\Manager as EventsManager,
    Phalcon\Mvc\Application;

// 根目录定义
define("ROOT_PATH", dirname(__DIR__) .'/');
// 开发模式 TRUE | 线上模式 FALSE
define('DEBUG', TRUE);
try {


    //Create a DI
    $di = new FactoryDefault();

    $di->set('conf', function() {
        $config = new Config(require(ROOT_PATH.'/app/config/config.php'));
        $config = $config->toArray();
        return $config;
    });

    $config = $di->get('conf');

    //Register an autoloader
    $loader = new Loader();
    $loader->registerDirs(array(
        $config['application']['controller'],
        $config['application']['libDir'],
        $config['application']['modelsDir'],
        $config['application']['logicsDir'],
    ))->register();

    //Setup a base URI so that all generated URIs include the "tutorial" folder
    $di->set('url', function() use ($config){
        $url = new UrlProvider();
        $url->setBaseUri($config['application']['baseUri']);
        return $url;
    });

    //Start the session the first time a component requests the session service
    $di->set('session', function() {
        $session = new Files();
        $session->start();
        return $session;
    });
    if(!DEBUG)
    {
        register_shutdown_function(function() use ($config) {
            $errInfo = error_get_last();
            if($errInfo && is_array($errInfo))
            {
                error_log('[' . date('Y-m-d H:i:s') . ']' . var_export($errInfo, TRUE), 3, $config['application']['errorFile']);
            }
        });
    }
	$class = 'index';
    $url = isset($_GET['_url']) ?trim($_GET['_url']) :false;
    if(false!=$url)
    {
    	$urlArr= explode('/', $url);
    	$class = $urlArr[1];
    }
    $className = ucfirst($class).'Controller';
    if(class_exists($className))
    {
    	\core\Config::init(DEBUG);
    	$service = new Yar_Server(new $className($di));
    	$service->handle();
    }
    else
    {
    	throw new Exception('the rpc controllers not exits');
    }

}
catch (\Exception $e)
{
    echo json_encode(array('flag' => 0, 'msg' => 'system error'));
	file_put_contents($config['application']['logFile'], '[' . date("Y-m-d H:i:s") . ']' . $e->getMessage() . "\n", FILE_APPEND);
}
