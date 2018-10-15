<?php

namespace Netliva\FileTypeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('netliva_file');

		
		$rootNode
			->children()
				->arrayNode('file_config')
					->addDefaultsIfNotSet()
					->children()
						->arrayNode('valid_filetypes')->defaultValue(array("pdf","docx","xlsx","pptx","rar","zip","bmp","gif","jpg","jpeg","png","tiff"))->prototype('scalar')->end()->end()
						->scalarNode('max_size')->defaultValue(1024 * 1024 * 1024)->end()
						->scalarNode('upload_dir')->defaultValue('public/netliva_uploads')->end()
						->scalarNode('download_uri')->defaultValue('/uploads')->end()
					->end()
				->end()
			->end()
				
		;

        return $treeBuilder;
    }
}
