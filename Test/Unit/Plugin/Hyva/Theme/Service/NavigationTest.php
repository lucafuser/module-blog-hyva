<?php

declare(strict_types=1);

namespace MageOS\BlogHyva\Test\Unit\Plugin\Hyva\Theme\Service;

use Hyva\Theme\Service\Navigation as NavigationService;
use Magento\Framework\App\Config as AppConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Tree;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Data\Tree\NodeFactory;
use Magento\Framework\UrlInterface;
use MageOS\Blog\Model\Config;
use MageOS\BlogHyva\Plugin\Hyva\Theme\Service\Navigation;
use PHPUnit\Framework\TestCase;

/**
 * The assertions here are mostly about Hyvä's own consumption of the node.
 *
 * Hyva\Theme\ViewModel\Navigation reads the tree back out in ways that discard
 * a malformed node silently — no exception, no log, the menu entry just is not
 * there. Each of those is pinned below.
 */
class NavigationTest extends TestCase
{
    private const BLOG_URL = 'https://example.com/blog';

    public function testAddsBlogNodeWhenEnabled(): void
    {
        $root = $this->rootNode();

        $result = $this->plugin(true)->afterGetMenuTree($this->subject(), $root);

        $children = $result->getChildren();
        $this->assertCount(1, $children);

        $node = $children->offsetGet('blog-node-0');
        $this->assertInstanceOf(Node::class, $node);
        $this->assertSame('Blog', $node->getData('name'));
        $this->assertSame(self::BLOG_URL, $node->getData('url'));
    }

    public function testAddsNothingWhenModuleDisabled(): void
    {
        $root = $this->rootNode();

        $result = $this->plugin(false)->afterGetMenuTree($this->subject(), $root);

        $this->assertCount(0, $result->getChildren());
    }

    /**
     * ViewModel\Navigation::removeChildrenWithoutActiveParent() deletes any
     * top-level child whose is_parent_active is strictly false.
     */
    public function testNodeSurvivesTheInactiveParentSweep(): void
    {
        $node = $this->buildNode();

        $this->assertNotFalse(
            $node->getData('is_parent_active'),
            'is_parent_active === false makes Hyvä drop the node from the top level'
        );
    }

    /**
     * ViewModel\Navigation::flattenTree() keys nodes on the substring after the
     * last hyphen, so an id without one collapses to the whole string.
     */
    public function testNodeIdParsesUnderFlattenTree(): void
    {
        $id = (string) $this->buildNode()->getData('id');

        $this->assertStringContainsString('-', $id);
        $this->assertSame('0', substr($id, strrpos($id, '-') + 1));
    }

    /**
     * The topmenu block is cached with ttl="3600" keyed on these identities, so
     * a node carrying none would outlive the config change that turns it off.
     */
    public function testNodeCarriesCacheIdentities(): void
    {
        $this->assertSame(
            [AppConfig::CACHE_TAG],
            $this->buildNode()->getData('identities')
        );
    }

    public function testNodeIsActiveOnBlogRoutes(): void
    {
        $this->assertTrue($this->buildNode('blog')->getData('is_active'));
        $this->assertFalse($this->buildNode('catalog')->getData('is_active'));
    }

    private function buildNode(string $moduleName = 'cms'): Node
    {
        $root = $this->rootNode();
        $this->plugin(true, $moduleName)->afterGetMenuTree($this->subject(), $root);

        return $root->getChildren()->offsetGet('blog-node-0');
    }

    private function plugin(bool $enabled, string $moduleName = 'cms'): Navigation
    {
        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn($enabled);

        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturn(self::BLOG_URL);

        $request = $this->createStub(RequestInterface::class);
        $request->method('getModuleName')->willReturn($moduleName);

        $nodeFactory = $this->createStub(NodeFactory::class);
        $nodeFactory->method('create')->willReturnCallback(
            static fn (array $args): Node => new Node(
                $args['data'],
                $args['idField'],
                $args['tree'],
                $args['parent'] ?? null
            )
        );

        return new Navigation($config, $url, $request, $nodeFactory);
    }

    private function subject(): NavigationService
    {
        return $this->createStub(NavigationService::class);
    }

    private function rootNode(): Node
    {
        return new Node([], 'root', new Tree());
    }
}
