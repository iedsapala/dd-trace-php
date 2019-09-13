<?php

namespace DDTrace\Integrations\CodeIgniter\V2_2;

use DDTrace\Configuration;
use DDTrace\Contracts\Span;
use DDTrace\GlobalTracer;
use DDTrace\Integrations\SandboxedIntegration;
use DDTrace\SpanData;
use DDTrace\Tag;
use DDTrace\Type;

class CodeIgniterSandboxedIntegration extends SandboxedIntegration
{
    const NAME = 'codeigniter';

    /**
     * @return string The integration name.
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * Add instrumentation to CodeIgniter requests
     */
    public function init()
    {

        $tracer = GlobalTracer::get();
        if (!$tracer) {
            return SandboxedIntegration::NOT_LOADED;
        }

        $integration = $this;
        $rootScope = $tracer->getRootScope();
        $service = Configuration::get()->appName(self::NAME);

        \dd_trace_method('CI_Router', '_set_routing',
            function () use ($integration, $rootScope, $service) {

                /* After _set_routing has been called the class and method are
                 * known, so now we can set up tracing on CodeIgniter. */
                $integration->register_integration($this, $rootScope->getSpan(), $service);
                // at the time of this writing, dd_untrace does not work with methods
                //\dd_untrace('CI_Router', '_set_routing');
                return false;
            });
    }

    public function register_integration(\CI_Router $router, Span $root, $service) {
        $root->setIntegration($this);
        $root->setTraceAnalyticsCandidate();

        $root->overwriteOperationName('codeigniter.request');
        $root->setTag(Tag::SERVICE_NAME, $service);
        $root->setTag(Tag::RESOURCE_NAME, "{$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}");
        $root->setTag(Tag::SPAN_TYPE, Type::WEB_SERVLET);

        $controller = $router->fetch_class();
        $method = $router->fetch_method();

        \dd_trace_method($controller, $method,
            function (SpanData $span) use ($root, $method, $service) {
                $class = \get_class($this);
                $span->name = "codeigniter.controller";
                $span->resource = "{$class}.{$method}";
                $span->service = $service;
                $span->type = Type::WEB_SERVLET;

                $root->setTag('app.endpoint', "{$class}::{$method}");
            });

        /* From https://codeigniter.com/userguide2/general/controllers.html:
         * If your controller contains a function named _remap(), it will
         * always get called regardless of what your URI contains. It
         * overrides the normal behavior in which the URI determines which
         * function is called, allowing you to define your own function
         * routing rules.
         */
        \dd_trace_method($controller, '_remap',
            function (SpanData $span, $args, $retval, $ex) use ($root, $service) {
                $class = \get_class($this);

                $span->name = "codeigniter.controller";
                $span->resource = "{$class}._remap";
                $span->service = $service;
                // TODO: what to name this meta var?
                if (!$ex && isset($args[0])) {
                    $span->meta['codeigniter._remap.method'] = (string) $args[0];
                }
                $span->type = Type::WEB_SERVLET;

                $root->setTag('app.endpoint', "{$class}::_remap");
            });

        \dd_trace_method('CI_Loader', 'view',
            function (SpanData $span, $args, $retval, $ex) use ($service) {
                $span->name = "codeigniter.view";
                $span->service = $service;
                $span->resource = !$ex && isset($args[0]) ? (string) $args[0] : $span->name;
                $span->type = Type::WEB_SERVLET;
            });

        \dd_trace_method('DB',
            function (SpanData $span, $args, $retval, $ex) use (&$is_database_registered, $service) {
                if (!$ex) {
                    /* The database is only conditionally returned, and if so
                     * it isn't set on the on the CodeIgniter super object. */
                    $db = \is_object($retval) ? $retval : get_instance()->db;

                    $class = \get_class($db);
                    \dd_trace_method($class, 'query',
                        function (SpanData $span, $args, $retval, $ex) use ($class, $service) {
                            if (\dd_trace_tracer_is_limited()) {
                                return false;
                            }
                            $span->name = 'codeigniter.db.query';
                            $span->service = $service;
                            $span->type = Type::SQL;
                            $span->resource = $ex ? $span->name : (string)$args[0];
                            $span->meta['codeigniter.db.driver'] = $class;
                        }
                    );

                    /* CI_DB_Cache does file I/O; we should consider tracing it in the future */

                    \dd_untrace('DB');
                }
                return false;
            });

        /* We can't just trace CI_Cache's methods, unfortunately. This
         * pattern is provided in CodeIgniter's documentation:
         *     $this->load->driver('cache')
         *     $this->cache->memcached->save('foo', 'bar', 10);
         * Which avoids get, save, delete, etc, on CI_Cache. But CI_Cache
         * requires a driver, so we can intercept the driver at __get.
         */
        $registered_cache_adapters = array();
        \dd_trace_method('CI_Cache', '__get',
            function (SpanData $span, $args, $retval, $ex) use ($service, &$registered_cache_adapters) {
                if (!$ex && is_object($retval)) {
                    $class = \get_class($retval);
                    if (!isset($registered_cache_adapters[$class])) {
                        CodeIgniterSandboxedIntegration::registerCacheAdapter($class, $service);
                        $registered_cache_adapters[$class] = true;
                    }
                }
                return false;
            }
        );
    }

    /**
     * @param string $adapter
     * @param string $service
     */
    public static function registerCacheAdapter($adapter, $service) {
        \dd_trace_method($adapter, 'get',
            function (SpanData $span, $args, $retval, $ex) use ($adapter, $service) {
                $span->name = 'codeigniter.cache';
                $span->service = $service;
                $span->type = Type::CACHE;
                $span->meta['codeigniter.cache.adapter'] = $adapter;
                $key =  !$ex && isset($args[0]) ? " {$args[0]}" : '';
                $span->resource = "get{$key}";
            });

        \dd_trace_method($adapter, 'save',
            function (SpanData $span, $args, $retval, $ex) use ($adapter, $service) {
                $span->name = 'codeigniter.cache';
                $span->service = $service;
                $span->type = Type::CACHE;
                $span->meta['codeigniter.cache.adapter'] = $adapter;
                $key =  !$ex && isset($args[0]) ? " {$args[0]}" : '';
                $span->resource = "save'{$key}";
            });

        \dd_trace_method($adapter, 'delete',
            function (SpanData $span, $args, $retval, $ex) use ($adapter, $service) {
                $span->name = 'codeigniter.cache';
                $span->service = $service;
                $span->type = Type::CACHE;
                $span->meta['codeigniter.cache.adapter'] = $adapter;
                $key =  !$ex && isset($args[0]) ? " {$args[0]}" : '';
                $span->resource = "delete{$key}";
            });

        \dd_trace_method($adapter, 'clean',
            function (SpanData $span, $args, $retval, $ex) use ($adapter, $service) {
                $span->name = 'codeigniter.cache';
                $span->service = $service;
                $span->type = Type::CACHE;
                $span->meta['codeigniter.cache.adapter'] = $adapter;
                $span->resource = 'clean';
            });
    }

}
