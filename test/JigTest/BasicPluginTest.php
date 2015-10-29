<?php

namespace JigTest;

use Jig\Jig;
use Jig\Converter\JigConverter;
use Jig\JigConfig;
use Jig\JigException;
use Jig\Plugin\BasicPlugin;

/**
 * Class BasicPluginTest
 * These tests are not particularly useful. They just add coverage to the code base,
 * to make it easier to see that important stuff is covered.
 */
class BasicPluginTest extends BaseTestCase
{
    /**
     *
     */
    public function setUp()
    {
        parent::setUp();

    }

    public function testCallFilterUnknown()
    {
        $plugin = new BasicPlugin();
        $this->setExpectedException(
            'Jig\JigException'
        );
        $plugin->callFilter('unknownFilter', 'foo');
    }

    public function testCallFunctionUnknown()
    {
        $plugin = new BasicPlugin();
        $this->setExpectedException(
            'Jig\JigException'
        );
        $plugin->callFunction('unknownFunction', ['foo']);
    }
    
    public function testCallRenderBlockStartUnknown()
    {
        $plugin = new BasicPlugin();
        $this->setExpectedException(
            'Jig\JigException'
        );
        $plugin->callBlockRenderStart('unknownBlock', 'foo');
    }
    
    public function testCallRenderBlockEndUnknown()
    {
        $plugin = new BasicPlugin();
        $this->setExpectedException(
            'Jig\JigException'
        );
        $plugin->callBlockRenderEnd('unknownBlock', 'foo');
    }
    
    public function testLower()
    {
        $plugin = new BasicPlugin();
        $result = $plugin->callFilter('lower', 'FOO');
        $this->assertEquals('foo', $result);
        
        ob_start();
        $result = $plugin->callFunction('var_dump', ['FOO']);
        $contents = ob_get_contents();
        ob_end_clean();

        $result = $plugin->callFunction('memory_usage', []);
        $this->assertGreaterThan(0, strlen($result));
    }
}
