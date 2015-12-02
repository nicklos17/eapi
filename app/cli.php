<?php
use Phalcon\DI\FactoryDefault\CLI as CliDI, Phalcon\CLI\Console as ConsoleApp;

define('VERSION', '1.0.0');
// 开发模式 TRUE | 线上模式 FALSE
define('DEBUG', TRUE);
// 使用CLI工厂类作为默认的服务容器
$di = new CliDI();

// 定义应用目录路径
define("ROOT_PATH", dirname(__DIR__) . '/');

$taskType = isset($argv[1])? $argv[1]: 'task';
if(! in_array($taskType, array('tmp','task')))
{
	echo 'one param only support tmp or task';
	exit();
}
$taskPath = ROOT_PATH . '/app/tasks';
if($taskType == 'tmp')
{
	$taskPath .= '/tmp';
}
/**
 * 注册类自动加载器
 */
$loader = new \Phalcon\Loader();
$loader->registerDirs(array($taskPath,ROOT_PATH . '/app/library',ROOT_PATH . '/app/models',ROOT_PATH . '/app/logic'));
$loader->register();

// 创建console应用
$console = new ConsoleApp();
$console->setDI($di);

/**
 * 处理console应用参数
 */
$arguments = array();
foreach($argv as $k => $arg)
{
	if($k == 2)
	{
		$arguments['task'] = $arg;
	}
	elseif($k == 3)
	{
		$arguments['action'] = $arg;
	}
	elseif($k >= 4)
	{
		$arguments['params'][] = $arg;
	}
}

// 定义全局的参数， 设定当前任务及动作
define('CURRENT_TASK', (isset($argv[1])? $argv[1]: null));
define('CURRENT_ACTION', (isset($argv[2])? $argv[2]: null));

try
{
	// 配置初始化
	\core\Config::init(DEBUG);
	// 处理参数
	$console->handle($arguments);
}
catch(\Phalcon\Exception $e)
{
	echo $e->getMessage();
	exit(255);
}