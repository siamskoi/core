<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Components\ORM\Selector\Loaders;

use Spiral\Components\ORM\Entity;
use Spiral\Components\ORM\Selector;

class BelongsToLoader extends HasOneLoader
{
    /**
     * Relation type is required to correctly resolve foreign model.
     */
    const RELATION_TYPE = Entity::BELONGS_TO;

    /**
     * Default load method (inload or postload).
     */
    const LOAD_METHOD = Selector::INLOAD; //TODO: change

    /**
     * Reference key (from parent object) required to speed up data normalization.
     *
     * @return string
    //     */
    //    public function getReferenceKey()
    //    {
    //        //No reference key is needed
    //        return null;
    //    }

    public function parseRow(array $row)
    {
        $data = $this->fetchData($row);

        if (!$referenceName = $this->getReferenceName($data))
        {
            //Relation not loaded
            return;
        }

        if (!$this->mountDuplicate($data))
        {
            //Clarifying parent dataset
            $this->registerReferences($data);
        }

        $this->parent->registerNested($referenceName, $this->container, $data, false);

        $this->parseNested($row);
    }
}