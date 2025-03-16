<?php

namespace Laminas\Mvc\Service;

use Laminas\Mvc\View\Http\DefaultRenderingStrategy;
use Interop\Container\ContainerInterface;
use Laminas\ModuleManager\Listener\ServiceListener;
use Laminas\ModuleManager\Listener\ServiceListenerInterface;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\View;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\Factory\InvokableFactory;

class ServiceListenerFactory implements FactoryInterface
{
    /**
     * @var string
     */
    public const MISSING_KEY_ERROR = 'Invalid service listener options detected, %s array must contain %s key.';

    /**
     * @var string
     */
    public const VALUE_TYPE_ERROR = 'Invalid service listener options detected, %s must be a string, %s given.';

    /**
     * Default mvc-related service configuration -- can be overridden by modules.
     *
     * @var array
     */
    protected $defaultServiceConfig = [
        'aliases' => [
            'application'                                  => 'Application',
            'Config'                                       => 'config',
            'configuration'                                => 'config',
            'Configuration'                                => 'config',
            'HttpDefaultRenderingStrategy'                 => DefaultRenderingStrategy::class,
            'MiddlewareListener'                           => \Laminas\Mvc\MiddlewareListener::class,
            'request'                                      => 'Request',
            'response'                                     => 'Response',
            'RouteListener'                                => \Laminas\Mvc\RouteListener::class,
            'SendResponseListener'                         => \Laminas\Mvc\SendResponseListener::class,
            'View'                                         => \Laminas\View\View::class,
            'ViewFeedRenderer'                             => \Laminas\View\Renderer\FeedRenderer::class,
            'ViewJsonRenderer'                             => \Laminas\View\Renderer\JsonRenderer::class,
            'ViewPhpRendererStrategy'                      => \Laminas\View\Strategy\PhpRendererStrategy::class,
            'ViewPhpRenderer'                              => \Laminas\View\Renderer\PhpRenderer::class,
            'ViewRenderer'                                 => \Laminas\View\Renderer\PhpRenderer::class,
            \Laminas\Mvc\Controller\PluginManager::class         => 'ControllerPluginManager',
            \Laminas\Mvc\View\Http\InjectTemplateListener::class => 'InjectTemplateListener',
            \Laminas\View\Renderer\RendererInterface::class      => \Laminas\View\Renderer\PhpRenderer::class,
            \Laminas\View\Resolver\TemplateMapResolver::class    => 'ViewTemplateMapResolver',
            \Laminas\View\Resolver\TemplatePathStack::class      => 'ViewTemplatePathStack',
            \Laminas\View\Resolver\AggregateResolver::class      => 'ViewResolver',
            \Laminas\View\Resolver\ResolverInterface::class      => 'ViewResolver',
            ControllerManager::class                       => 'ControllerManager',
        ],
        'invokables' => [],
        'factories'  => [
            'Application'                               => ApplicationFactory::class,
            'config'                                    => \Laminas\Mvc\Service\ConfigFactory::class,
            'ControllerManager'                         => \Laminas\Mvc\Service\ControllerManagerFactory::class,
            'ControllerPluginManager'                   => \Laminas\Mvc\Service\ControllerPluginManagerFactory::class,
            'DispatchListener'                          => \Laminas\Mvc\Service\DispatchListenerFactory::class,
            'HttpExceptionStrategy'                     => HttpExceptionStrategyFactory::class,
            'HttpMethodListener'                        => \Laminas\Mvc\Service\HttpMethodListenerFactory::class,
            'HttpRouteNotFoundStrategy'                 => HttpRouteNotFoundStrategyFactory::class,
            'HttpViewManager'                           => \Laminas\Mvc\Service\HttpViewManagerFactory::class,
            'InjectTemplateListener'                    => \Laminas\Mvc\Service\InjectTemplateListenerFactory::class,
            'PaginatorPluginManager'                    => \Laminas\Mvc\Service\PaginatorPluginManagerFactory::class,
            'Request'                                   => \Laminas\Mvc\Service\RequestFactory::class,
            'Response'                                  => \Laminas\Mvc\Service\ResponseFactory::class,
            'ViewHelperManager'                         => \Laminas\Mvc\Service\ViewHelperManagerFactory::class,
            DefaultRenderingStrategy::class   => HttpDefaultRenderingStrategyFactory::class,
            'ViewFeedStrategy'                          => \Laminas\Mvc\Service\ViewFeedStrategyFactory::class,
            'ViewJsonStrategy'                          => \Laminas\Mvc\Service\ViewJsonStrategyFactory::class,
            'ViewManager'                               => \Laminas\Mvc\Service\ViewManagerFactory::class,
            'ViewResolver'                              => \Laminas\Mvc\Service\ViewResolverFactory::class,
            'ViewTemplateMapResolver'                   => \Laminas\Mvc\Service\ViewTemplateMapResolverFactory::class,
            'ViewTemplatePathStack'                     => \Laminas\Mvc\Service\ViewTemplatePathStackFactory::class,
            'ViewPrefixPathStackResolver'
                => \Laminas\Mvc\Service\ViewPrefixPathStackResolverFactory::class,
            \Laminas\Mvc\MiddlewareListener::class            => InvokableFactory::class,
            \Laminas\Mvc\RouteListener::class                 => InvokableFactory::class,
            \Laminas\Mvc\SendResponseListener::class          => SendResponseListenerFactory::class,
            \Laminas\View\Renderer\FeedRenderer::class        => InvokableFactory::class,
            \Laminas\View\Renderer\JsonRenderer::class        => InvokableFactory::class,
            \Laminas\View\Renderer\PhpRenderer::class         => ViewPhpRendererFactory::class,
            \Laminas\View\Strategy\PhpRendererStrategy::class => ViewPhpRendererStrategyFactory::class,
            \Laminas\View\View::class                         => ViewFactory::class,
        ],
    ];

    /**
     * Create the service listener service
     *
     * Tries to get a service named ServiceListenerInterface from the service
     * locator, otherwise creates a ServiceListener instance, passing it the
     * container instance and the default service configuration, which can be
     * overridden by modules.
     *
     * It looks for the 'service_listener_options' key in the application
     * config and tries to add service/plugin managers as configured. The value
     * of 'service_listener_options' must be a list (array) which contains the
     * following keys:
     *
     * - service_manager: the name of the service manage to create as string
     * - config_key: the name of the configuration key to search for as string
     * - interface: the name of the interface that modules can implement as string
     * - method: the name of the method that modules have to implement as string
     *
     * @param  ContainerInterface  $container
     * @param  string              $requestedName
     * @param  null|array          $options
     * @return ServiceListenerInterface
     * @throws ServiceNotCreatedException for invalid ServiceListener service
     * @throws ServiceNotCreatedException For invalid configurations.
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $configuration   = $container->get('ApplicationConfig');

        $serviceListener = $container->has('ServiceListenerInterface')
            ? $container->get('ServiceListenerInterface')
            : new ServiceListener($container);

        if (! $serviceListener instanceof ServiceListenerInterface) {
            throw new ServiceNotCreatedException(
                'The service named ServiceListenerInterface must implement '
                .  ServiceListenerInterface::class
            );
        }

        $serviceListener->setDefaultServiceConfig($this->defaultServiceConfig);

        if (isset($configuration['service_listener_options'])) {
            $this->injectServiceListenerOptions($configuration['service_listener_options'], $serviceListener);
        }

        return $serviceListener;
    }

    /**
     * Validate and inject plugin manager options into the service listener.
     *
     * @param array $options
     * @throws ServiceListenerInterface for invalid $options types
     */
    private function injectServiceListenerOptions($options, ServiceListenerInterface $serviceListener)
    {
        if (! is_array($options)) {
            throw new ServiceNotCreatedException(sprintf(
                'The value of service_listener_options must be an array, %s given.',
                (get_debug_type($options))
            ));
        }

        foreach ($options as $key => $newServiceManager) {
            $this->validatePluginManagerOptions($newServiceManager, $key);

            $serviceListener->addServiceManager(
                $newServiceManager['service_manager'],
                $newServiceManager['config_key'],
                $newServiceManager['interface'],
                $newServiceManager['method']
            );
        }
    }

    /**
     * Validate the structure and types for plugin manager configuration options.
     *
     * Ensures all required keys are present in the expected types.
     *
     * @param array $options
     * @param string $name Plugin manager service name; used for exception messages
     * @throws ServiceNotCreatedException for any missing configuration options.
     * @throws ServiceNotCreatedException for configuration options of invalid types.
     */
    private function validatePluginManagerOptions($options, $name)
    {
        if (! is_array($options)) {
            throw new ServiceNotCreatedException(sprintf(
                'Plugin manager configuration for "%s" is invalid; must be an array, received "%s"',
                $name,
                (get_debug_type($options))
            ));
        }

        if (! isset($options['service_manager'])) {
            throw new ServiceNotCreatedException(sprintf(self::MISSING_KEY_ERROR, $name, 'service_manager'));
        }

        if (! is_string($options['service_manager'])) {
            throw new ServiceNotCreatedException(sprintf(
                self::VALUE_TYPE_ERROR,
                'service_manager',
                gettype($options['service_manager'])
            ));
        }

        if (! isset($options['config_key'])) {
            throw new ServiceNotCreatedException(sprintf(self::MISSING_KEY_ERROR, $name, 'config_key'));
        }

        if (! is_string($options['config_key'])) {
            throw new ServiceNotCreatedException(sprintf(
                self::VALUE_TYPE_ERROR,
                'config_key',
                gettype($options['config_key'])
            ));
        }

        if (! isset($options['interface'])) {
            throw new ServiceNotCreatedException(sprintf(self::MISSING_KEY_ERROR, $name, 'interface'));
        }

        if (! is_string($options['interface'])) {
            throw new ServiceNotCreatedException(sprintf(
                self::VALUE_TYPE_ERROR,
                'interface',
                gettype($options['interface'])
            ));
        }

        if (! isset($options['method'])) {
            throw new ServiceNotCreatedException(sprintf(self::MISSING_KEY_ERROR, $name, 'method'));
        }

        if (! is_string($options['method'])) {
            throw new ServiceNotCreatedException(sprintf(
                self::VALUE_TYPE_ERROR,
                'method',
                gettype($options['method'])
            ));
        }
    }
}
