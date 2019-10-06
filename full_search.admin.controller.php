<?php

/**
 * full_searchAdminController class
 * controller class of the full_search module
 * @author largeden (largeden@romanesque.io)
 * @package /modules/full_search
 */
class full_searchAdminController extends full_search
{

    /**
     * @brief initialization
     **/
    function init()
    {
    }

    /**
     * full_search general config
     * @return void
     */
    function procFull_searchAdminGeneralConfig()
    {
        $oModuleModel = getModel('module');
        $oModuleController = getController('module');

        $full_search_config = $oModuleModel->getModuleConfig('full_search');
        $full_search_config->is_full_search = Context::get('is_full_search');

        $oModuleController->insertModuleConfig('full_search', $full_search_config);

        $this->setMessage('success_updated');
        $this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispFull_searchAdminGeneralConfig'));
    }
}
/* End of file full_search.admin.controller.php */
/* Location: ./modules/full_search/full_search.admin.controller.php */