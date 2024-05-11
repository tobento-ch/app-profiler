<?php
$view->asset('assets/profiler/profiler.css');
$view->asset('assets/profiler/profiler.js')->attr('type', 'module');

$form = $view->form();

$menuCollectors = $view->menu('profiler.toolbar.collectors');
$menuCollectors->link('#profiler-profile', 'Profile');
$menuCollectors->many(
    $profiler->collectors(),
    static function($menu, $collector) use ($profiler, $profile): void {
        if (empty($data = $profiler->getCollectedData($collector->name(), $profile))) {
            return;
        }
        $data = $collector->data($data);        
        $item = $menu
            ->link('#profiler-'.$profiler->nameToId($collector->name()), $collector->name())
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

$menuBarCollectors = clone $menuCollectors;
$menuBarCollectors->tag('ul')->level(0)->class('profiler-ul-collectors');
$menuBarCollectors->tag('li')->attr('data-profiler-toggle', ['class' => 'profiler-open', 'el' => '#profiler-content']);

$menuCollectors->tag('ul')->level(0)->class('menu-v spaced menu-main');
?>
<div id="profiler">
    <div id="profiler-content">
        <div id="profiler-head">
            <div class="profiler-head-collectors"><?= $menuBarCollectors ?></div>
            <div>
                <div class="cols middle nowrap">
                    <?= $form->form([
                        'action' => $view->routeUrl('profiler.toolbar.profile'),
                        'method' => 'POST',
                    ]) ?>
                    <?= $form->select(
                        name: 'profiler_profile',
                        items: $form->each(items: $profiles, callback: function($profile) use ($view): array {
                            return [
                                $profile->id(),
                                sprintf(
                                    '%s | %s | %s',
                                    $view->formatDate($profile->time(), 'd/m/Y H:i:s'),
                                    $profile->method(),
                                    $profile->uri()
                                )
                            ];
                        }),
                        selected: [$profile->id()],
                        selectAttributes: ['class' => 'small fit'],
                    ) ?>
                    <?= $form->input(
                        name: 'profiler_profiles_count',
                        type: 'hidden',
                        value: (string)count($profiles),
                    ) ?>
                    <?= $form->close() ?>
                    <span class="pl-s text-highlight text-700"><?= count($profiles) ?></span>
                </div>
            </div>
        </div>
        <div id="profiler-body" class="scroll-behavior-smooth">
            <div class="profiler-nav">
                <nav><?= $menuCollectors ?></nav>
            </div>
            <div class="profiler-main">
                <div id="profiler-profile" class="title text-l">Profile</div>
                
                <div class="content">
                    <dl class="mb-l mt-xs">
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
                         id="profiler-<?= $view->esc($profiler->nameToId($collector->name())) ?>"
                         class="profiler-collector"
                         data-collector="<?= $view->esc($collector->name()) ?>"
                    >
                        <?php
                        echo $profiler->renderCollector($collector->name(), $profile, $view);
                        ?>
                    </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
        <div id="profiler-foot">
            <span class="link" data-profiler-toggle='{"class": "profiler-open", "el": "#profiler-content"}'>close</span>
        </div>
    </div>
</div>