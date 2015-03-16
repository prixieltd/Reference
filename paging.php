<?php



/**
 * Paging class
 * 
 *
 */
class Paging{
	/**
	 * UNIQUE id
	 * 
	 * @var int
	 */
  var $uid = 0;
  /**
   * All record number
   * 
   * @var int
   */
  var $rownum;
  /**
   * Record per page
   * 
   * @var int
   */
  var $rowperpage;
  /**
   * Page number
   * 
   * @var int 
   */
  var $pagenum;
  /**
   * Actual page
   * 
   * @var int
   */
  var $current_page;
  /**
   * Use SESSION cache
   * 
   * @var bool
   */
  var $storepagenum;

  /**
   * Constructor
   *     
   * @param $uid
   * @param $rownum
   * @param $rowsperpage
   * @param bool storepagenum
   * @return void
   */
  function Paging($uid, $rownum = 0, $rowsperpage = 10, $storepagenum = false){
    $this->uid = $uid;
    $this->rownum = $rownum;
    $this->rowsperpage = $rowsperpage;
  	$this->pagenum = (int)ceil($rownum/$rowsperpage);
  	$this->storepagenum = $storepagenum;
  	if (isset($_SESSION['paging_'.$this->uid]) && $this->storepagenum){
      if ($_SESSION['paging_'.$this->uid]['current_page'] > $this->pagenum)
        $this->current_page = $this->pagenum;
      else
  		  $this->current_page = $_SESSION['paging_'.$this->uid]['current_page'];
  	} else {
  		$this->current_page = 1;
  		if ($this->storepagenum) $_SESSION['paging_'.$this->uid]['current_page'] = 1;
  	}
  }

  
  /**
   * Jump to the next page
   * 
   * @return bool
   */
  function nextPage(){
		if ($this->current_page < $this->pagenum){
			$this->current_page++;
			if ($this->storepagenum) $_SESSION['paging_'.$this->uid]['current_page'] = $this->current_page;
			return true;
		}
		return false;
  }

  /**
   * Jump to the previous page
   * 
   * @return bool
   */
  function prevPage(){
		if ($this->current_page > 1){
			$this->current_page--;
			if ($this->storepagenum) $_SESSION['paging_'.$this->uid]['current_page'] = $this->current_page;
			return true;
		}
		return false;
  }

  
  /**
   * Jump to page
   * 
   * @param $page Oldalszám
   * @return void
   */
  function goToPage($page){
		if ($page >= 1 && $page <= $this->pagenum){
			$this->current_page = $page;
			if ($this->storepagenum) $_SESSION['paging_'.$this->uid]['current_page'] = $this->current_page;
		}
  }

  /**
   * Return with record number per page
   * 
   * @return int
   */
  function getRowsPerPage(){
    return $this->rowsperpage;
  }

  /**
   * Start with this index
   * 
   * @return int
   */
  function getFrom(){
    $ret = ($this->current_page-1)*$this->rowsperpage;
    return ($ret<0)?0:$ret;
  }

  /**
   * End with this index
   * 
   * @return int
   */
  function getTo(){
    $return = ($this->current_page)*$this->rowsperpage-1;
    if ($return > $this->rownum) $return = $this->rownum;
    return $return;
  }

  /**
   * Actual record number
   * 
   * @return int
   */
  function getItemNum(){
    return $this->getTo()-$this->getFrom()+1;
  }

}

?>
