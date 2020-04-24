<?php
/**
 * @author Rodrigo Gabriel <ralgcorp@gmail.com>
 * Date: 24/02/2020
 * Time: 15:50
 */
namespace MageHelp\Magento2RedirectOnLogin\Plugin;

use Magento\Customer\Controller\Account\LoginPost;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Connect
{
    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Connect constructor.
     * @param \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory
     * @param \Magento\Framework\App\Response\RedirectInterface $redirectInterface
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        RedirectFactory $redirectFactory,
        RedirectInterface $redirectInterface,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManagerInterface,
        \Magento\Checkout\Helper\Cart $cartHelper
    ) {
        $this->resultRedirectFactory = $redirectFactory;
        $this->redirect = $redirectInterface;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManagerInterface;
        $this->cartHelper = $cartHelper;
    }

    /**
     * @param \Magento\Customer\Controller\Account\LoginPost $subject
     * @param \Magento\Framework\Controller\Result\Redirect $result
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterExecute(LoginPost $subject, $result)
    {
        $redirectURL = $this->scopeConfig->getValue('customer/startup/redirect_particular_page', ScopeInterface::SCOPE_STORE);

        // Check that the admin doesn't want that the customer is redirected to the dashboard
        // + that that the admin gave a specific URL (if not, standard Magento behavior: the
        // user is redirected to the current page
        if (!$this->scopeConfig->getValue('customer/startup/redirect_dashboard', ScopeInterface::SCOPE_STORE)
            && mb_strlen(trim($redirectURL) !== 0)
        ) {
            $redirect = !$this->isLogOutUrl($result);

            if ($redirect) {
                $fullRedirectUrl = $this->storeManager->getStore()->getBaseUrl() . $redirectURL;
                /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setUrl($fullRedirectUrl);
                $result = $resultRedirect;
            }
        }

        return $result;
    }

    /**
     * @param \Magento\Framework\Controller\Result\Redirect $result
     * @return boolean
     */
    protected function isLogOutUrl($result)
    {
        $status = false;

        $reflectionClass = new \ReflectionClass($result);
        $property = $reflectionClass->getProperty('url');
        $property->setAccessible(true);
        $redirectURL = $property->getValue($result);
        $logOutUrl = $this->storeManager->getStore()->getBaseUrl() . 'customer/account/logoutSuccess/';

        if ($this->cartHelper->getItemsCount() === 0) {
            $status = true;
        }
        
        if ($redirectURL === $logOutUrl) {
            $status = true;
        }

        return $status;
    }
}