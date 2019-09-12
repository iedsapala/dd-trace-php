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

        return Integration::LOADED;
    }
}
