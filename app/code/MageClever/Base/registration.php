<?php
/**
 * MageClever_Base — foundation module for the AI Plush storefront.
 * Holds shared CMS content (data patches), ViewModels and Blocks that
 * the theme and feature modules build on.
 */
declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'MageClever_Base',
    __DIR__
);
