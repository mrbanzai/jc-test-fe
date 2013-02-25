<?php
class LocationController extends Skookum_Controller_Action
{

	// store the search model
    protected $Search;
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
        $location = !empty($this->_params['location']) ? $this->_params['location'] : NULL;
        if (!empty($location)) {
            // we have a particular location in mind, find all jobs
            $specificLocListing = $this->Api->get('/jobs/search/',array('location'=>$location,'perPage'=>'500'));
            $this->view->results = $specificLocListing['data']['results'];
        } else {
            // no location in mind, show all locations
            $allLocations = $this->Api->get('/cities/',array('perPage'=>'500'));
            $this->view->locations = $allLocations['data']['results'];
        }

    	$this->view->allstates = $this->getStatesArray();
        $this->view->selectedState = ($state = $this->getRequest()->getParam('state')) ? $state : null;

		$this->view->title = 'All Locations';

		$this->view->location = $location;

		/*DROPDOWNS*/
		$categoryListing = $this->Api->get('/categories/',array('perPage'=>'500'));
        $locationListing = $this->Api->get('/cities/',array('perPage'=>'500'));
		$this->view->dropdownCategories = $categoryListing['data']['results'];
		$this->view->dropdownLocations = $locationListing['data']['results'];

		// popular categories
        $categoryListing = $this->Api->get('/categories/',array('orderBy'=>'count','sortOrder'=>'desc'));
        $this->view->categories = $categoryListing['data']['results'];

        //most recent jobs
        $recentListing = $this->Api->get('/jobs/',array('orderBy'=>'date_posted','sortOrder'=>'desc', 'perPage'=>'5'));
        $this->view->recent = $recentListing['data']['results'];
    }

	function getStatesArray()
	{
	  $state_list = array('AL'=>"Alabama",  'AK'=>"Alaska",  'AZ'=>"Arizona",  'AR'=>"Arkansas", 'CA'=>"California",  'CO'=>"Colorado",  'CT'=>"Connecticut",  'DE'=>"Delaware",'DC'=>"District Of Columbia",  'FL'=>"Florida",  'GA'=>"Georgia",  'HI'=>"Hawaii", 'ID'=>"Idaho",  'IL'=>"Illinois",  'IN'=>"Indiana",  'IA'=>"Iowa",  'KS'=>"Kansas", 'KY'=>"Kentucky",  'LA'=>"Louisiana",  'ME'=>"Maine",  'MD'=>"Maryland", 'MA'=>"Massachusetts",  'MI'=>"Michigan",  'MN'=>"Minnesota",  'MS'=>"Mississippi", 'MO'=>"Missouri",  'MT'=>"Montana", 'NE'=>"Nebraska", 'NV'=>"Nevada", 'NH'=>"New Hampshire", 'NJ'=>"New Jersey", 'NM'=>"New Mexico", 'NY'=>"New York", 'NC'=>"North Carolina", 'ND'=>"North Dakota", 'OH'=>"Ohio",  'OK'=>"Oklahoma",  'OR'=>"Oregon",'PA'=>"Pennsylvania",  'RI'=>"Rhode Island",  'SC'=>"South Carolina",  'SD'=>"South Dakota", 'TN'=>"Tennessee",  'TX'=>"Texas",  'UT'=>"Utah",  'VT'=>"Vermont",  'VA'=>"Virginia", 'WA'=>"Washington",  'WV'=>"West Virginia",  'WI'=>"Wisconsin",  'WY'=>"Wyoming");
	  return $state_list;
	}

}
