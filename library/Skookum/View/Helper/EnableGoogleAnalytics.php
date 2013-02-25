<?php

    class Skookum_View_Helper_EnableGoogleAnalytics extends Zend_View_Helper_Abstract {

        public function enableGoogleAnalytics($trackerCode = null) {
            if (!$trackerCode) {
                $config = Zend_Registry::get('config')->get('application');
                if (isset($config->services->googleAnalytics->code)) {
                    $trackerCode = $config->services->googleAnalytics->code;
                } else {
                    return null;
                }
            }

            $this->view->inlineScript()->appendScript(<<<EOS
                var _gaq = _gaq || [];
                _gaq.push(['_setAccount', '{$trackerCode}']);
                _gaq.push(['_trackPageview']);

                (function() {
                var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
                })();
EOS
            );

            return $this;
        }

    }