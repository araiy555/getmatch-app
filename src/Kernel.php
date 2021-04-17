<?php

namespace App;

use App\DependencyInjection\Compiler\AddMarkdownExtensionsPass;
use FOS\HttpCache\SymfonyCache\HttpCacheProvider;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class Kernel extends BaseKernel implements HttpCacheProvider {
    use MicroKernelTrait;

    public function getHttpCache(): CacheKernel {
        return new CacheKernel($this);
    }

    protected function build(ContainerBuilder $container) {
        $container->addCompilerPass(new AddMarkdownExtensionsPass());
    }

    protected function configureContainer(ContainerConfigurator $container): void {
        $container->import('../config/{packages}/*.yaml');
        $container->import('../config/{packages}/'.$this->environment.'/*.yaml');
        $container->import('../config/{services}.yaml');
        $container->import('../config/{services}_'.$this->environment.'.yaml');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void {
        $routes->import('../config/app_routes/*.yaml');
        $routes->import('../config/{routes}/'.$this->environment.'/*.yaml');
        $routes->import('../config/{routes}/*.yaml');
        $routes->import('../config/{routes}.yaml');
    }
}
