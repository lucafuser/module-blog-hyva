<?php

declare(strict_types=1);

namespace MageOS\BlogHyva\Plugin\Hyva\Theme\Service;

use Hyva\Theme\Service\Navigation as NavigationService;
use Magento\Framework\App\Config as AppConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Data\Tree\NodeFactory;
use Magento\Framework\UrlInterface;
use MageOS\Blog\Model\Config;

/**
 * Adds a top-level "Blog" entry to the Hyvä main menu.
 *
 * MageOS_Blog puts its storefront link in `top.links`, which Hyvä does not
 * define — that referenceBlock matches nothing and is dropped silently, so
 * without this plugin a Hyvä storefront has no route into the blog.
 */
class Navigation
{
    /** Hyvä keys nodes on the substring after the last hyphen — see ViewModel\Navigation::flattenTree(). */
    private const NODE_ID = 'blog-node-0';

    private const ROUTE = 'blog';

    public function __construct(
        private readonly Config $config,
        private readonly UrlInterface $urlBuilder,
        private readonly RequestInterface $request,
        private readonly NodeFactory $nodeFactory
    ) {
    }

    public function afterGetMenuTree(NavigationService $subject, Node $result): Node
    {
        if (!$this->config->isEnabled()) {
            return $result;
        }

        $result->addChild($this->buildNode($result));

        return $result;
    }

    private function buildNode(Node $parent): Node
    {
        $isActive = $this->request->getModuleName() === self::ROUTE;

        return $this->nodeFactory->create([
            'data' => [
                'name' => (string) __('Blog'),
                'id' => self::NODE_ID,
                'url' => $this->urlBuilder->getUrl(self::ROUTE),
                'is_category' => false,
                'is_active' => $isActive,
                'has_active' => $isActive,
                // Must not be false: removeChildrenWithoutActiveParent() drops such nodes.
                'is_parent_active' => true,
                'position' => 0,
                // The topmenu block caches for an hour keyed on these; the entry depends only on config.
                'identities' => [AppConfig::CACHE_TAG],
                'path' => '',
            ],
            'idField' => 'id',
            'tree' => $parent->getTree(),
            'parent' => $parent,
        ]);
    }
}
