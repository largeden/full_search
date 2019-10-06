<?php

/**
 * full_searchAdminView class
 * admin view class of the full_search module
 * @author largeden (largeden@romanesque.io)
 * @package /modules/full_search
 */
class full_searchAdminView extends full_search
{
    /**
     * @brief initialization
     **/
    function init()
    {
        $this->setTemplatePath($this->module_path.'tpl');
    }

    function dispFull_searchAdminGeneralConfig()
    {
        $oModuleModel = getModel('module');
        $full_search_config = $oModuleModel->getModuleConfig('full_search');

        if(!$full_search_config)
        {
            $full_search_config = new stdClass();
        }
        Context::set('is_full_search', $full_search_config->is_full_search);

        $this->setTemplateFile('config');
    }

}
/* End of file full_search.admin.view.php */
/* Location: ./modules/full_search/full_search.admin.view.php */