<?php

    class RssController extends Skookum_Controller_Action
    {

        /**
         * The base level init handler.
         *
         * @access  public
         * @return  void
         */
        public function init()
        {
            parent::init();

            // disable the layout
            $this->disableRender();

            ini_set('display_errors', 0);
        }

        public function recentAction()
        {
            $recentListing = $this->Api->get('/jobs/', array(
                'orderBy' => 'date_posted',
                'sortOrder' => 'desc',
                'perPage' => '25'
            ));

            $jobList = array();
            if ($recentListing && !empty($recentListing['data']['results'])) {
                $jobList = $recentListing['data']['results'];
            }

            $this->_setFeedResponse(
                'Apollo Group - Recent Jobs',
                $jobList);
        }

        public function stateAction()
        {
            $state = $this->getRequest()->getParam('state');

            $stateListing = $this->Api->get('/jobs/search/', array(
                'state' => $state,
                'perPage' => '500'
            ));

            $jobList = array();
            if ($stateListing && !empty($stateListing['data']['results'])) {
                $jobList = $stateListing['data']['results'];
            }

            $this->_setFeedResponse(
                'Apollo Group - Jobs in ' . strtoupper($state),
                $jobList);
        }

        public function categoryAction()
        {
            $category = $this->getRequest()->getParam('category');

            $categoryListing = $this->Api->get('/jobs/search/', array(
                'category' => $category,
                'perPage' => '500'
            ));

            $jobList = array();
            if ($categoryListing && !empty($categoryListing['data']['results'])) {
                $jobList = $categoryListing['data']['results'];
            }

            $this->_setFeedResponse(
                'Apollo Group - Jobs in ' . ucwords($category),
                $jobList);
        }

        protected function _setFeedResponse($title, $jobs)
        {
            $self = $this;

            $entries = array_map(function ($job) use ($self) {
                    $entry = array(
                        'title' => htmlentities(trim($job['name'])),
                        'link' => trim($self->view->serverUrl('/job/details/' . $job['uristub'])),
                        // Right now, the descriptions are massive and contain (potentially) HTML
                        // and other nasty junk
                        'description' => '',
                    );
                    if (trim($job['category'])) {
                        // This is the crazy way Zend Framework wants the categories ...
                        $entry['category'] = array(
                            array(
                                'term' => htmlentities(trim($job['category']))
                            )
                        );
                    }
                    return $entry;
                }, $jobs);

            $feed = Zend_Feed::importArray(array(
                    'title' => $title,
                    'link' => $this->view->serverUrl(),
                    'charset' => 'UTF-8',
                    'entries' => $entries
                ), 'rss');
            $feedXml = $feed->saveXML();

            $this->getResponse()
                 ->setHeader('Content-Type', 'application/rss+xml')
                 ->appendBody($feedXml);
        }

    }