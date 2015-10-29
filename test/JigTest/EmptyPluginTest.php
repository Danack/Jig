<?php

namespace JigTest;

use Jig\Plugin\EmptyPlugin;

/**
 * Class BasicPluginTest
 * These tests are not particularly useful. They just add coverage to the code base,
 * to make it easier to see that important stuff is covered.
 */
class EmptyPluginTest extends BaseTestCase
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
        $plugin = new EmptyPlugin();
        $this->setExpectedException(
            'Jig\JigException'
        );
        $plugin->callFilter('unknownFilter', 'foo');
    }

    public function testCallFunctionUnknown()
    {
        $plugin = new EmptyPlugin();
        $this->setExpectedException(
            'Jig\JigException'
        );
        $plugin->callFunction('unknownFunction', ['foo']);
    }
    
    public function testCallRenderBlockStartUnknown()
    {
        $plugin = new EmptyPlugin();
        $this->setExpectedException(
            'Jig\JigException'
        );
        $plugin->callBlockRenderStart('unknownBlock', 'foo');
    }
    
    public function testCallRenderBlockEndUnknown()
    {
        $plugin = new EmptyPlugin();
        $this->setExpectedException(
            'Jig\JigException'
        );
        $plugin->callBlockRenderEnd('unknownBlock', 'foo');
    }
    
    public function testLower()
    {
        $plugin = new EmptyPlugin();
        $filters = $plugin->getFilterList();
        $functions = $plugin->getFunctionList();
        $blocks = $plugin->getBlockRenderList();
        
        $this->assertInternalType('array', $filters);
        $this->assertInternalType('array', $functions);
        $this->assertInternalType('array', $blocks);

        $this->assertEmpty($filters);
        $this->assertEmpty($functions);
        $this->assertEmpty($blocks);
    }
}
