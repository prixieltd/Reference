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
   * @access private     
	 */
  private $uid = 0;
  /**
   * Use SESSION cache
   * 
   * @var bool
   * @access private   
   */
  private $storepagenum;
  /**
   * All record number
   * 
   * @var int
   * @access public     
   */
  public $rownum;
  /**
   * Record per page
   * 
   * @var int
   * @access public    
   */
  public $rowperpage;
  /**
   * Page number
   * 
   * @var int 
   * @access public      
   */
  public $pagenum;
  /**
   * Actual page
   * 
   * @var int
   * @access public    
   */
  public $current_page;

                     
  /**
   * Constructor
   *     
   * @param $uid
   * @param $rownum
   * @param $rowsperpage
   * @param bool storepagenum
   * @return void                         
   */
  public function __construct($uid, $rownum = 0, $rowsperpage = 10, $storepagenum = false){
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
  public function nextPage(){
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
  public function prevPage(){
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
  public function goToPage($page){
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
  public function getRowsPerPage(){
    return $this->rowsperpage;
  }

  /**
   * Start with this index
   * 
   * @return int
   */
  public function getFrom(){
    $ret = ($this->current_page-1)*$this->rowsperpage;
    return ($ret<0)?0:$ret;
  }

  /**
   * End with this index
   * 
   * @return int
   */
  public function getTo(){
    $return = ($this->current_page)*$this->rowsperpage-1;
    if ($return > $this->rownum) $return = $this->rownum;
    return $return;
  }

  /**
   * Actual record number
   * 
   * @return int
   */
  public function getItemNum(){
    return $this->getTo()-$this->getFrom()+1;
  }

}

