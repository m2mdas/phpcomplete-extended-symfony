<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Bundle\FrameworkBundle\CacheWarmer\TemplateFinder;
use Symfony\Bundle\FrameworkBundle\Templating\Loader\TemplateLocator;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateFilenameParser;
use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Config\FileLocator as TemplateFileLocator;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;

class symfony //implements PhpCompletePluginInterface
{
    const ENV = 'dev';

    /**
     * @var array
     */
    protected $index = array();

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ContainerBuilder
     */
    protected $containerBuilder;

    /**
     * @var array
     */
    protected $bundles = array();

    /**
     * @var array
     */
    protected $parameters = array();

    /**
     * @var array
     */
    protected $services = array();

    /**
     * @var array
     */
    protected $tag_services = array();

    /**
     * @var array
     */
    protected $alias_services = array();

    /**
     * @var array
     */
    protected $routes = array();

    /**
     * @var array
     */
    protected $entities = array();

    /**
     * @var array
     */
    protected $doctrineData = array();

    /**
     * @var array
     */
    protected $twigFunctions = array();

    /**
     * @var array
     */
    protected $templates = array();

    /**
     * @var array
     */
    protected $annotations = array();

    /**
     * {@inheritdoc}
     */
    public function isValidForProject()
    {
        $finder = new Filesystem();

        return $finder->exists('app/AppKernel.php');
    }

    /**
     * {@inheritdoc}
     */
    public function init($loader)
    {
        if (extension_loaded('apc') && ini_get('apc_enabled')) {
            $loader = new ApcClassLoader('sf2', $loader);
            $loader->register(true);
        }
        AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
    }

    /**
     * {@inheritdoc}
     */
    public function postProcess($fqcn, $file, $classData)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function postCreateIndex($fullIndex, $generatorObject)
    {
        $this->process($fullIndex);
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdateIndex($prevIndex)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function postUpdateIndex($classData, $fullIndex, $generatorObject)
    {
        $this->process($fullIndex);
    }

    /**
     * {@inheritdoc}
     */
    public function getIndex()
    {
        $index = array(
            'parameters' => $this->parameters,
            'bundles' => $this->bundles,
            'templates' => $this->templates,
            'services' => $this->services,
            'alias_services' => $this->alias_services,
            'tag_services' => $this->tag_services,
            'routes' => $this->routes,
            'doctrine_data' => $this->doctrineData,
        );

        return $index;
    }

    /**
     * @param mixed $fullIndex
     *
     * @throws Exception
     */
    protected function process($fullIndex)
    {
        $this->loadKernelClasses();

        $this->kernel = new AppKernel(self::ENV, true);
        $this->kernel->boot();
        $this->container = $this->kernel->getContainer();
        $this->containerBuilder = $this->getContainerBuilder();

        $this->processBundles();
        $this->processViews();
        $this->processParameters($fullIndex);
        $this->processServices($fullIndex);
        $this->processRoutes($fullIndex);
        $this->processDoctrineData($fullIndex);
        $this->processTwigFunctions($fullIndex);
        $this->processTemplates($fullIndex);
        $this->processAnnotations($fullIndex);
    }

    /**
     * Load kernel classes
     */
    protected function loadKernelClasses()
    {
        $finder = new Finder();
        $finder->name('*Kernel.php')->depth(0)->in('app/');
        $results = iterator_to_array($finder);

        if (!count($results)) {
            return;
        }

        $file = current($results);

        require_once $file;
    }

    /**
     * @return ContainerBuilder
     * @throws Exception
     */
    protected function getContainerBuilder()
    {
        if (!$this->kernel->isDebug()) {
            throw new \Exception('Debug mode required');
        }

        $cachedFile = $this->container->getParameter('debug.container.dump');
        $container = new ContainerBuilder();

        $loader = new XmlFileLoader($container, new FileLocator());
        $loader->load($cachedFile);

        return $container;
    }

    /**
     * @throws Exception
     */
    protected function processBundles()
    {
        $bundlesMap = $this->container->getParameter('kernel.bundles');

        /**
         * @todo: looks like this cache is excess
         *      try to remove if bundle dir is not required parameter
         *      pass $bundlesMap keys to index
         */
        $FQCNs = array();
        if (!empty($fullIndex['fqcn_file'])) {
            $FQCNs = $fullIndex['fqcn_file'];
        }

        foreach ($bundlesMap as $bundleName => $bundleFQCN) {
            if (!empty($FQCNs[$bundleFQCN])) {
                $bundleFile = $FQCNs[$bundleFQCN];
                $bundleDir = pathinfo($bundleFile, PATHINFO_DIRNAME);
            } else {
                $bundleDir = $this->kernel->locateResource(sprintf('@%s', $bundleName));
                $bundleFile = sprintf('%s%s.php', $bundleDir, $bundleName);
                $FQCNs[$bundleFQCN] = $bundleFile;
            }

            /**
             * @todo: dir seems to be excess with new view processing
             *      all we need is bundle short name only
             */
            $this->bundles[$bundleName] = array(
                'bundle_fqcn' => $bundleFQCN,
                'bundle_dir' => $this->normalizePath($bundleDir)
            );
        }
    }

    /**
     * Create templates list
     */
    protected function processViews()
    {
        $finder = new TemplateFinder(
            $this->kernel,
            new TemplateFilenameParser(),
            $this->kernel->getRootDir()
        );

        $locator = new TemplateLocator(new TemplateFileLocator($this->kernel));

        /** @var TemplateReferenceInterface $template */
        foreach ($finder->findAllTemplates() as $template) {
            $this->templates[$template->getLogicalName()] = $locator->locate($template);
        }
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
            if ($containerBuilder->hasDefinition($serviceId)) {
                $definition = $containerBuilder->getDefinition($serviceId);
                $definitionFQCN = $definition->getClass();
                if (array_key_exists($definitionFQCN, $fqcn_file)) {
                    $definitionFile = $fqcn_file[$definitionFQCN];
                } else {
                    $definitionFile = $this->normalizePath($definition->getFile());
                }
                if (strpos($definitionFile, "app/cache") !== false) { //may be a generator class
                    include $definitionFile;
                    $serviceReflectionClass = new ReflectionClass($definitionFQCN);
                    $parentClass = $serviceReflectionClass->getParentClass();
                    if ($parentClass && array_key_exists($parentClass->getName(), $fqcn_file)) {
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
            } elseif ($containerBuilder->hasAlias($serviceId)) {
                continue;
            } else {
                $serviceObj = $this->containerBuilder->get($serviceId);
                $service_fqcn = get_class($serviceObj);
                if (array_key_exists($service_fqcn, $fqcn_file)) {
                    $service_file = $fqcn_file[$service_fqcn];
                }
                $serviceArray['service_fqcn'] = $service_fqcn;
                $serviceArray['service_file'] = $this->normalizePath($service_file);
            }
            if ($serviceId == 'request') {
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
            $serviceArrayKey = $serviceArray['is_public'] ? 'public' : 'private';
            $services[$serviceArrayKey][$serviceId] = $serviceArray;
        }

        foreach ($aliass as $key => $alias) {
            $alias_service = (string)$alias;
            $alias_services[$key] = $alias_service;
            if (array_key_exists($alias_service, $services['public'])) {
                $services['public'][$key] = $services['public'][$alias_service];
            }
            if (array_key_exists($alias_service, $services['private'])) {
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
            $routeArray['schemes'] = $route->getSchemes() ? $route->getSchemes() : array('ANY');
            $routeArray['methods'] = $route->getMethods() ? $route->getMethods() : array('ANY');
            $routeArray['options'] = $route->getOptions();
            $defaults = $route->getDefaults();
            $routeArray['defaults'] = $defaults;
            $routeArray['controller']['fqcn'] = "";
            $routeArray['controller']['file'] = "";
            $routeArray['controller']['start_line'] = 0;
            $routeArray['requirements'] = $route->getRequirements();
            if (array_key_exists('_controller', $defaults)) {
                $controller = $defaults['_controller'];
                if (strpos($controller, "::") !== false) {
                    $splits = explode("::", $controller);
                    $controllerFQCN = $splits[0];
                    $controllerMethod = $splits[1];
                } elseif (strpos($controller, ":") !== false) {
                    $splits = explode(":", $controller);
                    $controllerService = $splits[0];
                    $controllerFQCN = "";
                    if (array_key_exists($controllerService, $this->services['public'])) {
                        $controllerFQCN = $this->services['public'][$controllerService]['service_fqcn'];
                    }
                    $controllerMethod = $splits[1];
                }
                //ld($controllerFQCN);
                //ldd(array_key_exists($controllerFQCN, $fullIndex['classes']));
                if (array_key_exists($controllerFQCN, $fullIndex['classes'])
                    && array_key_exists($controllerMethod, $fullIndex['classes'][$controllerFQCN]['methods']['all'])
                ) {
                    if (!array_key_exists($controllerFQCN, $fqcn_file)) {
                        try {
                            $ref = new ReflectionClass($controllerFQCN);
                            $controllerFile = $ref->getFileName();
                        } catch (\Exception $e) {
                        }
                    } else {
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
                if (!$isEntity) {
                    continue;
                }
                $entityFQCN = $metadata->name;
                if (!array_key_exists($entityFQCN, $fqcn_file)) {
                    try {
                        $ref = new ReflectionClass($entityFQCN);
                        $entityFile = $ref->getFileName();
                    } catch (\Exception $e) {
                    }
                } else {
                    $entityFile = $fqcn_file[$entityFQCN];
                }
                $entityRepository = get_class($manager->getRepository($entityFQCN));
                $entityName = str_replace($metadata->namespace . "\\", "", $metadata->name);
                $bundleName = str_replace("\\", "", str_replace("\\Entity", "", $metadata->namespace));
                $entityIdentifier = $bundleName . ":" . $entityName;
                $doctrineData['entities'][$entityIdentifier]['fqcn'] = $entityFQCN;
                $doctrineData['entities'][$entityIdentifier]['file'] = $entityFile;
                $doctrineData['entities'][$entityIdentifier]['repository'] = $entityRepository;
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
        $containerClass = $containerName . ucfirst($environment) . $debug . 'ProjectContainer';
        $cacheFile = $kernel->getCacheDir() . '/' . $containerClass . '.php.meta';
        $meta = unserialize(file_get_contents($cacheFile));
        //ldd($meta);
    }

    private function normalizePath($path)
    {
        if ($path == "") {
            return "";
        }

        $cwd = str_replace('\\', '/', getcwd()) . "/";
        if (strpos($cwd, ':') == 1) {
            $drive = strtolower(substr($cwd, 0, 2));
            $cwd = substr_replace($cwd, $drive, 0, 2);
        }
        $path = str_replace("\\", '/', $path);
        if (strpos($path, ':') == 1) {
            $drive = strtolower(substr($path, 0, 2));
            $path = substr_replace($path, $drive, 0, 2);
        }
        $path = str_replace($cwd, '', $path);

        return $path;
    }

}

