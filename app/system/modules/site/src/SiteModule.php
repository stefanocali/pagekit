<?php

namespace Pagekit\Site;

use Pagekit\Application as App;
use Pagekit\Module\Module;
use Pagekit\Site\Entity\Node;

class SiteModule extends Module
{
    protected $types;
    protected $menus;
    protected $frontpage;

    /**
     * {@inheritdoc}
     */
    public function main(App $app)
    {
        $app['node'] = function ($app) {
            if ($id = $app['request']->attributes->get('_node') and $node = Node::find($id)) {
                return $node;
            }

            return new Node;
        };

        $app->on('app.request', function () use ($app) {

            foreach (Node::where(['status = ?'], [1])->get() as $node) {

                if (!$type = $this->getType($node->getType())) {
                    continue;
                }

                $type['path']     = $node->getPath();
                $type['defaults'] = array_merge(isset($type['defaults']) ? $type['defaults'] : [], $node->get('defaults', []), ['_node' => $node->getId()]);

                $route = null;
                if (isset($type['alias'])) {
                    $route = $app['routes']->alias($type['path'], $this->getLink($node, $type['alias']), $type['defaults']);
                } elseif (isset($type['controller'])) {
                    $route = $app['routes']->add($type);
                }

                if ($route && $node->getId() == $this->config('frontpage')) {
                    $this->setFrontpage($route->getName());
                }

            }

            if ($this->frontpage) {
                $app['routes']->alias('/', $this->frontpage);
            } else {
                $app['routes']->get('/', function () {
                    return __('No Frontpage assigned.');
                });
            }

        }, 110);

    }

    /**
     * @param  string $type
     * @return array
     */
    public function getType($type)
    {
        $types = $this->getTypes();

        return isset($types[$type]) ? $types[$type] : null;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        if (!$this->types) {

            foreach (App::module() as $module) {

                if (!isset($module->nodes)) {
                    continue;
                }

                foreach ($module->nodes as $type => $route) {
                    $this->registerType($type, $route);
                }

            }

            $this->registerType('alias', ['label' => __('Alias')]);

            App::trigger('site.types', [$this]);
        }

        return $this->types;
    }

    /**
     * Register a node type.
     *
     * @param string $type
     * @param array  $route
     */
    public function registerType($type, array $route)
    {
        $route['id'] = $type;
        $this->types[$type] = $route;
    }

    /**
     * @param  string $id
     * @return array
     */
    public function getMenu($id)
    {
        $menus = $this->getMenus();

        return isset($menus[$id]) ? $menus[$id] : null;
    }

    /**
     * @return array[]
     */
    public function getMenus()
    {
        if (!$this->menus) {

            foreach (App::module() as $module) {

                if (!isset($module->menus)) {
                    continue;
                }

                foreach ($module->menus as $id => $menu) {
                    $this->registerMenu($id, $menu, ['fixed' => true]);
                }
            }

            foreach ($this->config('menus') as $menu) {
                $this->registerMenu($menu['id'], $menu['label']);
            }

            App::trigger('site.menus', [$this]);
        }

        return $this->menus;
    }

    /**
     * Registers a menu.
     *
     * @param string $id
     * @param string $label
     * @param array  $options
     */
    public function registerMenu($id, $label, array $options = [])
    {
        $this->menus[$id] = array_merge($options, compact('id', 'label'));
    }

    /**
     * Gets the site's frontpage route.
     *
     * @return string
     */
    public function getFrontpage()
    {
        return $this->frontpage;
    }

    /**
     * Sets the site's frontpage route.
     *
     * @param string $name
     */
    public function setFrontpage($name)
    {
        $this->frontpage = $name;
    }

    /**
     * Gets the node's link.
     *
     * @param Node   $node
     * @param string $url
     * @return string
     */
    protected function getLink(Node $node, $url = '')
    {
        return $this->parseQuery($node->get('url', $url), $node->get('variables', []));
    }

    /**
     * Parses query parameters into a URL.
     *
     * @param  string $url
     * @param  array  $parameters
     * @return string
     */
    protected function parseQuery($url, $parameters = [])
    {
        if ($query = substr(strstr($url, '?'), 1)) {
            parse_str($query, $params);
            $url        = strstr($url, '?', true);
            $parameters = array_replace($parameters, $params);
        }

        if ($query = http_build_query($parameters, '', '&')) {
            $url .= '?'.$query;
        }

        return $url;
    }
}
