<?php
namespace Mailer\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver\AggregateResolver;
use Zend\View\Resolver\TemplatePathStack;
use Zend\View\Resolver\TemplateMapResolver;

/**
 * Email renderer factory.
 *
 */
class RendererFactory implements FactoryInterface
{
    /**
     * Create, configure and return the email renderer.
     *
     * @see FactoryInterface::createService()
     * @return \Zend\View\Renderer\RendererInterface
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('config');

        if (empty($config['mail']['renderer'])) {
            throw new \Exception(
            'Config required in order to create Mailer\Renderer.' .
            'required config key: $config["mail"]["renderer"].'
            );
        }

        $rendererConfig = $config['mail']['renderer'];

        $resolver = new AggregateResolver();

        if (isset($rendererConfig['templateMap'])) {
            $templateMapResolver = new TemplateMapResolver;
            $templateMapResolver->setMap($rendererConfig['templateMap']);
            $resolver->attach($templateMapResolver);
        }

        if (isset($rendererConfig['templatePathStack'])) {
            $pathStackResolver = new TemplatePathStack;
            $pathStackResolver->setPaths($rendererConfig['templatePathStack']);
            $resolver->attach($pathStackResolver);
        }

        $renderer = new PhpRenderer();
        $renderer->setHelperPluginManager($serviceLocator->get('ViewHelperManager'));
        $renderer->setResolver($resolver);

        return $renderer;
    }

}