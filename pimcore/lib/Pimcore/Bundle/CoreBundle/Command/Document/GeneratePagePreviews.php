<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\CoreBundle\Command\Document;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Application;
use Pimcore\Console\Traits\DryRun;
use Pimcore\Document\Tag\NamingStrategy\Migration\AbstractMigrationStrategy;
use Pimcore\Document\Tag\NamingStrategy\Migration\Exception\NameMappingException;
use Pimcore\Document\Tag\NamingStrategy\NamingStrategyInterface;
use Pimcore\File;
use Pimcore\Model\Document;
use Pimcore\Tool;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Simple\ArrayCache;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class GeneratePagePreviews extends AbstractCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('pimcore:documents:generate-page-previews')
            ->setDescription('Generates the previews shown in the tree on hover')
            ->addOption(
                'urlPrefix',
                'u',
                InputOption::VALUE_OPTIONAL,
                'Prefix for the document path, eg. https://example.com, if not specified, Pimcore will try use the main domain from system settings.',
                null
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $hostUrl = $input->getOption('urlPrefix');
        if(!$hostUrl) {
            $hostUrl = Tool::getHostUrl();
        }

        if(!$hostUrl) {
            $this->io->error('Unable to determine URL prefix, please use option -u or specify a main domain in system settings');
            return;
        }

        $docs = new \Pimcore\Model\Document\Listing();
        $docs->setCondition("type = 'page'");
        $docs->load();

        foreach($docs as $doc) {
            /**
             * @var Document\Page $doc
             */

            try {
                $success = Document\Service::generatePagePreview($doc->getId(), null, $hostUrl);
            } catch (\Exception $e) {
                $this->io->error($e->getMessage());
            }
        }
    }
}
