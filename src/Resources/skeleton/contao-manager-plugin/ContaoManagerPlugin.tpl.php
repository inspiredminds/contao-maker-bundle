<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

<?php if ($load_bundle): ?>
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
<?php endif; ?>
<?php if ($load_routes): ?>
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;
<?php endif; ?>

/**
 * Plugin for the Contao Manager.
 */
class Plugin<?php if ($load_bundle || $load_routes): ?> implements<?php endif; ?><?php if ($load_bundle): ?> BundlePluginInterface<?php endif; ?><?php if ($load_bundle && $load_routes): ?>,<?php endif; ?><?php if ($load_routes): ?> RoutingPluginInterface<?php endif; ?>

{<?php if ($load_bundle): ?>

    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(\<?= $load_bundle; ?>::class),
        ];
    }
<?php endif; ?><?php if ($load_routes): ?>

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): ?RouteCollection
    {
    	$rootDir = $kernel->getRootDir();

        return $resolver
            ->resolve($rootDir.'/config/routing.yml')
            ->load($rootDir.'/config/routing.yml')
        ;
    }
<?php endif; ?>
}
