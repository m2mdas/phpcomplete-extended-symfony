<?php
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Exception\InactiveScopeException;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Config\ConfigCache;

class symfony
{

    /**
     * @var array
     */
    private $index;

    /**
     * @var Symfony\Component\HttpKernel\KernelInterface
     */
    private $kernel;


    /**
     * @var Symfony\Component\DependencyInjection\Container
     */
    private $container;

    /**
     * @var Symfony\Component\DependencyInjection\ContainerBuilder
     */
    private $containerBuilder;

    /**
     * @var array
     */
    private $bundles;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var array
     */
    private $services;

    /**
     * @var array
     */
    private $tag_services;

    /**
     * @var array
     */
    private $alias_services;

    /**
     * @var array
     */
    private $routes;

    /**
     * @var array
     */
    private $entities;

    /**
     * @var array
     */
    private $doctrineData;

    /**
     * @var array
     */
    private $twigFunctions;

    /**
     * @var array
     */
    private $viewsKeyPath;

    /**
     * @var array
     */
    private $templates;

    /**
     * @var array
     */
    private $annotations;


    public function __construct()
    {
        $this->index            = array();
        $this->kernel           = new stdClass;
        $this->container        = new stdClass;
        $this->containerBuilder = new stdClass;
        $this->bundles          = array();
        $this->parameters       = array();
        $this->services         = array();
        $this->tag_services     = array();
        $this->alias_services   = array();
        $this->routes           = array();
        $this->entities         = array();
        $this->viewsKeyPath     = array();
        $this->doctrineData     = array();
        $this->twigFunctions    = array();
        $this->templates        = array();
        $this->forms            = array();
        $this->validators       = array();
        $this->annotations      = array();
    }

    /**
     * called to check that this plugin script is valid for current project
     *
     * @return bool
     */
    public function isValidForProject()
    {
        return is_file('app/AppKernel.php');
    }

    /**
     * This hook is called before indexing started. Bootstraping code of the
     * framework goes here
     * @param loader $loader locader object
     */
    public function init($loader)
    {
        if(extension_loaded('apc') && ini_get('apc_enabled')) {
            $loader = new ApcClassLoader('sf2', $loader);
            $loader->register(true);
        }
        AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

    }

    /**
     * Called after a class is processed
     *
     * @param string $fqcn FQCN of the class
     * @param string $file file name of the class
     * @param array $classData processed class info
     */
    public function postProcess($fqcn, $file, $classData)
    {
    }

    /**
     * Called after main index is created
     *
     * @param mixed $fullIndex the main index
     * @param object $generatorObject
     */
    public function postCreateIndex($fullIndex, $generatorObject)
    {
        $this->process($fullIndex);
    }

    /**
     * Called after update script initialized
     *
     * @param mixed $prevIndex the previous index of this plugin
     */
    public function preUpdateIndex($prevIndex)
    {

    }

    /**
     * Called after update index created
     *
     * @param mixed $classData class data of processed class
     * @param mixed $fullIndex the main index
     * @param obj $generatorObject
     */
    public function postUpdateIndex($classData, $fullIndex, $generatorObject)
    {
        $this->process($fullIndex);
    }

    /**
     * Returns the plugin index
     *
     * @return mixed the index created by this plugin script
     */
    public function getIndex()
    {
        $index = array(
            'parameters' => $this->parameters,
            'bundles' => $this->bundles,
            'views_key_path' => $this->viewsKeyPath,
            'services' => $this->services,
            'alias_services' =>  $this->alias_services,
            'tag_services' => $this->tag_services,
            'routes' => $this->routes,
            'doctrine_data' => $this->doctrineData,
        );
        return $index;
    }

    private function process($fullIndex)
    {
        //require_once 'app/bootstrap.php.cache';
        require_once 'app/AppKernel.php';
        $env = 'debug';
        $debug = 1;
        $this->kernel = new AppKernel('dev', true);
        //$this->kernel->loadClassCache();
        /* @var $kernel Symfony\Component\HttpKernel\Kernel */
        $this->kernel->boot();
        $this->container = $this->kernel->getContainer();
        $this->containerBuilder = $this->getContainerBuilder();

        $this->processBundles($fullIndex);
        $this->processParameters($fullIndex);
        $this->processServices($fullIndex);
        $this->processRoutes($fullIndex);
        $this->processDoctrineData($fullIndex);
        $this->processTwigFunctions($fullIndex);
        $this->processTemplates($fullIndex);
        $this->processAnnotations($fullIndex);
    }

    private function getContainerBuilder()
    {
        if (!$this->kernel->isDebug()) {
            throw new \Exception("Not in debug mode");
        }

        $cachedFile = $this->container->getParameter('debug.container.dump');
        $container = new ContainerBuilder();

        $loader = new XmlFileLoader($container, new FileLocator());
        $loader->load($cachedFile);

        return $container;
    }

    private function processBundles($fullIndex)
    {
        $kernel = $this->kernel;
        $container = $this->container;
        $containerBuilder = $this->containerBuilder;
        /* @var $containerBuilder Symfony\Component\DependencyInjection\ContainerBuilder */
        /* @var $kernel Symfony\Component\HttpKernel\KernelInterface */
        /* @var $container Symfony\Component\DependencyInjection\Container */
        $bundles = array();
        $bundleParameters  = $container->getParameter('kernel.bundles');
        $fqcn_file = $fullIndex['fqcn_file'];
        $viewsKeyPath = array();

        foreach ($bundleParameters as $bundleName => $bundleFQCN) {
            $bundleFile = "";
            if(!array_key_exists($bundleFQCN, $fqcn_file)) {
                try {
                    $ref = new ReflectionClass($bundleFQCN);
                    $bundleFile = $ref->getFileName();
                } catch (\Exception $e) {
                }
            }else{
                $bundleFile = $fqcn_file[$bundleFQCN];
            }

            if(empty($bundleFile)) {
                continue;
            }

            $bundleDir = pathinfo($bundleFile, PATHINFO_DIRNAME);
            $bundleViewData = $this->processBundleViews($bundleName, $bundleDir);
            $viewsKeyPath = array_merge($viewsKeyPath, $bundleViewData['views_key_path']);
            $bundles[$bundleName] = array(
                'bundle_fqcn' => $bundleFQCN,
                'bundle_dir' => $this->normalizePath($bundleDir),
                'dir_views' => $bundleViewData['dir_views'],
            );
            //views
        }
        $this->bundles = $bundles;
        $this->viewsKeyPath = $viewsKeyPath;
    }

    private function processParameters()
    {
        $kernel = $this->kernel;
        $container = $this->container;
        $containerBuilder = $this->containerBuilder;
        /* @var $containerBuilder Symfony\Component\DependencyInjection\ContainerBuilder */
        /* @var $kernel Symfony\Component\HttpKernel\KernelInterface */
        /* @var $container Symfony\Component\DependencyInjection\Container */
        $this->parameters = $container->getParameterBag()->all();
        ksort($this->parameters);
    }

    private function processServices($fullIndex)
    {
        $kernel = $this->kernel;
        $container = $this->container;
        $containerBuilder = $this->containerBuilder;
        /* @var $containerBuilder Symfony\Component\DependencyInjection\ContainerBuilder */
        /* @var $kernel Symfony\Component\HttpKernel\KernelInterface */
        /* @var $container Symfony\Component\DependencyInjection\Container */
        $fqcn_file = $fullIndex['fqcn_file'];
        $defaultServiceArray = array(
            'service_fqcn' => "",
            'service_file' => "",
            'alias' => false,
            'is_public' => true,
            'scope' => "container",
            'tags' => array()
        );
        $tag_services = array();
        $tags = $containerBuilder->findTags();
        foreach ($tags as $tag) {
            $tag_services[$tag] = array();
        }
        $aliass = $containerBuilder->getAliases();
        $alias_services = array();
        $serviceIds = $containerBuilder->getServiceIds();
        $services = array(
            'public' => array(),
            'private' => array(),
        );
        foreach ($serviceIds as $serviceId) {
            $serviceArray = array();
            if($containerBuilder->hasDefinition($serviceId)) {
                $definition = $containerBuilder->getDefinition($serviceId);
                $definitionFQCN = $definition->getClass();
                if(array_key_exists($definitionFQCN, $fqcn_file)) {
                    $definitionFile = $fqcn_file[$definitionFQCN];
                }else{
                    $definitionFile = $this->normalizePath($definition->getFile());
                }
                if(strpos($definitionFile, "app/cache") !== false){ //may be a generator class
                    include $definitionFile;
                    $serviceReflectionClass = new ReflectionClass($definitionFQCN);
                    $parentClass = $serviceReflectionClass->getParentClass();
                    if($parentClass && array_key_exists($parentClass->getName(), $fqcn_file)) {
                        $definitionFQCN = $parentClass->getName();
                        $definitionFile = $fqcn_file[$definitionFQCN];
                    }
                }
                $serviceArray['service_fqcn'] = $definitionFQCN;
                $serviceArray['service_file'] = $definitionFile;
                $serviceArray['is_public'] = $definition->isPublic();
                $serviceArray['scope'] = $definition->getScope();
                $serviceTags = $definition->getTags();
                foreach ($serviceTags as $tagName => $attr) {
                    $tag_services[$tagName][] = $serviceId;
                }
            }
            elseif($containerBuilder->hasAlias($serviceId)) {
                continue;
            }
            else {
                $serviceObj = $this->containerBuilder->get($serviceId);
                $service_fqcn = get_class($serviceObj);
                if(array_key_exists($service_fqcn, $fqcn_file)) {
                    $service_file = $fqcn_file[$service_fqcn];
                }
                $serviceArray['service_fqcn'] = $service_fqcn;
                $serviceArray['service_file'] = $this->normalizePath($service_file);
            }
            if($serviceId == 'request') {
                $serviceArray = array(
                    'service_fqcn' => "Symfony\Component\HttpFoundation\Request",
                    'service_file' => "vendor/symfony/symfony/src/Symfony/Component/HttpFoundation/Request.php",
                    'alias' => false,
                    'is_public' => true,
                    'scope' => "request",
                    'tags' => array()
                );
            }
            $serviceArray = array_merge($defaultServiceArray, $serviceArray);
            $serviceArrayKey = $serviceArray['is_public']? 'public' : 'private';
            $services[$serviceArrayKey][$serviceId] = $serviceArray;
        }

        foreach ($aliass as $key => $alias) {
            $alias_service = (string) $alias;
            $alias_services[$key] = $alias_service;
            if(array_key_exists($alias_service, $services['public'])) {
                $services['public'][$key] = $services['public'][$alias_service];
            }
            if(array_key_exists($alias_service, $services['private'])) {
                $services['private'][$key] = $services['private'][$alias_service];
            }
        }
        ksort($tag_services);
        ksort($services['public']);
        ksort($services['private']);
        asort($alias_services);
        $this->alias_services = $alias_services;
        $this->tag_services = $tag_services;
        $this->services = $services;
    }

    private function processRoutes($fullIndex)
    {
        $kernel = $this->kernel;
        $container = $this->container;
        $containerBuilder = $this->containerBuilder;
        /* @var $containerBuilder Symfony\Component\DependencyInjection\ContainerBuilder */
        /* @var $kernel Symfony\Component\HttpKernel\KernelInterface */
        /* @var $container Symfony\Component\DependencyInjection\Container */
        $routes = $this->container->get('router')->getRouteCollection()->all();
        $fqcn_file = $fullIndex['fqcn_file'];
        $defaultRouteArray = array(
            'path' => "",
            'host' => "",
            'schemes' => array(),
            'methods' => array(),
            'options' => array(),
            'defaults' => array(),
            'requirements' => array(),
        );
        /* @var $route Symfony\Component\Routing\Route */
        foreach ($routes as $name => $route) {
            $routeArray = array();
            $routeArray['name'] = $name;
            $routeArray['path'] = $route->getpath();
            $routeArray['host'] = '' !== $route->getHost() ? $route->getHost() : 'ANY';
            $routeArray['schemes'] = $route->getSchemes() ?  $route->getSchemes() : array('ANY');
            $routeArray['methods'] = $route->getMethods() ?  $route->getMethods() : array('ANY');
            $routeArray['options'] = $route->getOptions();
            $defaults = $route->getDefaults();
            $routeArray['defaults'] = $defaults;
            $routeArray['controller']['fqcn'] = "";
            $routeArray['controller']['file'] = "";
            $routeArray['controller']['start_line'] = 0;
            $routeArray['requirements'] = $route->getRequirements();
            if(array_key_exists('_controller', $defaults)){
                $controller = $defaults['_controller'];
                if(strpos($controller, "::") !== false) {
                    $splits = explode("::", $controller);
                    $controllerFQCN = $splits[0];
                    $controllerMethod = $splits[1];
                }elseif(strpos($controller, ":") !== false){
                    $splits = explode(":", $controller);
                    $controllerService = $splits[0];
                    $controllerFQCN = "";
                    if(array_key_exists($controllerService, $this->services['public'])) {
                        $controllerFQCN = $this->services['public'][$controllerService]['service_fqcn'];
                    }
                    $controllerMethod = $splits[1];
                }
                //ld($controllerFQCN);
                //ldd(array_key_exists($controllerFQCN, $fullIndex['classes']));
                if(array_key_exists($controllerFQCN, $fullIndex['classes'])
                    && array_key_exists($controllerMethod, $fullIndex['classes'][$controllerFQCN]['methods']['all'])
                ){
                    if(!array_key_exists($controllerFQCN, $fqcn_file)) {
                        try {
                            $ref = new ReflectionClass($controllerFQCN);
                            $controllerFile = $ref->getFileName();
                        } catch (\Exception $e) {
                        }
                    } else{
                        $controllerFile = $fqcn_file[$controllerFQCN];
                    }

                    $controllerMethodStartLine = $fullIndex['classes'][$controllerFQCN]['methods']['all'][$controllerMethod]['startLine'];
                    $routeArray['controller']['file'] = $controllerFile;
                    $routeArray['controller']['fqcn'] = $controllerFQCN;
                    $routeArray['controller']['start_line'] = $controllerMethodStartLine;
                }
                $controller = "";
            }
            $this->routes[$name] = $routeArray;
        }
        ksort($this->routes);
    }

    private function processDoctrineData($fullIndex)
    {
        $kernel = $this->kernel;
        $container = $this->container;
        $containerBuilder = $this->containerBuilder;
        $fqcn_file = $fullIndex['fqcn_file'];
        /* @var $containerBuilder Symfony\Component\DependencyInjection\ContainerBuilder */
        /* @var $kernel Symfony\Component\HttpKernel\KernelInterface */
        /* @var $container Symfony\Component\DependencyInjection\Container */
        /* @var $registry Doctrine\Common\Persistence\ManagerRegistry */
        $registry = $container->get('doctrine');
        $managers = $registry->getManagers();
        $connections = $registry->getConnections();
        $doctrineData = array(
            'entities' => array(),
            'managers' => array()
        );
        /* @var $manager Doctrine\Common\Persistence\ObjectManager */
        foreach ($managers as $managerName => $manager) {
            $doctrineData['managers'][$managerName] = array();
            $metadatas = $manager->getMetadataFactory()->getAllMetadata();
            /* @var $metadata Doctrine\ORM\Mapping\ClassMetadata */
            foreach ($metadatas as $metadata) {
                $isEntity = isset($metadata->isMappedSuperclass) && $metadata->isMappedSuperclass === false;
                if(!$isEntity) {
                    continue;
                }
                $entityFQCN = $metadata->name;
                if(!array_key_exists($entityFQCN, $fqcn_file)) {
                    try {
                        $ref = new ReflectionClass($entityFQCN);
                        $entityFile = $ref->getFileName();
                    } catch (\Exception $e) {
                    }
                } else{
                    $entityFile = $fqcn_file[$entityFQCN];
                }
                $entityRepository = get_class($manager->getRepository($entityFQCN));
                $entityName = str_replace($metadata->namespace ."\\", "", $metadata->name);
                $bundleName = str_replace("\\", "", str_replace("\\Entity", "", $metadata->namespace));
                $entityIdentifier = $bundleName . ":" . $entityName;
                $doctrineData['entities'][$entityIdentifier]['fqcn']= $entityFQCN;
                $doctrineData['entities'][$entityIdentifier]['file']= $entityFile;
                $doctrineData['entities'][$entityIdentifier]['repository']= $entityRepository;
                $doctrineData['managers'][$managerName][$bundleName][] = $entityName;
            }
        }
        /* @var $connection Doctrine\DBAL\Connection */
        //TODO: implement later
        //foreach ($connections as $connectionName => $connection) {
        //}
        ksort($doctrineData['entities']);
        ksort($doctrineData['managers']);
        $this->doctrineData = $doctrineData;
    }

    public function processBundleViews($bundleName, $bundleDir)
    {
        $viewDir = $bundleDir. "/Resources/views";
        $viewsData = array(
            'dir_views' => array(),
            'views_key_path' => array()
        );
        if(!is_dir($viewDir)) {
            return $viewsData;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewDir, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $key => $fileInfo) {
            $baseName = $fileInfo->getBaseName();
            $path = str_replace("\\", "/", $fileInfo->getPath());
            $fileName = str_replace("\\","/", $key);
            $bundlePathKey = trim(str_replace($viewDir, "", $path), "/");
            $viewKey = $bundleName. ":". $bundlePathKey . ":".$baseName;
            if(empty($bundlePathKey)) {
                $bundlePathKey  = "::";
            }
            //$viewKey = $bundleName. ":". $bundlePathKey == 'root_view_dir'? ":": $bundlePathKey . ":".$baseName;
            $viewsData['dir_views'][$bundlePathKey][$baseName] = $fileName;
            $viewsData['views_key_path'][$viewKey] = $fileName;
        }
        return $viewsData;
    }

    private function processTwigFunctions()
    {
        $kernel = $this->kernel;
        $container = $this->container;
        $containerBuilder = $this->containerBuilder;
        /* @var $containerBuilder Symfony\Component\DependencyInjection\ContainerBuilder */
        /* @var $kernel Symfony\Component\HttpKernel\KernelInterface */
        /* @var $container Symfony\Component\DependencyInjection\Container */
        //TODO: later

    }

    private function processTemplates()
    {
        $kernel = $this->kernel;
        $container = $this->container;
        $containerBuilder = $this->containerBuilder;
        /* @var $containerBuilder Symfony\Component\DependencyInjection\ContainerBuilder */
        /* @var $kernel Symfony\Component\HttpKernel\KernelInterface */
        /* @var $container Symfony\Component\DependencyInjection\Container */
        //TODO: later
    }

    private function processAnnotations($fullIndex)
    {
        //TODO: later

    }

    private function processMeta($fullIndex)
    {
        $kernel = $this->kernel;
        $container = $this->container;
        $containerBuilder = $this->containerBuilder;
        /* @var $containerBuilder Symfony\Component\DependencyInjection\ContainerBuilder */
        /* @var $kernel Symfony\Component\HttpKernel\KernelInterface */
        /* @var $container Symfony\Component\DependencyInjection\Container */
        $containerName = $kernel->getName();
        $environment = $kernel->getEnvironment();
        $debug = 'Debug';
        $containerClass = $containerName.ucfirst($environment).$debug.'ProjectContainer';
        $cacheFile = $kernel->getCacheDir().'/'.$containerClass.'.php.meta';
        $meta = unserialize(file_get_contents($cacheFile));
        //ldd($meta);
    }

    private function normalizePath($path)
    {
        if($path == "") {
            return "";
        }

        $cwd = str_replace('\\','/',getcwd())."/";
        if(strpos($cwd, ':') == 1) {
            $drive = strtolower(substr($cwd, 0, 2));
            $cwd = substr_replace($cwd, $drive, 0, 2);
        }
        $path = str_replace("\\", '/', $path);
        if(strpos($path, ':') == 1) {
            $drive = strtolower(substr($path, 0, 2));
            $path = substr_replace($path, $drive, 0, 2);
        }
        $path = str_replace($cwd, '', $path);
        return $path;
    }

}

