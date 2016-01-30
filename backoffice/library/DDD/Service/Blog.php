<?php
namespace DDD\Service;

use DDD\Service\ServiceBase;
use Library\Constants\Objects;
use Library\Constants\DbTables;
use Library\Upload\Images;
use Library\Utility\Helper;
use FileManager\Constant\DirectoryStructure;

class Blog extends ServiceBase
{
    protected $_blogDao = FALSE;

    public function getBlogResult($filterParams = array()) {
    	$dao = $this->getBlogDao();
    	$blogs = $dao->fetchAll();
    	return $blogs;
    }

    public function getBlogById($id){
        $dao = $this->getBlogDao();
    	$blogs = $dao->fetchOne(['id'=>$id]);
    	return $blogs;
    }

    public function saveToTemp(Array $file)
    {
        $image = new Images($file);
        if($image->errors){
            $result['status'] = 'error';
            $result['msg'] = $image->errors;
            return $result;
        } else {
            $result['status'] = 'success';
            $result['msg'] = 'Successfully uploaded';
            $result['src'] = $image->saveImage(DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_IMAGES_ROOT
                . DirectoryStructure::FS_IMAGES_TEMP_PATH);
            return $result;
        }
    }

    public function blogSave($data, $id){
        $blogDao = $this->getBlogDao();
        $data    = (array)$data;
        $saveData = array(
            'content'=> $data['body'],
            'en_status'=> 3,
            'title'=> $data['title'],
            'date'=> ($data['date'] ? date('Y-m-d', strtotime($data['date'])) : ''),
            'en_title_status'=>3,
            'slug' => str_replace(' ', '-', strtolower(preg_replace('/[^a-zA-Z0-9 -]/','',$data['title'])))
        );

        $insert_id = $id;
        $location_id = 0;
        if($id > 0){
            $blogDao->save($saveData, array('id'=>(int)$id));
        } else {
            if(!$data['date'])
                $saveData['date'] = date('Y-m-d');
            $insert_id = $blogDao->save($saveData);
        }

        if($data['img_post'] !== '' AND file_exists($data['img_post'])){

            $filePathArray = explode('/', $data['img_post']);
            $fileInfo[0]['name'] = end($filePathArray);

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $fileInfo[0]['type'] = finfo_file($finfo, $data['img_post']);
            finfo_close($finfo);

            $fileInfo[0]['tmp_name'] = $data['img_post'];
            $fileInfo[0]['error'] = 0;
            $fileInfo[0]['size'] = filesize($data['img_post']);

            $image = new Images($fileInfo);
            $img_size = 300;
            $image->resizeToWidth(array($img_size));

            $blogImagesPath = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_IMAGES_ROOT
                . DirectoryStructure::FS_IMAGES_BLOG_PATH;

            $newImg = str_replace(array('/ginosi/images', '_original', 'jpeg', 'jpg', 'gif'), array('','_300', 'png', 'png', 'png'), $image->saveImage($blogImagesPath.$insert_id.'/'));

            if($newImg){
                $row = $blogDao->fetchOne(['id'=>$insert_id]);
                if($row->getImg() != ''){
                    if (strstr($row->getImg(), '_300')) {
                        $imagePathParts = explode('_300', $row->getImg());
                    } elseif (strstr($row->getImg(), '_orig')) {
                        $imagePathParts = explode('_orig', $row->getImg());
                    }

                    $imagePathMask = '/ginosi/images' . $imagePathParts[0] . '*';

                    array_map('unlink', glob($imagePathMask));
                 }
                $blogDao->save(array('img'=>$newImg), array('id' => $insert_id));
            }
        }

        return array($insert_id, $location_id);
    }

    public function removeImage($id)
    {
        $blogDao = $this->getBlogDao();
        $row = $blogDao->fetchOne(['id'=>$id]);

        if($row->getImg() != '') {

            $currentImg = explode('_', explode('/', $row->getImg())[3])[0];

            $removeMask = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_IMAGES_ROOT
                . DirectoryStructure::FS_IMAGES_BLOG_PATH
                . $id . '/' . $currentImg . '*';

            $removedByMask = array_map( "unlink", glob($removeMask));

            if (empty($removedByMask)) {
                return FALSE;
            }

            $blogDao->save(array('img'=>''), array('id' => $id));
        }
    }

    function deleteBlog($id){
        Helper::deleteDirectory(DirectoryStructure::FS_GINOSI_ROOT
            . DirectoryStructure::FS_IMAGES_ROOT
            . DirectoryStructure::FS_IMAGES_BLOG_PATH
            . $id);

        $blogDao = $this->getBlogDao();
        $blogDao->deleteWhere(array('id'=>$id));
        return true;
    }

    private function getBlogDao()
    {
        if (!$this->_blogDao)
            $this->_blogDao = $this->getServiceLocator()->get('dao_blog_blog');

        return $this->_blogDao;
    }
}
