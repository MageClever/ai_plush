<?php
/**
 * Sets up the storefront's two store views under the default website/group:
 *   - "vi"      → Vietnamese (vi_VN) — becomes the group's DEFAULT store view
 *   - "default" → renamed to "English" (en_US)
 *
 * Idempotent: guards on store code / current value before every write.
 * After this patch: setup:static-content:deploy vi_VN + reindex + cache:flush
 * so the new locale renders (and i18n/vi_VN.csv dictionaries are generated).
 */
declare(strict_types=1);

namespace MageClever\Base\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\ResourceModel\Group as GroupResource;
use Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreFactory;
use Psr\Log\LoggerInterface;

class CreateVietnameseStoreView implements DataPatchInterface
{
    private const VI_CODE = 'vi';
    private const VI_NAME = 'Vietnamese';
    private const VI_LOCALE = 'vi_VN';

    private const EN_CODE = 'default';
    private const EN_NAME = 'English';
    private const EN_LOCALE = 'en_US';

    private const LOCALE_PATH = 'general/locale/code';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly StoreFactory $storeFactory,
        private readonly StoreResource $storeResource,
        private readonly GroupFactory $groupFactory,
        private readonly GroupResource $groupResource,
        private readonly WebsiteRepositoryInterface $websiteRepository,
        private readonly WriterInterface $configWriter,
        private readonly LoggerInterface $logger
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        try {
            $website = $this->websiteRepository->getDefault();
            $websiteId = (int) $website->getId();
            $groupId = (int) $website->getDefaultGroupId();

            $viStore = $this->ensureViStore($websiteId, $groupId);
            $this->renameEnglishStore();
            $this->setLocales((int) $viStore->getId());
            $this->setGroupDefaultStore($groupId, (int) $viStore->getId());
        } catch (\Throwable $e) {
            $this->logger->error('CreateVietnameseStoreView: ' . $e->getMessage());
        }

        $this->moduleDataSetup->getConnection()->endSetup();
        return $this;
    }

    private function ensureViStore(int $websiteId, int $groupId): \Magento\Store\Model\Store
    {
        $store = $this->storeFactory->create();
        $store->load(self::VI_CODE); // load by `code` (non-numeric key)

        if (!$store->getId()) {
            $store->setData([
                'code' => self::VI_CODE,
                'website_id' => $websiteId,
                'group_id' => $groupId,
                'name' => self::VI_NAME,
                'sort_order' => 0,
                'is_active' => 1,
            ]);
            $this->storeResource->save($store);
        }

        return $store;
    }

    private function renameEnglishStore(): void
    {
        $store = $this->storeFactory->create();
        $store->load(self::EN_CODE);

        if ($store->getId() && $store->getName() !== self::EN_NAME) {
            $store->setName(self::EN_NAME);
            $this->storeResource->save($store);
        }
    }

    private function setLocales(int $viStoreId): void
    {
        $this->configWriter->save(self::LOCALE_PATH, self::VI_LOCALE, ScopeInterface::SCOPE_STORES, $viStoreId);

        $enStore = $this->storeFactory->create();
        $enStore->load(self::EN_CODE);
        if ($enStore->getId()) {
            $this->configWriter->save(
                self::LOCALE_PATH,
                self::EN_LOCALE,
                ScopeInterface::SCOPE_STORES,
                (int) $enStore->getId()
            );
        }
    }

    private function setGroupDefaultStore(int $groupId, int $viStoreId): void
    {
        // Group with >1 store will NOT auto-promote a default — set it explicitly.
        // GroupRepositoryInterface has no save(); use the model + resource model.
        $group = $this->groupFactory->create();
        $this->groupResource->load($group, $groupId);

        if ($group->getId() && (int) $group->getDefaultStoreId() !== $viStoreId) {
            $group->setDefaultStoreId($viStoreId);
            $this->groupResource->save($group);
        }
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
