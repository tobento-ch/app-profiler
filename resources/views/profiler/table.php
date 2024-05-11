<?php if ($table->render()) { ?>
    <?php if ($table->title()) { ?>
        <div class="title text-m"><?= $view->esc($table->title()) ?></div>
    <?php } ?>
    <?php if ($table->description()) { ?>
        <p><?= $view->esc($table->description()) ?></p>
    <?php } ?>
    <div class="content overflow-y-auto mb-l">
        <?php if ($table->headings() || $table->rows()) { ?>
        <table>
            <?php if ($table->headings()) { ?>
            <tr>
            <?php foreach($table->headings() as $heading) { ?>
                <th><?= $table->renderValue($heading, $heading) ?></th>
            <?php } ?>
            </tr>
            <?php } ?>
            <?php if ($table->rows()) { ?>
                <?php foreach($table->rows() as $row) { ?>
                    <tr>
                    <?php foreach($table->verifyRow($row) as $name => $value) { ?>
                        <td><?= $table->renderValue($value, $name) ?></td>
                    <?php } ?>
                    </tr>
                <?php } ?>
            <?php } ?>
        </table>
        <?php } ?>
    </div>
<?php } ?>