<?php
class CityController extends Skookum_Controller_Action
{

	// store the search model
    protected $Search;
	protected $City;
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
        $city = !empty($this->_params['city']) ? $this->_params['city'] : NULL;
        if (!empty($city)) {
            // we have a particular location in mind, find all jobs
            $cityListing = $this->Api->get('/jobs/search/',array('city'=>$city,'perPage'=>'500','sortOrder'=>'asc'));
            $this->view->results = $cityListing['data']['results'];
        } else {
            // no location in mind, show all locations
            $allCitiesListing = $this->Api->get('/cities/');
            $this->view->cities = $allCitiesListing['data']['results'];
        }

		$this->view->title = 'All Cities';

		$this->view->city = $city;
		
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
