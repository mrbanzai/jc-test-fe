<?php
class IndexController extends Skookum_Controller_Action
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
        $this->setLayout('home');
        $mobileContext = $this->_helper->getHelper('MobileContext');
        $mobileContext->addActionContext('index')
                      ->initContext();
	}

	/**
	 * Default landing page.
	 *
	 * @access	public
	 * @return	void
	 */
	public function indexAction()
    {
    //popular categories
    $categoryListing = $this->Api->get('/categories/',array('orderBy'=>'count','sortOrder'=>'desc' , 'perPage'=>'5'));
    $this->view->categories =
      (array_key_exists('results', $categoryListing['data'])) ?
      $categoryListing['data']['results'] :
      array();

    //popular locations
    $locationListing = $this->Api->get('/cities/',array('orderBy'=>'count','sortOrder'=>'asc' , 'perPage'=>'5'));
    $this->view->locations =
      (array_key_exists('results', $locationListing['data'])) ?
      $locationListing['data']['results'] :
      array();

    //most recent jobs
    $recentListing = $this->Api->get('/jobs/',array('orderBy'=>'date_posted','sortOrder'=>'desc', 'perPage'=>'5'));
    $this->view->recent =
      (array_key_exists('results', $recentListing['data'])) ?
      $recentListing['data']['results'] :
      array();

    /*DROPDOWNS*/
    $categoryListing = $this->Api->get('/categories/',array('perPage'=>'500'));
    $locationListing = $this->Api->get('/cities/',array('perPage'=>'500'));
		$this->view->dropdownCategories =
      (array_key_exists('results', $categoryListing['data'])) ?
      $categoryListing['data']['results'] :
      array();

		$this->view->dropdownLocations =
      (array_key_exists('results', $locationListing['data'])) ?
      $locationListing['data']['results'] :
      array();
		}

}
