<?php

/**
 * full_searchModel class
 * model class of the full_search module
 * @author largeden (largeden@romanesque.io)
 * @package /modules/full_search
 */
class full_searchModel extends full_search
{

  /**
   * @brief display board content list (2019/10/06 Choi Modified.)
   **/
  function dispBoardContentList($_this)
  {
    $oDocumentModel = getModel('document');

    // setup module_srl/page number/ list number/ page count
    $args = new stdClass();
    $args->module_srl = $_this->module_srl;
    $args->page = Context::get('page');
    $args->list_count = $_this->list_count;
    $args->page_count = $_this->page_count;

    // get the search target and keyword
    $args->search_target = Context::get('search_target');
    $args->search_keyword = Context::get('search_keyword');

    $search_option = Context::get('search_option');
    if($search_option==FALSE)
    {
      $search_option = $_this->search_option;
    }

    // if the category is enabled, then get the category
    if($_this->module_info->use_category=='Y')
    {
      $args->category_srl = Context::get('category');
    }

    // setup the sort index and order index
    $args->sort_index = Context::get('sort_index');
    $args->order_type = Context::get('order_type');
    if(!in_array($args->sort_index, $_this->order_target))
    {
      $args->sort_index = $_this->module_info->order_target?$_this->module_info->order_target:'list_order';
    }
    if(!in_array($args->order_type, array('asc','desc')))
    {
      $args->order_type = $_this->module_info->order_type?$_this->module_info->order_type:'asc';
    }

    // set the current page of documents
    $document_srl = Context::get('document_srl');
    if($document_srl)
    {
      $oDocument = $oDocumentModel->getDocument($document_srl);
      
      if($oDocument->isExists() && !$oDocument->isNotice())
      {
        $page = $this->getDocumentPage($oDocument, $args);
        Context::set('page', $page);
        $args->page = $page;
      }
    }

    // setup the list count to be serach list count, if the category or search keyword has been set
    if($args->category_srl || $args->search_keyword)
    {
      $args->list_count = $_this->search_list_count;
    }

    // if the consultation function is enabled,  the get the logged user information
    if($this->consultation)
    {
      $logged_info = Context::get('logged_info');

      if($this->module_info->use_anonymous === 'Y')
      {
        $args->member_srl = array($logged_info->member_srl, $logged_info->member_srl * -1);
      }
      else
      {
        $args->member_srl = $logged_info->member_srl;
      }
    }

    return $args;
  }

  /**
   * Module_srl value, bringing the list of documents (2019/10/06 Choi modified.)
   * @param object $obj
   * @param bool $except_notice
   * @param bool $load_extra_vars
   * @param array $columnList
   * @return Object
   */
  function getDocumentList($obj, $except_notice = false, $load_extra_vars=true, $columnList = array())
  {
    $oDocumentModel = getModel('document');

    // If an alternate output is set, use it instead of running the default queries
    $use_alternate_output = (isset($obj->use_alternate_output) && $obj->use_alternate_output instanceof BaseObject);
    if (!$use_alternate_output)
    {
      $this->_setSearchOption($obj, $args, $query_id, $use_division);
    }

    if ($use_alternate_output)
    {
      $output = $obj->use_alternate_output;
      unset($obj->use_alternate_output);
    }
    elseif ($sort_check->isExtraVars && substr_count($obj->search_target,'extra_vars'))
    {
      $query_id = 'document.getDocumentListWithinExtraVarsExtraSort';
      $args->sort_index = str_replace('documents.','',$args->sort_index);
      $output = executeQueryArray($query_id, $args);
    }
    elseif ($sort_check->isExtraVars)
    {
      $output = executeQueryArray($query_id, $args);
    }
    else
    {
      // document.getDocumentList query execution
      // Query_id if you have a group by clause getDocumentListWithinTag or used again to perform the query because
      $groupByQuery = array('document.getDocumentListWithinTag' => 1);
      if(isset($groupByQuery[$query_id]))
      {
        $group_args = clone($args);
        $group_args->sort_index = 'documents.'.$args->sort_index;
        $output = executeQueryArray($query_id, $group_args);
        if(!$output->toBool()||!count($output->data)) return $output;

        foreach($output->data as $key => $val)
        {
          if($val->document_srl) $target_srls[] = $val->document_srl;
        }

        $page_navigation = $output->page_navigation;
        $keys = array_keys($output->data);
        $virtual_number = $keys[0];

        $target_args = new stdClass();
        $target_args->document_srls = implode(',',$target_srls);
        $target_args->list_order = $args->sort_index;
        $target_args->order_type = $args->order_type;
        $target_args->list_count = $args->list_count;
        $target_args->page = 1;
        $output = executeQueryArray('document.getDocuments', $target_args);
        $output->page_navigation = $page_navigation;
        $output->total_count = $page_navigation->total_count;
        $output->total_page = $page_navigation->total_page;
        $output->page = $page_navigation->cur_page;
      }
      else
      {
        $query_id = 'full_search.getDocumentListTotal';
        $output = executeQueryArray($query_id, $args, $columnList);
      }
    }
    // Return if no result or an error occurs
    if(!$output->toBool()||!count($output->data)) return $output;
    $idx = 0;
    $data = $output->data;
    unset($output->data);

    if(!isset($virtual_number))
    {
      $keys = array_keys($data);
      $virtual_number = $keys[0];
    }

    if($except_notice)
    {
      foreach($data as $key => $attribute)
      {
        if($attribute->is_notice == 'Y') $virtual_number --;
      }
    }

    foreach($data as $key => $attribute)
    {
      if($except_notice && $attribute->is_notice == 'Y') continue;
      $document_srl = $attribute->document_srl;
      if(!$GLOBALS['XE_DOCUMENT_LIST'][$document_srl])
      {
        $oDocument = null;
        $oDocument = new documentItem();
        $oDocument->setAttribute($attribute, false);
        if($is_admin) $oDocument->setGrant();
        $GLOBALS['XE_DOCUMENT_LIST'][$document_srl] = $oDocument;
      }

      $output->data[$virtual_number] = $GLOBALS['XE_DOCUMENT_LIST'][$document_srl];
      $virtual_number--;
    }

    if($load_extra_vars) $oDocumentModel->setToAllDocumentExtraVars();

    if(count($output->data))
    {
      foreach($output->data as $number => $document)
      {
        $output->data[$number] = $GLOBALS['XE_DOCUMENT_LIST'][$document->document_srl];
      }
    }

    return $output;
  }

  /**
   * 게시물 목록의 검색 옵션을 Setting  (2019/10/06 Choi modified.)
   * page변수가 없는 상태에서 page 값을 알아오는 method(getDocumentPage)는 검색하지 않은 값을 return해서 검색한 값을 가져오도록 검색옵션이 추가 됨.
   * 검색옵션의 중복으로 인해 private method로 별도 분리
   * @param object $searchOpt
   * @param object $args
   * @param string $query_id
   * @param bool $use_division
   * @return void
   */
  function _setSearchOption($searchOpt, &$args, &$query_id, &$use_division)
  {
    $oDocumentModel = getModel('document');

    // Variable check
    $args = new stdClass();
    $args->category_srl = $searchOpt->category_srl?$searchOpt->category_srl:null;
    $args->order_type = $searchOpt->order_type;
    $args->page = $searchOpt->page?$searchOpt->page:1;
    $args->list_count = $searchOpt->list_count?$searchOpt->list_count:20;
    $args->page_count = $searchOpt->page_count?$searchOpt->page_count:10;
    $args->start_date = $searchOpt->start_date?$searchOpt->start_date:null;
    $args->end_date = $searchOpt->end_date?$searchOpt->end_date:null;
    $args->member_srl = $searchOpt->member_srl ?: ($searchOpt->member_srls ?: null);

    $logged_info = Context::get('logged_info');

    $args->sort_index = $searchOpt->sort_index;
    
    // Check the target and sequence alignment
    $orderType = array('desc' => 1, 'asc' => 1);
    if(!isset($orderType[$args->order_type])) $args->order_type = 'asc';

    // If that came across mid module_srl instead of a direct module_srl guhaejum
    if($searchOpt->mid)
    {
      $oModuleModel = getModel('module');
      $args->module_srl = $oModuleModel->getModuleSrlByMid($searchOpt->mid);
      unset($searchOpt->mid);
    }

    // Module_srl passed the array may be a check whether the array
    if(is_array($searchOpt->module_srl)) $args->module_srl = implode(',', $searchOpt->module_srl);
    else $args->module_srl = $searchOpt->module_srl;

    // Except for the test module_srl
    if(is_array($searchOpt->exclude_module_srl)) $args->exclude_module_srl = implode(',', $searchOpt->exclude_module_srl);
    else $args->exclude_module_srl = $searchOpt->exclude_module_srl;

    // only admin document list, temp document showing
    if($searchOpt->statusList) $args->statusList = $searchOpt->statusList;
    else
    {
      if($logged_info->is_admin == 'Y' && !$searchOpt->module_srl)
        $args->statusList = array($oDocumentModel->getConfigStatus('secret'), $oDocumentModel->getConfigStatus('public'), $oDocumentModel->getConfigStatus('temp'));
      else
        $args->statusList = array($oDocumentModel->getConfigStatus('secret'), $oDocumentModel->getConfigStatus('public'));
    }

    // Category is selected, further sub-categories until all conditions
    if($args->category_srl)
    {
      $category_list = $oDocumentModel->getCategoryList($args->module_srl);
      $category_info = $category_list[$args->category_srl];
      $category_info->childs[] = $args->category_srl;
      $args->category_srl = implode(',',$category_info->childs);
    }

    $args->lang_code = Context::getLangType();
    $args->s_lang_code = $args->lang_code.',0';

    // Used to specify the default query id (based on several search options to query id modified)
    $query_id = 'full_search.getDocumentListTotal';

    // If the search by specifying the document division naeyonggeomsaekil processed for
    $use_division = false;

    if(!in_array($args->order_target, array('regdate', 'list_order', 'update_order'))) $args->order_target = 'regdate';
    if($searchOpt->search_target && $searchOpt->search_keyword)
    {
      $search_list = array();
      $eid = array();
      $var_idx = array();

      // Search options
      $search_targets = explode(",", $searchOpt->search_target);
      $search_keywords = explode(" ", $searchOpt->search_keyword);

      foreach($search_targets as $i => $search_target)
      {
        switch(true)
        {
          case in_array($search_target, array('title','content','title_content','comment','tag','extra_vars','title_content_comment','title_content_tag','title_content_extra_vars','title_content_comment_tag','all_content')) :
            $use_division = true;
            $search_list[$search_target] = 's_'.$search_target;
            break;
          case in_array($search_target, array('user_id','user_name','nick_name','email_address','homepage','member_srl','regdate','last_update')) :
            $args->{'s_'.$search_target} = $search_keywords[0];
            break;
          case 'is_notice' == $search_target :
            if($search_keywords[0]=='N') $args->s_is_notice = 'N';
            elseif($search_keywords[0]=='Y') $args->s_is_notice = 'Y';
            else $args->s_is_notice = '';
            break;
          case 'is_secret' == $search_target :
            if($search_keywords[0]=='N')
            {
              $args->statusList = array($$oDocumentModel->getConfigStatus('public'));
            }
            elseif($search_keywords[0]=='Y')
            {
              $args->statusList = array($oDocumentModel->getConfigStatus('secret'));
              $args->is_secret = 'N';
            }
            elseif($search_keywords[0]=='temp')
            {
              $args->statusList = array($oDocumentModel->getConfigStatus('temp'));
            }
            break;
          case in_array($search_target, array('readed_count','voted_count','comment_count','trackback_count','uploaded_count')) :
            $args->{'s_'.$search_target} = (int)$search_keywords[0];
            break;
          case 'blamed_count' == $search_target :
            $args->s_blamed_count = (int)$search_keywords[0] * -1;
            break;
          case 'ipaddress' == $search_target :
            if($this->grant->manager)
            {
              $args->s_ipaddress = $search_keywords[0];
            }
            break;
          case strpos($search_target,'extra_vars')!==false :
            $var_idx[] = substr($search_target, strlen('extra_vars'));
            $search_list['extra_vars'] = 's_extra_vars';
            break;
          default : /* extra_vars eid */ 
            $eid[] = $search_target;
            $search_list['extra_vars'] = 's_extra_vars';
            break;
        }
      }

      if(count($eid) > 0) $args->eid = implode(',', $eid);
      if(count($var_idx) > 0) $args->var_idx = implode(',', $var_idx);

      if(count(array_intersect($search_list, array('title' => 's_title', 'content' => 's_content'))) == 2)
      {
        unset($search_list['title']);
        unset($search_list['content']);
        $search_list['title_content'] = 's_title_content';
      }
      if(count(array_intersect($search_list, array('title_content' => 's_title_content'))) == 1)
      {
        unset($search_list['title']);
        unset($search_list['content']);
      }
      if(count(array_intersect($search_list, array('title_content' => 's_title_content', 'comment' => 's_comment'))) == 2)
      {
        unset($search_list['title_content']);
        unset($search_list['comment']);
        $search_list['title_content_comment'] = 's_title_content_comment';
      }
      if(count(array_intersect($search_list, array('title_content' => 's_title_content', 'tag' => 's_tag'))) == 2)
      {
        unset($search_list['title_content']);
        unset($search_list['tag']);
        $search_list['title_content_tag'] = 's_title_content_tag';
      }
      if(count(array_intersect($search_list, array('title_content' => 's_title_content', 'extra_vars' => 's_extra_vars'))) == 2)
      {
        unset($search_list['title_content']);
        unset($search_list['extra_vars']);
        $search_list['title_content_extra_vars'] = 's_title_content_extra_vars';
      }
      if(count(array_intersect($search_list, array('title_content_comment' => 's_title_content_comment', 'tag' => 's_tag'))) == 2)
      {
        unset($search_list['title_content_comment']);
        unset($search_list['tag']);
        $search_list['title_content_comment_tag'] = 's_title_content_comment_tag';
      }
      if(count(array_intersect($search_list, array('title_content_comment_tag' => 's_title_content_comment_tag', 'extra_vars' => 's_extra_vars'))) == 2)
      {
        unset($search_list['title_content_comment_tag']);
        unset($search_list['extra_vars']);
        $search_list['all_content'] = 's_all_content';
      }

      foreach($search_keywords as $i => $s)
      {
        if($s == '') continue;

        foreach($search_list as $k => $v)
        {
          $args->{$v.($i+1)} = $s; 
        }
      }
    }

    /**
     * list_order asc sort of division that can be used only when
     */
    if($args->sort_index != 'list_order' || $args->order_type != 'asc') $use_division = false;

    /**
     * If it is true, use_division changed to use the document division
     */
    if($use_division)
    {
      // Division begins
      $division = (int)Context::get('division');

      // order by list_order and (module_srl===0 or module_srl may count), therefore case table full scan
      if($args->sort_index == 'list_order' && ($args->exclude_module_srl === '0' || count(explode(',', $args->module_srl)) > 5))
      {
        $listSqlID = 'document.getDocumentListUseIndex';
        $divisionSqlID = 'document.getDocumentDivisionUseIndex';
      }
      else
      {
        $listSqlID = 'document.getDocumentListTotal';
        $divisionSqlID = 'document.getDocumentDivision';
      }

      // If you do not value the best division top
      if(!$division)
      {
        $division_args = new stdClass();
        $division_args->module_srl = $args->module_srl;
        $division_args->exclude_module_srl = $args->exclude_module_srl;
        $division_args->list_count = 1;
        $division_args->sort_index = $args->sort_index;
        $division_args->order_type = $args->order_type;
        $division_args->statusList = $args->statusList;

        $output = executeQuery($divisionSqlID, $division_args, array('list_order'));
        if($output->data)
        {
          $item = array_pop($output->data);
          $division = $item->list_order;
        }
        $division_args = null;
      }

      // The last division
      $last_division = (int)Context::get('last_division');

      // Division after division from the 5000 value of the specified Wanted
      if(!$last_division)
      {
        $last_division_args = new stdClass();
        $last_division_args->module_srl = $args->module_srl;
        $last_division_args->exclude_module_srl = $args->exclude_module_srl;
        $last_division_args->list_count = 1;
        $last_division_args->sort_index = $args->sort_index;
        $last_division_args->order_type = $args->order_type;
        $last_division_args->list_order = $division;
        $last_division_args->page = 5001;

        $output = executeQuery($divisionSqlID, $last_division_args, array('list_order'));
        if($output->data)
        {
          $item = array_pop($output->data);
          $last_division = $item->list_order;
        }
      }

      // Make sure that after last_division article
      if($last_division)
      {
        $last_division_args = new stdClass();
        $last_division_args->module_srl = $args->module_srl;
        $last_division_args->exclude_module_srl = $args->exclude_module_srl;
        $last_division_args->list_order = $last_division;
        $output = executeQuery('document.getDocumentDivisionCount', $last_division_args);
        if($output->data->count<1) $last_division = null;
      }

      $args->division = $division;
      $args->last_division = $last_division;
      Context::set('division', $division);
      Context::set('last_division', $last_division);

    }
  }

  /**
   * Import page of the document, module_srl Without throughout ..
   * @param documentItem $oDocument
   * @param object $opt
   * @return int
   */
  function getDocumentPage($oDocument, $opt)
  {
    $oDocumentModel = getModel('document');

    $sort_check = $oDocumentModel->_setSortIndex($opt, TRUE);
    $opt->sort_index = $sort_check->sort_index;
    $opt->isExtraVars = $sort_check->isExtraVars;

    $this->_setSearchOption($opt, $args, $query_id, $use_division);

    if($sort_check->isExtraVars || !$opt->list_count)
    {
      return 1;
    }
    else
    {
      if($sort_check->sort_index === 'list_order' || $sort_check->sort_index === 'update_order')
      {
        if($args->order_type === 'desc')
        {
          $args->{'rev_' . $sort_check->sort_index} = $oDocument->get($sort_check->sort_index);
        }
        else
        {
          $args->{$sort_check->sort_index} = $oDocument->get($sort_check->sort_index);
        }
      }
      elseif($sort_check->sort_index === 'regdate')
      {

        if($args->order_type === 'asc')
        {
          $args->{'rev_' . $sort_check->sort_index} = $oDocument->get($sort_check->sort_index);
        }
        else
        {
          $args->{$sort_check->sort_index} = $oDocument->get($sort_check->sort_index);
        }

      }
      else
      {
        return 1;
      }
    }

    // Guhanhu total number of the article search page
    $output = executeQuery($query_id . 'Page', $args);
    $count = $output->data->count;
    $page = (int)(($count-1)/$opt->list_count)+1;
    return $page;
  }


  function triggerGetDocumentListTotalBefore(&$obj)
  {
//    if(Context::get('mid') !== 'lab2') return;

    $oModuleModel = getModel('module');
    $full_search_config = $oModuleModel->getModuleConfig('full_search');

    if($full_search_config->is_full_search == 'Y')
    {
      if($obj->search_target && $obj->search_keyword)
      {
        unset($obj->page);
        unset($obj->search_target);
        unset($obj->search_keyword);
      }
    }
  }

  function triggerGetDocumentListTotalAfter(&$output)
  {
//    if(Context::get('mid') !== 'lab2') return;

    $oModuleModel = getModel('module');
    $full_search_config = $oModuleModel->getModuleConfig('full_search');

    if($full_search_config->is_full_search == 'Y')
    {
      if(Context::get('search_target') && Context::get('search_keyword'))
      {
        $_this = $GLOBALS['_loaded_module']['board']['view']['svc'];
        $args = $this->dispBoardContentList($_this);
        $args->columnList = $_this->columnList;
    
        $output = $this->getDocumentList($args, $args->except_notice, TRUE, $args->columnList);
      }
    }
  }
}
/* End of file full_search.model.php */
/* Location: ./modules/romanesque/full_search.model.php */