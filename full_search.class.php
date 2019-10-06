<?php

/**
 * full_search class
 * Base class of the full_search module
 * @author largeden (largeden@romanesque.co)
 * @package /modules/full_search
 */
class full_search extends ModuleObject
{
  /**
   * Install full_search module
   * @return Object
   */
  function moduleInstall()
  {
    $oModuleController = &getController('module');
    $oModuleController->insertTrigger('document.getDocumentList', 'full_search', 'model', 'triggerGetDocumentListTotalBefore', 'before');
    $oModuleController->insertTrigger('document.getDocumentList', 'full_search', 'model', 'triggerGetDocumentListTotalAfter', 'after');

    return class_exists('BaseObject') ? new BaseObject() : new Object();
  }

  /**
   * If update is necessary it returns true
   * @return bool
   */
  function checkUpdate()
  {
    $oModuleModel = &getModel('module');

    if(!$oModuleModel->getTrigger('document.getDocumentList', 'full_search', 'model', 'triggerGetDocumentListTotalBefore', 'before'))
    {
      return true;
    }
    if(!$oModuleModel->getTrigger('document.getDocumentList', 'full_search', 'model', 'triggerGetDocumentListTotalAfter', 'after'))
    {
      return true;
    }

    return FALSE;
  }

  /**
   * Update module
   * @return Object
   */
  function moduleUpdate()
  {
    $oModuleModel = &getModel('module');
    $oModuleController = &getController('module');

    if(!$oModuleModel->getTrigger('document.getDocumentList', 'full_search', 'model', 'triggerGetDocumentListTotalBefore', 'before'))
    {
      $oModuleController->insertTrigger('document.getDocumentList', 'full_search', 'model', 'triggerGetDocumentListTotalBefore', 'before');
    }
    if(!$oModuleModel->getTrigger('document.getDocumentList', 'full_search', 'model', 'triggerGetDocumentListTotalAfter', 'after'))
    {
      $oModuleController->insertTrigger('document.getDocumentList', 'full_search', 'model', 'triggerGetDocumentListTotalAfter', 'after');
    }

    return class_exists('BaseObject') ? new BaseObject() : new Object();
  }

  /**
   * Regenerate cache file
   * @return void
   */
  function recompileCache()
  {
  }

  /**
   * Module deleted
   * @return Object
   */
  function moduleUninstall()
  {
    $oModuleModel = &getModel('module');
    $oModuleController = &getController('module');

    // Trigger Delete
    if($oModuleModel->getTrigger('document.getDocumentList', 'full_search', 'model', 'triggerGetDocumentListTotalBefore', 'before'))
    {
      $oModuleController->deleteTrigger('document.getDocumentList', 'full_search', 'model', 'triggerGetDocumentListTotalBefore', 'before');
    }
    if($oModuleModel->getTrigger('document.getDocumentList', 'full_search', 'model', 'triggerGetDocumentListTotalAfter', 'after'))
    {
      $oModuleController->deleteTrigger('document.getDocumentList', 'full_search', 'model', 'triggerGetDocumentListTotalAfter', 'after');
    }

    return class_exists('BaseObject') ? new BaseObject() : new Object();
  }
}
/* End of file full_search.class.php */
/* Location: ./modules/full_search/full_search.class.php */