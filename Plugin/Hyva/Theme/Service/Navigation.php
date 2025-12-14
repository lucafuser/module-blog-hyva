<?php

namespace MageOS\BlogHyva\Plugin\Hyva\Theme\Service;

use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Helper\Menu;

class Navigation
{
    public function __construct(
        private readonly Menu $menu
    ) {

    }

    public function afterGetMenuTree(
        \Hyva\Theme\Service\Navigation $subject,
        $result
    ) {
        $menu = $result;
        try {
            $blogNode = $this->menu->getBlogNode($result, $menu->getTree());
            if ($blogNode) {
                $result->addChild($blogNode);
            }
        } catch (NoSuchEntityException $e) {

        }

        return $result;
    }
}
