<?php
$this->bodyClasses[] = 'agent-2';
$this->bodyClasses[] = 'layout-entity';
$this->bodyClasses[] = 'action-single';
?>
<?php $this->part('header', $render_data) ?>
<?php $this->part('main-header', $render_data) ?>
<mc-entity #default="{entity}">
<?= $TEMPLATE_CONTENT ?>
</mc-entity>
<?php $this->part('main-footer', $render_data) ?>
<?php $this->part('footer', $render_data); ?>
