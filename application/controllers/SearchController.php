<?php
class SearchController extends Skookum_Controller_Action
{

    // store the search model
    protected $Search;
    protected $Location;
    protected $Category;
    protected $Ats_Job;

    /**
     * Initialize all instance fields for needed model objects.
     *
     * @access  public
     * @return  void
     */
    public function init() {
        parent::init();

        // use the search layout
        $this->setLayout('search');

        // ajax context switching
        $this->_helper->ajaxContext()
            ->addActionContext('job', 'json')
            ->addActionContext('category', 'json')
            ->addActionContext('location', 'json')
            ->initContext();
    }

    /**
     * Default job search page.
     *
     * @access  public
     * @return  void
     */
    public function indexAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {

            // get post data
            $post = $request->getPost();

            $search = !empty($post['search']) ? trim($post['search']) : null;
            if (!empty($search)) {
                $this->view->title = 'Search results for "' . Clean::xss($search) . '"';
            }

            $location = !empty($post['location']) ? $post['location'] : null;
            $category = !empty($post['category']) ? $post['category'] : null;

            $this->view->location = $location;
            $this->view->category = $category;

            $category = urldecode($category);
            $location = urldecode($location);

            $resultsLocation = array();
            $resultsCategory = array();

            $criteria = array('location'=>$location,'category'=>$category, 'perPage'=>'500');
            if (preg_match('#^\d+$#', $search)) {
                $criteria['job_id'] = $search;
            } else {
                $criteria['name'] = $search;
            }

            $resultsLocationCategory = $this->Api->get('/jobs/search/', $criteria);

            $this->view->results = $resultsLocationCategory['data']['results'];

            $this->view->searchterm = $search;

            // check for matching jobs in a given category or location
            $jobsInCategorySearch = !empty($category) ? $this->Api->get('/jobs/search/',array('category'=>$category,'perPage'=>'5')) : NULL;

            $this->view->jobsInCategory = $jobsInCategorySearch['data']['results'];
            $jobsInLocationSearch = !empty($location) ? $this->Api->get('/jobs/search/',array('location'=>$location,'perPage'=>'5')) : NULL;
            $this->view->jobsInLocation = $jobsInLocationSearch['data']['results'];

        }

        //most recent jobs
        $recentListing = $this->Api->get('/jobs/',array('orderBy'=>'date_posted','sortOrder'=>'desc', 'perPage'=>'5'));
        $this->view->recent = $recentListing['data']['results'];

        /*DROPDOWNS*/
        $categoryListing = $this->Api->get('/categories/',array('perPage'=>'500'));
        $locationListing = $this->Api->get('/cities/',array('perPage'=>'500'));
        $this->view->dropdownCategories = $categoryListing['data']['results'];
        $this->view->dropdownLocations = $locationListing['data']['results'];
    }

    /**
     * Search for jobs matching given restraints.
     *
     * @access  public
     * @return  void
     */
    public function jobAction()
    {
        $context = $this->_helper->ajaxContext()->getCurrentContext();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->view->status = "error";

            // get the search data
            $post = $request->getPost();

            // search for matching jobs
            //$this->view->categories = $this->Search->job($this->_getSubdomain(), $post['value']);
            $catJobAction = $this->Api->get('/jobs/search/',array('category'=>$post['value'],'perPage'=>'500'));
            $this->view->categories = $catJobAction['data']['results'];
            $this->view->status = "success";

            var_dump($catJobAction);

            echo 'catjobaction to left';

        }

        // redirect if not using the json context
        if ($context != 'json') {
            $this->_redirect('/');
        }
    }

    /**
     * Job category autocompletion.
     *
     * @access  public
     * @return  void
     */
    public function categoryAction()
    {
        $context = $this->_helper->ajaxContext()->getCurrentContext();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->view->status = "error";

            // get the search data
            $post = $request->getPost();

            // search for matching categories
            //$this->view->categories = $this->Search->category($post['value'], $this->_getSubdomain());
            $catAction = $this->Api->get('/jobs/search/',array('category'=>$post['value'],'perPage'=>'500'));;
            $this->view->categories = $catAction['data']['results'];

            $this->view->status = "success";

        } else {
            $this->view->status = 'error';
        }

        // redirect if not using the json context
        if ($context != 'json') {
            $this->_redirect('/');
        }
    }

    /**
     * Job location autocompletion.
     *
     * @access  public
     * @return  void
     */
    public function locationAction()
    {
        $context = $this->_helper->ajaxContext()->getCurrentContext();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->view->status = "error";

            // get the search data
            $post = $request->getPost();

            // search for matching categories
            //$this->view->locations = $this->Search->location($post['value'], $this->_getSubdomain());
            $locAction = $this->Api->get('/jobs/search/',array('location'=>$post['value'],'perPage'=>'500'));;
            $this->view->locations = $locAction['data']['results'];

            $this->view->status = "success";

        }

        // redirect if not using the json context
        if ($context != 'json') {
            $this->_redirect('/');
        }
    }

}