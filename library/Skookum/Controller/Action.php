<?php
class Skookum_Controller_Action extends Zend_Controller_Action
{
    // session handlers
    protected $_csrf;
    protected $_session;

    // store request params
    protected $_params;

    // ajax handlers
    protected $_blacklist;
    protected $_isAjax = false;
    protected $_isCsrfTokenValid;

    // custom for subdomain handling
    protected $_subdomain;
    protected $_cname;

    // global registry
    protected $_global;

    /**
     * The base level init handler.
     *
     * @access  public
     * @return  void
     */
    public function init()
    {
        parent::init();

        // get any params
        $this->_params = $this->getRequest()->getParams();

        // check if ajax
        $this->_isAjax = $this->getRequest()->isXmlHttpRequest();
        if ($this->_isAjax) {
            // disable layout for ajax requests
            $this->disableLayout();
        }
        
        $config = (array)Zend_Registry::get('global');

        $this->Api = new Skookum_Api_Client(
            array('apiBaseUrl' => $config['globalApiURL']),
            $config['PublicKey'],
            $config['PrivateKey']
        );

        // update the view navigation key
        $this->view->route = $this->getRequest()->controller . '_' . $this->getRequest()->action;

        // set client theme data
        $this->setClientData();
    }

    /**
     * Lazy load session.
     *
     * @access  public
     * @return  Zend_Session_Namespace
     */
    public function getSession()
    {
        if (!$this->_session) $this->_session = new Zend_Session_Namespace('global');
        return $this->_session;
    }

    /**
     * Get the user data.
     *
     * @access  public
     */
    public function getUser()
    {
        return (object) $this->getSession()->user;
    }

    /**
     * Set the user in global spots.
     *
     * @access  public
     * @param   mixed   $user
     */
    public function setUser($user)
    {
        $this->getSession()->user = $this->view->user = (object) $user;
    }

    /**
     * set the name of the layout to render
     *
     * @access public
     * @param string $layout
     * @return void
     */
    public function setLayout($layout)
    {
        $this->_helper->layout()->setLayout($layout);
    }

    /**
     * disable the layout
     *
     * @access public
     * @return void
     */
    public function disableLayout()
    {
        $this->_helper->layout()->disableLayout();
    }

    /**
     * enable the layout
     *
     * @access public
     * @return void
     */
    public function enableLayout()
    {
        $this->_helper->layout()->enableLayout();
    }

    /**
     * disable normal view rendering
     *
     * @access public
     * @return void
     */
    public function disableRender()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

    /**
     * Get a loaded configuration object.
     *
     * @access  public
     * @param   $name
     * @return  Zend_Config
     */
    public function getConfig($name)
    {
        $config = $this->getFrontController()->getParam('config');

        if (!empty($name)) {
            return $config->get($name);
        }

        return $config;
    }

    /**
     * Get a bootstrapped resource.
     *
     * @access  public
     * @param   string  $name   the name of the resource
     * @return  mixed
     */
    public function getResource($name)
    {
        return $this->getFrontController()
                ->getParam("bootstrap")
                ->getResource($name);
    }

    /**
     * Set pertinent client data for branding the UI.
     *
     * @access  public
     * @return  void
     */
    public function setClientData()
    {

        // load the global registry
        $this->_global = Zend_Registry::get('global');
        $this->view->global = $this->_global;
    }

    /**
     * Post dispatch handling for AJAX.
     *
     * @access  public
     * @return  void
     */
    public function postDispatch()
    {
        parent::postDispatch();

        if ($this->_isAjax) {
            // force unsetting uneccessary params for ajax
            $this->_ajaxBlacklist();
        }
    }

    /**
     * Sets up view data for handling form errors.
     *
     * @access  protected
     * @return  void
     */
    protected function _handleFormError(Exception $e, $formId = '')
    {
        $this->_helper->FlashMessenger(array('error' => $e->getMessage()));
        $this->_helper->layout->getView()->errors = $e->getErrors();
        $this->_helper->layout->getView()->formId = $formId;
    }

    /**
     * Defines a set of view parameters that should be blacklisted
     * so they don't get returned via ajaxContext. To add to the
     * blacklist, use the protected $_blacklist variable in the controller.
     *
     * @access  protected
     * @return  void
     */
    protected function _ajaxBlacklist() {
        // list of default blacklisted items
        $list = array('applicationPath', 'loggedIn', 'isAdmin', 'isSuper', 'route', 'user', 'environment');

        // merge the lists
        if (!empty($this->_blacklist)) {
            $list = array_unique(array_merge($list, (array) $this->_blacklist));
        }

        // remove the view data
        foreach ($list as $key) {
            if (isset($this->view->{$key})) {
                unset($this->view->{$key});
            }
        }
    }

    /**
     * Return the current subdomain for performing more granular, targeted
     * searches.
     *
     * @access  protected
     * @return  string
     */
    protected function _getSubdomain()
    {
        return $this->_subdomain;
    }

    /**
     * Return the current cname for performing more granular, targeted
     * searches.
     *
     * @access  protected
     * @return  string
     */
    protected function _getCname()
    {
        return $this->_cname;
    }
}
