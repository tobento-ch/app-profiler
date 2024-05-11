<?php
$form = $view->form();
$table = $view->table('profiles');

$table->row([
    'uri' => 'Uri',
    'method' => 'Method',
    'status' => 'Status Code',
    'time' => 'Profiled on',
    'actions' => 'Actions',
])->heading();

$table->rows($profiles, static function($row, $profile) use ($view): void {
    $row->column(
        'uri',
        $profile->isUriVisitable()
            ? '<a target="_blank" href="'.$view->esc($profile->uri()).'">'.$view->esc($profile->uri()).'</a>'
            : $view->esc($profile->uri())
    );
    $row->column('method', $profile->method());
    $row->column('status', $profile->statusCode());
    $row->column('time', $view->dateTime($profile->time(), 'EEEE, dd. MMMM yyyy, HH:mm:ss'));
    $row->column(
        'actions',
        '<a class="button text-xxs" href="'.$view->routeUrl('profiler.profiles.show', ['id' => $profile->id()]).'">View</a>'
    );
    $row->html('uri', 'actions');
});
?>
<!DOCTYPE html>
<html lang="<?= $view->esc($view->get('htmlLang', 'en')) ?>">
	
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
        <title>Profiles</title>
        
        <?= $view->render('inc/head') ?>
        <?= $view->assets()->render() ?>
        
        <?php
        $view->asset('assets/profiler/profiler.css');
        $view->asset('assets/css/table.css');
        ?>
    </head>
    
	<body<?= $view->tagAttributes('body')->add('class', 'page page-profiler')->render() ?>>

        <?= $view->render('inc/header') ?>
        <?= $view->render('inc/nav') ?>
        
        <main class="page-main">
            
            <?=	$view->render('inc.breadcrumb') ?>
            <?=	$view->render('inc.messages') ?>

            <h1 class="title text-xl">Profiles</h1>
            
            <div class="buttons spaced">
                <?= $form->form([
                    'action' => $view->routeUrl('profiler.profiles.clear'),
                    'method' => 'POST',
                ]) ?>
                <?= $form->button(text: 'Clear profiles', attributes: ['class' => 'button text-xs']) ?>
                <?= $form->close() ?>
            </div>
            
            <?= $table ?>
        </main>
        
        <?=	$view->render('inc/footer') ?>
	</body>
</html>