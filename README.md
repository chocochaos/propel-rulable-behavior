# Rulable behavior for Propel 2

When writing real-world applications, you often have to deal with complex buisiness rules. Maintaining these rules can be tedious and prone to errors, especially if they have to be checked in multiple places. It is easy to miss one implementation of a rule while making changes,

In propel you can already add your own methods to query and object classes. Even then, you are still defining the same logic in multiple places: in the query classes to filter your query results based on a rule, and in the models themselves, to check if a specific object meets a certain rule.

This behavior allows you to define buisiness rules in centralised classes. Both your query filters and your model checks can be defined in one place. Upon building the schemas, convenient methods for checking these rules are added to the query and model classes, resulting in cleaning and more easily maintainable code.

## Installation

    composer require chocochaos/propel-rulable-behavior

## Usage

### Defining a rule

To define a rule, first create a new directory in your models directory called `Rules`. This directory should be on the same level as the `Base` and `Map` directories generated by Propel.

![Screenshot of the directory structure.](http://chocochaos.com/dev/rulable/DirectoryStructure.png)

Next, create a new class for your rule in this directory (with proper namespacing of course). For example:

```php
<?php

namespace Chocochaos\SampleProject\Models\User\Rules;

use Chocochaos\SampleProject\Models\User\Map\UserGroupFunctionTableMap;
use Chocochaos\SampleProject\Models\User\UserGroupFunction;
use Chocochaos\SampleProject\Models\User\UserGroupFunctionQuery;
use Chocochaos\Rulable\RuleInterface;
use DateTime;
use LogicException;
use Propel\Runtime\ActiveQuery\BaseModelCriteria;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;

/**
 * Class UserGroupFunctionIsActive
 *
 * @package Chocochaos\SampleProject\Models\User\Rules
 */
class UserGroupFunctionIsActive implements RuleInterface
{
    /**
     * @param ActiveRecordInterface $object
     *
     * @return bool
     */
    public function objectMeetsRule(ActiveRecordInterface $object): bool
    {
        if ($object instanceof UserGroupFunction) {
            if (!($object->getStart() instanceof DateTime
                && $object->getStart() <= new DateTime())) {
                return false;
            }
            if ($object->getEnd() instanceof DateTime
                && $object->getEnd() < new DateTime()) {
                return false;
            }

            return true;
        }

        throw new LogicException(
            sprintf(
                'The rule %s can only be applied to objects of type %s.',
                static::class,
                UserGroupFunction::class
            )
        );
    }

    /**
     * @param BaseModelCriteria $query
     *
     * @return BaseModelCriteria
     */
    public function filterByMeetsRule(
        BaseModelCriteria $query
    ): BaseModelCriteria {
        if ($query instanceof UserGroupFunctionQuery) {
            return $query
                ->condition(
                    'has_start_date',
                    UserGroupFunctionTableMap::COL_START . ' IS NOT NULL'
                )
                ->condition(
                    'start_date_in_past',
                    UserGroupFunctionTableMap::COL_START . ' <= NOW()'
                )
                ->condition(
                    'has_no_end_date',
                    UserGroupFunctionTableMap::COL_END . ' IS NULL'
                )
                ->condition(
                    'end_date_in_future',
                    UserGroupFunctionTableMap::COL_END . ' >= NOW()'
                )
                ->combine(
                    ['has_start_date', 'start_date_in_past'],
                    Criteria::LOGICAL_AND,
                    'start_date_valid'
                )
                ->combine(
                    ['has_no_end_date', 'end_date_in_future'],
                    Criteria::LOGICAL_OR,
                    'end_date_valid'
                )
                ->where(
                    ['start_date_valid', 'end_date_valid'],
                    Criteria::LOGICAL_AND
                );
        }

        throw new LogicException(
            sprintf(
                'The rule %s can only be applied to queries of type %s.',
                static::class,
                UserGroupFunctionQuery::class
            )
        );
    }

    /**
     * @param BaseModelCriteria $query
     *
     * @return BaseModelCriteria
     */
    public function filterByFailsRule(
        BaseModelCriteria $query
    ): BaseModelCriteria {
        if ($query instanceof UserGroupFunctionQuery) {
            return $query
                ->condition(
                    'has_no_start_date',
                    UserGroupFunctionTableMap::COL_START . ' IS NULL'
                )
                ->condition(
                    'start_date_in_future',
                    UserGroupFunctionTableMap::COL_START . ' > NOW()'
                )
                ->condition(
                    'has_end_date',
                    UserGroupFunctionTableMap::COL_END . ' IS NOT NULL'
                )
                ->condition(
                    'end_date_in_past',
                    UserGroupFunctionTableMap::COL_END . ' < NOW()'
                )
                ->combine(
                    ['has_no_start_date', 'start_date_in_future'],
                    Criteria::LOGICAL_OR,
                    'start_date_invalid'
                )
                ->combine(
                    ['has_end_date', 'end_date_in_past'],
                    Criteria::LOGICAL_AND,
                    'end_date_invalid'
                )
                ->where(
                    ['start_date_invalid', 'end_date_invalid'],
                    Criteria::LOGICAL_OR
                );
        }

        throw new LogicException(
            sprintf(
                'The rule %s can only be applied to queries of type %s.',
                static::class,
                UserGroupFunctionQuery::class
            )
        );
    }
}
```

As you can see, there are three methods you need to implement:
* `objectMeetsRule`: checks wether an object meets the rule.
* `filterByMeetsRule`: apply filters to a query to only find items which meet the rule.
* `filterByFailsRule`: apply filters to a query to only find items which do not meet the rule.

In the example above, RuleInterface was implemented. This is not required, I would actually recommend not doing so in most cases, as not implementing the interface allows you to apply proper type hinting instead of having to check the types manually in your methods. The example above could be simplified to:

```php
<?php

namespace Chocochaos\SampleProject\Models\User\Rules;

use Chocochaos\SampleProject\Models\User\Map\UserGroupFunctionTableMap;
use Chocochaos\SampleProject\Models\User\UserGroupFunction;
use Chocochaos\SampleProject\Models\User\UserGroupFunctionQuery;
use DateTime;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * Class UserGroupFunctionIsActive
 *
 * @package Chocochaos\SampleProject\Models\User\Rules
 */
class UserGroupFunctionIsActive
{
    /**
     * @param UserGroupFunction $userGroupFunction
     *
     * @return bool
     */
    public function objectMeetsRule(UserGroupFunction $userGroupFunction): bool
    {
        if (!($userGroupFunction->getStart() instanceof DateTime
            && $userGroupFunction->getStart() <= new DateTime())) {
            return false;
        }
        if ($userGroupFunction->getEnd() instanceof DateTime
            && $userGroupFunction->getEnd() < new DateTime()) {
            return false;
        }

        return true;
    }

    /**
     * @param UserGroupFunctionQuery $query
     *
     * @return UserGroupFunctionQuery
     */
    public function filterByMeetsRule(
        UserGroupFunctionQuery $query
    ): UserGroupFunctionQuery {
        return $query
            ->condition(
                'has_start_date',
                UserGroupFunctionTableMap::COL_START . ' IS NOT NULL'
            )
            ->condition(
                'start_date_in_past',
                UserGroupFunctionTableMap::COL_START . ' <= NOW()'
            )
            ->condition(
                'has_no_end_date',
                UserGroupFunctionTableMap::COL_END . ' IS NULL'
            )
            ->condition(
                'end_date_in_future',
                UserGroupFunctionTableMap::COL_END . ' >= NOW()'
            )
            ->combine(
                ['has_start_date', 'start_date_in_past'],
                Criteria::LOGICAL_AND,
                'start_date_valid'
            )
            ->combine(
                ['has_no_end_date', 'end_date_in_future'],
                Criteria::LOGICAL_OR,
                'end_date_valid'
            )
            ->where(
                ['start_date_valid', 'end_date_valid'],
                Criteria::LOGICAL_AND
            );
    }

    /**
     * @param UserGroupFunctionQuery $query
     *
     * @return UserGroupFunctionQuery
     */
    public function filterByFailsRule(
        UserGroupFunctionQuery $query
    ): UserGroupFunctionQuery {
        return $query
            ->condition(
                'has_no_start_date',
                UserGroupFunctionTableMap::COL_START . ' IS NULL'
            )
            ->condition(
                'start_date_in_future',
                UserGroupFunctionTableMap::COL_START . ' > NOW()'
            )
            ->condition(
                'has_end_date',
                UserGroupFunctionTableMap::COL_END . ' IS NOT NULL'
            )
            ->condition(
                'end_date_in_past',
                UserGroupFunctionTableMap::COL_END . ' < NOW()'
            )
            ->combine(
                ['has_no_start_date', 'start_date_in_future'],
                Criteria::LOGICAL_OR,
                'start_date_invalid'
            )
            ->combine(
                ['has_end_date', 'end_date_in_past'],
                Criteria::LOGICAL_AND,
                'end_date_invalid'
            )
            ->where(
                ['start_date_invalid', 'end_date_invalid'],
                Criteria::LOGICAL_OR
            );
    }
}
```

### Add behavior to your schema and build

Finally, add the rulable behavior to your schema, and set the correct parameters:

```xml
<table name="user_group_function">
    <column name="id" type="INTEGER" primaryKey="true" autoIncrement="true" required="true" />
    <column name="user_id" type="INTEGER" required="true" />
    <column name="group_function_id" type="INTEGER" />
    <column name="start" type="TIMESTAMP" required="true" />
    <column name="end" type="TIMESTAMP" />
    <behavior name="timestampable"/>
    <foreign-key foreignTable="group_function" onDelete="CASCADE" onUpdate="CASCADE">
        <reference local="group_function_id" foreign="id" />
    </foreign-key>
    <foreign-key foreignTable="user" onDelete="CASCADE" onUpdate="CASCADE">
        <reference local="user_id" foreign="id" />
    </foreign-key>
    <behavior name="rulable">
        <parameter name="Active" value="UserGroupFunctionIsActive" />
    </behavior>
</table>
```

- The `name` parameter defines the PHPName for your rule, this name will be used for method generation.
- The `value` parameter defined the rule class to be used.
- Don't forget to build your schemas after adding or updating the behavior.

### Use the generated methods

In the example above, `Active` was supplied as the PHPName for the rule. The following method will now be available:
- `->isActive()` on `UserGroupFunction`.
- `->filterByIsActive()` on `UserGroupFunctionQuery`.
- `->filterByIsNotActive()` on `UserGroupFunctionQuery`.

## Other options

There are a few things not yet covered above:
- You can add multiple parameters to add multiple rules to a model.
- Instead of specifying a short class name in the XML, you can also specify a fully qualified class name (including namespace). This removes the requirement to put the rules in a `Rules' directory with your models, which can be useful when writing rules that apply to multiple entities.
- Your rules class can have a constructor to pass extra parameters. These parameters will then be available as parameters on the generated functions, including type hints and default values. Variadics and constants as default values are not (yet) supported though.

## License

See the [LICENSE](https://github.com/chocochaos/propel-rulable-behavior/blob/master/LICENSE) file.
