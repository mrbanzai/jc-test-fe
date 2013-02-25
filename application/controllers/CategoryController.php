<?php
class CategoryController extends Skookum_Controller_Action
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
	 * Default category page. Handles scenarios of both showing
	 * all categories as well as jobs for a particular category.
	 *
	 * @access	public
	 * @return	void
	 */
	public function indexAction()
	{
    $category = !empty($this->_params['category']) ? $this->_params['category'] : NULL;
    $category = str_replace("_", "/", $category);
        if (!empty($category)) {
          // we have a particular category in mind, find all jobs
          $specificCatListing = $this->Api->get('/jobs/search/',array('category'=>$category,'perPage'=>'500')); 
          $this->view->results = $specificCatListing['data']['results'];
          // set the title and description
          $clean = Clean::xss($category);
          $this->view->title = $clean;
          $this->view->description = 'Check out all of the job openings for ' . $clean . '.';
        } else {
          // no category in mind, show all categories
          $generalCategoryListing = $this->Api->get('/categories/',array('perPage'=>'500'));
          $this->view->categories = $generalCategoryListing['data']['results'];
          
          // set the title
          $this->view->title = 'All Categories';
        }

		$this->view->category = $category;
		/*DROPDOWNS*/
		$categoryListing = $this->Api->get('/categories/',array('perPage'=>'500'));
    $locationListing = $this->Api->get('/cities/',array('perPage'=>'500'));
		$this->view->dropdownCategories = $categoryListing['data']['results'];
		$this->view->dropdownLocations = $locationListing['data']['results'];
		
		// popular locations
		$locationListing = $this->Api->get('/cities/',array('orderBy'=>'count','sortOrder'=>'desc'));
		$this->view->locations = $locationListing['data']['results'];
		
		//most recent jobs
    $recentListing = $this->Api->get('/jobs/',array('orderBy'=>'date_posted','sortOrder'=>'desc', 'perPage'=>'5')); 
    $this->view->recent = $recentListing['data']['results'];
	}

}
