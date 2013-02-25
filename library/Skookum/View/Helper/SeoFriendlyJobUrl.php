<?php

    class Skookum_View_Helper_SeoFriendlyJobUrl extends Zend_View_Helper_Abstract {

        public function seoFriendlyJobUrl($job, $baseUrl = '/job/details/') {
            $url = $baseUrl;
            if (!empty($location)) $url .= Clean::uristub($job['location']) . '/';
            if (!empty($category)) $url .= Clean::uristub($job['category']) . '/';
            $url .= $job['uristub'] . '/';

            return $url;
        }

    }