
/**
 * Applies a filter to return only objects which fail the <?= $shortClassName ?> rule.
 *<?= $docBlockParams ?>

 * @return $this|<?= $childQueryClass ?>

 */
public function filterByIsNot<?= $phpName ?>(<?= $methodParams ?>): <?= $childQueryClass ?>
{
    $class = '<?= $fullClassName ?>';
    /** @var \Chocochaos\Rulable\RuleInterface $rule */
    $rule = new $class(<?= $callParams ?>);

    return $rule->filterByFailsRule($this);
}
