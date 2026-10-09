<?php declare(strict_types=1);











namespace Composer\Advisory;

use Composer\Config;





class AuditConfig
{



public $audit;




public $auditFormat;




public $auditAbandoned;




public $blockInsecure;




public $blockAbandoned;




public $ignoreUnreachable;




public $ignoreListForAudit;




public $ignoreListForBlocking;




public $ignoreSeverityForAudit;




public $ignoreSeverityForBlocking;




public $ignoreAbandonedForAudit;




public $ignoreAbandonedForBlocking;











public function __construct(bool $audit, string $auditFormat, string $auditAbandoned, bool $blockInsecure, bool $blockAbandoned, bool $ignoreUnreachable, array $ignoreListForAudit, array $ignoreListForBlocking, array $ignoreSeverityForAudit, array $ignoreSeverityForBlocking, array $ignoreAbandonedForAudit, array $ignoreAbandonedForBlocking)
{
$this->audit = $audit;
$this->auditFormat = $auditFormat;
$this->auditAbandoned = $auditAbandoned;
$this->blockInsecure = $blockInsecure;
$this->blockAbandoned = $blockAbandoned;
$this->ignoreUnreachable = $ignoreUnreachable;
$this->ignoreListForAudit = $ignoreListForAudit;
$this->ignoreListForBlocking = $ignoreListForBlocking;
$this->ignoreSeverityForAudit = $ignoreSeverityForAudit;
$this->ignoreSeverityForBlocking = $ignoreSeverityForBlocking;
$this->ignoreAbandonedForAudit = $ignoreAbandonedForAudit;
$this->ignoreAbandonedForBlocking = $ignoreAbandonedForBlocking;
}










private static function parseIgnoreWithApply(array $config): array
{
$forAudit = [];
$forBlock = [];

foreach ($config as $key => $value) {

if (is_int($key) && is_string($value)) {
$id = $value;
$apply = 'all';
$reason = null;
}

elseif (is_string($value)) {
$id = $key;
$apply = 'all';
$reason = $value;
}

elseif (is_array($value)) {
$id = $key;
$apply = $value['apply'] ?? 'all';
$reason = $value['reason'] ?? null;


if (!in_array($apply, ['audit', 'block', 'all'], true)) {
throw new \InvalidArgumentException(
"Invalid 'apply' value for '{$id}': {$apply}. Expected 'audit', 'block', or 'all'."
);
}
}

elseif ($value === null) {
$id = $key;
$apply = 'all';
$reason = null;
}
else {
continue;
}


if ($apply === 'audit' || $apply === 'all') {
$forAudit[$id] = $reason;
}
if ($apply === 'block' || $apply === 'all') {
$forBlock[$id] = $reason;
}
}

return [
'audit' => $forAudit,
'block' => $forBlock,
];
}




public static function fromConfig(Config $config, bool $audit = true, string $auditFormat = Auditor::FORMAT_SUMMARY): self
{
$auditConfig = $config->get('audit');


$ignoreListParsed = self::parseIgnoreWithApply($auditConfig['ignore'] ?? []);
$ignoreAbandonedParsed = self::parseIgnoreWithApply($auditConfig['ignore-abandoned'] ?? []);
$ignoreSeverityParsed = self::parseIgnoreWithApply($auditConfig['ignore-severity'] ?? []);

return new self(
$audit,
$auditFormat,
$auditConfig['abandoned'] ?? Auditor::ABANDONED_FAIL,
(bool) ($auditConfig['block-insecure'] ?? true),
(bool) ($auditConfig['block-abandoned'] ?? false),
(bool) ($auditConfig['ignore-unreachable'] ?? false),
$ignoreListParsed['audit'],
$ignoreListParsed['block'],
$ignoreSeverityParsed['audit'],
$ignoreSeverityParsed['block'],
$ignoreAbandonedParsed['audit'],
$ignoreAbandonedParsed['block']
);
}
}
