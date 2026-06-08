<?php
/**
 * Seeds the footer CMS blocks: the three link columns (tier 3) and the
 * social + app-badge row (tier 2 white strip). Content is seeded in English
 * (All Store Views) and localized per store view in Admin → Content → Blocks.
 * Markup uses `aip-*` classes the theme styles; social links reference sprite icons.
 */
declare(strict_types=1);

namespace MageClever\Base\Setup\Patch\Data;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Cms\Api\GetBlockByIdentifierInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

class CreateFooterBlocks implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly BlockInterfaceFactory $blockFactory,
        private readonly BlockRepositoryInterface $blockRepository,
        private readonly GetBlockByIdentifierInterface $getBlockByIdentifier,
        private readonly LoggerInterface $logger
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        foreach ($this->blocks() as $identifier => $data) {
            try {
                $this->getBlockByIdentifier->execute($identifier, Store::DEFAULT_STORE_ID);
            } catch (NoSuchEntityException) {
                $this->createBlock($identifier, $data['title'], $data['content']);
            } catch (\Throwable $e) {
                $this->logger->error('CreateFooterBlocks (' . $identifier . '): ' . $e->getMessage());
            }
        }

        $this->moduleDataSetup->getConnection()->endSetup();
        return $this;
    }

    private function createBlock(string $identifier, string $title, string $content): void
    {
        /** @var BlockInterface $block */
        $block = $this->blockFactory->create();
        $block->setIdentifier($identifier)
            ->setTitle($title)
            ->setIsActive(true)
            ->setContent($content);
        // Store linkage is read from the `stores` data key (NOT store_id).
        $block->setData('stores', [Store::DEFAULT_STORE_ID]);

        $this->blockRepository->save($block);
    }

    /**
     * @return array<string, array{title: string, content: string}>
     */
    private function blocks(): array
    {
        $columns = <<<HTML
<div class="aip-foot__cols">
    <div class="aip-foot__col">
        <h3 class="aip-foot__head">Support</h3>
        <ul class="aip-foot__links">
            <li><a href="#">FAQ</a></li>
            <li><a href="#">Purchase policy</a></li>
            <li><a href="#">Shipping</a></li>
            <li><a href="#">Returns</a></li>
            <li><a href="#">Track order</a></li>
            <li><a href="#">Store locator</a></li>
        </ul>
    </div>
    <div class="aip-foot__col">
        <h3 class="aip-foot__head">About AI Plush</h3>
        <ul class="aip-foot__links">
            <li><a href="#">News</a></li>
            <li><a href="#">Artists</a></li>
            <li><a href="#">Our story</a></li>
            <li><a href="#">Careers</a></li>
            <li><a href="#">Contact</a></li>
        </ul>
    </div>
    <div class="aip-foot__col">
        <h3 class="aip-foot__head">Shop</h3>
        <ul class="aip-foot__links">
            <li><a href="#">Drop schedule</a></li>
            <li><a href="#">Trending</a></li>
            <li><a href="#">Blind Boxes</a></li>
            <li><a href="#">MEGA</a></li>
            <li><a href="#">Accessories</a></li>
        </ul>
    </div>
</div>
HTML;

        $social = <<<HTML
<div class="aip-foot__follow">
    <a class="aip-foot__social" href="#" aria-label="Facebook" rel="noopener" target="_blank">
        <svg class="aip-icon aip-icon--md"><use href="#icon-facebook"></use></svg>
    </a>
    <a class="aip-foot__social" href="#" aria-label="Instagram" rel="noopener" target="_blank">
        <svg class="aip-icon aip-icon--md"><use href="#icon-instagram"></use></svg>
    </a>
    <a class="aip-foot__social" href="#" aria-label="TikTok" rel="noopener" target="_blank">
        <svg class="aip-icon aip-icon--md"><use href="#icon-music"></use></svg>
    </a>
</div>
HTML;

        return [
            'aiplush_footer_columns' => [
                'title' => 'AI Plush — Footer Columns',
                'content' => $columns,
            ],
            'aiplush_footer_social' => [
                'title' => 'AI Plush — Footer Social',
                'content' => $social,
            ],
        ];
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
