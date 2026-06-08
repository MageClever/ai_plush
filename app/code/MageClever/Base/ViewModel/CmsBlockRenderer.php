<?php
/**
 * Renders a CMS block by identifier as fully-filtered HTML (directives + widgets
 * processed via FilterProvider). Lets templates pull editable content without
 * hardcoding markup. Prefer the layout `Magento\Cms\Block\Block` for plain static
 * placement; use this when a template needs the block conditionally or inline.
 */
declare(strict_types=1);

namespace MageClever\Base\ViewModel;

use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\GetBlockByIdentifierInterface;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class CmsBlockRenderer implements ArgumentInterface
{
    public function __construct(
        private readonly GetBlockByIdentifierInterface $getBlockByIdentifier,
        private readonly FilterProvider $filterProvider,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Fully-filtered HTML for a CMS block identifier, or '' if missing/inactive.
     */
    public function getBlockHtml(string $identifier): string
    {
        try {
            $storeId = (int) $this->storeManager->getStore()->getId();
            $block = $this->getBlockByIdentifier->execute($identifier, $storeId);

            if (!(bool) $block->getData(BlockInterface::IS_ACTIVE)) {
                return '';
            }

            return $this->filterProvider->getBlockFilter()
                ->setStoreId($storeId)
                ->filter((string) $block->getContent());
        } catch (NoSuchEntityException) {
            return '';
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('CmsBlockRenderer failed for "%s": %s', $identifier, $e->getMessage())
            );
            return '';
        }
    }
}
