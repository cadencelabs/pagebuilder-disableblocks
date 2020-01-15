<?php
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */
namespace Cadence\PageBuilderDisable\Model;

use Magento\Cms\Model\BlockRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Config extends \Magento\PageBuilder\Model\Config
{
    const CONFIG_PATH_DISABLED_BLOCKS = 'page_builder_disable/default/excluded_blocks';

    /**
     * @var string
     */
    protected $disableBlockRegex = '~cms/block/edit/block_id/{{id}}/~';

    /**
     * @var \Magento\Cms\Api\BlockRepositoryInterface
     */
    protected $blockRepository;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Config constructor.
     * @param \Magento\Cms\Api\BlockRepositoryInterface $blockRepository
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Magento\PageBuilder\Model\Config\CompositeReader $reader
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param ScopeConfigInterface $scopeConfig
     * @param string $cacheId
     */
    public function __construct(
        \Magento\Cms\Api\BlockRepositoryInterface $blockRepository,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\PageBuilder\Model\Config\CompositeReader $reader,
        \Magento\Framework\Config\CacheInterface $cache,
        ScopeConfigInterface $scopeConfig,
        $cacheId = 'pagebuilder_config'
    ) {
        $this->blockRepository = $blockRepository;
        $this->urlInterface = $urlInterface;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($reader, $cache, $scopeConfig, $cacheId);
    }

    /**
     * Returns config setting if page builder enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        if (parent::isEnabled()) {
            $excludedBlocks = trim($this->scopeConfig->getValue(self::CONFIG_PATH_DISABLED_BLOCKS));
            if (strlen($excludedBlocks) && $this->_isDisabledUrlCandidate()) {
                $excludedBlocks = explode(",", $excludedBlocks);
                foreach($excludedBlocks as $excludedBlock) {
                    if ($this->_isDisabledBlock($excludedBlock)) {
                        return false;
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Determine if we should examine the URL further for exclusion of page builder
     * @return bool
     */
    protected function _isDisabledUrlCandidate()
    {
        $disableCandidateRegex = str_replace('{{id}}/', '', $this->disableBlockRegex);
        return preg_match($disableCandidateRegex, $this->urlInterface->getCurrentUrl());
    }

    /**
     * Examine the URL to determine if pagebuilder is disabled
     * @param string $block
     * @return bool
     */
    protected function _isDisabledBlock(string $block)
    {
        try {
            $blockModel = $this->blockRepository->getById($block);
            // Create the url pattern for this specific block based on the primary key id
            $urlPattern = str_replace("{{id}}", $blockModel->getId(), $this->disableBlockRegex);
            // If it matches, return false
            return preg_match($urlPattern, $this->urlInterface->getCurrentUrl());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            // If that block id no longer exists, don't worry about it
            return false;
        }
        return true;
    }
}