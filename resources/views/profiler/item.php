<?php if ($item->render()) { ?>
    <?php if ($item->title()) { ?>
        <div class="title text-m"><?= $view->esc($item->title()) ?></div>
    <?php } ?>
    <?php if ($item->description()) { ?>
        <p><?= $view->esc($item->description()) ?></p>
    <?php } ?>
    <div class="content overflow-y-auto mb-l mt-s"><?= $item->html() ?></div>
<?php } ?>