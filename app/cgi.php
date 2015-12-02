<?php
use Phalcon\Config, Phalcon\Loader, Phalcon\DI\FactoryDefault;

// 根目录定义
define("ROOT_PATH", dirname(__DIR__) . '/');
// 开发模式 TRUE | 线上模式 FALSE
define('DEBUG', TRUE);
try
{
	
	// Create a DI
	$di = new FactoryDefault();
	
	$di->set('conf', 
		function ()
		{
			$config = new Config(require (ROOT_PATH . '/app/config/config.php'));
			$config = $config->toArray();
			return $config;
		});
	
	$config = $di->get('conf');
	
	// Register an autoloader
	$loader = new Loader();
	$loader->registerDirs(array(
			$config['application']['logicsDir'],
			$config['application']['libDir']),
			$config['application']['modelsDir']
		)->register();
	
	// Handle the request
	if(! DEBUG)
	{
		register_shutdown_function(
			function () use($config)
			{
				$errInfo = error_get_last();
				if($errInfo && is_array($errInfo))
				{
					error_log('[' . date('Y-m-d H:i:s') . ']' . var_export($errInfo, TRUE), 3, 
						$config['application']['errorFile']);
				}
			});
	}
	$controller = isset($_GET['controller'])? $_GET['controller']: false;
	$action = isset($_GET['action'])? $_GET['action']: false;
	if(! $controller || ! $action)
	{
		echo json_encode(array('flag'=> false,'msg'=> 'must have controller and action'));
	}
	else
	{
		\core\Config::init(DEBUG);
		$params = file_get_contents("php://input");
		$logic = new $controller();
		$paramsArr = json_decode($params,true);
		$result = call_user_func_array(array($logic,$action), $paramsArr);
		echo json_encode($result);
	}
}
catch(\Exception $e)
{
	echo json_encode(array('flag'=> false,'msg'=> 'system error'));
	file_put_contents($config['application']['logFile'], '[' . date("Y-m-d H:i:s") . ']' . $e->getMessage() . "\n", 
		FILE_APPEND);
}
