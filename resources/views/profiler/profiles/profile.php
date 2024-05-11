<?php
$menuCollectors = $view->menu('profiler.collectors');
$menuCollectors->link($view->routeUrl('profiler.profiles.index'), 'Back To Profiles');
$menuCollectors->link('#profile', 'Profile');
$menuCollectors->many(
    $profiler->collectors(),
    static function($menu, $collector) use ($profiler, $profile): void {
        if (empty($data = $profiler->getCollectedData($collector->name(), $profile))) {
            return;
        }
        $data = $collector->data($data);
        $item = $menu
            ->link('#'.$profiler->nameToId($collector->name()), $collector->name())
            ->id($collector->name())
            ->badgeIf(
                badge: !empty($data['badge']),
                text: $data['badge'] ?? '',
                attributes: $data['badgeAttributes'] ?? []
            );
        
        if (!empty($data['icon'])) {
            $item->icon(name: $data['icon']);
        }
    }
);
$menuCollectors->tag('ul')->level(0)->class('menu-v spaced menu-main');
?>
<!DOCTYPE html>
<html lang="<?= $view->esc($view->get('htmlLang', 'en')) ?>" class="scroll-behavior-smooth">
	
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
        <title>Profile</title>
        
        <?= $view->render('inc/head') ?>
        <?= $view->assets()->render() ?>
        
        <?php
        $view->asset('assets/profiler/profiler.css');
        $view->asset('assets/profiler/profiler.js')->attr('type', 'module');
        ?>
    </head>
    
	<body<?= $view->tagAttributes('body')->add('class', 'page page-profiler')->render() ?>>
        
        <?= $view->render('inc/header') ?>
        
        <div class="page-nav">
            <nav id="menu-main"><?= $menuCollectors ?></nav>
        </div>
        
        <main class="page-main">
            <?=	$view->render('inc.breadcrumb') ?>
            <?=	$view->render('inc.messages') ?>

            <h1 id="profile" class="title text-l">Profile</h1>
            
            <div class="content">
                <dl class="mb-l">
                    <dt>Uri</dt>
                    <dd>
                    <?= $profile->isUriVisitable()
                        ? '<a target="_blank" href="'.$view->esc($profile->uri()).'">'.$view->esc($profile->uri()).'</a>'
                        : $view->esc($profile->uri())
                    ?>
                    </dd>
                    <dt>Method</dt>
                    <dd><?= $view->esc($profile->method()) ?></dd>
                    <dt>Status Code</dt>
                    <dd><?= $view->esc($profile->statusCode()) ?></dd>
                    <dt>Profiled on</dt>
                    <dd><?= $view->esc($view->dateTime($profile->time(), 'EEEE, dd. MMMM yyyy, HH:mm:ss')) ?></dd>
                </dl>
            </div>
            
            <?php foreach($profiler->collectors() as $collector) { ?>
                <?php if ($menuCollectors->get($collector->name())) { ?>
                <div
                     id="<?= $view->esc($profiler->nameToId($collector->name())) ?>"
                     class="profiler-collector"
                     data-collector="<?= $view->esc($collector->name()) ?>"
                >
                    <?php
                    echo $profiler->renderCollector($collector->name(), $profile, $view);
                    ?>
                </div>
                <?php } ?>
            <?php } ?>
        </main>
        
        <?=	$view->render('inc/footer') ?>
	</body>
</html>