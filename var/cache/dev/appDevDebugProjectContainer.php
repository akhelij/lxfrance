<?php

// This file has been auto-generated by the Symfony Dependency Injection Component for internal use.

if (\class_exists(\ContainerNwujeno\appDevDebugProjectContainer::class, false)) {
    // no-op
} elseif (!include __DIR__.'/ContainerNwujeno/appDevDebugProjectContainer.php') {
    touch(__DIR__.'/ContainerNwujeno.legacy');

    return;
}

if (!\class_exists(appDevDebugProjectContainer::class, false)) {
    \class_alias(\ContainerNwujeno\appDevDebugProjectContainer::class, appDevDebugProjectContainer::class, false);
}

return new \ContainerNwujeno\appDevDebugProjectContainer([
    'container.build_hash' => 'Nwujeno',
    'container.build_id' => 'd06ce8f1',
    'container.build_time' => 1648597046,
], __DIR__.\DIRECTORY_SEPARATOR.'ContainerNwujeno');