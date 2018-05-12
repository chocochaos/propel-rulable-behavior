
/**
 * Applies a filter to return only objects which meet the <?= $shortClassName ?> rule.
 *<?= $docBlockParams ?>

 * @return $this|<?= $childQueryClass ?>

 */
public function filterByIs<?= $phpName ?>(<?= $methodParams ?>): <?= $childQueryClass ?>
{
    $class = '<?= $fullClassName ?>';
    /** @var \Chocochaos\Rulable\RuleInterface $rule */
    $rule = new $class(<?= $callParams ?>);

    return $rule->filterByMeetsRule($this);
}
