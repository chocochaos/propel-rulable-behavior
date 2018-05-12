<?php

namespace Chocochaos\Rulable;

use Propel\Runtime\ActiveQuery\BaseModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;

/**
 * Interface RuleInterface
 *
 * @package Chocochaos\Rulable
 */
interface RuleInterface
{
    /**
     * @param ActiveRecordInterface $object
     *
     * @return bool
     */
    public function objectMeetsRule(ActiveRecordInterface $object): bool;

    /**
     * @param BaseModelCriteria $query
     *
     * @return BaseModelCriteria
     */
    public function filterByMeetsRule(
        BaseModelCriteria $query
    ): BaseModelCriteria;

    /**
     * @param BaseModelCriteria $query
     *
     * @return BaseModelCriteria
     */
    public function filterByFailsRule(
        BaseModelCriteria $query
    ): BaseModelCriteria;
}
