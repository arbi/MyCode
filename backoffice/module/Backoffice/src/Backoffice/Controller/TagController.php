<?php
namespace Backoffice\Controller;


use Library\Controller\ControllerBase;
use Library\Constants\TextConstants;


use Zend\Http\Request;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;


/**
 *
 */
class TagController extends ControllerBase
{
    public function indexAction()
    {
        return new ViewModel();
    }

    public function ajaxTagsListAction()
    {
        $request = $this->params();
        /** @var \DDD\Service\Tag\Tag $tagService */
        $tagService = $this->getServiceLocator()->get('service_tag_tag');
        $aaData     = [];

        $tags = $tagService->getTagsList(
            (int)$request->fromQuery('iDisplayStart'),
            (int)$request->fromQuery('iDisplayLength'),
            (int)$request->fromQuery('iSortCol_0'),
            $request->fromQuery('sSortDir_0'),
            $request->fromQuery('sSearch')
        );

        $tagsCount = $tagService->getTagsCount($request->fromQuery('sSearch'));

        foreach ($tags as $tag) {

            $taskPageUrl = $this->url()->fromRoute(
                'backoffice/default',
                [
                    'controller' => 'task',
                    'action' => 'index'
                ],
                ['query' => [
                    'tag_id' => $tag->getId()
                ]]);
            array_push($aaData, [
                '<span data-id="' . $tag->getId() . '" data-style="' . $tag->getStyle() . '"   class="label editable-label ' . $tag->getStyle() . '"  >'
                    . '<span class="glyphicon glyphicon-tag"></span> ' . $tag->getName()
                    . '</span>',
                $tag->getUsedCount(),
                '<a target="_blank" class="btn btn-xs btn-info" href="' . $taskPageUrl . '">'
                    . '<span class="glyphicon glyphicon-share"></span> '
                    . 'View Tasks'
                    . '</a>',
                '<button class="btn btn-xs btn-primary btn-edit-tag" '
                    . 'data-id="' . $tag->getId() . '" data-style="' . $tag->getStyle() . '" data-text="' . $tag->getName() . '">'
                    . 'Edit'
                    . '</button>',
                '<button class="btn btn-xs btn-danger btn-delete-tag" data-id="' . $tag->getId() . '">'
                    . 'Delete'
                    . '</button>',
            ]);
        }

        if (!isset($aaData) || is_null($aaData)) {
            array_push($aaData, [' ', '', '', '', '', '', '', '', '']);
        }

        $resultArray = [
            'sEcho'                => $request->fromQuery('sEcho'),
            'iTotalRecords'        => $tagsCount,
            'iTotalDisplayRecords' => $tagsCount,
            'iDisplayStart'        => $request->fromQuery('iDisplayStart'),
            'iDisplayLength'       => (int)$request->fromQuery('iDisplayLength'),
            'aaData'               => $aaData,
        ];

        return new JsonModel($resultArray);
    }

    public function ajaxEditTagAction()
    {
        $result = [
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_ADD
        ];

        $request = $this->getRequest();

        try {
            if($request->isXmlHttpRequest()) {
                $tagName    = $request->getPost('tag-name');
                $style      = $request->getPost('style');
                $id         = $request->getPost('tag-id');

                /** @var \DDD\Service\Tag\Tag $tagService */
                $tagService = $this->getServiceLocator()->get('service_tag_tag');
                if ($tagService->alreadyExists($tagName, $id)) {
                    return new JsonModel(
                        [
                            'status' => 'error',
                            'msg'    => 'Tag with this name already exists'
                        ]
                    );
                }
                if ($id) {
                    $result['msg'] = TextConstants::SUCCESS_UPDATE;
                }

                $tagService->editTag($id, $tagName, $style);
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }
        return new JsonModel($result);
    }

    public function ajaxDeleteTagAction()
    {
        $result = [
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_DELETE
        ];

        $request = $this->getRequest();

        try {
            if($request->isXmlHttpRequest()) {
                $id        = $request->getPost('id');
                if ($id) {
                    $result['msg'] = TextConstants::SUCCESS_UPDATE;
                }
                /** @var \DDD\Service\Tag\Tag $tagService */
                $tagService = $this->getServiceLocator()->get('service_tag_tag');
                $tagService->deleteTag($id);
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }
        return new JsonModel($result);
    }

}
