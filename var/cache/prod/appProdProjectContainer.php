<?php

// This file has been auto-generated by the Symfony Dependency Injection Component for internal use.

if (\class_exists(\Container0cedafn\appProdProjectContainer::class, false)) {
    // no-op
} elseif (!include __DIR__.'/Container0cedafn/appProdProjectContainer.php') {
    touch(__DIR__.'/Container0cedafn.legacy');

    return;
}

if (!\class_exists(appProdProjectContainer::class, false)) {
    \class_alias(\Container0cedafn\appProdProjectContainer::class, appProdProjectContainer::class, false);
}

return new \Container0cedafn\appProdProjectContainer([
    'container.build_hash' => '0cedafn',
    'container.build_id' => 'b3aa2005',
    'container.build_time' => 1648597537,
], __DIR__.\DIRECTORY_SEPARATOR.'Container0cedafn');