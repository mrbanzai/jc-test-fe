<?php

class Skookum_Controller_Plugin_Mobile extends Zend_Controller_Plugin_Abstract
{

    const SUFFIX_PREPEND = 'mobile';

    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        $frontController = Zend_Controller_Front::getInstance();
        $bootstrap = $frontController->getParam('bootstrap');

        if (!$bootstrap->hasResource('useragent')) {
            throw new Zend_Controller_Exception('The mobile plugin can only be loaded when the UserAgent resource is bootstrapped');
        }

        $userAgent = $bootstrap->getResource('useragent');
        // Load device settings, required to perform $userAgent->getBrowserType()
        $userAgent->getDevice();

        $namespace = new Zend_Session_Namespace('mobile');

        if ($request->has('mobile')) {
            if ($mobile = $request->get('mobile')) {
                $namespace->forceMobileView = true;
            } else {
                unset($namespace->forceMobileView);
            }
        }

        if ($userAgent->getBrowserType() === 'mobile' || $namespace->forceMobileView) {
            if ($frontController->getParam('mobileLayout') == '1') {
                $layout = $bootstrap->getResource('layout');
                $suffix = $layout->getViewSuffix();
                /*
                $currentLayout = $layout->getLayout();

                $mobileLayoutFile = $layout->getLayoutPath() . DIRECTORY_SEPARATOR .
                    implode('.', array($currentLayout, self::SUFFIX_PREPEND, $suffix));
                error_log($mobileLayoutFile);

                if (file_exists($mobileLayoutFile)) {
                */
                    $layout->setViewSuffix(self::SUFFIX_PREPEND . '.' . $suffix);
                /*
                }
                */
            }

            if ($frontController->getParam('mobileViews') == '1') {
                Zend_Controller_Action_HelperBroker::getStaticHelper('MobileContext')->enable();
            }
        }
    }

}