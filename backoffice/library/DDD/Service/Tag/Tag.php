<?php

namespace DDD\Service\Tag;

use DDD\Service\ServiceBase;

class Tag extends ServiceBase
{
    CONST CLASS_LABEL_RED            = 'label-red';
    CONST CLASS_LABEL_PINK           = 'label-pink';
    CONST CLASS_LABEL_PURPLE         = 'label-purple';
    CONST CLASS_LABEL_DEEP_PURPLE    = 'label-deep-purple';
    CONST CLASS_LABEL_INDIGO         = 'label-indigo';
    CONST CLASS_LABEL_BLUE           = 'label-blue';
    CONST CLASS_LABEL_LIGHT_BLUE     = 'label-light-blue';
    CONST CLASS_LABEL_CYAN           = 'label-cyan';
    CONST CLASS_LABEL_TEAL           = 'label-teal';
    CONST CLASS_LABEL_GREEN          = 'label-green';
    CONST CLASS_LABEL_LIGHT_GREEN    = 'label-light-green';
    CONST CLASS_LABEL_LIME           = 'label-lime';
    CONST CLASS_LABEL_YELLOW         = 'label-yellow';
    CONST CLASS_LABEL_AMBER          = 'label-amber';
    CONST CLASS_LABEL_ORANGE         = 'label-orange';
    CONST CLASS_LABEL_DEEP_ORANGE    = 'label-deep-orange';
    CONST CLASS_LABEL_BROWN          = 'label-brown';
    CONST CLASS_LABEL_GREY           = 'label-grey';
    CONST CLASS_LABEL_BLUE_GREY      = 'label-blue-grey';
    CONST CLASS_LABEL_BLACK          = 'label-black';

    CONST NAME_LABEL_RED = 'Red';
    CONST NAME_LABEL_PINK = 'Pink';
    CONST NAME_LABEL_PURPLE = 'Purple';
    CONST NAME_LABEL_DEEP_PURPLE = 'Deep Purple';
    CONST NAME_LABEL_INDIGO = 'Indigo';
    CONST NAME_LABEL_BLUE = 'Blue';
    CONST NAME_LABEL_LIGHT_BLUE = 'Light Blue';
    CONST NAME_LABEL_CYAN = 'Cyan';
    CONST NAME_LABEL_TEAL = 'Teal';
    CONST NAME_LABEL_GREEN = 'Green';
    CONST NAME_LABEL_LIGHT_GREEN = 'Light Green';
    CONST NAME_LABEL_LIME = 'Lime';
    CONST NAME_LABEL_YELLOW = 'Yellow';
    CONST NAME_LABEL_AMBER = 'Amber';
    CONST NAME_LABEL_ORANGE = 'Orange';
    CONST NAME_LABEL_DEEP_ORANGE = 'Deep Orange';
    CONST NAME_LABEL_BROWN = 'Brown';
    CONST NAME_LABEL_GREY = 'Grey';
    CONST NAME_LABEL_BLUE_GREY = 'Blue Grey';
    CONST NAME_LABEL_BLACK = 'Black';

    CONST DEFAULT_LABEL  = self::CLASS_LABEL_GREY;

    public static function getAllLabelClasses()
    {
        return [
          ['name' => self::NAME_LABEL_RED, 'class' => self::CLASS_LABEL_RED],
          ['name' => self::NAME_LABEL_PINK, 'class' => self::CLASS_LABEL_PINK],
          ['name' => self::NAME_LABEL_PURPLE, 'class' => self::CLASS_LABEL_PURPLE],
          ['name' => self::NAME_LABEL_DEEP_PURPLE, 'class' => self::CLASS_LABEL_DEEP_PURPLE],
          ['name' => self::NAME_LABEL_INDIGO, 'class' => self::CLASS_LABEL_INDIGO],
          ['name' => self::NAME_LABEL_BLUE, 'class' => self::CLASS_LABEL_BLUE],
          ['name' => self::NAME_LABEL_LIGHT_BLUE, 'class' => self::CLASS_LABEL_LIGHT_BLUE],
          ['name' => self::NAME_LABEL_CYAN, 'class' => self::CLASS_LABEL_CYAN],
          ['name' => self::NAME_LABEL_TEAL, 'class' => self::CLASS_LABEL_TEAL],
          ['name' => self::NAME_LABEL_GREEN, 'class' => self::CLASS_LABEL_GREEN],
          ['name' => self::NAME_LABEL_LIGHT_GREEN, 'class' => self::CLASS_LABEL_LIGHT_GREEN],
          ['name' => self::NAME_LABEL_LIME, 'class' => self::CLASS_LABEL_LIME],
          ['name' => self::NAME_LABEL_YELLOW, 'class' => self::CLASS_LABEL_YELLOW],
          ['name' => self::NAME_LABEL_AMBER, 'class' => self::CLASS_LABEL_AMBER],
          ['name' => self::NAME_LABEL_ORANGE, 'class' => self::CLASS_LABEL_ORANGE],
          ['name' => self::NAME_LABEL_DEEP_ORANGE, 'class' => self::CLASS_LABEL_DEEP_ORANGE],
          ['name' => self::NAME_LABEL_BROWN, 'class' => self::CLASS_LABEL_BROWN],
          ['name' => self::NAME_LABEL_GREY, 'class' => self::CLASS_LABEL_GREY],
          ['name' => self::NAME_LABEL_BLUE_GREY, 'class' => self::CLASS_LABEL_BLUE_GREY],
          ['name' => self::NAME_LABEL_BLACK, 'class' => self::CLASS_LABEL_BLACK],
        ];
    }

    /**
     * @param $start
     * @param $limit
     * @param $sortCol
     * @param $sortDir
     * @param $search
     * @return \DDD\Domain\Tag\Tag[]
     */
    public function getTagsList($start, $limit, $sortCol, $sortDir, $search)
    {
        /** @var \DDD\Dao\Tag\Tag $tagsDao */
        $tagsDao = $this->getServiceLocator()->get('dao_tag_tag');

        return $tagsDao->getTagsList($start, $limit, $sortCol, $sortDir, $search);
    }

    public function getTagsCount($search)
    {
        $tagsDao = $this->getServiceLocator()->get('dao_tag_tag');

        return $tagsDao->getTagsCount($search);
    }

    public function editTag($id, $tagName, $style)
    {
        $tagsDao = $this->getServiceLocator()->get('dao_tag_tag');

        if ($id) {
            $tagsDao->save(
                [
                    'name' => $tagName,
                    'style' => $style,
                ],
                ['id' => $id]
            );
        } else {
            $tagsDao->save(
                [
                    'name' => $tagName,
                    'style' => $style,
                ]
            );
        }
    }

    public function getAllTagsAsArray()
    {
        $tagDao = $this->getServiceLocator()->get('dao_tag_tag');

        $allTags = $tagDao->fetchAll();

        $tagsArray = [];

        foreach($allTags as $tag) {
            array_push($tagsArray,
                [
                    'id'    => $tag->getId(),
                    'name'  => $tag->getName(),
                    'style' => $tag->getStyle(),
                ]
            );
        }
        return  $tagsArray;

    }

    public function deleteTag($id)
    {
        $tagDao = $this->getServiceLocator()->get('dao_tag_tag');

        $tagDao->delete(['id' => $id]);
    }

    public function alreadyExists($tagName,$id)
    {
        $tagDao = $this->getServiceLocator()->get('dao_tag_tag');
        return $tagDao->alreadyExists($tagName,$id);

    }

}

