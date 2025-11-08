<?php
/**
 * @var \App\View\AppView $this
 * @var string $message
 * @var string $url
 */
?>

<p class="error">
    <strong><?= __d('cake', 'Error') ?>: </strong>
    <?= __d('cake', 'The requested address {0} was not found on this server.', "<strong>'{$url}'</strong>") ?>
</p>
