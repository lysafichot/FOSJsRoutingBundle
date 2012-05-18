<?php

/*
 * This file is part of the FOSJsRoutingBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\JsRoutingBundle\Command;

use FOS\JsRoutingBundle\Response\RoutesResponse;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dumps routes to the filesystem.
 *
 * @author Benjamin Dulau <benjamin.dulau@anonymation.com>
 */
class DumpCommand extends ContainerAwareCommand
{
    private $targetPath;

    protected function configure()
    {
        $this
            ->setName('fos:js-routing:dump')
            ->setDescription('Dumps exposed routes to the filesystem')
            ->addOption(
                'callback',
                null,
                InputOption::VALUE_REQUIRED,
                'Callback function to pass the routes as an argument.',
                'fos.Router.setData'
            )
            ->addOption(
                'target',
                null,
                InputOption::VALUE_REQUIRED,
                'Override the target directory to dump routes in.'
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->targetPath = $input->getOption('target') ?:
            sprintf('%s/../web/js/fos_js_routes.js', $this->getContainer()->getParameter('kernel.root_dir'));
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Dumping exposed routes.');
        $output->writeln('');

        $this->doDump($input, $output);
    }

    /**
     * @return \FOS\JsRoutingBundle\Extractor\ExposedRoutesExtractorInterface
     */
    public function getExposedRoutesExtractor()
    {
        return $this->getContainer()->get('fos_js_routing.extractor');
    }

    /**
     * @return \Symfony\Component\Serializer\Serializer
     */
    public function getSerializer()
    {
        return $this->getContainer()->get('fos_js_routing.serializer');
    }

    /**
     * Performs the routes dump.
     *
     * @param InputInterface  $input  The command input
     * @param OutputInterface $output The command output
     */
    private function doDump(InputInterface $input, OutputInterface $output)
    {
        if (!is_dir($dir = dirname($this->targetPath))) {
            $output->writeln('<info>[dir+]</info>  ' . $dir);
            if (false === @mkdir($dir, 0777, true)) {
                throw new \RuntimeException('Unable to create directory ' . $dir);
            }
        }

        $output->writeln('<info>[file+]</info> ' . $this->targetPath);

        $content = $this->getSerializer()->serialize(
            new RoutesResponse(
                $this->getExposedRoutesExtractor()->getBaseUrl(),
                $this->getExposedRoutesExtractor()->getRoutes()
            ),
            'json'
        );

        if (!$input->getOption('callback')) {
            throw new \RuntimeException('Callback option value cannot be empty.');

        }
        $content = sprintf("%s(%s);", $input->getOption('callback'), $content);

        if (false === @file_put_contents($this->targetPath, $content)) {
            throw new \RuntimeException('Unable to write file ' . $this->targetPath);
        }
    }
}
