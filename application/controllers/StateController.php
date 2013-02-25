<?php
class StateController extends Skookum_Controller_Action
{

	// store the search model
    protected $Search;
	protected $State;
    protected $Location;
	protected $Category;
	protected $Ats_Job;

	/**
	 * Initialize all instance fields for needed model objects.
	 *
	 * @access	public
	 * @return	void
	 */
	public function init() {
		parent::init();

		// use the search layout
		$this->setLayout('search');
	}

	/**
	 * Default location page. Handles scenarios of both showing
	 * all locations as well as jobs for a particular location.
	 *
	 * @access	public
	 * @return	void
	 */
	public function indexAction()
	{
        $state = !empty($this->_params['state']) ? $this->_params['state'] : NULL;
        if (!empty($state)) {
            // we have a particular location in mind, find all jobs
            $stateListing = $this->Api->get('/jobs/search/',array('state'=>$state,'perPage'=>'500'));
            $this->view->results = $stateListing['data']['results'];
        } else {
            // no location in mind, show all locations
            $allStatesListing = $this->Api->get('/states/');
            $this->view->states = $allStatesListing['data']['results'];
        }

		$this->view->title = 'All States';

		$this->view->state = $state;
		/*DROPDOWNS*/
		$categoryListing = $this->Api->get('/categories/',array('perPage'=>'500'));
    $locationListing = $this->Api->get('/cities/',array('perPage'=>'500'));
		$this->view->dropdownCategories = $categoryListing['data']['results'];
		$this->view->dropdownLocations = $locationListing['data']['results'];

		// popular categories
		$categoryListing = $this->Api->get('/categories/',array('orderBy'=>'count','sortOrder'=>'desc'));
    $this->view->categories = $categoryListing['data']['results'];
	}

}
