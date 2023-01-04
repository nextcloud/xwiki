<?php
script('xwiki', 'script');
style('xwiki', 'style');

if ($_['integratedMode']) { ?>
<div id="app-navigation">
	<?php print_unescaped($this->inc('navigation/index')); ?>
	<?php print_unescaped($this->inc('settings/index')); ?>
</div><?php
} ?>
<div id="app-content">
	<div id="app-content-wrapper">
		<?php print_unescaped($this->inc('content/index')); ?>
	</div>
</div>
