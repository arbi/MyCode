<?php
namespace DDD\Service;

use DDD\Service\ServiceBase;
use Library\Constants\Constants;
use Library\Constants\Objects;
use Library\Constants\DbTables;
use Library\Upload\Images;
use Library\Utility\Helper;

class News extends ServiceBase
{
    protected $_newsDao = FALSE;
    
    public function getNewsResult($filterParams = array()) {
    	$dao = $this->getNewsDao();
    	$news = $dao->fetchAll();
    	return $news;
    }
    
    public function getNewsById($id){
        $dao = $this->getNewsDao();
    	$news = $dao->fetchOne(['id'=>$id]);
    	return $news;
    }
    
    public function newsSave($data, $id){
        $newsDao = $this->getNewsDao();
        $data               = (array)$data;
        $saveData = array(
            'en'       => $data['body'],
            'en_title' => $data['title'],
            'date'     => ($data['date'] ? date('Y-m-d', strtotime($data['date'])) : ''),
            'slug'     => str_replace(
                ' ', 
                '-', 
                strtolower(preg_replace('/[^a-zA-Z0-9 -]/','',$data['title']))
            )
        );

        $insert_id = $id;
        if($id > 0){
            $newsDao->save($saveData, array('id'=>(int)$id));
        } else {
            if(!$data['date'])
                $saveData['date'] = date("Y-m-d");
            $insert_id = $newsDao->save($saveData);
        }
        return $insert_id;
    }
    
    function deleteNews($id){
        $blogDao = $this->getNewsDao();
        $blogDao->deleteWhere(array('id'=>$id)); 
        return true;
    }
    
    private function getNewsDao()
    {
        if (!$this->_newsDao)
            $this->_newsDao = $this->getServiceLocator()->get('dao_news_news');

        return $this->_newsDao;
    }
}
