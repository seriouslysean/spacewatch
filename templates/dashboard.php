<?php if (!defined('ABSPATH')) exit; ?>
<li class="<?php echo self::DASHBOARD_CLASS ?>">
    <a class="progress" data-warning-level="<?php echo $this->getWarningLevel() ?>" title="<?php echo $this->getFreeSpaceReadable() ?> remaining (<?php echo $this->getTotalSpaceReadable() ?> total)"><span class="percentage" style="width:<?php echo $this->getUsedSpacePercent() ?>%;"></span></a>
</li>