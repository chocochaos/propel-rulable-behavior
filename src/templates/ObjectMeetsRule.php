
/**
 * Checks if this object meets the <?= $shortClassName ?> rule.
 *<?= $docBlockParams ?>

 * @return bool
 */
public function is<?= $phpName ?>(<?= $methodParams ?>): bool
{
    $class = '<?= $fullClassName ?>';
    /** @var \Chocochaos\Rulable\RuleInterface $rule */
    $rule = new $class(<?= $callParams ?>);

    return $rule->objectMeetsRule($this);
}
