<?php

namespace DDTrace\Tests\Integrations\CodeIgniter\V2_2;

use DDTrace\Tests\Common\SpanAssertion;
use DDTrace\Tests\Common\WebFrameworkTestCase;
use DDTrace\Tests\Frameworks\Util\Request\RequestSpec;
use DDTrace\Type;

final class CommonScenariosTest extends WebFrameworkTestCase
{
    const IS_SANDBOX = true;

    protected static function getAppIndexScript()
    {
        return __DIR__ . '/../../../Frameworks/CodeIgniter/V2_2/index.php';
    }

    protected static function getEnvs()
    {
        return array_merge(parent::getEnvs(), [
            'DD_SERVICE_NAME' => 'codeigniter_test_app',
        ]);
    }

    /**
     * @dataProvider provideSpecs
     * @param RequestSpec $spec
     * @param array $spanExpectations
     * @throws \Exception
     */
    public function testScenario(RequestSpec $spec, array $spanExpectations)
    {
        $traces = $this->tracesFromWebRequest(function () use ($spec) {
            $this->call($spec);
        });

        $this->assertExpectedSpans($traces, $spanExpectations);
    }

    public function provideSpecs()
    {
        return $this->buildDataProvider(
            [
                'A simple GET request returning a string' => [
                    SpanAssertion::build(
                        'codeigniter.request',
                        'codeigniter_test_app',
                        'web',
                        'GET /simple'
                    )->withExactTags([
                        'http.method' => 'GET',
                        'http.url' => '/simple',
                        'http.status_code' => '200',
                        'integration.name' => 'codeigniter',
                        'app.endpoint' => 'Simple::index',
                    ]),
                    SpanAssertion::build(
                        'codeigniter.controller',
                        'codeigniter_test_app',
                        Type::WEB_SERVLET,
                        'Simple.index'
                    ),
                ],
                'A simple GET request with a view' => [
                    SpanAssertion::build(
                        'codeigniter.request',
                        'codeigniter_test_app',
                        'web',
                        'GET /simple_view'
                    )->withExactTags([
                        'http.method' => 'GET',
                        'http.url' => '/simple_view',
                        'http.status_code' => '200',
                        'integration.name' => 'codeigniter',
                        'app.endpoint' => 'Simple_View::index',
                    ]),
                    SpanAssertion::build(
                        'codeigniter.controller',
                        'codeigniter_test_app',
                        Type::WEB_SERVLET,
                        'Simple_View.index'
                    ),
                    SpanAssertion::build(
                        'codeigniter.view',
                        'codeigniter_test_app',
                        Type::WEB_SERVLET,
                        'simple_view'
                    ),
                ],
                'A GET request with an exception' => [
                    SpanAssertion::build(
                        'codeigniter.request',
                        'codeigniter_test_app',
                        'web',
                        'GET /error'
                    )->withExactTags([
                        'http.method' => 'GET',
                        'http.url' => '/error',
                        // todo: does CodeIgniter set the proper error code?
                        'http.status_code' => '200',
                        'integration.name' => 'codeigniter',
                        'app.endpoint' => 'Error_::index',
                    ]),
                    SpanAssertion::build(
                        'codeigniter.controller',
                        'codeigniter_test_app',
                        Type::WEB_SERVLET,
                        'Error_.index'
                    )->setError('Exception', 'datadog', true),
                ],
            ]
        );
    }
}
