<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

use <?= $full_bundle_name; ?>;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;

/**
 * Plugin for the Contao Manager.
 */
class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(<?= $bundle_name; ?>::class)->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
