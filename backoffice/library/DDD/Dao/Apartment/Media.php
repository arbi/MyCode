<?php

namespace DDD\Dao\Apartment;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\ServiceManager\ServiceLocatorInterface;

class Media extends TableGatewayManager
{
    /**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_APARTMENT_IMAGES;

    /**
     * @access public
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Apartment\Media\Images')
    {
        parent::__construct($sm, $domain);
    }
    
    /**
     * 
     * @param int $apartmentId
     * @return \DDD\Domain\Apartment\Media\Media
     */
    public function getImages($apartmentId)
    {
        $result = $this->fetchOne(function(Select $select) use($apartmentId){
            $select->columns([
                'id',
                'apartment_id',
                'img1',
                'img2',
                'img3',
                'img4',
                'img5',
                'img6',
                'img7',
                'img8',
                'img9',
                'img10',
                'img11',
                'img12',
                'img13',
                'img14',
                'img15',
                'img16',
                'img17',
                'img18',
                'img19',
                'img20',
                'img21',
                'img22',
                'img23',
                'img24',
                'img25',
                'img26',
                'img27',
                'img28',
                'img29',
                'img30',
                'img31',
                'img32',
            ]);
            
            $where = new Where();
            $where->equalTo($this->table.'.apartment_id', $apartmentId);
            
            $select->where($where);
        });
        
        return $result;
    }
    
    public function getFirstImage($apartmentId)
    {
        $result = $this->fetchOne(function(Select $select) use($apartmentId){
            $select->columns([
                'img1'
            ]);
            
            $where = new Where();
            $where->equalTo($this->table.'.apartment_id', $apartmentId);
            
            $select->where($where);
        });
        
        return $result;
    }
    
    public function getVideos($apartmentId)
    {
        $result = $this->fetchOne(function(Select $select) use($apartmentId) {
            $select->columns([
                'id',
                'apartment_id',
                'video',
                'key_entry_video'
            ]);
            
            $where = new Where();
            $where->equalTo($this->table.'.apartment_id', $apartmentId);
            
            $select->where($where);
        });
        
        return $result;
    }
}
