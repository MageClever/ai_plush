<?php
/**
 * Seeds the announcement-bar CMS block (ink strip above the header).
 * Editable in admin → Content → Blocks (identifier prefixed `aiplush_`).
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

class CreateAnnouncementBlock implements DataPatchInterface
{
    private const IDENTIFIER = 'aiplush_announcement_bar';

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

        try {
            // Idempotency guard — skip if already present for All Store Views.
            $this->getBlockByIdentifier->execute(self::IDENTIFIER, Store::DEFAULT_STORE_ID);
        } catch (NoSuchEntityException) {
            $this->createBlock();
        } catch (\Throwable $e) {
            $this->logger->error('CreateAnnouncementBlock: ' . $e->getMessage());
        }

        $this->moduleDataSetup->getConnection()->endSetup();
        return $this;
    }

    private function createBlock(): void
    {
        // Seeded in English (default / All Store Views). Localize per store view in
        // Admin → Content → Blocks; the storefront wrapper template adds the dismiss control.
        $content = <<<HTML
<span class="aip-announce__msg">Free shipping on orders over 500,000₫ &middot; 7-day returns &middot; Secure payment</span>
<a class="aip-announce__link" href="#">Learn more</a>
HTML;

        /** @var BlockInterface $block */
        $block = $this->blockFactory->create();
        $block->setIdentifier(self::IDENTIFIER)
            ->setTitle('AI Plush — Announcement Bar')
            ->setIsActive(true)
            ->setContent($content);
        // Store linkage is read from the `stores` data key (NOT store_id).
        $block->setData('stores', [Store::DEFAULT_STORE_ID]);

        $this->blockRepository->save($block);
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
