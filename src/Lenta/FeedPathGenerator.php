<?php

namespace MagicProSrc\Lenta;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;

/**
 * Where the images of the module land inside the disk.
 *
 * By default Spatie puts every file in a folder named after the media id, right
 * in the root of the disk. Ours go one level lower, into magicFeed, so that the
 * files of the module are together and apart from whatever else the site keeps
 * on the same disk.
 *
 * It is claimed for `FeedItem` alone: MagicServiceProvider calls
 * `PathGeneratorFactory::setCustomPathGenerators()` with that model, so
 * whatever else the site keeps in the media library keeps its own layout.
 */
class FeedPathGenerator extends DefaultPathGenerator
{
    protected string $prefix = 'magicFeed/';

    public function getPath(Media $media): string
    {
        return $this->prefix . parent::getPath($media);
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->prefix . parent::getPathForConversions($media);
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->prefix . parent::getPathForResponsiveImages($media);
    }
}
