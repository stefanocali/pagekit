<?php

namespace Pagekit\Page;

use Pagekit\Application as App;
use Pagekit\Database\Event\EntityEvent;
use Pagekit\Editor\Event\EditorLoadEvent;
use Pagekit\Page\Entity\Page;
use Pagekit\System\Extension;

class PageModule extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function main(App $app)
    {
        $app->on('view.site:views/edit', function($event, $view) use ($app) {
            $view->style('codemirror');
            $view->script('page-site', 'system/page:app/bundle/site.js', ['site', 'editor']);
        });

        $app->on('site.node.preSave', function(EntityEvent $event) use ($app) {
            $node = $event->getEntity();
            $data = $app['request']->get('page');

            if ('page' !== $node->getType() or $data === null) {
                return;
            }

            $page = $this->getPage($node);
            $page->save($data);

            $node->set('variables', ['id' => $page->getId()]);
        });

        $app->on('site.node.postDelete', function(EntityEvent $event) use ($app) {
            $node = $event->getEntity();

            if ('page' !== $node->getType()) {
                return;
            }

            $page = $this->getPage($node);

            if ($page->getId()) {
                $page->delete();
            }
        });

    }

    protected function getPage($node)
    {
        $variables = $node->get('variables', []);

        if (!isset($variables['id']) or !$page = Page::find($variables['id'])) {
            $page = new Page();
        }

        return $page;
    }
}
