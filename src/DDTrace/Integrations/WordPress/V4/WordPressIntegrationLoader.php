<?php

namespace DDTrace\Integrations\WordPress\V4;

use DDTrace\Contracts\Scope;
use DDTrace\GlobalTracer;
use DDTrace\Integrations\WordPress\WordPressSandboxedIntegration;
use DDTrace\Integrations\Integration;
use DDTrace\Contracts\Span;
use DDTrace\SpanData;
use DDTrace\Tag;
use DDTrace\Type;

class WordPressIntegrationLoader
{
    /**
     * @var Span
     */
    public $rootSpan;

    public function load(WordPressSandboxedIntegration $integration)
    {
        $scope = GlobalTracer::get()->getRootScope();
        if (!$scope instanceof Scope) {
            return;
        }
        $this->rootSpan = $scope->getSpan();
        // Overwrite the default web integration
        $this->rootSpan->setIntegration($integration);
        $this->rootSpan->setTraceAnalyticsCandidate();
        $this->rootSpan->overwriteOperationName('wordpress.request');
        $this->rootSpan->setTag(Tag::SERVICE_NAME, WordPressSandboxedIntegration::getAppName());

        $loader = $this;

        // Core
        /*
        dd_trace_function('wp_initial_constants', function (SpanData $span) {
            $span->name = $span->resource = 'wp_initial_constants';
            $span->type = Type::WEB_SERVLET;
            $span->service = WordPressSandboxedIntegration::getAppName();
        });
        */

        dd_trace_method('WP', 'main', function (SpanData $span) {
            $span->name = $span->resource = 'WP.main';
            $span->type = Type::WEB_SERVLET;
            $span->service = WordPressSandboxedIntegration::getAppName();
        });

        dd_trace_method('WP', 'init', function (SpanData $span) {
            $span->name = $span->resource = 'WP.init';
            $span->type = Type::WEB_SERVLET;
            $span->service = WordPressSandboxedIntegration::getAppName();
        });

        dd_trace_method('WP', 'parse_request', function (SpanData $span) use ($loader) {
            $span->name = $span->resource = 'WP.parse_request';
            $span->type = Type::WEB_SERVLET;
            $span->service = WordPressSandboxedIntegration::getAppName();

            $url = add_query_arg($_GET, $this->request);
            $loader->rootSpan->setTag(
                Tag::RESOURCE_NAME,
                $_SERVER['REQUEST_METHOD'] . ' /' . $url
            );
            $loader->rootSpan->setTag(Tag::HTTP_URL, home_url($url));
        });

        dd_trace_method('WP', 'send_headers', function (SpanData $span) {
            $span->name = $span->resource = 'WP.send_headers';
            $span->type = Type::WEB_SERVLET;
            $span->service = WordPressSandboxedIntegration::getAppName();
        });

        dd_trace_method('WP', 'query_posts', function (SpanData $span) {
            $span->name = $span->resource = 'WP.query_posts';
            $span->type = Type::WEB_SERVLET;
            $span->service = WordPressSandboxedIntegration::getAppName();
        });

        dd_trace_method('WP', 'handle_404', function (SpanData $span) {
            $span->name = $span->resource = 'WP.handle_404';
            $span->type = Type::WEB_SERVLET;
            $span->service = WordPressSandboxedIntegration::getAppName();
        });

        dd_trace_method('WP', 'register_globals', function (SpanData $span) {
            $span->name = $span->resource = 'WP.register_globals';
            $span->type = Type::WEB_SERVLET;
            $span->service = WordPressSandboxedIntegration::getAppName();
        });

        /*
        dd_trace_function('wp_load_alloptions', function (SpanData $span) {
            $span->name = $span->resource = 'wp_load_alloptions';
            $span->type = Type::WEB_SERVLET;
            $span->service = WordPressSandboxedIntegration::getAppName();
        });
        */

        dd_trace_function('do_action', function (SpanData $span, array $args) {
            $span->name = 'do_action';
            $span->resource = $args[0];
            $span->type = Type::WEB_SERVLET;
            $span->service = WordPressSandboxedIntegration::getAppName();
            $span->meta = [
                'wordpress.action' => $args[0],
            ];
        });

        // Database
        dd_trace_method('wpdb', '__construct', function (SpanData $span, array $args) {
            $span->name = $span->resource = 'wpdb.__construct';
            $span->type = Type::SQL;
            $span->service = WordPressSandboxedIntegration::getAppName();
            $span->meta = [
                'db.user' => (string) $args[0],
                'db.name' => (string) $args[2],
                'db.host' => (string) $args[3],
            ];
        });

        dd_trace_method('wpdb', 'query', function (SpanData $span, array $args) {
            $span->name = 'wpdb.query';
            $span->resource = (string) $args[0];
            $span->type = Type::SQL;
            $span->service = WordPressSandboxedIntegration::getAppName();
        });

        // Views
        dd_trace_function('get_header', function (SpanData $span, array $args) {
            $span->name = 'get_header';
            $span->resource = !empty($args[0]) ? (string) $args[0] : $span->name;
            $span->type = Type::WEB_SERVLET;
            $span->service = WordPressSandboxedIntegration::getAppName();
        });

        dd_trace_function('load_template', function (SpanData $span, array $args) {
            $span->name = 'load_template';
            $span->resource = !empty($args[0]) ? (string) $args[0] : $span->name;
            $span->type = Type::WEB_SERVLET;
            $span->service = WordPressSandboxedIntegration::getAppName();
        });

        dd_trace_function('comments_template', function (SpanData $span, array $args) {
            $span->name = 'comments_template';
            $span->resource = !empty($args[0]) ? (string) $args[0] : $span->name;
            $span->type = Type::WEB_SERVLET;
            $span->service = WordPressSandboxedIntegration::getAppName();
        });

        dd_trace_function('get_sidebar', function (SpanData $span, array $args) {
            $span->name = 'get_sidebar';
            $span->resource = !empty($args[0]) ? (string) $args[0] : $span->name;
            $span->type = Type::WEB_SERVLET;
            $span->service = WordPressSandboxedIntegration::getAppName();
        });

        dd_trace_function('get_footer', function (SpanData $span, array $args) {
            $span->name = 'get_footer';
            $span->resource = !empty($args[0]) ? (string) $args[0] : $span->name;
            $span->type = Type::WEB_SERVLET;
            $span->service = WordPressSandboxedIntegration::getAppName();
        });

        // Cache

        return Integration::LOADED;
    }
}
