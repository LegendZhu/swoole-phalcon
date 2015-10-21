<?php
/**
 * Created by PhpStorm.
 * User: Lin Wang
 * Date: 2015/8/27
 * Time: 16:06
 */
class App{
    public static function run()
    {
        global $di;
        $di = new \Phalcon\DI\FactoryDefault();
        self::initSystemConst();
        self::initSystemService();
        self::initAutoloaders();
        $application = new \Phalcon\Mvc\Application($di);
        //Register the installed modules
        $application->registerModules(array(
            'home' => array(
                'className' => 'Application\Home\Module',
                'path' => '../application/modules/home/module.php'
            ),
            'admin' => array(
                'className' => 'Application\Admin\Module',
                'path' => '../application/modules/admin/module.php'
            ),
            'api' => array(
                'className' => 'Application\Api\Module',
                'path' => '../application/modules/Api/module.php'
            )
        ));
        echo $application->handle()->getContent();
    }

    //��ʼ��ϵͳ����
    public static function initSystemConst()
    {
        define('ROOT_PATH', realpath('../'));
        define('PUBLIC_PATH',ROOT_PATH."/public");
        define('APP_PATH',ROOT_PATH."/application");
        define('COMMON_PATH',APP_PATH."/common");
        define('MODULES_PATH',APP_PATH.'/modules');
        define('CONFIG_PATH',ROOT_PATH."/config");
        define('RUNTIME_PATH',ROOT_PATH . '/runtime');
        define('CACHE_PATH',RUNTIME_PATH."/cache");
        define('LOGS_PATH',RUNTIME_PATH."/logs");
        define('FUNCTIONS_PATH',COMMON_PATH."/function");
        define('LIB_PATH',COMMON_PATH."/library");
        define('PLUGINS_PATH',COMMON_PATH."/plugins");
    }

    //��ʼ��ϵͳ����
    public static function initSystemService()
    {
        global $di;
        //��ȡ������
        $config = require(CONFIG_PATH . "/config.php");
        $di->setShared('config', function() use ($config){
            return $config;
        });

        //����master���ݿ�
        $di->setShared('dbMaster', function() use ($config) {
            return new \Phalcon\Db\Adapter\Pdo\Mysql($config->dbMaster->toArray());
        });
        //����slave1���ݿ�
        $di->setShared('dbSlave1', function() use ($config) {
            return new \Phalcon\Db\Adapter\Pdo\Mysql($config->dbSlave1->toArray());
        });
        //����slave2���ݿ�
        $di->setShared('dbSlave2', function() use ($config) {
            return new \Phalcon\Db\Adapter\Pdo\Mysql($config->dbSlave2->toArray());
        });

        //����redis����
        $di->setShared('redis', function() use ($config){
            $frontCache = new \Phalcon\Cache\Frontend\Data($config->cache_life->toArray());
            return new Phalcon\Cache\Backend\Redis($frontCache, $config->redis->toArray());
        });

        //����Beanstalk����
        $di->setShared('queue', function() use ($config){
            return new Phalcon\Queue\Beanstalk($config->beanstalk->toArray());
        });
		
		//����session
		$di->setShared('session', function () {
			$session = new \Phalcon\Session\Adapter\Files();
			$session->start();
			return $session;
		});

        //����router
        $di->set('router', function () {
            $router = new \Phalcon\Mvc\Router();
            $router->setDefaultModule("home");
            $router->add('/:module/:controller/:action', array(
                'module' => 1,
                'controller' => 2,
                'action' => 3,
            ));
            return $router;
        });
    }

    //��ʼ��loader
    public static function initAutoloaders()
    {
        $loader = new \Phalcon\Loader();
        //ע�ṫ�������ռ�
        $loader->registerNamespaces(array(
            'Application\Common\Controller' 	=> '../application/common/controller/',
            'Application\Common\Model' 		    => '../application/common/model/'
        ))->register();

        //ע�ṫ��������������,����������
        $loader->registerDirs(array(
            LIB_PATH,
            PLUGINS_PATH,
            FUNCTIONS_PATH
        ))->register();
    }
}
